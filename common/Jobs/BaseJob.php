<?php namespace Common\Jobs;

use Exception;
use MongoDB\BSON\ObjectID;
use Illuminate\Bus\Queueable;
use Maknz\Slack\Client as SlackClient;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

abstract class BaseJob implements ShouldQueue
{

    use InteractsWithQueue, Queueable, SerializesModels;

    /** @var ObjectID  */
    protected $userId;
    protected $pushErrorsToSlackOnFail = true;
    protected $pushErrorsToFrontendOnFail = false;
    protected $frontendFailMessageTitle = "Unexpected Error!";
    protected $frontendFailMessageBody = "An unexpected error has occurred. We are looking into it!";

    /**
     * The job failed to process.
     * @param Exception $exception
     */
    public function failed(Exception $exception)
    {
        if ($this->pushErrorsToFrontendOnFail) {
            notify_frontend($this->getFrontendErrorChannel(), 'error', [
                'title'   => $this->frontendFailMessageTitle,
                'message' => $this->frontendFailMessageBody
            ]);
        }

        if ($this->pushErrorsToSlackOnFail) {
            $this->sendSlackAlert($exception);
        }
    }

    /**
     * @return string
     */
    protected function getFrontendErrorChannel()
    {
        return "{$this->userId}_notifications";
    }

    /**
     * @param Exception $exception
     */
    protected function sendSlackAlert(Exception $exception)
    {
        $slackWebhook = config('services.slack.monitor_webhook');
        if (! $slackWebhook) {
            return;
        }
        $client = new SlackClient($slackWebhook);

        $class = get_called_class();

        $client->withIcon(':robot_face:')->attach([
            'fallback' => 'Job failed: ' . $class,
            'text'     => 'Job failed: ' . $class,
            'color'    => 'warning',
            'fields'   => [
                [
                    'title' => 'Error Code',
                    'value' => $exception->getCode(),
                    'short' => true
                ],
                [
                    'title' => 'File',
                    'value' => $exception->getFile() . ' ( line: ) ' . $exception->getLine(),
                    'short' => true
                ],
                [
                    'title' => 'Error Message',
                    'value' => $exception->getMessage(),
                    'short' => false
                ]
            ]
        ])->send('Job  ' . $class . ' is failing');
    }

    /**
     * @param ObjectID $botId
     */
    public function setSentryContext(ObjectID $botId)
    {
        if (! config('sentry.dsn')) {
            return;
        }
        $context = ['bot_id' => (string)$botId];

        if ($this->userId) {
            $context['user_id'] = (string)$this->userId;
        }

        app('sentry')->user_context($context);
    }
}