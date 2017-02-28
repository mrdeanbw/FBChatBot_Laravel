<?php 
namespace App\Console\Commands;

use Illuminate\Console\Command;

use Maknz\Slack\Client as SlackClient;
use Modules\Monitor\Services\MonitorService;

class MonitorAlert extends Command
{
	/**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:alert';

    /**
     * Const THERHOLDS
     */ 
    const MEMORY_THRESHOLD = 80;
    const CPU_THRESHOLD = 1;

    /**
     * @var Messages template
     */
    protected $messages = [
    	'memory' => 'high memory usage ',
    	'cpu' => 'high cpu load '
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Alert our team if there is an emergency';

    /**
     * @var Monitor Service
     */
    protected $monitor;

    /**
     * Constructor
     * @param MonitorService $monitor 
     */
    public function __construct(MonitorService $monitor)
    {
    	parent::__construct();
    	$this->monitor = $monitor;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $servers = $this->monitor->getServersInfo();

        foreach ($servers as $server) {
        	if($server['memory']['percent'] > self::MEMORY_THRESHOLD){
        		$this->error('Memory Danger');
        		$this->sendAlert('memory',$server);
        		continue;
        	}
        	if(
        		$server['load'][0] > self::CPU_THRESHOLD || 
        		$server['load'][1] > self::CPU_THRESHOLD ||
        		$server['load'][2] > self::CPU_THRESHOLD 
        	){
        		$this->error('Cpu Danger');
        		$this->sendAlert('cpu',$server);
        		continue;
        	}
        }

        $this->info('Done');
        
    }


    public function sendAlert($type , $server)
    {
    	$slackwebhook = getenv('MONITOR_SLACK_WEBHOOK');
        if($slackwebhook == ''){
        	return;
        }
    	$client = new SlackClient($slackwebhook);
        $client->withIcon(':robot_face:')->attach([
			    'fallback' => 'Server :'.$server['host'],
			    'text' => 'Server :'.$server['host'],
			    'color' => 'danger',
			    'fields' => [
			        [
			            'title' => 'CPU Load',
			            'value' => implode(',',$server['load']),
			            'short' => true 
			        ],
			        [
			            'title' => 'RAM usage',
			            'value' => $server['memory']['taken'].' MB of '.$server['memory']['total'].'MB',
			            'short' => true
			        ]
			    ]
			])->send($this->messages[$type] .' on '.$server['host']);
    }
}