<?php namespace Common\Exceptions;

use Exception;
use Raven_Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param \Exception $e
     * @return void
     */
    public function report(Exception $e)
    {
        if ($this->shouldntReport($e)) {
            return;
        }

        if (! config('sentry.dsn')) {
            if (is_a($e, ClientException::class)) {
                /** @type ClientException $e */
                $message = $this->getFormattedGuzzleExceptionMessage($e);
                try {
                    /** @type \Psr\Log\LoggerInterface $logger */
                    $logger = app('Psr\Log\LoggerInterface');
                } catch (Exception $ex) {
                    throw $e; // throw the original exception
                }

                /** @noinspection PhpInconsistentReturnPointsInspection */
                return $logger->error($message);
            }

            /** @noinspection PhpInconsistentReturnPointsInspection */
            return parent::report($e);
        }

        $lastTraceId = app('sentry')->captureException($e);

        if (is_a($e, ClientException::class)) {
            /** @type ClientException $e */
            /** @noinspection PhpInconsistentReturnPointsInspection */
            app('sentry')->captureMessage($this->getGuzzleFullRequestAndResponse($e), [], [
                'level' => Raven_Client::INFO,
                'extra' => ['event_id' => $lastTraceId]
            ]);
        }

    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception               $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        return parent::render($request, $e);
    }

    /**
     * @param ClientException $e
     * @return string
     */
    protected function getFormattedGuzzleExceptionMessage(ClientException $e)
    {
        $fullRequestAndResponse = $this->getGuzzleFullRequestAndResponse($e);

        return
            '[' . date('Y-m-d H:i:s') . '] ' .
            get_class($e) . ': ' .
            $e->getMessage() .
            $fullRequestAndResponse .
            "in " . $e->getFile() . ':' . $e->getLine() .
            "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    }

    /**
     * @param ClientException $e
     * @return string
     */
    protected function getGuzzleFullRequestAndResponse(ClientException $e)
    {
        $request = $e->getRequest();
        $fullRequest = $request->getMethod() . ' ' . $request->getUri() . "\n";
        $fullRequest .= "Request: " . $request->getBody() . "\n";
        $fullResponse = "Response: " . $e->getResponse()->getBody() . "\n";

        return $fullRequest . $fullResponse;
    }
}
