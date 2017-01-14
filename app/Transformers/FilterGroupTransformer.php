<?php
namespace App\Transformers;

use App\Models\AudienceFilterGroup;

class FilterGroupTransformer extends BaseTransformer
{

    protected $defaultIncludes = ['rules'];

    public function transform(AudienceFilterGroup $group)
    {
        return [
            'id'   => (int)$group->id,
            'type' => $group->type,
        ];
    }

    public function includeRules(AudienceFilterGroup $model)
    {
        return $this->collection($model->rules, new FilterRuleTransformer(), false);
    }

}