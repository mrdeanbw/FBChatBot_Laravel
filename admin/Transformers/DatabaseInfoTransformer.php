<?php namespace Admin\Transformers;

use Admin\Models\DatabaseInfo;
use Common\Transformers\BaseTransformer;

class DatabaseInfoTransformer extends BaseTransformer
{

    public function transform(DatabaseInfo $info)
    {
        return [
            'id'               => 0,
            'name'             => $info->databaseName,
            'collection_count' => $info->collectionCount,
            'document_count'   => $info->documentCount,
            'doc_size'         => human_size($info->averageDocumentSize),
            'data_size'        => human_size($info->dataSize),
            'storage_size'     => human_size($info->allocatedStorage),
            'index_count'      => $info->indexCount,
            'index_size'       => human_size($info->indexSize)
        ];
    }
}