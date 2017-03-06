<?php namespace App\Repositories\AutoReplyRule;

use App\Models\Bot;
use App\Models\AutoReplyRule;
use App\Repositories\DBAssociatedWithBotRepository;

class DBAutoReplyRuleRepository extends DBAssociatedWithBotRepository implements AutoReplyRuleRepositoryInterface
{

    public function model()
    {
        return AutoReplyRule::class;
    }

    /**
     * Get the first matching auto reply rule.
     * @param string $searchKeyword
     * @param Bot    $bot
     * @return AutoReplyRule|null
     */
    public function getMatchingRuleForBot($searchKeyword, Bot $bot)
    {
        if ($rule = $this->exactMatchRule($searchKeyword, $bot)) {
            return $rule;
        }

        return $this->prefixOrContainsMatch($searchKeyword, $bot);
    }

    /**
     * @param string $searchKeyword
     * @param Bot    $bot
     * @return AutoReplyRule|null;
     */
    protected function exactMatchRule($searchKeyword, Bot $bot)
    {
        return AutoReplyRule::where("bot_id", $bot->_id)
                            ->where('mode', AutoReplyRuleRepositoryInterface::MATCH_MODE_IS)
                            ->where('keyword', $searchKeyword)->first();
    }

    /**
     * @param string $searchKeyword
     * @param Bot    $bot
     * @return null
     */
    protected function prefixOrContainsMatch($searchKeyword, Bot $bot)
    {
        foreach ($this->prefixOrContainsRules($bot) as $rule) {

            $escapedKeyword = preg_quote($rule->keyword, "|");

            // prefix
            if ($rule->mode == AutoReplyRuleRepositoryInterface::MATCH_MODE_PREFIX && preg_match("|^{$escapedKeyword}|", $searchKeyword)) {
                return $rule;
            }

            // contains
            if ($rule->mode == AutoReplyRuleRepositoryInterface::MATCH_MODE_CONTAINS && preg_match("|.*?{$escapedKeyword}.*?|", $searchKeyword)) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * @param Bot $bot
     * @return mixed
     */
    protected function prefixOrContainsRules(Bot $bot)
    {
        $rules = AutoReplyRule::where("bot_id", $bot->_id)
                              ->where('mode', '!=', AutoReplyRuleRepositoryInterface::MATCH_MODE_IS)
                              ->orderBy('mode')
                              ->get();

        return $rules;
    }
}
