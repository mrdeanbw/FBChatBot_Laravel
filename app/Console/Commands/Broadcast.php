<?php namespace App\Console\Commands;

use App\Repositories\Broadcast\BroadcastRepository;
use DB;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\BroadcastSchedule;
use App\Services\AudienceService;
use App\Services\BroadcastService;
use App\Services\FacebookAPIAdapter;
use Illuminate\Database\Eloquent\Collection;

class Broadcast extends Command
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
     * @var FacebookAPIAdapter
     */
    private $FacebookAdapter;
    /**
     * @type AudienceService
     */
    private $audience;
    /**
     * @type BroadcastRepository
     */
    private $broadcastRepo;


    /**
     * Broadcast constructor.
     * @param BroadcastRepository $broadcastRepo
     * @param AudienceService     $audience
     * @param FacebookAPIAdapter  $FacebookAdapter
     */
    public function __construct(BroadcastRepository $broadcastRepo, AudienceService $audience, FacebookAPIAdapter $FacebookAdapter)
    {
        parent::__construct();
        $this->FacebookAdapter = $FacebookAdapter;
        $this->audience = $audience;
        $this->broadcastRepo = $broadcastRepo;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $schedules = $this->broadcastRepo->getDueBroadcastSchedule();

        /** @var BroadcastSchedule $schedule */
        foreach ($schedules as $schedule) {
            $this->processBroadcastSchedule($schedule);
        }
    }

    /**
     * @param BroadcastSchedule $schedule
     */
    private function processBroadcastSchedule(BroadcastSchedule $schedule)
    {
        DB::transaction(function () use ($schedule) {

            $this->markScheduleAsRunning($schedule);

            $this->runSchedule($schedule);

            $this->markScheduleAsCompleted($schedule);
        });
    }

    /**
     * @param BroadcastSchedule $schedule
     */
    protected function markScheduleAsRunning(BroadcastSchedule $schedule)
    {
        DB::transaction(function () use ($schedule) {
            $this->broadcastRepo->update($schedule->broadcast, ['status' => 'running']);
            $this->broadcastRepo->updateSchedule($schedule, ['status' => 'running']);
        });
    }

    /**
     * @param BroadcastSchedule $schedule
     */
    private function markScheduleAsCompleted(BroadcastSchedule $schedule)
    {
        DB::transaction(function () use ($schedule) {
            $this->broadcastRepo->updateSchedule($schedule, ['status' => 'completed']);

            // If there are no more schedules for the broadcast, mark it as completed as well.
            if (! $this->broadcastRepo->broadcastHasIncompleteSchedule($schedule->broadcast)) {
                $this->broadcastRepo->update($schedule->broadcast, ['status' => 'completed']);
            }
        });
    }

    /**
     * @param BroadcastSchedule $schedule
     */
    public function runSchedule(BroadcastSchedule $schedule)
    {
        $audience = $this->getTargetAudience($schedule);

        foreach ($audience as $subscriber) {

            $ret = $this->FacebookAdapter->sendBlocks(
                $schedule->broadcast,
                $subscriber,
                strtoupper($schedule->broadcast->notification)
            );

            $this->broadcastRepo->attachSubscriber(
                $schedule->broadcast,
                $subscriber,
                ['sent_at' => $ret[0]->sent_at]
            );
        }
    }


    /**
     * @param $schedule
     * @return Collection
     */
    protected function getTargetAudience(BroadcastSchedule $schedule)
    {
        $filters = [
            [
                'type'      => 'exact',
                'attribute' => 'timezone',
                'value'     => $schedule->timezone
            ]
        ];

        $audience = $this->audience->getActiveTargetAudience($schedule->broadcast, $filters);

        return $audience;
    }
}
