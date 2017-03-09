<?php namespace Admin\Repositories\MongoDatabase;

use MongoDB\Model\DatabaseInfo;
use Illuminate\Support\Collection;
use Common\Repositories\BaseRepositoryInterface;

interface MongoDatabaseRepositoryInterface extends BaseRepositoryInterface
{

    /**
     * Retrieve the slow queries info
     * @param int $milliseconds
     * @param int $page
     * @param int $perPage
     * @return Collection
     */
    public function paginateSlowQueries($milliseconds = 100, $page = 1, $perPage = 15);

    /**
     * get the last queries
     * @param int $page
     * @param int $perPage
     * @return Collection
     */
    public function paginateLatestQueries($page = 1, $perPage = 15);

    /**
     * Get general database info
     * @return DatabaseInfo
     */
    public function getDatabaseInfo();

    /**
     * @return mixed
     */
    public function getIndexDetails();
}