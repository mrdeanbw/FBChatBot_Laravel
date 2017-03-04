<?php namespace App\Repositories\MessageRevision;

use App\Models\Bot;
use MongoDB\BSON\ObjectID;
use App\Models\MessageRevision;
use Illuminate\Support\Collection;
use App\Repositories\DBAssociatedWithBotRepository;

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
     * @param Bot      $bot
     * @return Collection
     */
    public function getMessageRevisions(ObjectID $messageId, Bot $bot)
    {
        $filter = [
            ['key' => 'message_id', 'operator' => '=', 'value' => $messageId],
            ['key' => 'bot_id', 'operator' => '=', 'value' => $bot->_id]
        ];

        return $this->getAll($filter, ['created_at' => 'asc']);
    }
}
