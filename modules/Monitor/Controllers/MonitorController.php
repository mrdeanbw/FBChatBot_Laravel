<?php
namespace Modules\Monitor\Controllers;
use Illuminate\Routing\Controller;

use Modules\Monitor\Services\MonitorService;

class MonitorController extends Controller
{
	private $monitor;

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

		return view('modules-monitor::index',[
				'servers'=>$servers,
				'logFiles'=>$logs
			]);
	}
}