<?php namespace Admin\Repositories\MongoDatabase;

use Admin\Models\DatabaseInfo;
use Admin\Models\MongoQuery;
use Admin\Models\CollectionInfo;
use Illuminate\Support\Collection;
use Common\Repositories\DBBaseRepository;

class DBMongoDatabaseRepository extends DBBaseRepository implements MongoDatabaseRepositoryInterface
{

    /**
     * @return string
     */
    public function model()
    {
        return MongoQuery::class;
    }

    /**
     * Retrieve the slow queries info
     * @param int $page
     * @param int $perPage
     * @param int $milliseconds
     * @return Collection
     */
    public function paginateSlowQueries($page = 1, $perPage = 15, $milliseconds = 100)
    {
        $filterBy = [['key' => 'millis', 'operator' => '>', 'value' => $milliseconds]];
        $orderBy = ['millis' => 'desc'];

        return $this->paginate($page, $filterBy, $orderBy, $perPage);
    }

    /**
     * get the last queries
     * @param int $page
     * @param int $perPage
     * @return Collection
     * @internal param int $num
     */
    public function paginateLatestQueries($page = 1, $perPage = 15)
    {
        $name = "{$this->getDatabase()->getDatabaseName()}.system.profile";
        $filterBy = [
            ['key' => 'op', 'operator' => '!=', 'value' => 'command'],
            ['key' => 'ns', 'operator' => '!=', 'value' => $name]
        ];
        $orderBy = ['ts' => 'desc'];

        return $this->paginate($page, $filterBy, $orderBy, $perPage);
    }

    /**
     * Get general database info
     * @return DatabaseInfo
     */
    public function getDatabaseInfo()
    {
        $info = $this->getDatabase()->command(['dbStats' => 1])->toArray()[0];

        return DatabaseInfo::factory($info);
    }

    /**
     * @return Collection
     */
    public function getCollectionDetails()
    {
        $db = $this->getDatabase();

        // Retrieve collections
        $ret = [];

        foreach ($db->listCollections() as $info) {

            $collectionName = $info->getName();

            if ($collectionName == 'system.profile') {
                continue;
            }

            /** @type \MongoDB\Model\BSONDocument $colStats */
            $colStats = $db->command(['collStats' => $collectionName])->toArray();

            $ret[] = new CollectionInfo([
                'name'          => $collectionName,
                'dataSize'      => $colStats[0]['size'],
                'indexSize'     => $colStats[0]['totalIndexSize'],
                'indexes'       => iterator_to_array($colStats[0]['indexSizes'])
            ]);
        }

        return collect($ret);
    }
}