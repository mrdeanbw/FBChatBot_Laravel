<?php namespace App\Repositories\AutoReplyRule;

use App\Models\Bot;
use App\Models\AutoReplyRule;
use Illuminate\Database\Eloquent\Builder;
use App\Repositories\DBAssociatedWithBotRepository;

class DBAutoReplyRuleRepository extends DBAssociatedWithBotRepository implements AutoReplyRuleRepositoryInterface
{

    public function model()
    {
        return AutoReplyRule::class;
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
        $query = AutoReplyRule::where("bot_id", $bot->_id)->where(function (Builder $query) use ($keyword) {

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
