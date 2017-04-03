<?php namespace App\Http\Controllers\API;

use Common\Transformers\BaseTransformer;
use Common\Services\MessageRevisionService;
use Common\Transformers\MessageTransformer;
use MongoDB\BSON\ObjectID;

class MessageRevisionController extends APIController
{

    /**
     * @type MessageRevisionService
     */
    private $messageRevisions;


    /**
     * MessageRevisionController constructor.
     * @param MessageRevisionService $messageRevisions
     */
    public function __construct(MessageRevisionService $messageRevisions)
    {
        $this->messageRevisions = $messageRevisions;
        parent::__construct();
    }

    /**
     * @param $messageId
     * @return \Dingo\Api\Http\Response
     */
    public function index($messageId)
    {
        $messageId = new ObjectID($messageId);
        $revisions = $this->messageRevisions->getRevisionsWithStatsForMessage($messageId, $this->enabledBot());

        return $this->collectionResponse($revisions);
    }

    /**
     * @param $buttonId
     * @return \Dingo\Api\Http\Response
     */
    public function mainMenuButton($buttonId)
    {
        $buttonId = new ObjectID($buttonId);
        $revisions = $this->messageRevisions->getRevisionsWithStatsForMainMenuButton($buttonId, $this->enabledBot());

        return $this->collectionResponse($revisions);
    }

    /**
     * @return BaseTransformer
     */
    protected function transformer()
    {
        return new MessageTransformer();
    }
}
