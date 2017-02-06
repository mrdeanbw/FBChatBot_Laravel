<?php namespace App\Repositories\AutoReplyRule;

use App\Models\Bot;
use App\Models\AutoReplyRule;
use App\Repositories\CommonRepositoryInterface;
use Illuminate\Pagination\Paginator;

interface AutoReplyRuleRepositoryInterface extends CommonRepositoryInterface
{

    /**
     * Find an auto reply rule by his artificial ID.
     * @param int $id
     * @param Bot $bot
     * @return AutoReplyRule|null
     */
    public function findByIdForBot($id, Bot $bot);

    /**
     * Get the first matching auto reply rule.
     * @param string $keyword
     * @param Bot    $bot
     * @return AutoReplyRule|null
     */
    public function getMatchingRuleForPage($keyword, Bot $bot);

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
