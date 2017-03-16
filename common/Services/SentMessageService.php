<?php namespace Common\Services;

use Carbon\Carbon;
use Common\Models\Card;
use Common\Models\Button;
use Common\Models\Message;
use MongoDB\BSON\ObjectID;
use Common\Models\MessageRevision;
use Common\Repositories\SentMessage\SentMessageRepositoryInterface;

class SentMessageService
{

    /**
     * @type SentMessageRepositoryInterface
     */
    private $sentMessageRepo;

    /**
     * MessageRevisionService constructor.
     * @param SentMessageRepositoryInterface $sentMessageRepo
     */
    public function __construct(SentMessageRepositoryInterface $sentMessageRepo)
    {
        $this->sentMessageRepo = $sentMessageRepo;
    }

    /**
     * @param Message|MessageRevision $message
     * @param ObjectID                $messageId
     * @param Carbon|null             $startDateTime
     * @param Carbon|null             $endDateTime
     */
    public function setFullMessageStats($message, $messageId, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $message->stats = [
            'sent' => [
                'total'          => $this->sentMessageRepo->totalSentForMessage($messageId, $startDateTime, $endDateTime),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberSentForMessage($messageId, $startDateTime, $endDateTime),
            ],

            'delivered' => [
                'total'          => $this->sentMessageRepo->totalDeliveredForMessage($messageId, $startDateTime, $endDateTime),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberDeliveredForMessage($messageId, $startDateTime, $endDateTime),
            ],

            'read' => [
                'total'          => $this->sentMessageRepo->totalReadForMessage($messageId, $startDateTime, $endDateTime),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberReadForMessage($messageId, $startDateTime, $endDateTime),
            ],
        ];

        $this->setMessageClickableStats($message, $messageId, $startDateTime, $endDateTime);
    }

    /**
     * @param Message|MessageRevision $message
     * @param ObjectID                $messageId
     * @param Carbon|null             $startDateTime
     * @param Carbon|null             $endDateTime
     */
    public function setMessageClickableStats($message, $messageId, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {

        if ($message->type == 'text') {
            /** @type \Common\Models\Text $message */
            foreach ($message->buttons as $button) {
                $this->setTextButtonStats($button, $messageId, $startDateTime, $endDateTime);
            }

            return;
        }

        if ($message->type == 'card_container') {
            /** @type \Common\Models\CardContainer $message */
            foreach ($message->cards as $card) {
                $this->setCardStats($card, $messageId, $startDateTime, $endDateTime);
            }
        }
    }

    /**
     * @param Button   $button
     * @param ObjectID $textMessageId
     * @param Carbon   $startDateTime
     * @param Carbon   $endDateTime
     */
    protected function setTextButtonStats(Button $button, $textMessageId, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $button->stats = [
            'clicked' => [
                'total'          => $this->sentMessageRepo->totalTextMessageButtonClicks($button->id, $textMessageId, $startDateTime, $endDateTime),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberTextMessageButtonClicks($button->id, $textMessageId, $startDateTime, $endDateTime),
            ]
        ];
    }

    /**
     * @param Card     $card
     * @param ObjectID $cardContainerId
     * @param Carbon   $startDateTime
     * @param Carbon   $endDateTime
     */
    protected function setCardStats(Card $card, $cardContainerId, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $card->stats = [
            'clicked' => [
                'total'          => $this->sentMessageRepo->totalCardClicks($card->id, $cardContainerId, $startDateTime, $endDateTime),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberCardClicks($card->id, $cardContainerId, $startDateTime, $endDateTime),
            ]
        ];

        foreach ($card->buttons as $button) {
            $this->setCardButtonStats($button, $card->id, $cardContainerId, $startDateTime, $endDateTime);
        }
    }


    /**
     * @param Button      $button
     * @param ObjectID    $cardId
     * @param ObjectID    $cardContainerId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     */
    protected function setCardButtonStats(Button $button, $cardId, $cardContainerId, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $button->stats = [
            'clicked' => [
                'total'          => $this->sentMessageRepo->totalCardButtonClicks($button->id, $cardId, $cardContainerId, $startDateTime, $endDateTime),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberCardButtonClicks($button->id, $cardId, $cardContainerId, $startDateTime, $endDateTime),
            ]
        ];
    }
}