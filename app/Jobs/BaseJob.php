<?php namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maknz\Slack\Client as SlackClient;

abstract class BaseJob implements ShouldQueue
{

    use InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;

    protected $pushErrorsOnFail = false;
    protected $failMessageTitle = "Unexpected Error!";
    protected $failMessageBody = "An unexpected error has occurred. We are looking into it!";

    /**
     * The job failed to process.
     */
    public function failed(Exception $exception)
    {
        if (! $this->pushErrorsOnFail) {
            return;
        }

        notify_frontend($this->getErrorChannel(), 'error', [
            'title'   => $this->failMessageTitle,
            'message' => $this->failMessageBody
        ]);

        $this->sendSlackAlert($exception);
    }


    protected function sendSlackAlert(Exception $exception)
    {
        $slackwebhook = getenv('MONITOR_SLACK_WEBHOOK');
        if($slackwebhook == ''){
            return;
        }
        $client = new SlackClient($slackwebhook);
        $client->withIcon(':robot_face:')->attach([
                'fallback' => 'Job failed :'.get_class(),
                'text' => 'Job failed :'.get_class(),
                'color' => 'warning',
                'fields' => [
                    [
                        'title' => 'Error Code',
                        'value' => $exception->getCode(),
                        'short' => true 
                    ],
                    [
                        'title' => 'File',
                        'value' => $exception->getFile(). ' ( line: ) '.$exception->getLine(),
                        'short' => true
                    ],
                    [
                        'title' => 'Error Message',
                        'value' => $exception->getMessage(),
                        'short' => false 
                    ]
                ]
            ])->send('Job  '. get_class() .' is failing');
    }

    /**
     * @return string
     */
    protected function getErrorChannel()
    {
        return "{$this->userId}_notifications";
    }

}