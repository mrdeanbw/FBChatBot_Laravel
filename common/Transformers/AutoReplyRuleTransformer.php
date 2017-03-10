<?php namespace Common\Transformers;

use Common\Models\AutoReplyRule;
use Common\Repositories\AutoReplyRule\AutoReplyRuleRepositoryInterface;

class AutoReplyRuleTransformer extends BaseTransformer
{

    protected $defaultIncludes = ['template'];

    public function transform(AutoReplyRule $rule)
    {
        return [
            'id'       => $rule->id,
            'readonly' => $rule->readonly,
            'mode'     => $this->getMode($rule->mode),
            'keyword'  => $rule->keyword,
            'action'   => $rule->action,
        ];
    }

    /**
     * @param $mode
     * @return string
     */
    protected function getMode($mode)
    {
        switch ($mode) {
            case AutoReplyRuleRepositoryInterface::MATCH_MODE_IS:
                return 'is';
            case AutoReplyRuleRepositoryInterface::MATCH_MODE_PREFIX:
                return 'begins_with';
            case AutoReplyRuleRepositoryInterface::MATCH_MODE_CONTAINS:
                return 'contains';
            default:
                return null;
        }
    }
}