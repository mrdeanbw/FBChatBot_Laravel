<?php namespace Admin\Http\Controllers\API\Monitor;

use Admin\Http\Controllers\API\APIController;
use Admin\Repositories\MongoDatabase\MongoDatabaseRepositoryInterface;

class DatabaseController extends APIController
{

    private $db;
    /**
     * @type MongoDatabaseRepositoryInterface
     */
    private $databaseRepo;

    /**
     * Monitor Controller Constructor
     * @param MongoDatabaseRepositoryInterface $databaseRepo
     */
    public function __construct(MongoDatabaseRepositoryInterface $databaseRepo)
    {
        $this->databaseRepo = $databaseRepo;
    }

    public function index()
    {
        $info = $this->db->getDBInfo();
        $indexes = $this->db->getIndexesDetails();
        $slow = $this->db->getSlowQueries();
        $latest = $this->db->getLatestQueries();

        return view('modules-monitor::db', [
            'info'      => $info,
            'indexes'   => $indexes,
            'slow'      => $slow,
            'latest'    => $latest,
            'converter' => function ($size) {
                $unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

                return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
            }
        ]);
    }

    protected function transformer()
    {
    }
}
