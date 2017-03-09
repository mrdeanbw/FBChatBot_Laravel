<?php namespace App\Console\Commands;

use Carbon\Carbon;
use Common\Models\Broadcast;
use App\Jobs\SendBroadcast;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use App\Services\BroadcastService;
use Common\Repositories\Broadcast\BroadcastRepositoryInterface;
use Common\Repositories\Subscriber\SubscriberRepositoryInterface;

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
     * @type BroadcastService
     */
    private $broadcasts;
    /**
     * @type SubscriberRepositoryInterface
     */
    private $subscriberRepo;


    /**
     * SendDueBroadcasts constructor.
     *
     * @param BroadcastRepositoryInterface  $broadcastRepo
     * @param BroadcastService              $broadcasts
     * @param SubscriberRepositoryInterface $subscriberRepo
     */
    public function __construct(
        BroadcastService $broadcasts,
        BroadcastRepositoryInterface $broadcastRepo,
        SubscriberRepositoryInterface $subscriberRepo
    ) {
        parent::__construct();
        $this->broadcasts = $broadcasts;
        $this->broadcastRepo = $broadcastRepo;
        $this->subscriberRepo = $subscriberRepo;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $broadcasts = $this->broadcastRepo->getDueBroadcasts();

        /** @var Broadcast $broadcast */
        foreach ($broadcasts as $broadcast) {
            $this->processBroadcast($broadcast);
        }

        $this->info("Done");
    }

    /**
     * @param Broadcast $broadcast
     */
    private function processBroadcast(Broadcast $broadcast)
    {
        $this->markAsRunning($broadcast);

        $this->send($broadcast);

        $this->scheduleNextRunAndMarkAsCompleted($broadcast);
    }

    /**
     * @param Broadcast $broadcast
     */
    protected function markAsRunning(Broadcast $broadcast)
    {
        $this->broadcastRepo->update($broadcast, ['status' => BroadcastRepositoryInterface::STATUS_RUNNING]);
    }

    public function send(Broadcast $broadcast)
    {
        $subscribers = $this->getTargetAudience($broadcast);

        foreach ($subscribers as $subscriber) {
            $job = (new SendBroadcast($broadcast, $subscriber))->onQueue('onetry');
            dispatch($job);
        }
    }

    /**
     * @param Broadcast $broadcast
     */
    private function scheduleNextRunAndMarkAsCompleted(Broadcast $broadcast)
    {
        $data = $this->broadcasts->calculateNextScheduleDateTime($broadcast);

        if (is_null($data['next_send_at'])) {
            $data['status'] = BroadcastRepositoryInterface::STATUS_COMPLETED;
            $data['completed_at'] = Carbon::now();
        } else {
            $data['status'] = BroadcastRepositoryInterface::STATUS_PENDING;
        }

        $this->broadcastRepo->update($broadcast, $data);
    }

    /**
     * @param $broadcast
     *
     * @return Collection
     */
    protected function getTargetAudience(Broadcast $broadcast)
    {
        $filters = [];
        if ($broadcast->timezone != 'same_time') {
            $filters[] = [
                'operator' => '=',
                'key'      => 'timezone',
                'value'    => $broadcast->next_utc_offset
            ];
        }

        $audience = $this->subscriberRepo->getActiveTargetAudience($broadcast, $filters);

        return $audience;
    }
}
