<?php namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Common\Transformers\BaseTransformer;
use Common\Services\MessagePreviewService;

class MessagePreviewController extends APIController
{

    /**
     * @type MessagePreviewService
     */
    private $messagePreviews;

    /**
     * WelcomeMessageController constructor.
     * @param MessagePreviewService $messagePreviews
     */
    public function __construct(MessagePreviewService $messagePreviews)
    {
        $this->messagePreviews = $messagePreviews;
        parent::__construct();
    }

    /**
     * Create a message preview.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(Request $request)
    {
        $bot = $this->enabledBot();
        $this->validateForBot($bot, $request, [
            'template'            => 'bail|required|array',
            'template.messages'   => 'bail|required|array|max:10',
            'template.messages.*' => 'bail|required|array|message',
        ], true);

        $this->messagePreviews->createAndSend($request->all(), $this->user(), $this->enabledBot());

        return $this->response->created();
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
    }
}
