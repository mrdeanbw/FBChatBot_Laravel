<?php

namespace App\Console\Commands;

use App\Models\BroadcastSchedule;
use App\Services\AudienceService;
use App\Services\BroadcastService;
use App\Services\Facebook\Makana\MakanaAdapter;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
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
     * @var BroadcastService
     */
    private $broadcasts;
    /**
     * @var MakanaAdapter
     */
    private $Makana;
    /**
     * @type AudienceService
     */
    private $audience;


    /**
     * Broadcast constructor.
     * @param BroadcastService $sequences
     * @param AudienceService  $audience
     * @param MakanaAdapter    $Makana
     */
    public function __construct(BroadcastService $sequences, AudienceService $audience, MakanaAdapter $Makana)
    {
        parent::__construct();
        $this->broadcasts = $sequences;
        $this->Makana = $Makana;
        $this->audience = $audience;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $schedules = BroadcastSchedule::whereStatus('pending')->where('send_at', '<=', Carbon::now())->get();

        /** @var BroadcastSchedule $schedule */
        foreach ($schedules as $schedule) {

            DB::beginTransaction();

            $this->markAsRunning($schedule);

            $audience = $this->getTargetAudience($schedule);
            
            foreach ($audience as $subscriber) {
                $ret = $this->Makana->sendBlocks($schedule->broadcast, $subscriber, strtoupper($schedule->broadcast->notification));
                $schedule->broadcast->subscribers()->attach($subscriber, ['sent_at' => $ret[0]->sent_at]);
            }

            $this->markAsCompleted($schedule);

            DB::commit();
        }
    }

    /**
     * @param BroadcastSchedule $schedule
     */
    protected function markAsRunning(BroadcastSchedule $schedule)
    {
        DB::beginTransaction();
        if ($schedule->broadcast->status == 'pending') {
            $schedule->broadcast->status = 'running';
            $schedule->broadcast->save();
        }
        $schedule->status = 'running';
        $schedule->save();
        DB::commit();
    }

    /**
     * @param BroadcastSchedule $schedule
     */
    private function markAsCompleted(BroadcastSchedule $schedule)
    {
        DB::beginTransaction();

        $schedule->status = 'completed';
        $schedule->save();

        if (! $schedule->broadcast->schedule()->whereStatus(['pending', 'running'])->exists()) {
            $schedule->broadcast->status = 'completed';
        }

        $schedule->broadcast->save();

        DB::commit();
    }


    /**
     * @param $schedule
     * @return Collection
     */
    protected function getTargetAudience(BroadcastSchedule $schedule)
    {
        $audience = $this->audience->targetAudienceQuery($schedule->broadcast)->whereTimezone($schedule->timezone)->get();

        return $audience;
    }
}
