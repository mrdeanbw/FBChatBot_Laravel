<?php

namespace App\Console\Commands;

use App\Models\SequenceMessage;
use App\Models\SequenceMessageSchedule;
use App\Models\Subscriber;
use App\Services\AudienceService;
use App\Services\SequenceService;
use App\Services\Facebook\Makana\MakanaAdapter;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;

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
     * @var MakanaAdapter
     */
    private $Makana;


    /**
     * Broadcast constructor.
     * @param SequenceService $sequences
     * @param AudienceService $audience
     * @param MakanaAdapter   $Makana
     */
    public function __construct(SequenceService $sequences, AudienceService $audience, MakanaAdapter $Makana)
    {
        parent::__construct();
        $this->sequences = $sequences;
        $this->Makana = $Makana;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $schedules = SequenceMessageSchedule::whereStatus('pending')->where('send_at', '<=', Carbon::now())->get();

        /** @var SequenceMessageSchedule $schedule */
        foreach ($schedules as $schedule) {
            DB::beginTransaction();

            $sequenceMessage = $schedule->sequence_message()->withTrashed()->first();

            $this->info("Sending Message {$sequenceMessage->name}, To {$schedule->subscriber->first_name} {$schedule->subscriber->last_name}");

            $this->markAsRunning($schedule);


            $sent = $this->sendMessage($sequenceMessage, $schedule->subscriber);

            $this->markAsCompleted($schedule, $sent);

            if ($nextMessage = $sequenceMessage->next()) {
                $this->info("Scheduling next message: {$nextMessage->name}");
                $this->sequences->scheduleMessage($nextMessage, $schedule->subscriber, $schedule->sent_at?: Carbon::now());
            }

            DB::commit();
        }


        $this->forceDeleteMessagesWithNoQueuedSchedules();

        $this->info("Done");
    }

    /**
     * @param SequenceMessageSchedule $schedule
     */
    protected function markAsRunning(SequenceMessageSchedule $schedule)
    {
        $schedule->status = 'running';
        $schedule->save();
    }

    /**
     * @param SequenceMessageSchedule $schedule
     * @param bool                    $success
     */
    private function markAsCompleted(SequenceMessageSchedule $schedule, $success)
    {
        $schedule->status = 'completed';
        if ($success) {
            $schedule->sent_at = Carbon::now();
        }
        $schedule->save();
    }

    /**
     * @param SequenceMessage|null $sequenceMessage (might be deleted)
     * @param Subscriber           $subscriber
     * @return bool
     */
    protected function sendMessage(SequenceMessage $sequenceMessage, Subscriber $subscriber)
    {
        if ($sequenceMessage->trashed() || ! $sequenceMessage->is_live) {
            $this->info("Message is either deleted, or not live. Skip it.");

            return false;
        }

        $this->info("Sending using facebook API.");
        $this->Makana->sendBlocks($sequenceMessage, $subscriber);

        return true;
    }


    private function forceDeleteMessagesWithNoQueuedSchedules()
    {
        $messagesWithNoQueuedSchedules = SequenceMessage::onlyTrashed()->whereHas('schedules', function ($query) {
            $query->whereStatus('pending');
        }, '=', 0);

        $messagesWithNoQueuedSchedules->each(function (SequenceMessage $message) {
            $message->forceDelete();
        });
    }

}
