<?php namespace App\Http\Controllers\API;

use App\Services\Facebook\Makana\MakanaAdapter;
use App\Services\MessagePreviewService;
use App\Services\Validation\MessageBlockRuleValidator;
use App\Transformers\BaseTransformer;
use Illuminate\Http\Request;

class MessagePreviewController extends APIController
{

    use MessageBlockRuleValidator;

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
    }
    

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(Request $request)
    {
        $page = $this->page();

        $validator = $this->makeValidator($request->all(), [], $page);

        if ($validator->fails()) {
            return $this->errorsResponse($validator->errors());
        }

        $this->messagePreviews->createAndSend($request->all(), $this->user(), $page);

        return $this->response->created();
    }
    
    /** @return BaseTransformer */
    protected function transformer()
    {
    }
}
