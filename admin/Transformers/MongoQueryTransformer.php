<?php namespace Admin\Transformers;

use Admin\Models\MongoQuery;
use Common\Transformers\BaseTransformer;

class MongoQueryTransformer extends BaseTransformer
{

    public function transform(MongoQuery $query)
    {
        return $query;
    }
}