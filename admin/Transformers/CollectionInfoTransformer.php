<?php namespace Admin\Transformers;

use Admin\Models\CollectionInfo;
use Common\Transformers\BaseTransformer;

class CollectionInfoTransformer extends BaseTransformer
{

    public $defaultIncludes = ['indexes'];

    /**
     * @param CollectionInfo $info
     * @return array
     */
    public function transform(CollectionInfo $info)
    {
        return [
            'name'           => $info->name,
            'data_size'      => human_size($info->dataSize),
            'index_size'     => human_size($info->indexSize)
        ];
    }

    /**
     * @param CollectionInfo $info
     * @return \League\Fractal\Resource\Collection
     */
    public function includeIndexes(CollectionInfo $info)
    {
        return $this->collection($info->indexInfo, new IndexInfoTransformer(), false);
    }
}