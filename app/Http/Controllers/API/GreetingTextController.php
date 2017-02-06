<?php namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Services\GreetingTextService;
use App\Transformers\BaseTransformer;
use App\Transformers\GreetingTextTransformer;

class GreetingTextController extends APIController
{

    /**
     * @type GreetingTextService
     */
    private $greetingTexts;

    /**
     * GreetingTextController constructor.
     *
     * @param GreetingTextService $greetingTexts
     */
    public function __construct(GreetingTextService $greetingTexts)
    {
        $this->greetingTexts = $greetingTexts;
    }

    /**
     * Update the greeting text.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(Request $request)
    {
        $bot = $this->bot();

        $this->validate(
            $request,
            ['text' => 'required|string|max:160'],
            $this->greetingTextValidationCallback($bot)
        );

        $this->greetingTexts->update($request->all(), $bot, $this->user());

        return $this->response->accepted();
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new GreetingTextTransformer();
    }

    /**
     * Ensure that the greeting text include the copyright
     * sentence, if the user isn't on a premium payment plan.
     * @param $page
     * @return \Closure
     */
    private function greetingTextValidationCallback($page)
    {
        return function ($validator, $input) use ($page) {

            $greetingText = trim(array_get($input, 'text'));
            $copyrightSentence = "- Powered By: MrReply.com";

            // @todo and on free plan
            if (! ends_with($greetingText, $copyrightSentence)) {
                $validator->errors()->add('text', "The greeting text has to end with the copyright sentence \"{$copyrightSentence}\".");
            }

            return $validator;
        };
    }
}
