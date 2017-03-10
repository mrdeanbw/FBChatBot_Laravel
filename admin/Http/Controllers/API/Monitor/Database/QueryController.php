<?php namespace Admin\Http\Controllers\API\Monitor\Database;

use Illuminate\Http\Request;
use Admin\Http\Controllers\API\APIController;
use Admin\Transformers\MongoQueryTransformer;
use Admin\Repositories\MongoDatabase\MongoDatabaseRepositoryInterface;

class QueryController extends APIController
{

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

    /**
     * @param Request $request
     * @return mixed
     */
    public function latest(Request $request)
    {
        $queries = $this->databaseRepo->paginateLatestQueries($request->get('page', 1));

        return $this->paginatorResponse($queries);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function slow(Request $request)
    {
        $queries = $this->databaseRepo->paginateSlowQueries($request->get('page', 1));

        return $this->paginatorResponse($queries);
    }

    /**
     * @return MongoQueryTransformer
     */
    protected function transformer()
    {
        return new MongoQueryTransformer();
    }
}
