<?php namespace App\Console;

use App\Console\Commands\SendDueBroadcasts;
use App\Console\Commands\Sequence;
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
        SendDueBroadcasts::class,
        Sequence::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('sequence:process')->everyMinute();
        $schedule->command('broadcast:process')->everyMinute();
    }
}
