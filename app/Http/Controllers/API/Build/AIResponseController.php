<?php namespace App\Http\Controllers\API\Build;

use App\Http\Controllers\API\APIController;
use App\Services\AutoReplyRuleService;
use App\Transformers\AutoReplyRuleTransformer;
use App\Transformers\BaseTransformer;
use Illuminate\Http\Request;

class AIResponseController extends APIController
{

    /**
     * @type AutoReplyRuleService
     */
    private $AIResponses;

    /**
     * AIResponseController constructor.
     *
     * @param AutoReplyRuleService $AIResponses
     */
    public function __construct(AutoReplyRuleService $AIResponses)
    {
        $this->AIResponses = $AIResponses;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function rules()
    {
        $page = $this->page();

        return $this->collectionResponse($this->AIResponses->all($page));
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createRule(Request $request)
    {
        $page = $this->page();

        $this->validate($request, $this->KeywordRuleValidationRules($page));

        $rule = $this->AIResponses->create($request->all(), $page);

        return $this->itemResponse($rule);
    }

    /**
     * @param         $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateRule($id, Request $request)
    {
        $page = $this->page();

        $this->validate($request, $this->KeywordRuleValidationRules($page, $id));

        $this->AIResponses->update($id, $request->all(), $page);

        return $this->response->accepted();
    }

    /**
     * @param $id
     *
     * @return \Dingo\Api\Http\Response
     */
    public function deleteRule($id)
    {
        $page = $this->page();

        $this->AIResponses->delete($id, $page);

        return $this->response->accepted();
    }

    /**
     * @param      $page
     * @param null $id
     *
     * @return array
     */
    private function KeywordRuleValidationRules($page, $id = null)
    {
        $additionalKeywordRule = "|unique:auto_reply_rules,keyword,";
        $additionalKeywordRule .= $id ? "{$id},id," : "NULL,NULL,";
        $additionalKeywordRule .= "page_id,{$page->id}";

        return [
            'mode'        => 'bail|required|in:is,contains,begins_with',
            'keyword'     => "bail|required|max:255{$additionalKeywordRule}",
            'action'      => 'bail|required', // must be "send"
            'template'    => 'bail|required|array',
            'template.id' => 'bail|required|exists:templates,id,page_id,' . $page->id
        ];
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new AutoReplyRuleTransformer();
    }
}
