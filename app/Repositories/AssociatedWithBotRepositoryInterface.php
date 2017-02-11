<?php namespace App\Repositories;

use App\Models\Bot;
use App\Models\BaseModel;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

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
}
