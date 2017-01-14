<?php
namespace App\Transformers;

use App\Models\AudienceFilterRule;

class FilterRuleTransformer extends BaseTransformer
{

    public function transform(AudienceFilterRule $rule)
    {
        return [
            'id'    => (int)$rule->id,
            'key'   => $rule->key,
            'value' => $rule->value,
        ];
    }


}