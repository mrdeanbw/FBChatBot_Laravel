<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Common\Repositories\Sequence\SequenceRepositoryInterface;

class CleanUpDeletedSequenceMessages extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sequence:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up soft deleted sequence messages that has no queued subscribers.';
    /**
     * @type SequenceRepositoryInterface
     */
    private $sequenceRepo;

    /**
     * CleanUpDeletedSequenceMessages constructor.
     *
     * @param SequenceRepositoryInterface $sequenceRepo
     */
    public function __construct(SequenceRepositoryInterface $sequenceRepo)
    {
        parent::__construct();
        $this->sequenceRepo = $sequenceRepo;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->sequenceRepo->completelyDeleteSoftDeletedSequenceMessagesWithNoPeopleQueued();
    }
}
