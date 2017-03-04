<?php namespace App\Jobs;

use Carbon\Carbon;
use App\Models\Sequence;
use App\Models\Subscriber;
use App\Models\SequenceMessage;
use App\Models\SequenceSchedule;
use App\Services\SequenceService;
use App\Services\FacebookAPIAdapter;
use App\Repositories\Sequence\SequenceRepositoryInterface;
use App\Repositories\Template\TemplateRepositoryInterface;
use App\Repositories\Subscriber\SubscriberRepositoryInterface;
use App\Repositories\Sequence\SequenceScheduleRepositoryInterface;

class SendScheduledSequenceMessage extends BaseJob
{

    /** @var  TemplateRepositoryInterface */
    protected $templateRepo;
    /** @var  SequenceRepositoryInterface */
    protected $sequenceRepo;
    /** @var  SubscriberRepositoryInterface */
    protected $subscriberRepo;
    /** @var  FacebookAPIAdapter */
    protected $FacebookAdapter;
    /** @var  SequenceService */
    protected $sequenceService;
    /** @var  SequenceScheduleRepositoryInterface */
    protected $sequenceScheduleRepo;

    /** @var  Sequence */
    protected $sequence;
    /** @var  SequenceMessage */
    protected $message;
    /** @var  Subscriber */
    protected $subscriber;
    /** @var SequenceSchedule */
    private $schedule;

    /**
     * SendBroadcast constructor.
     *
     * @param SequenceSchedule $schedule
     */
    public function __construct(SequenceSchedule $schedule)
    {
        $this->schedule = $schedule;
    }

    /**
     * Execute the job.
     *
     * @param FacebookAPIAdapter                  $FacebookAdapter
     * @param TemplateRepositoryInterface         $templateRepo
     * @param SequenceRepositoryInterface         $sequenceRepo
     * @param SubscriberRepositoryInterface       $subscriberRepo
     * @param SequenceScheduleRepositoryInterface $sequenceScheduleRepo
     */
    public function handle(
        FacebookAPIAdapter $FacebookAdapter,
        TemplateRepositoryInterface $templateRepo,
        SequenceRepositoryInterface $sequenceRepo,
        SubscriberRepositoryInterface $subscriberRepo,
        SequenceScheduleRepositoryInterface $sequenceScheduleRepo
    ) {
        // Initialize
        $this->templateRepo = $templateRepo;
        $this->sequenceRepo = $sequenceRepo;
        $this->subscriberRepo = $subscriberRepo;
        $this->FacebookAdapter = $FacebookAdapter;
        $this->sequenceScheduleRepo = $sequenceScheduleRepo;

        $this->setSequence();

        $this->setMessage();

        $this->setSubscriber();

        $sentAt = $this->sendMessage();

        $nextMessage = $this->scheduleNextMessage($sentAt);

        $sentMessageIndex = $this->sequenceRepo->getMessageIndexInSequence($this->sequence, $this->message->id);
        $nextMessageIndex = $this->sequenceRepo->getMessageIndexInSequence($this->sequence, $nextMessage->id);

        $this->sequenceRepo->update($this->sequence, [
            "messages.{$sentMessageIndex}.queued" => $this->message->queued - 1,
            "messages.{$nextMessageIndex}.queued" => $nextMessage->queued + 1,
        ]);
    }

    /**
     * @return null|Carbon
     */
    private function sendMessage()
    {
        // Send the template if the message is not deleted, and is not marked as draft.
        if (! $this->message->deleted_at && $this->message->live) {
            $sentAt = Carbon::now();
            $template = $this->templateRepo->findById($this->message->template_id);
            $this->FacebookAdapter->sendTemplate($template, $this->subscriber);

            return $sentAt;
        }

        return null;
    }

    private function setSequence()
    {
        $this->sequence = $this->sequenceRepo->findById($this->schedule->sequence_id);
    }

    private function setMessage()
    {
        $this->message = $this->sequenceRepo->findSequenceMessageById($this->schedule->message_id, $this->sequence);
    }

    private function setSubscriber()
    {
        $this->subscriber = $this->subscriberRepo->findById($this->schedule->subscriber_id);
    }

    /**
     * @param Carbon|null $sentAt
     *
     * @return SequenceMessage|null
     */
    private function scheduleNextMessage($sentAt)
    {
        if ($newMessage = $this->sequenceRepo->getNextSendableMessage($this->sequence, $this->message)) {

            $this->sequenceScheduleRepo->update($this->schedule, [
                'message_id' => $newMessage->id,
                'status'     => ScheduleRepositoryInter,
                'send_at'    => change_date($sentAt?: Carbon::now(), $newMessage->conditions['wait_for'])
            ]);

        } else {

            $this->sequenceScheduleRepo->delete($this->schedule);

        }

        return $newMessage;
    }
}