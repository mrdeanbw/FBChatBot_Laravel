<?php namespace Common\Services;

use Common\Models\Card;
use Common\Models\Button;
use Common\Models\Message;
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
     * @param MessageRevision $revision
     */
    public function setFullStatsForRevision(MessageRevision $revision)
    {
        $revision->stats = [
            'sent' => [
                'total'          => $this->sentMessageRepo->totalSentForRevision($revision),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberSentForRevision($revision),
            ],

            'delivered' => [
                'total'          => $this->sentMessageRepo->totalDeliveredForRevision($revision),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberDeliveredForRevision($revision),
            ],

            'read' => [
                'total'          => $this->sentMessageRepo->totalReadForRevision($revision),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberReadForRevision($revision),
            ],
        ];

        $this->setClickableStatsForRevision($revision);
    }

    /**
     * @param MessageRevision $revision
     */
    public function setClickableStatsForRevision(MessageRevision $revision)
    {
        if ($revision->type == 'text') {
            /** @type \Common\Models\Text $revision */
            foreach (object_get($revision, 'buttons', []) as $button) {
                $this->setTextButtonStatsForRevision($button, $revision);
            }

            return;
        }

        if ($revision->type == 'card_container') {
            /** @type \Common\Models\CardContainer $revision */
            foreach ($revision->cards as $card) {
                $this->setCardStatsForRevision($card, $revision);
            }
        }
    }

    /**
     * @param Button          $button
     * @param MessageRevision $revision
     */
    protected function setTextButtonStatsForRevision(Button $button, MessageRevision $revision)
    {
        $button->stats = [
            'clicked' => [
                'total'          => $this->sentMessageRepo->totalTextMessageButtonClicksForRevision($button, $revision),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberTextMessageButtonClicksForRevision($button, $revision),
            ]
        ];
    }

    /**
     * @param Card            $card
     * @param MessageRevision $revision
     */
    protected function setCardStatsForRevision(Card $card, MessageRevision $revision)
    {
        $card->stats = [
            'clicked' => [
                'total'          => $this->sentMessageRepo->totalCardClicksForRevision($card, $revision),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberCardClicksForRevision($card, $revision),
            ]
        ];

        foreach (object_get($card, 'buttons', []) as $button) {
            $this->setCardButtonStatsForRevision($button, $card, $revision);
        }
    }

    /**
     * @param Button          $button
     * @param Card            $card
     * @param MessageRevision $revision
     */
    protected function setCardButtonStatsForRevision(Button $button, Card $card, MessageRevision $revision)
    {
        $button->stats = [
            'clicked' => [
                'total'          => $this->sentMessageRepo->totalCardButtonClicksForRevision($button, $card, $revision),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberCardButtonClicksForRevision($button, $card, $revision),
            ]
        ];
    }

    /**
     * @param Message $message
     */
    public function setFullStatsForMessage(Message $message)
    {
        $message->stats = [
            'sent'      => $this->sentMessageRepo->totalSentForMessage($message),
            'delivered' => $this->sentMessageRepo->totalDeliveredForMessage($message),
            'read'      => $this->sentMessageRepo->totalReadForMessage($message),
        ];

        $this->setClickableStatsForMessage($message);
    }

    /**
     * @param Message $message
     */
    public function setClickableStatsForMessage(Message $message)
    {
        if ($message->type == 'text') {
            /** @type \Common\Models\Text $message */
            foreach (object_get($message, 'buttons', []) as $button) {
                $this->setTextButtonStatsForMessage($button, $message);
            }

            return;
        }

        if ($message->type == 'card_container') {
            /** @type \Common\Models\CardContainer $message */
            foreach ($message->cards as $card) {
                $this->setCardStatsForMessage($card, $message);
            }
        }

    }

    /**
     * @param Button  $button
     * @param Message $message
     */
    protected function setTextButtonStatsForMessage(Button $button, Message $message)
    {
        $button->stats = [
            'clicked' => [
                'total'          => $this->sentMessageRepo->totalTextMessageButtonClicksForMessage($button, $message),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberTextMessageButtonClicksForMessage($button, $message),
            ]
        ];
    }

    /**
     * @param Card    $card
     * @param Message $message
     */
    protected function setCardStatsForMessage(Card $card, Message $message)
    {
        $card->stats = [
            'clicked' => [
                'total'          => $this->sentMessageRepo->totalCardClicksForMessage($card, $message),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberCardClicksForMessage($card, $message),
            ]
        ];

        foreach (object_get($card, 'buttons', []) as $button) {
            $this->setCardButtonStatsForMessage($button, $card, $message);
        }
    }

    /**
     * @param Button  $button
     * @param Card    $card
     * @param Message $message
     */
    protected function setCardButtonStatsForMessage(Button $button, Card $card, Message $message)
    {
        $button->stats = [
            'clicked' => [
                'total'          => $this->sentMessageRepo->totalCardButtonClicksForMessage($button, $card, $message),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberCardButtonClicksForMessage($button, $card, $message),
            ]
        ];
    }

    /**
     * @param Message $message
     */
    public function setSummaryStatsForMessage(Message $message)
    {
        $message->stats = [
            'sent'      => $this->sentMessageRepo->totalSentForMessage($message),
            'delivered' => $this->sentMessageRepo->totalDeliveredForMessage($message),
            'read'      => $this->sentMessageRepo->totalReadForMessage($message),
        ];
    }
}