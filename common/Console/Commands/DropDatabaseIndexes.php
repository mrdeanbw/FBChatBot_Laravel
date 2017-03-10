<?php namespace Common\Console\Commands;

use MongoDB\Collection;
use Illuminate\Console\Command;

class DropDatabaseIndexes extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db-index:drop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop database indexes.';


    protected $collections = [
        \Common\Models\Bot::class,
        \Common\Models\User::class,
        \Common\Models\Template::class,
        \Common\Models\Sequence::class,
        \Common\Models\Broadcast::class,
        \Common\Models\Subscriber::class,
        \Common\Models\SentMessage::class,
        \Common\Models\AutoReplyRule::class,
        \Common\Models\MessageRevision::class,
        \Common\Models\SequenceSchedule::class,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! $this->confirm("This cannot be undone. Proceed?")) {
            $this->comment("Wise choice.");

            return;
        }

        foreach ($this->collections as $class) {
            /** @type Collection $collection */
            $collection = $class::raw();
            $collection->dropIndexes();
        }

        $this->info("All database indexes have been dropped.");
    }
}
