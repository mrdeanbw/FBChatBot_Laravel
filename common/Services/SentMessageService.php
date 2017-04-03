<?php namespace Common\Services;

use Common\Models\Card;
use Common\Models\Button;
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
    public function setFullMessageStats(MessageRevision $revision)
    {
        $revision->stats = [
            'sent' => [
                'total'          => $this->sentMessageRepo->totalSentForMessage($revision),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberSentForMessage($revision),
            ],

            'delivered' => [
                'total'          => $this->sentMessageRepo->totalDeliveredForMessage($revision),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberDeliveredForMessage($revision),
            ],

            'read' => [
                'total'          => $this->sentMessageRepo->totalReadForMessage($revision),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberReadForMessage($revision),
            ],
        ];

        $this->setMessageClickableStats($revision);
    }

    /**
     * @param MessageRevision $revision
     */
    public function setMessageClickableStats(MessageRevision $revision)
    {

        if ($revision->type == 'text') {
            /** @type \Common\Models\Text $revision */
            foreach (object_get($revision, 'buttons', []) as $button) {
                $this->setTextButtonStats($button, $revision);
            }

            return;
        }

        if ($revision->type == 'card_container') {
            /** @type \Common\Models\CardContainer $revision */
            foreach ($revision->cards as $card) {
                $this->setCardStats($card, $revision);
            }
        }
    }

    /**
     * @param Button          $button
     * @param MessageRevision $revision
     */
    protected function setTextButtonStats(Button $button, MessageRevision $revision)
    {
        $button->stats = [
            'clicked' => [
                'total'          => $this->sentMessageRepo->totalTextMessageButtonClicks($button, $revision),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberTextMessageButtonClicks($button, $revision),
            ]
        ];
    }

    /**
     * @param Card            $card
     * @param MessageRevision $revision
     */
    protected function setCardStats(Card $card, MessageRevision $revision)
    {
        $card->stats = [
            'clicked' => [
                'total'          => $this->sentMessageRepo->totalCardClicks($card, $revision),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberCardClicks($card, $revision),
            ]
        ];

        foreach (object_get($card, 'buttons', []) as $button) {
            $this->setCardButtonStats($button, $card, $revision);
        }
    }


    /**
     * @param Button          $button
     * @param Card            $card
     * @param MessageRevision $revision
     */
    protected function setCardButtonStats(Button $button, Card $card, MessageRevision $revision)
    {
        $button->stats = [
            'clicked' => [
                'total'          => $this->sentMessageRepo->totalCardButtonClicks($button, $card, $revision),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberCardButtonClicks($button, $card, $revision),
            ]
        ];
    }
}