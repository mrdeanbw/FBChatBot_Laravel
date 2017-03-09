<?php namespace Admin\Models;

use Common\Models\ArrayModel;

class CollectionInfo extends ArrayModel
{

    public $name;
    public $dataSize;
    public $indexSize;
    /** @type IndexInfo[] */
    public $indexInfo = [];

    /**
     * CollectionInfo constructor.
     * @param array $data
     * @param bool  $strict
     */
    public function __construct(array $data, $strict = false)
    {
        foreach (array_pull($data, 'indexes', []) as $name => $size) {
            $this->indexInfo[] = new IndexInfo(compact('name', 'size'));
        }
        parent::__construct($data, $strict);
    }
}
