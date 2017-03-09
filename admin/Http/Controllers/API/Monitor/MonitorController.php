<?php namespace Admin\Http\Controllers;

use Admin\Services\MonitorService;
use App\Transformers\BaseTransformer;
use Admin\Http\Controllers\API\APIController;

class MonitorController extends APIController
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
     * @return \Response
     */
    public function index()
    {
        $servers = $this->monitor->getServersInfo();
        $logs = $this->monitor->getLogsInfo();

        return view('modules-monitor::index', [
            'servers'  => $servers,
            'logFiles' => $logs
        ]);
    }

    /**
     * @return BaseTransformer
     */
    protected function transformer()
    {
    }
}