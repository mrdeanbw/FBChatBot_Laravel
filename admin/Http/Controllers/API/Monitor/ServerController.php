<?php namespace Admin\Http\Controllers\API\Monitor;

use Admin\Services\MonitorService;
use App\Transformers\BaseTransformer;
use Admin\Http\Controllers\API\APIController;

class ServerController extends APIController
{

    /**
     * @type MonitorService
     */
    protected $monitor;

    /**
     * Monitor Controller Constructor
     * @param MonitorService $monitorService
     */
    public function __construct(MonitorService $monitorService)
    {
        $this->monitor = $monitorService;
    }

    /**
     * Index , Get main info
     * @return \Dingo\Api\Http\Response
     */
    public function index()
    {
        return $this->arrayResponse($this->monitor->getServersInfo());
    }

    /**
     * @return BaseTransformer
     */
    protected function transformer()
    {
    }
}