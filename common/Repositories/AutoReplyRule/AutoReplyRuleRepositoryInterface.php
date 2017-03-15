<?php namespace Common\Repositories\AutoReplyRule;

use Common\Models\Bot;
use Common\Models\AutoReplyRule;
use Common\Repositories\AssociatedWithBotRepositoryInterface;

interface AutoReplyRuleRepositoryInterface extends AssociatedWithBotRepositoryInterface
{

    // (the lower value, the higher priority)
    const MATCH_MODE_IS = 10;
    const MATCH_MODE_PREFIX = 20;
    const MATCH_MODE_CONTAINS = 30;
    const _MATCH_MODE_MAP = [
        self::MATCH_MODE_IS       => 'is',
        self::MATCH_MODE_PREFIX   => 'begins_with',
        self::MATCH_MODE_CONTAINS => 'contains',
    ];

    /**
     * Get the first matching auto reply rule.
     * @param string $searchKeyword
     * @param Bot    $bot
     * @return AutoReplyRule|null
     */
    public function getMatchingRuleForBot($searchKeyword, Bot $bot);
}
