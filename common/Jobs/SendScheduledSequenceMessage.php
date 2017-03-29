<?php namespace Common\Jobs;

use Carbon\Carbon;
use Common\Models\Template;
use Common\Models\Sequence;
use Common\Models\Subscriber;
use Common\Models\SequenceMessage;
use Common\Models\SequenceSchedule;
use Common\Services\SequenceService;
use Common\Services\FacebookMessageSender;
use Common\Exceptions\DisallowedBotOperation;
use Common\Repositories\Sequence\SequenceRepositoryInterface;
use Common\Repositories\Template\TemplateRepositoryInterface;
use Common\Repositories\Subscriber\SubscriberRepositoryInterface;
use Common\Repositories\Sequence\SequenceScheduleRepositoryInterface;

class SendScheduledSequenceMessage extends BaseJob
{

    /** @var  TemplateRepositoryInterface */
    protected $templateRepo;
    /** @var  SequenceRepositoryInterface */
    protected $sequenceRepo;
    /** @var  SubscriberRepositoryInterface */
    protected $subscriberRepo;
    /** @var  FacebookMessageSender */
    protected $FacebookMessageSender;
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
     * @param FacebookMessageSender               $FacebookMessageSender
     * @param TemplateRepositoryInterface         $templateRepo
     * @param SequenceRepositoryInterface         $sequenceRepo
     * @param SubscriberRepositoryInterface       $subscriberRepo
     * @param SequenceScheduleRepositoryInterface $sequenceScheduleRepo
     */
    public function handle(
        TemplateRepositoryInterface $templateRepo,
        SequenceRepositoryInterface $sequenceRepo,
        FacebookMessageSender $FacebookMessageSender,
        SubscriberRepositoryInterface $subscriberRepo,
        SequenceScheduleRepositoryInterface $sequenceScheduleRepo
    ) {
        $this->setSentryContext($this->sequence->bot_id);

        // Initialize
        $this->templateRepo = $templateRepo;
        $this->sequenceRepo = $sequenceRepo;
        $this->subscriberRepo = $subscriberRepo;
        $this->sequenceScheduleRepo = $sequenceScheduleRepo;
        $this->FacebookMessageSender = $FacebookMessageSender;

        $this->setSequence();

        $this->setMessage();

        $this->setSubscriber();

        try {
            $sentAt = $this->sendMessage();
        } catch (DisallowedBotOperation $e) {
            $sentAt = null;
        }

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
            /** @type Template $template */
            $template = $this->templateRepo->findById($this->message->template_id);
            $this->FacebookMessageSender->sendTemplate($template, $this->subscriber);

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
     * @return SequenceMessage|null
     */
    private function scheduleNextMessage($sentAt)
    {
        if ($newMessage = $this->sequenceRepo->getNextSendableMessage($this->sequence, $this->message)) {

            $this->sequenceScheduleRepo->update($this->schedule, [
                'message_id' => $newMessage->id,
                'status'     => SequenceScheduleRepositoryInterface::STATUS_PENDING,
                'send_at'    => change_date($sentAt?: Carbon::now(), $newMessage->conditions['wait_for'])
            ]);

        } else {

            $this->sequenceScheduleRepo->delete($this->schedule);

        }

        return $newMessage;
    }
}