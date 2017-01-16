<?php namespace App\Http\Controllers\API\Build;

use Illuminate\Http\Request;
use App\Services\GreetingTextService;
use App\Transformers\BaseTransformer;
use App\Http\Controllers\API\APIController;
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
     * Return the details of the greeting text associated with the page.
     * @return \Dingo\Api\Http\Response
     */
    public function show()
    {
        $page = $this->page();
        $greetingText = $this->greetingTexts->get($page);

        return $this->itemResponse($greetingText);
    }

    /**
     * Update the greeting text.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(Request $request)
    {
        $page = $this->page();

        $this->validate(
            $request,
            ['text' => 'required|string|max:160'],
            $this->greetingTextValidationCallback($page)
        );

        $this->greetingTexts->persist($request->all(), $page);

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

            if (! $page->payment_plan && ! ends_with($greetingText, $copyrightSentence)) {
                $validator->errors()->add('text', "The greeting text has to end with the copyright sentence \"{$copyrightSentence}\".");
            }

            return $validator;
        };
    }
}
