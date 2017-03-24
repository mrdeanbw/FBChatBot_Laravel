<?php namespace Admin\Services;

use ReflectionClass;
use Psr\Log\LogLevel;
use GuzzleHttp\Client;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MonitorService
{

    /** Return the content of the file if it doesn't exceed 5 MB */
    const MAX_FILE_SIZE = 5242880;
    /**
     * @type string
     */
    protected static $dateTimePattern = '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\].*/';
    /**
     * @type Client
     */
    protected $guzzle;
    /**
     * @type Filesystem
     */
    private $files;

    /**
     * MonitorService constructor.
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
        $this->guzzle = new Client();
        $this->logLevels = (new ReflectionClass(new LogLevel))->getConstants();
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

        foreach ($servers as $ip) {
            $response = $this->guzzle->request('POST', $ip, ['json' => ['key' => $monitorKey]]);
            if ($response->getStatusCode() != 200) {
                continue;
            }
            $data = json_decode($response->getBody(), true);
            $serversData[] = [
                'host'    => $data['host'],
                'address' => $ip,
                'load'    => $data['load'],
                'disk'    => trim($data['disk']),
                'memory'  => [
                    'total' => (int)round($data['memory']['total']),
                    'free'  => (int)round($data['memory']['free']),
                    'taken' => (int)round($data['memory']['taken']),
                ]
            ];
        }

        return $serversData;
    }

    /**
     * @return array
     */
    public function getLogsInfo()
    {
        $logData = [];

        $logDir = storage_path('logs/');

        $files = scandir($logDir);

        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'log') {
                $logData[] = [
                    'id'            => base64_encode($file),
                    'name'          => $file,
                    'size'          => human_size(filesize($logDir . $file)),
                    'last_modified' => carbon_date(filemtime($logDir . $file))->toAtomString(),
                ];
            }
        }

        usort($logData, function ($a, $b) {
            return $a['name'] < $b['name'];
        });

        return $logData;
    }

    /**
     * @param $id
     * @return array
     */
    public function getLog($id)
    {
        $filePath = $this->decodeAndValidateLogFile($id);
        $name = base64_decode($id);
        $size = filesize($filePath);
        $content = $size <= self::MAX_FILE_SIZE? $this->getLogFileContent($filePath) : [];
        $last_modified = carbon_date(filemtime($filePath))->toAtomString();

        return compact('id', 'name', 'size', 'last_modified', 'content');
    }

    /**
     * @param $encodedFileName
     */
    public function deleteLog($encodedFileName)
    {
        $filePath = $this->decodeAndValidateLogFile($encodedFileName);
        $this->files->delete($filePath);
    }

    /**
     * Rap2hpoutre\LaravelLogViewer::all
     * @param $filePath
     * @return array
     */
    private function getLogFileContent($filePath)
    {
        $file = $this->files->get($filePath);

        preg_match_all(static::$dateTimePattern, $file, $headings);

        if (! is_array($headings) || ! is_array($headings[0])) {
            return [];
        }

        $headings = $headings[0];

        $logData = preg_split(static::$dateTimePattern, $file);

        if ($logData[0] < 1) {
            array_shift($logData);
        }

        $logs = [];

        foreach ($headings as $i => $heading) {
            foreach ($this->logLevels as $levelKey => $levelValue) {
                if (! strpos(strtolower($heading), '.' . $levelValue)) {
                    continue;
                }
                preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*?(\w+)\.' . $levelKey . ': (.*?)( in .*?:[0-9]+)?$/', $heading, $match);
                if (! isset($match[3])) {
                    continue;
                }
                $logs[] = [
                    'context' => $match[2],
                    'level'   => $levelValue,
                    'date'    => carbon_date($match[1])->toAtomString(),
                    'text'    => $match[3],
                    'file'    => isset($match[4])? $match[4] : null,
                    'stack'   => preg_replace("/^\n*/", '', $logData[$i])
                ];
            }
        }

        return array_reverse($logs);
    }

    /**
     * @param $encodedFileName
     * @return mixed
     */
    public function decodeAndValidateLogFile($encodedFileName)
    {
        if (! ($fileName = base64_decode($encodedFileName))) {
            throw new NotFoundHttpException;
        }

        $filePath = storage_path("logs/{$fileName}");

        if (! $this->files->exists($filePath)) {
            throw new NotFoundHttpException;
        }

        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if ($ext != 'log') {
            throw new NotFoundHttpException;
        }

        return $filePath;
    }
}
