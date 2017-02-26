<?php namespace App\Services;

use App\Models\Bot;
use App\Models\Card;
use App\Models\Button;
use MongoDB\BSON\ObjectID;
use App\Models\MessageRevision;
use App\Repositories\SentMessage\SentMessageRepositoryInterface;
use App\Repositories\MessageRevision\MessageRevisionRepositoryInterface;

class MessageRevisionService
{

    /**
     * @type SentMessageRepositoryInterface
     */
    private $sentMessageRepo;

    /**
     * @type MessageRevisionRepositoryInterface
     */
    private $messageRevisionRepo;

    /**
     * MessageRevisionService constructor.
     * @param SentMessageRepositoryInterface     $sentMessageRepo
     * @param MessageRevisionRepositoryInterface $messageRevisionRepo
     */
    public function __construct(SentMessageRepositoryInterface $sentMessageRepo, MessageRevisionRepositoryInterface $messageRevisionRepo)
    {
        $this->sentMessageRepo = $sentMessageRepo;
        $this->messageRevisionRepo = $messageRevisionRepo;
    }

    public function getRevisionsWithStatsForMessage($messageId, Bot $bot)
    {
        $revisions = $this->messageRevisionRepo->getMessageRevisions(new ObjectID($messageId), $bot);
        $this->associateRevisionStats($revisions);

        return $revisions;
    }

    /**
     * @param $revisions
     */
    private function associateRevisionStats($revisions)
    {
        $revisionCount = count($revisions);
        for ($i = 0; $i < $revisionCount; $i++) {
            $revision = $revisions[$i];
            $nextRevision = $i == $revisionCount - 1? null : $revisions[$i + 1];
            $this->setRevisionStats($revision, $nextRevision);
        }
    }

    /**
     * @param MessageRevision      $revision
     * @param MessageRevision|null $nextRevision
     * @return array
     */
    private function setRevisionStats(MessageRevision $revision, $nextRevision)
    {
        $end = $nextRevision? $nextRevision->created_at : null;

        $revision->stats = [
            'sent' => [
                'total'          => $this->sentMessageRepo->totalSentForMessage($revision->message_id, $revision->created_at, $end),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberSentForMessage($revision->message_id, $revision->created_at, $end),
            ],

            'delivered' => [
                'total'          => $this->sentMessageRepo->totalDeliveredForMessage($revision->message_id, $revision->created_at, $end),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberDeliveredForMessage($revision->message_id, $revision->created_at, $end),
            ],

            'read' => [
                'total'          => $this->sentMessageRepo->totalReadForMessage($revision->message_id, $revision->created_at, $end),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberReadForMessage($revision->message_id, $revision->created_at, $end),
            ],
        ];

        if ($revision->type == 'text') {
            foreach ($revision->buttons as $button) {
                $this->setTextButtonStats($button, $revision, $nextRevision);
            }

            return;
        }

        if ($revision->type == 'card_container') {
            foreach ($revision->cards as $card) {
                $this->setCardStats($card, $revision, $nextRevision);
            }

            return;
        }

    }

    /**
     * @param Button               $button
     * @param MessageRevision      $parent
     * @param MessageRevision|null $next
     * @return array
     */
    private function setTextButtonStats(Button $button, MessageRevision $parent, $next)
    {
        $end = $next? $next->created_at : null;

        $button->stats = [
            'clicked' => [
                'total'          => $this->sentMessageRepo->totalTextMessageButtonClicks($button->id, $parent->message_id, $parent->created_at, $end),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberTextMessageButtonClicks($button->id, $parent->message_id, $parent->created_at, $end),
            ]
        ];

        // @todo: handle nested messages
    }

    /**
     * @param Card                 $card
     * @param MessageRevision      $parent
     * @param MessageRevision|null $next
     * @return array
     */
    private function setCardStats(Card $card, MessageRevision $parent, $next)
    {
        $end = $next? $next->created_at : null;

        $card->stats = [
            'clicked' => [
                'total'          => $this->sentMessageRepo->totalCardClicks($card->id, $parent->created_at, $end),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberCardClicks($card->id, $parent->created_at, $end),
            ]
        ];

        foreach ($card->buttons as $button) {
            $this->setCardButtonStats($button, $card, $parent, $next);
        }
    }


    /**
     * @param Button               $button
     * @param Card                 $card
     * @param MessageRevision      $parent
     * @param MessageRevision|null $next
     * @return array
     */
    private function setCardButtonStats(Button $button, Card $card, MessageRevision $parent, $next)
    {
        $end = $next? $next->created_at : null;

        $button->stats = [
            'clicked' => [
                'total'          => $this->sentMessageRepo->totalCardButtonClicks($button->id, $card->id, $parent->message_id, $parent->created_at, $end),
                'per_subscriber' => $this->sentMessageRepo->perSubscriberCardButtonClicks($button->id, $card->id, $parent->message_id, $parent->created_at, $end),
            ]
        ];

        // @todo: handle nested messages
    }

}