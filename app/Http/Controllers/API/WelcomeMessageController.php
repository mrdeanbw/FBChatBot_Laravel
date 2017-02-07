<?php namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Transformers\BaseTransformer;
use App\Services\WelcomeMessageService;
use App\Transformers\WelcomeMessageTransformer;

class WelcomeMessageController extends APIController
{

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
     * Update the welcome message associated with the bot.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(Request $request)
    {
        $rules = $this->validationRules();
        $this->validate($request, $rules);

        $welcomeMessage = $this->welcomeMessages->update($request->all(), $this->bot());

        return $this->itemResponse($welcomeMessage);
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new WelcomeMessageTransformer();
    }

    private function validationRules()
    {
        return [
            'template'            => 'bail|required|array',
            'template.messages'   => 'bail|required|array|max:10',
            'template.messages.*' => 'bail|required|message',
        ];
    }
}
