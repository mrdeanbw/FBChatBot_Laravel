<?php
namespace Modules\Monitor\Services;
use GuzzleHttp\Client;

class MonitorService
{

    protected $guzzle;

    public function __construct()
    {
        $this->guzzle = new Client();
    }

	public function getServersInfo()
	{
		$servers = explode(',',env('MONITOR_SERVERS'));
		$monitorKey = env('MONITOR_KEY');

		//print_r($servers);exit;
		$serversData = [];

		foreach($servers as $ip):
			$response = $this->guzzle->request('POST', $ip, ['json' => ['key' => $monitorKey]]);
			if($response->getStatusCode() != 200){

			}

			$data  = json_decode($response->getBody(),true);

			$serversData[] = [
				'host'   => $data['host'],
				'load'   => $data['load'],
				'memory' => [
					'total'   => round($data['memory']['total'] / 1024 / 1024),
					'free'    => round($data['memory']['free'] / 1024 / 1024),
					'taken'   => round($data['memory']['taken'] / 1024 / 1024),
					'percent' => round( $data['memory']['taken'] / $data['memory']['total'] * 100 ,1)
				]
			];

		endforeach;


		return $serversData;

	}

	public function getLogsInfo()
	{
		$logDir = app()->basePath().'/storage/logs/';
		$files = scandir($logDir);
		foreach($files as $file){
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			if($ext == 'log'){
				$logData[] = [
					'name'=>$file,
					'size'=> $this->human_filesize(filesize($logDir.$file))
				];
			}
		}
		
		return $logData;
		
	}

	private function human_filesize($bytes, $decimals = 2) {
	  $sz = 'BKMGTP';
	  $factor = floor((strlen($bytes) - 1) / 3);
	  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	}

}