<?php namespace Admin\Models;

use Common\Models\ArrayModel;
use MongoDB\Model\BSONDocument;

class DatabaseInfo extends ArrayModel
{

    public $databaseName;
    public $collectionCount;
    public $documentCount;
    public $averageDocumentSize;
    public $dataSize;
    public $allocatedStorage;
    public $indexCount;
    public $indexSize;

    /**
     * @see https://docs.mongodb.com/v3.2/reference/command/dbStats/
     * @param BSONDocument $info
     * @return DatabaseInfo
     */
    public static function factory(BSONDocument $info)
    {
        $data = [
            'databaseName'        => $info->db,
            'collectionCount'     => $info->collections,
            'documentCount'       => $info->objects,
            'averageDocumentSize' => $info->avgObjSize,
            'dataSize'            => $info->dataSize,
            'allocatedStorage'    => $info->storageSize,
            'indexCount'          => $info->indexes,
            'indexSize'           => $info->indexSize,
        ];

        return new static($data);
    }
}
