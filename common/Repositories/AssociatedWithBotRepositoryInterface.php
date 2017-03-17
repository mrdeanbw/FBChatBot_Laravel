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
     * @param int $id
     * @param Bot $bot
     * @return BaseModel|null
     */
    public function findByIdForBot($id, Bot $bot);

    /**
     * Get all broadcasts that
     * @param Bot $bot
     * @return Collection
     */
    public function getAllForBot(Bot $bot);

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
     * @param array    $models
     * @param ObjectID $botId
     * @return mixed
     */
    public function bulkCreateForBot(array $models, ObjectID $botId);
}
