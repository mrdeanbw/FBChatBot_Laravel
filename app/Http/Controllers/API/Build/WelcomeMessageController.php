<?php namespace App\Http\Controllers\API\Build;

use App\Http\Controllers\API\APIController;
use App\Services\WelcomeMessageService;
use App\Services\Validation\MessageBlockRuleValidator;
use App\Transformers\BaseTransformer;
use App\Transformers\WelcomeMessageTransformer;
use Illuminate\Http\Request;

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
     * @return \Dingo\Api\Http\Response
     */
    public function show()
    {
        $page = $this->page();

        return $this->itemResponse($this->welcomeMessages->get($page));
    }



    /**
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

        $this->welcomeMessages->persist($request->all(), $page);

        return $this->response->accepted();
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new WelcomeMessageTransformer();
    }
}
