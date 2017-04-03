<?php namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Common\Transformers\BaseTransformer;
use Common\Services\WelcomeMessageService;
use Common\Transformers\WelcomeMessageTransformer;

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
        parent::__construct();
    }

    /**
     * Update the welcome message associated with the bot.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(Request $request)
    {
        $bot = $this->enabledBot();
        $this->validateForBot($bot, $request, [
            'template'            => 'bail|required|array',
            'template.messages'   => 'bail|required|array|max:10',
            'template.messages.*' => 'bail|required|array|message',
        ]);

        $welcomeMessage = $this->welcomeMessages->update($request->all(), $bot);

        return $this->itemResponse($welcomeMessage);
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new WelcomeMessageTransformer();
    }
}
