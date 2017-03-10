<?php namespace Admin\Repositories\MongoDatabase;

use MongoDB\Model\DatabaseInfo;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Common\Repositories\BaseRepositoryInterface;

interface MongoDatabaseRepositoryInterface extends BaseRepositoryInterface
{

    /**
     * Retrieve the slow queries info
     * @param int $page
     * @param int $perPage
     * @param int $milliseconds
     * @return Paginator
     */
    public function paginateSlowQueries($page = 1, $perPage = 15, $milliseconds = 100);

    /**
     * get the last queries
     * @param int $page
     * @param int $perPage
     * @return Paginator
     */
    public function paginateLatestQueries($page = 1, $perPage = 15);

    /**
     * Get general database info
     * @return DatabaseInfo
     */
    public function getDatabaseInfo();

    /**
     * @return Collection
     */
    public function getCollectionDetails();
}