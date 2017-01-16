<?php namespace App\Http\Controllers\API\Build;

use Illuminate\Http\Request;
use App\Transformers\BaseTransformer;
use App\Services\WelcomeMessageService;
use App\Http\Controllers\API\APIController;
use App\Transformers\WelcomeMessageTransformer;
use App\Services\Validation\MessageBlockRuleValidator;

class WelcomeMessageController extends APIController
{

    use MessageBlockRuleValidator;

    /**
     * @type WelcomeMessageService
     */
    private $welcomeMessages;

    /**
     * WelcomeMessageController constructor.
     * @param WelcomeMessageService $welcomeMessages
     */
    public function __construct(WelcomeMessageService $welcomeMessages)
    {
        $this->welcomeMessages = $welcomeMessages;
    }

    /**
     * Return the welcome message associated with the page.
     * @return \Dingo\Api\Http\Response
     */
    public function show()
    {
        $page = $this->page();
        $welcomeMessage = $this->welcomeMessages->getOrFail($page);

        return $this->itemResponse($welcomeMessage);
    }

    /**
     * Update the welcome message associated with the page.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(Request $request)
    {
        $page = $this->page();

        $validator = $this->makeValidator($request->all(), [], $page);

        if ($validator->fails()) {
            return $this->errorsResponse($validator->errors());
        }

        $this->welcomeMessages->update($request->all(), $page);

        return $this->response->accepted();
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new WelcomeMessageTransformer();
    }
}
