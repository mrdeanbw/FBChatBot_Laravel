<?php namespace App\Repositories;

use App\Models\Bot;
use App\Models\BaseModel;
use MongoDB\BSON\ObjectID;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;

abstract class DBAssociatedWithBotRepository extends DBBaseRepository implements AssociatedWithBotRepositoryInterface
{

    /**
     * Find a template for a given bot
     * @param string|ObjectID $id
     * @param Bot             $bot
     * @return BaseModel|null
     */
    public function findByIdForBot($id, Bot $bot)
    {
        $filter = [
            ['operator' => '=', 'key' => '_id', 'value' => $id],
            ['operator' => '=', 'key' => 'bot_id', 'value' => $bot->_id],
        ];

        return $this->getOne($filter);
    }

    /**
     * Get all broadcasts that
     * @param Bot $bot
     * @return Collection
     */
    public function getAllForBot(Bot $bot)
    {
        $filter = [['operator' => '=', 'key' => 'bot_id', 'value' => $bot->_id]];

        return $this->getAll($filter);
    }

    /**
     * @param Bot   $bot
     * @param int   $page
     * @param array $filterBy
     * @param array $orderBy
     * @param int   $perPage
     * @return Paginator
     */
    public function paginateForBot(Bot $bot, $page, array $filterBy, array $orderBy, $perPage)
    {
        $filterBy[] = ['operator' => '=', 'key' => 'bot_id', 'value' => $bot->_id];

        return $this->paginate($page, $filterBy, $orderBy, $perPage);
    }

    /**
     * @param array    $models
     * @param ObjectID $botId
     * @return bool
     */
    public function bulkCreateForBot(array $models, ObjectID $botId)
    {
        foreach ($models as $model) {
            $model['bot_id'] = $botId;
        }

        return $this->bulkCreate($models);
    }

}
