<?php namespace App\Repositories\AutoReplyRule;

use App\Models\Bot;
use App\Models\AutoReplyRule;
use App\Repositories\AssociatedWithBotRepositoryInterface;

interface AutoReplyRuleRepositoryInterface extends AssociatedWithBotRepositoryInterface
{
    /**
     * Get the first matching auto reply rule.
     * @param string $keyword
     * @param Bot    $bot
     * @return AutoReplyRule|null
     */
    public function getMatchingRuleForBot($keyword, Bot $bot);
}
