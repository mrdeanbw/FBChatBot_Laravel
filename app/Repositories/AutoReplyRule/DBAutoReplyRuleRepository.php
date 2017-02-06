<?php namespace App\Repositories\AutoReplyRule;

use App\Models\Bot;
use App\Models\AutoReplyRule;
use Illuminate\Pagination\Paginator;
use App\Repositories\BaseDBRepository;
use Illuminate\Database\Eloquent\Builder;

class DBAutoReplyRuleRepository extends BaseDBRepository implements AutoReplyRuleRepositoryInterface
{

    public function model()
    {
        return AutoReplyRule::class;
    }

    /**
     * Find an auto reply rule by his artificial ID.
     * @param int $id
     * @param Bot $bot
     * @return AutoReplyRule|null
     */
    public function findByIdForBot($id, Bot $bot)
    {
        return AutoReplyRule::where('bot_id', $bot->id)->find($id);
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
        $filterBy[] = ['type' => 'exact', 'attribute' => 'bot_id', 'value' => $bot->id];

        return $this->paginate($page, $filterBy, $orderBy, $perPage);
    }

    /**
     * Get the first matching auto reply rule.
     * @param string $keyword
     * @param Bot    $bot
     * @return AutoReplyRule|null
     */
    public function getMatchingRuleForPage($keyword, Bot $bot)
    {

        /** @type Builder $query */
        $query = AutoReplyRule::where("bot_id", $bot->id)->where(function (Builder $query) use ($keyword) {

            $query->orWhere(function ($subQuery) use ($keyword) {
                $subQuery->where('mode', 'is')->where('keyword', $keyword);
            });

            $query->orWhere(function (Builder $subQuery) use ($keyword) {
                $subQuery->Where('mode', 'begins_with')->where('keyword', 'regexp', "/{$keyword}.*/i");
            });

            $query->where(function (Builder $subQuery) use ($keyword) {
                $subQuery->where('mode', 'contains')->where('keyword', 'regexp', "/.*{$keyword}.*/i");
            });

        });

        return $query->orderBy('mode_priority')->first();
    }

}
