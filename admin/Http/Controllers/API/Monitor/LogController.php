<?php namespace Admin\Http\Controllers\API\Monitor;

use Admin\Services\MonitorService;
use App\Transformers\BaseTransformer;
use Admin\Http\Controllers\API\APIController;

class LogController extends APIController
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
     * @return \Dingo\Api\Http\Response
     */
    public function index()
    {
        return $this->arrayResponse($this->monitor->getLogsInfo());
    }

    /**
     * @param $fileName
     * @return \Dingo\Api\Http\Response
     */
    public function show($fileName)
    {
        return $this->arrayResponse($this->monitor->getLog(urldecode($fileName)));
    }

    /**
     * @param $fileName
     * @return \Dingo\Api\Http\Response
     */
    public function destroy($fileName)
    {
        $this->monitor->deleteLog(urldecode($fileName));

        return $this->response->accepted();
    }

    /**
     * @param $fileName
     * @return mixed
     */
    public function download($fileName)
    {
        $filePath = $this->monitor->decodeAndValidateLogFile(urldecode($fileName));

        return response()->download($filePath);
    }

    /**
     * @return BaseTransformer
     */
    protected function transformer()
    {
        // TODO: Implement transformer() method.
    }
}