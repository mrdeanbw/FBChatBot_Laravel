<?php namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

abstract class BaseJob implements ShouldQueue
{

    use InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;

    protected $pushErrorsOnFail = true;
    protected $failMessageTitle = "Unexpected Error!";
    protected $failMessageBody = "An unexpected error has occurred. We are looking into it!";

    /**
     * The job failed to process.
     */
    public function failed()
    {
        if (! $this->pushErrorsOnFail) {
            return;
        }

        notify_frontend($this->getErrorChannel(), 'error', [
            'title'   => $this->failMessageTitle,
            'message' => $this->failMessageBody
        ]);
    }

    /**
     * @return string
     */
    protected function getErrorChannel()
    {
        return "{$this->userId}_notifications";
    }

}