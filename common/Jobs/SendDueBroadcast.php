<?php namespace Common\Jobs;

use Carbon\Carbon;
use Common\Models\Broadcast;
use Common\Models\BroadcastSchedule;
use Common\Services\BroadcastService;
use Common\Services\LoadsAssociatedModels;
use Common\Repositories\Broadcast\BroadcastRepositoryInterface;
use Common\Repositories\Subscriber\SubscriberRepositoryInterface;

class SendDueBroadcast extends BaseJob
{

    use LoadsAssociatedModels;

    /**
     * @type BroadcastRepositoryInterface
     */
    protected $broadcastRepo;

    /**
     * @type SubscriberRepositoryInterface
     */
    protected $subscriberRepo;
    /**
     * @type BroadcastService
     */
    protected $broadcasts;

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
     * @param BroadcastService              $broadcasts
     */
    public function handle(BroadcastRepositoryInterface $broadcastRepo, SubscriberRepositoryInterface $subscriberRepo, BroadcastService $broadcasts)
    {
        $this->setSentryContext($this->broadcast->bot_id);
        $this->broadcasts = $broadcasts;
        $this->broadcastRepo = $broadcastRepo;
        $this->subscriberRepo = $subscriberRepo;

        $this->loadModelsIfNotLoaded($this->broadcast, ['bot']);
        if (! $this->broadcast->bot->enabled) {
            return $this->cancelBroadcast();
        }

        return $this->process();
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
            $this->incrementTargetAudienceAndMarkSchedulesAsCompleted($dueSchedules, $subscriberCount);
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

    /**
     * @param array $dueSchedules
     * @param int   $targetCount
     */
    protected function incrementTargetAudienceAndMarkSchedulesAsCompleted(array $dueSchedules, $targetCount)
    {
        $update = [];

        $pendingScheduleTimezones = [];
        $pendingSchedules = $this->pendingSchedules($this->broadcast, $dueSchedules);
        foreach ($pendingSchedules as $schedule) {
            $pendingScheduleTimezones[] = $schedule->utc_offset;
        }

        if ($pendingSchedules) {
            $pendingSubscribers = $this->broadcasts->matchingSubscriberCount($this->broadcast->message_type, $this->broadcast->filter, $pendingScheduleTimezones);
        } else {
            $pendingSubscribers = 0;
        }

        if (! $pendingSubscribers) {
            $update['remaining_target'] = 0;
            $update['completed_at'] = Carbon::now();
            $update['status'] = BroadcastRepositoryInterface::STATUS_COMPLETED;
            foreach ($this->broadcast->schedules as $i => $schedule) {
                if ($schedule->status != BroadcastRepositoryInterface::STATUS_COMPLETED) {
                    $update["schedules.{$i}.status"] = BroadcastRepositoryInterface::STATUS_COMPLETED;
                }
            }
        } else {
            foreach ($this->broadcast->schedules as $i => $schedule) {
                if (in_array($schedule->utc_offset, $dueSchedules)) {
                    $update["schedules.{$i}.status"] = BroadcastRepositoryInterface::STATUS_COMPLETED;
                }
            }
            $update['remaining_target'] = $pendingSubscribers;
        }

        $this->broadcastRepo->update($this->broadcast, [
            '$inc' => ['stats.target' => $targetCount],
            '$set' => $update
        ]);
    }

    /**
     * @param Broadcast $broadcast
     * @param array     $dueSchedules
     * @return BroadcastSchedule[]
     */
    private function pendingSchedules(Broadcast $broadcast, array $dueSchedules)
    {
        return array_filter($broadcast->schedules, function (BroadcastSchedule $schedule) use ($dueSchedules) {
            return ! in_array($schedule->utc_offset, $dueSchedules) && $schedule->status != BroadcastRepositoryInterface::STATUS_COMPLETED;
        });
    }

    /**
     * 
     */
    protected function cancelBroadcast()
    {
        $update = ['status' => BroadcastRepositoryInterface::STATUS_CANCELLED];
        foreach ($this->broadcast->schedules as $i => $schedule) {
            if ($schedule->status != BroadcastRepositoryInterface::STATUS_COMPLETED) {
                $update["schedules.{$i}.status"] = BroadcastRepositoryInterface::STATUS_COMPLETED;
            }
        }
        $this->broadcastRepo->update($this->broadcast, $update);
    }
}
