<?php namespace App\Transformers;

use App\Models\AutoReplyRule;

class AutoReplyRuleTransformer extends BaseTransformer
{
    protected $defaultIncludes = ['template'];

    public function transform(AutoReplyRule $rule)
    {
        return [
            'id'       => $rule->id,
            'readonly' => $rule->readonly,
            'mode'     => $rule->mode,
            'keyword'  => $rule->keyword,
            'action'   => $rule->action,
        ];
    }
}