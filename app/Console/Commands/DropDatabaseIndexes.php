<?php namespace App\Console\Commands;

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
        \App\Models\Bot::class,
        \App\Models\User::class,
        \App\Models\Template::class,
        \App\Models\Sequence::class,
        \App\Models\Broadcast::class,
        \App\Models\Subscriber::class,
        \App\Models\SentMessage::class,
        \App\Models\AutoReplyRule::class,
        \App\Models\MessageRevision::class,
        \App\Models\SequenceSchedule::class,
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
