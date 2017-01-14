<?php
namespace App\Transformers;

use App\Models\AutoReplyRule;

class AutoReplyRuleTransformer extends BaseTransformer
{

    protected $defaultIncludes = ['template'];

    public function transform(AutoReplyRule $rule)
    {
        return [
            'id'          => (int)$rule->id,
            'is_disabled' => (bool)$rule->is_disabled,
            'mode'        => $rule->mode,
            'keyword'     => $rule->keyword,
            'action'     => $rule->action,
        ];
    }

    public function includeTemplate(AutoReplyRule $rule)
    {
        return $rule->template? $this->item($rule->template, new TemplateTransformer(), false) : $this->null();
    }
}