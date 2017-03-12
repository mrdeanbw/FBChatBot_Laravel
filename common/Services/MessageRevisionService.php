<?php namespace Common\Services;

use Common\Models\Bot;
use Common\Models\Button;
use MongoDB\BSON\ObjectID;
use Common\Models\MessageRevision;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Common\Repositories\MessageRevision\MessageRevisionRepositoryInterface;

class MessageRevisionService
{

    /**
     * @type MessageRevisionRepositoryInterface
     */
    private $messageRevisionRepo;
    /**
     * @type SentMessageService
     */
    private $sentMessages;

    /**
     * MessageRevisionService constructor.
     * @param SentMessageService                 $sentMessages
     * @param MessageRevisionRepositoryInterface $messageRevisionRepo
     * @internal param SentMessageRepositoryInterface $sentMessageRepo
     */
    public function __construct(SentMessageService $sentMessages, MessageRevisionRepositoryInterface $messageRevisionRepo)
    {
        $this->sentMessages = $sentMessages;
        $this->messageRevisionRepo = $messageRevisionRepo;
    }

    /**
     * @param     $messageId
     * @param Bot $bot
     * @return Collection
     */
    public function getRevisionsWithStatsForMessage($messageId, Bot $bot)
    {
        $revisions = $this->messageRevisionRepo->getMessageRevisions(new ObjectID($messageId), $bot);
        $this->associateRevisionStats($revisions);

        return $revisions;
    }

    /**
     * @param Collection $revisions
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
        $this->sentMessages->setMessageStat($revision, $revision->message_id, $revision->created_at, $end);
    }

    /**
     * @param     $buttonId
     * @param Bot $bot
     * @return Collection
     */
    public function getRevisionsWithStatsForMainMenuButton($buttonId, Bot $bot)
    {
        $button = array_first($bot->main_menu->buttons, function (Button $button) use ($buttonId) {
            return (string)$button->id === $buttonId;
        });

        if (! $button) {
            throw new NotFoundHttpException;
        }

        $revisions = $this->messageRevisionRepo->getMessageRevisions(new ObjectID($buttonId), $bot);

        return $this->normalizeMainMenuButtonRevisions($revisions);
    }

    /**
     * @param Collection $revisions
     * @return Collection
     */
    protected function normalizeMainMenuButtonRevisions(Collection $revisions)
    {
        $revisions->map(function (MessageRevision $revision) {
            $revision->stats = [
                'clicked' => [
                    'total'          => $revision->clicks['total'],
                    'per_subscriber' => count($revision->clicks['subscribers'])
                ]
            ];
        });

        return $revisions;
    }
}