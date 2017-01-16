<?php namespace App\Http\Controllers\API\Build;

use Illuminate\Http\Request;
use App\Transformers\BaseTransformer;
use App\Services\AutoReplyRuleService;
use App\Http\Controllers\API\APIController;
use App\Transformers\AutoReplyRuleTransformer;

class AutoReplyController extends APIController
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
     * Return the list of auto reply rules.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function rules()
    {
        $page = $this->page();
        $rules = $this->AIResponses->all($page);

        return $this->collectionResponse($rules);
    }

    /**
     * Create a new rule.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createRule(Request $request)
    {
        $page = $this->page();

        $this->validate($request, $this->AIResponseRuleValidationRules($page));

        $rule = $this->AIResponses->create($request->all(), $page);

        return $this->itemResponse($rule);
    }

    /**
     * Update a rule.
     * @param         $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateRule($id, Request $request)
    {
        $page = $this->page();

        $this->validate($request, $this->AIResponseRuleValidationRules($page, $id));

        $this->AIResponses->update($id, $request->all(), $page);

        return $this->response->accepted();
    }

    /**
     * Delete a rule.
     * @param $id
     * @return \Dingo\Api\Http\Response
     */
    public function deleteRule($id)
    {
        $page = $this->page();

        $this->AIResponses->delete($id, $page);

        return $this->response->accepted();
    }

    /**
     * Array of validation rules for creating a new auto reply rule.
     * @param      $page
     * @param null $id
     * @return array
     */
    private function AIResponseRuleValidationRules($page, $id = null)
    {
        $keywordUniqueRule = "unique:auto_reply_rules,keyword,";
        $keywordUniqueRule .= $id? "{$id},id," : "NULL,NULL,";
        $keywordUniqueRule .= "page_id,{$page->id}";

        return [
            'mode'        => 'bail|required|in:is,contains,begins_with',
            'keyword'     => "bail|required|max:255|{$keywordUniqueRule}",
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
