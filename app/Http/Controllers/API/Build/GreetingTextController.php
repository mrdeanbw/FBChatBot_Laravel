<?php namespace App\Http\Controllers\API\Build;

use App\Http\Controllers\API\APIController;
use App\Services\GreetingTextService;
use App\Transformers\BaseTransformer;
use App\Transformers\GreetingTextTransformer;
use Illuminate\Http\Request;

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
     * @return \Dingo\Api\Http\Response
     */
    public function show()
    {
        $page = $this->page();

        return $this->itemResponse($this->greetingTexts->get($page));
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(Request $request)
    {
        $page = $this->page();

        $this->validate($request, ['text' => 'required|string|max:160'], function ($validator, $input) use ($page) {

            $copyrightSentence = "Powered By: MrReply.com";
            if (! $page->payment_plan && substr(trim(array_get($input, 'text')), -strlen($copyrightSentence)) !== $copyrightSentence) {
                $validator->errors()->add('text', 'The greeting text has to end with the copyright sentence "- Powered By: MrReply.com".');
            }

            return $validator;
        });

        $this->greetingTexts->persist($request->all(), $page);

        return $this->response->accepted();
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new GreetingTextTransformer();
    }
}
