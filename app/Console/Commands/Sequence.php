<?php namespace App\Console\Commands;

use DB;
use Carbon\Carbon;
use App\Models\Subscriber;
use Illuminate\Console\Command;
use App\Models\SequenceMessage;
use App\Services\AudienceService;
use App\Services\SequenceService;
use App\Services\FacebookAPIAdapter;
use App\Models\SequenceMessageSchedule;
use App\Repositories\Sequence\SequenceRepository;

class Sequence extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sequence:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process sequences and send due sequence messages.';
    /**
     * @var SequenceService
     */
    private $sequences;
    /**
     * @var FacebookAPIAdapter
     */
    private $FacebookAdapter;
    /**
     * @type SequenceRepository
     */
    private $sequenceRepo;
    /**
     * @type AudienceService
     */
    private $audience;


    /**
     * Broadcast constructor.
     * @param SequenceRepository $sequenceRepo
     * @param SequenceService    $sequences
     * @param AudienceService    $audience
     * @param FacebookAPIAdapter $FacebookAdapter
     */
    public function __construct(
        SequenceRepository $sequenceRepo,
        SequenceService $sequences,
        AudienceService $audience,
        FacebookAPIAdapter $FacebookAdapter
    ) {
        parent::__construct();
        $this->sequences = $sequences;
        $this->FacebookAdapter = $FacebookAdapter;
        $this->sequenceRepo = $sequenceRepo;
        $this->audience = $audience;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $schedules = $this->sequenceRepo->getDueMessageSchedule();

        /** @var SequenceMessageSchedule $schedule */
        foreach ($schedules as $schedule) {
            $this->processSequenceMessageSchedule($schedule);
        }

        $this->forceDeleteMessagesWithNoQueuedSchedules();

        $this->info("Done");
    }

    /**
     * @param SequenceMessageSchedule $schedule
     */
    private function processSequenceMessageSchedule(SequenceMessageSchedule $schedule)
    {
        DB::transaction(function () use ($schedule) {

            $sequenceMessage = $this->sequenceRepo->getMessageFromSchedule($schedule, true);

            $this->info("Sending Message {$sequenceMessage->name}, To {$schedule->subscriber->first_name} {$schedule->subscriber->last_name}");

            $this->markAsRunning($schedule);

            $isSent = $this->sendMessage($sequenceMessage, $schedule->subscriber);

            $this->markAsCompleted($schedule, $isSent);

            $this->scheduleNextMessage($sequenceMessage, $schedule);
        });
    }


    /**
     * @param SequenceMessageSchedule $schedule
     */
    protected function markAsRunning(SequenceMessageSchedule $schedule)
    {
        $this->sequenceRepo->updateMessageSchedule($schedule, ['status' => 'running']);
    }

    /**
     * @param SequenceMessageSchedule $schedule
     * @param bool                    $isSent
     */
    private function markAsCompleted(SequenceMessageSchedule $schedule, $isSent)
    {
        $data = ['status' => 'completed'];
        if ($isSent) {
            $data['sent_at'] = Carbon::now();
        }
        $this->sequenceRepo->updateMessageSchedule($schedule, $data);
    }

    /**
     * Send a sequence message to a subscriber.
     * @param SequenceMessage $sequenceMessage (might be deleted)
     * @param Subscriber      $subscriber
     * @return bool
     */
    protected function sendMessage(SequenceMessage $sequenceMessage, Subscriber $subscriber)
    {
        // If the message is deleted, or is marked as draft. Don't send it.
        if ($sequenceMessage->trashed() || ! $sequenceMessage->is_live) {
            $this->warn("Message is either deleted, or not live. Skip it.");

            return false;
        }

        $this->info("Sending using facebook API.");
        $this->FacebookAdapter->sendBlocks($sequenceMessage, $subscriber);

        return true;
    }
    
    /**
     * Schedule the next message (if it exists).
     * @param SequenceMessage         $sequenceMessage
     * @param SequenceMessageSchedule $schedule
     */
    private function scheduleNextMessage(SequenceMessage $sequenceMessage, SequenceMessageSchedule $schedule)
    {
        if ($nextMessage = $this->sequenceRepo->getNextSequenceMessage($sequenceMessage)) {

            $this->info("Scheduling next message: {$nextMessage->name}");

            $this->sequences->scheduleMessage(
                $nextMessage,
                $schedule->subscriber,
                $schedule->sent_at?: Carbon::now()
            );
        }
    }


    /**
     * Completely delete soft deleted sequence messages that have no more schedules.
     */
    private function forceDeleteMessagesWithNoQueuedSchedules()
    {
        $messagesWithNoQueuedSchedules = $this->sequenceRepo->getTrashedMessagesWithNoSchedules();

        $messagesWithNoQueuedSchedules->each(function (SequenceMessage $message) {
            $this->sequenceRepo->deleteMessage($message, true);
        });
    }

}
