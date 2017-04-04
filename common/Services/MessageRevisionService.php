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
     * @param ObjectID $messageId
     * @param Bot      $bot
     * @return Collection
     */
    public function getRevisionsWithStatsForMessage(ObjectID $messageId, Bot $bot)
    {
        $revisions = $this->messageRevisionRepo->getMessageRevisionsWithBot($messageId, $bot);
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
            $this->sentMessages->setFullStatsForRevision($revision);
        }
    }

    /**
     * @param ObjectID $buttonId
     * @param Bot      $bot
     * @return Collection
     */
    public function getRevisionsWithStatsForMainMenuButton(ObjectID $buttonId, Bot $bot)
    {
        $button = array_first($bot->main_menu->buttons, function (Button $button) use ($buttonId) {
            return $button->id == $buttonId;
        });

        if (! $button) {
            throw new NotFoundHttpException;
        }

        $revisions = $this->messageRevisionRepo->getMessageRevisionsWithBot($buttonId, $bot);

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
                    'total'          => array_get($revision->clicks, 'total', 0),
                    'per_subscriber' => count(array_get($revision->clicks, 'subscribers', []))
                ]
            ];
        });

        return $revisions;
    }
}