<?php namespace Admin\Http\Controllers\API\Monitor\Database;

use Admin\Http\Controllers\API\APIController;
use Admin\Transformers\CollectionInfoTransformer;
use Admin\Repositories\MongoDatabase\MongoDatabaseRepositoryInterface;

class CollectionController extends APIController
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
    public function index()
    {
        $info = $this->databaseRepo->getCollectionDetails();

        return $this->collectionResponse($info);
    }
    
    /**
     * @return CollectionInfoTransformer
     */
    protected function transformer()
    {
        return new CollectionInfoTransformer();
    }
}
