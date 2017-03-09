<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SendScheduledSequenceMessage;
use Common\Repositories\Sequence\SequenceRepositoryInterface;
use Common\Repositories\Sequence\SequenceScheduleRepositoryInterface;

class SendDueSequenceMessages extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sequence:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process sequences and send due sequence messages.';
    /**
     * @type SequenceRepositoryInterface
     */
    private $sequenceRepo;
    /**
     * @var SequenceScheduleRepositoryInterface
     */
    private $sequenceScheduleRepo;


    /**
     * SendDueSequenceMessages constructor.
     *
     * @param SequenceRepositoryInterface         $sequenceRepo
     * @param SequenceScheduleRepositoryInterface $sequenceScheduleRepo
     */
    public function __construct(SequenceRepositoryInterface $sequenceRepo, SequenceScheduleRepositoryInterface $sequenceScheduleRepo) {
        parent::__construct();
        $this->sequenceRepo = $sequenceRepo;
        $this->sequenceScheduleRepo = $sequenceScheduleRepo;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $schedules = $this->sequenceScheduleRepo->getDue();

        foreach ($schedules as $schedule) {
            $this->sequenceScheduleRepo->update($schedule, ['status' => SequenceScheduleRepositoryInterface::STATUS_RUNNING]);
            $job = (new SendScheduledSequenceMessage($schedule))->onQueue('onetry');
            dispatch($job);
        }

        $this->info("Done");
    }

}
