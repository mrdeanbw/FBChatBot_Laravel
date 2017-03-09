<?php namespace Admin\Services;

use GuzzleHttp\Client;

class MonitorService
{

    /**
     * @type Client
     */
    protected $guzzle;

    /**
     * MonitorService constructor.
     */
    public function __construct()
    {
        $this->guzzle = new Client();
    }

    /**
     * @return array
     */
    public function getServersInfo()
    {
        $servers = array_filter(explode(',', config('admin.monitor.servers')));
        if (! $servers) {
            return [];
        }

        $monitorKey = config('admin.monitor.key');

        $serversData = [];

        foreach ($servers as $ip):
            $response = $this->guzzle->request('POST', $ip, ['json' => ['key' => $monitorKey]]);
            if ($response->getStatusCode() != 200) {

            }

            $data = json_decode($response->getBody(), true);

            $serversData[] = [
                'host'      => $data['host'],
                'load'      => array_map(function ($num) {
                    return round($num, 2);
                }, $data['load']),
                'diskspace' => $data['disk'],
                'memory'    => [
                    'total'   => round($data['memory']['total']),
                    'free'    => round($data['memory']['free']),
                    'taken'   => round($data['memory']['taken']),
                    'percent' => round($data['memory']['taken'] / $data['memory']['total'] * 100, 1)
                ]
            ];

        endforeach;

        return $serversData;

    }

    /**
     * @return array
     */
    public function getLogsInfo()
    {
        $logData = [];

        $logDir = app()->basePath() . '/storage/logs/';
        $files = scandir($logDir);

        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'log') {
                $logData[] = [
                    'name' => $file,
                    'size' => human_size(filesize($logDir . $file))
                ];
            }
        }

        usort($logData, function ($a, $b) {
            return $a['name'] < $b['name'];
        });

        return $logData;
    }
}