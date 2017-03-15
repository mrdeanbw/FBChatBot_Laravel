<?php namespace Common\Jobs;

use Carbon\Carbon;
use Common\Models\Broadcast;
use Common\Repositories\Broadcast\BroadcastRepositoryInterface;
use Common\Repositories\Subscriber\SubscriberRepositoryInterface;

class SendDueBroadcast extends BaseJob
{

    /**
     * @type BroadcastRepositoryInterface
     */
    protected $broadcastRepo;

    /**
     * @type SubscriberRepositoryInterface
     */
    protected $subscriberRepo;

    /**
     * @type Broadcast
     */
    private $broadcast;

    /**
     * SendBroadcast constructor.
     * @param Broadcast $broadcast
     */
    public function __construct(Broadcast $broadcast)
    {
        $this->broadcast = $broadcast;
    }

    /**
     * Execute the job.
     *
     * @param BroadcastRepositoryInterface  $broadcastRepo
     * @param SubscriberRepositoryInterface $subscriberRepo
     */
    public function handle(BroadcastRepositoryInterface $broadcastRepo, SubscriberRepositoryInterface $subscriberRepo)
    {
        $this->broadcastRepo = $broadcastRepo;
        $this->subscriberRepo = $subscriberRepo;

        $this->process();
    }

    /**
     *
     */
    private function process()
    {
        if ($this->broadcast->status == BroadcastRepositoryInterface::STATUS_PENDING) {
            $this->broadcastRepo->markAsRunning($this->broadcast);
        }

        $this->send();
    }

    /**
     *
     */
    public function send()
    {
        $filters = [];
        $dueSchedules = [];
        $hasSchedules = ! ! $this->broadcast->schedules;
        if ($hasSchedules) {
            $dueSchedules = $this->getDueSchedules();
            $filters[] = [
                'operator' => 'in',
                'key'      => 'timezone',
                'value'    => $dueSchedules
            ];
        }

        $subscribers = $this->subscriberRepo->getActiveTargetAudience($this->broadcast, $filters);

        foreach ($subscribers as $subscriber) {
            $job = (new SendBroadcastToSubscriber($this->broadcast, $subscriber))->onQueue('onetry');
            dispatch($job);
        }

        $subscriberCount = count($subscribers);

        if ($hasSchedules) {
            $this->broadcastRepo->incrementTargetAudienceAndMarkSchedulesAsCompleted($dueSchedules, $this->broadcast, $subscriberCount);
        } else {
            $this->broadcastRepo->setTargetAudienceAndMarkAsCompleted($this->broadcast, $subscriberCount);
        }
    }

    /**
     * @return array
     */
    private function getDueSchedules()
    {
        $ret = [];
        $now = Carbon::now();
        foreach ($this->broadcast->schedules as $schedule) {
            if ($schedule->status == BroadcastRepositoryInterface::STATUS_PENDING && carbon_date($schedule->send_at)->lte($now)) {
                $ret[] = $schedule->utc_offset;
            }
        }

        return $ret;
    }

}