<?php namespace Common\Repositories\AutoReplyRule;

use Common\Models\Bot;
use Common\Models\AutoReplyRule;
use Common\Repositories\DBAssociatedWithBotRepository;

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
                            ->where('keywords', 'regexp', "/^{$searchKeyword}$/i")
                            ->orderBy('_id')
                            ->first();
    }

    /**
     * @param string $searchKeyword
     * @param Bot    $bot
     * @return null
     */
    protected function prefixOrContainsMatch($searchKeyword, Bot $bot)
    {
        foreach ($this->prefixOrContainsRules($bot) as $rule) {
            foreach ($rule->keywords as $keyword) {
                $escapedKeyword = preg_quote($keyword, "/");
                // prefix
                if ($rule->mode == AutoReplyRuleRepositoryInterface::MATCH_MODE_PREFIX && preg_match("/^{$escapedKeyword}($|\s)/i", $searchKeyword)) {
                    return $rule;
                }
                // contains
                if ($rule->mode == AutoReplyRuleRepositoryInterface::MATCH_MODE_CONTAINS && preg_match("/(^|\s){$escapedKeyword}($|\s)/i", $searchKeyword)) {
                    return $rule;
                }
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
                              ->orderBy('_id')
                              ->get();

        return $rules;
    }
}
