<?php namespace App\Console\Commands;

use DB;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\BroadcastSchedule;
use App\Services\AudienceService;
use App\Services\BroadcastService;
use Illuminate\Database\Eloquent\Collection;
use App\Services\FacebookAPIAdapter;

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
     * @var BroadcastService
     */
    private $broadcasts;
    /**
     * @var FacebookAPIAdapter
     */
    private $FacebookAdapter;
    /**
     * @type AudienceService
     */
    private $audience;


    /**
     * Broadcast constructor.
     * @param BroadcastService   $sequences
     * @param AudienceService    $audience
     * @param FacebookAPIAdapter $FacebookAdapter
     */
    public function __construct(BroadcastService $sequences, AudienceService $audience, FacebookAPIAdapter $FacebookAdapter)
    {
        parent::__construct();
        $this->broadcasts = $sequences;
        $this->FacebookAdapter = $FacebookAdapter;
        $this->audience = $audience;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $schedules = $this->getDueBroadcastSchedules();

        /** @var BroadcastSchedule $schedule */
        foreach ($schedules as $schedule) {
            $this->processBroadcastSchedule($schedule);
        }
    }

    /**
     * @return mixed
     */
    private function getDueBroadcastSchedules()
    {
        $schedules = BroadcastSchedule::whereStatus('pending')->where('send_at', '<=', Carbon::now())->get();

        return $schedules;
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
    public function runSchedule(BroadcastSchedule $schedule)
    {
        $audience = $this->getTargetAudience($schedule);

        foreach ($audience as $subscriber) {

            $ret = $this->FacebookAdapter->sendBlocks(
                $schedule->broadcast,
                $subscriber,
                strtoupper($schedule->broadcast->notification)
            );

            $schedule->broadcast->subscribers()->attach($subscriber, ['sent_at' => $ret[0]->sent_at]);
        }
    }

    /**
     * @param BroadcastSchedule $schedule
     */
    protected function markScheduleAsRunning(BroadcastSchedule $schedule)
    {
        DB::transaction(function () use ($schedule) {
            $schedule->broadcast->status = 'running';
            $schedule->broadcast->save();
            $schedule->status = 'running';
            $schedule->save();
        });
    }

    /**
     * @param BroadcastSchedule $schedule
     */
    private function markScheduleAsCompleted(BroadcastSchedule $schedule)
    {
        DB::transaction(function () use ($schedule) {
            $schedule->status = 'completed';
            $schedule->save();

            /**
             * If there are no more schedules for the broadcast, mark it as completed as well.
             */
            if (! $schedule->broadcast->schedule()->whereStatus(['pending', 'running'])->exists()) {
                $schedule->broadcast->status = 'completed';
                $schedule->broadcast->save();
            }
        });
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
