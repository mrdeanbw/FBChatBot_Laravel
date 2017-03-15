<?php namespace Common\Console\Commands;

use Common\Models\Broadcast;
use Illuminate\Console\Command;
use Common\Jobs\SendDueBroadcast;
use Common\Repositories\Broadcast\BroadcastRepositoryInterface;

class SendDueBroadcasts extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'broadcast:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and run active broadcasts.';
    /**
     * @type BroadcastRepositoryInterface
     */
    private $broadcastRepo;
    
    /**
     * SendDueBroadcasts constructor.
     *
     * @param BroadcastRepositoryInterface $broadcastRepo
     */
    public function __construct(BroadcastRepositoryInterface $broadcastRepo)
    {
        parent::__construct();
        $this->broadcastRepo = $broadcastRepo;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $broadcasts = $this->broadcastRepo->getDueBroadcasts();

        /** @var Broadcast $broadcast */
        foreach ($broadcasts as $broadcast) {
            $job = (new SendDueBroadcast($broadcast))->onQueue('onetry');
            dispatch($job);
        }

        $this->info("Done");
    }
}
