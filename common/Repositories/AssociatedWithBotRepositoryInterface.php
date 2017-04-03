<?php namespace Common\Repositories;

use Common\Models\Bot;
use MongoDB\BSON\ObjectID;
use Common\Models\BaseModel;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;

interface AssociatedWithBotRepositoryInterface extends BaseRepositoryInterface
{

    /**
     * Find a template for a given bot
     * @param ObjectID $id
     * @param Bot      $bot
     * @return bool
     */
    public function existsByIdForBot(ObjectID $id, Bot $bot);

    /**
     * Find a template for a given bot
     * @param ObjectID $id
     * @param ObjectID $botId
     * @return BaseModel|null
     */
    public function findByIdForBot(ObjectID $id, ObjectID $botId);

    /**
     * Get all broadcasts that
     * @param Bot   $bot
     * @param array $filterBy
     * @param array $orderBy
     * @param array $columns
     * @return Collection
     */
    public function getAllForBot(Bot $bot, array $filterBy = [], array $orderBy = [], array $columns = ['*']);

    /**
     * @param Bot   $bot
     * @param int   $page
     * @param array $filterBy
     * @param array $orderBy
     * @param int   $perPage
     * @return Paginator
     */
    public function paginateForBot(Bot $bot, $page, array $filterBy, array $orderBy, $perPage);

    /**
     * @param Bot   $bot
     * @param array $filterBy
     * @return int
     */
    public function countForBot(Bot $bot, array $filterBy);

    /**
     * @param array    $models
     * @param ObjectID $botId
     * @return mixed
     */
    public function bulkCreateForBot(array $models, ObjectID $botId);
}
