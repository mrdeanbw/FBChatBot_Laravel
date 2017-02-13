<?php namespace App\Console;

use App\Console\Commands;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\SendDueBroadcasts::class,
        Commands\SendDueSequenceMessages::class,
        Commands\CleanUpDeletedSequenceMessages::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('broadcast:process')->everyMinute();
        $schedule->command('sequence:process')->everyFiveMinutes();
        $schedule->command('sequence:clear')->everyThirtyMinutes();
    }
}
