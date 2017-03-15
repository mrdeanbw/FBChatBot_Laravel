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
            'mode'     => AutoReplyRuleRepositoryInterface::_MATCH_MODE_MAP[$rule->mode],
            'keyword'  => $rule->keyword,
            'action'   => $rule->action,
        ];
    }
}