<?php namespace App\Repositories\AutoReplyRule;

use App\Models\Bot;
use App\Models\AutoReplyRule;
use App\Repositories\AssociatedWithBotRepositoryInterface;

interface AutoReplyRuleRepositoryInterface extends AssociatedWithBotRepositoryInterface
{

    // (the lower value, the higher priority)
    CONST MATCH_MODE_IS = 10;
    CONST MATCH_MODE_PREFIX = 20;
    CONST MATCH_MODE_CONTAINS = 30;

    /**
     * Get the first matching auto reply rule.
     * @param string $searchKeyword
     * @param Bot    $bot
     * @return AutoReplyRule|null
     */
    public function getMatchingRuleForBot($searchKeyword, Bot $bot);
}
