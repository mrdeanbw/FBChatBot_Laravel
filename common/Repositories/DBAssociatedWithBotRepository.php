<?php namespace Common\Repositories;

use Common\Models\Bot;
use MongoDB\BSON\ObjectID;
use Common\Models\BaseModel;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

abstract class DBAssociatedWithBotRepository extends DBBaseRepository implements AssociatedWithBotRepositoryInterface
{

    /**
     * Find a template for a given bot
     * @param ObjectID $id
     * @param Bot      $bot
     * @return bool
     */
    public function existsByIdForBot(ObjectID $id, Bot $bot)
    {
        $filter = [
            ['operator' => '=', 'key' => '_id', 'value' => $id],
            ['operator' => '=', 'key' => 'bot_id', 'value' => $bot->_id],
        ];

        return $this->exists($filter);
    }

    /**
     * Find a template for a given bot
     * @param string|ObjectID $id
     * @param ObjectID        $botId
     * @return BaseModel|null
     */
    public function findByIdForBot(ObjectID $id, ObjectID $botId)
    {
        $filter = [
            ['operator' => '=', 'key' => '_id', 'value' => $id],
            ['operator' => '=', 'key' => 'bot_id', 'value' => $botId],
        ];

        return $this->getOne($filter);
    }

    /**
     * Get all broadcasts that
     * @param Bot   $bot
     * @param array $filterBy
     * @param array $orderBy
     * @param array $columns
     * @return Collection
     */
    public function getAllForBot(Bot $bot, array $filterBy = [], array $orderBy = [], array $columns = ['*'])
    {
        $filterBy[] = ['operator' => '=', 'key' => 'bot_id', 'value' => $bot->_id];

        return $this->getAll($filterBy, $orderBy, $columns);
    }

    /**
     * @param Bot   $bot
     * @param int   $page
     * @param array $filterBy
     * @param array $orderBy
     * @param int   $perPage
     * @return LengthAwarePaginator
     */
    public function paginateForBot(Bot $bot, $page, array $filterBy, array $orderBy, $perPage)
    {
        $filterBy[] = ['operator' => '=', 'key' => 'bot_id', 'value' => $bot->_id];

        return $this->paginate($page, $filterBy, $orderBy, $perPage);
    }

    /**
     * @param Bot   $bot
     * @param array $filterBy
     * @return int
     */
    public function countForBot(Bot $bot, array $filterBy)
    {
        $filterBy[] = ['operator' => '=', 'key' => 'bot_id', 'value' => $bot->_id];

        return $this->count($filterBy);
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
