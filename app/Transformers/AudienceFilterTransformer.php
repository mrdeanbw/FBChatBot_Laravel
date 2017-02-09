<?php namespace App\Transformers;

use App\Models\AudienceFilter;

class AudienceFilterTransformer extends BaseTransformer
{

    public function transform(AudienceFilter $filter)
    {
        return [
            'groups'    => $filter->groups,
            'enabled'   => $filter->enabled,
            'join_type' => $filter->join_type
        ];
    }

}