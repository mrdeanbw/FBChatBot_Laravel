<?php namespace Admin\Transformers;

use Admin\Models\IndexInfo;
use Common\Transformers\BaseTransformer;

class IndexInfoTransformer extends BaseTransformer
{

    public function transform(IndexInfo $info)
    {
        return [
            'name' => $info->name,
            'size' => human_size($info->size),
        ];
    }

}