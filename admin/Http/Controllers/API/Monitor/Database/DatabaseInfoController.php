<?php namespace Admin\Http\Controllers\API\Monitor\Database;

use Admin\Http\Controllers\API\APIController;
use Admin\Transformers\DatabaseInfoTransformer;
use Admin\Repositories\MongoDatabase\MongoDatabaseRepositoryInterface;

class DatabaseInfoController extends APIController
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
     * @return mixed
     */
    public function show()
    {
        $info = $this->databaseRepo->getDatabaseInfo();

        return $this->itemResponse($info);
    }

    /**
     * @return DatabaseInfoTransformer
     */
    protected function transformer()
    {
        return new DatabaseInfoTransformer();
    }
}
