<?php namespace Common\Repositories\MessageRevision;

use Common\Models\Bot;
use Common\Models\Subscriber;
use MongoDB\BSON\ObjectID;
use Common\Models\MessageRevision;
use Illuminate\Support\Collection;
use Common\Repositories\DBAssociatedWithBotRepository;

class DBMessageRevisionRepository extends DBAssociatedWithBotRepository implements MessageRevisionRepositoryInterface
{

    /**
     * @return string
     */
    public function model()
    {
        return MessageRevision::class;
    }

    /**
     * @param ObjectID $messageId
     * @return Collection
     */
    public function getMessageRevisions(ObjectID $messageId)
    {
        $filter = [['key' => 'message_id', 'operator' => '=', 'value' => $messageId],];

        return $this->getAll($filter);
    }

    /**
     * @param ObjectID $messageId
     * @param Bot      $bot
     * @return Collection
     */
    public function getMessageRevisionsWithBot(ObjectID $messageId, Bot $bot)
    {
        $filter = [
            ['key' => 'message_id', 'operator' => '=', 'value' => $messageId],
            ['key' => 'bot_id', 'operator' => '=', 'value' => $bot->_id]
        ];

        return $this->getAll($filter, ['created_at' => 'desc']);
    }

    /**
     * @param ObjectID   $id
     * @param Bot        $bot
     * @param Subscriber $subscriber
     */
    public function recordMainMenuButtonClick(ObjectID $id, Bot $bot, Subscriber $subscriber = null)
    {
        $update = [
            '$inc' => ['clicks.total' => 1],
        ];

        if ($subscriber) {
            $update['$addToSet'] = ['clicks.subscribers' => $subscriber->_id];
        }

        MessageRevision::where('_id', $id)->where('bot_id', $bot->_id)->getQuery()->update($update);
    }
}
