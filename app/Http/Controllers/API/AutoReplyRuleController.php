<?php namespace App\Http\Controllers\API;

use App\Models\Bot;
use Illuminate\Http\Request;
use App\Transformers\BaseTransformer;
use App\Services\AutoReplyRuleService;
use App\Transformers\AutoReplyRuleTransformer;

class AutoReplyRuleController extends APIController
{

    /**
     * @type AutoReplyRuleService
     */
    private $autoReplies;

    /**
     * AIResponseController constructor.
     *
     * @param AutoReplyRuleService $AutoReplies
     */
    public function __construct(AutoReplyRuleService $AutoReplies)
    {
        $this->autoReplies = $AutoReplies;
    }

    /**
     * Return the list of auto reply rules.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request)
    {
        $paginator = $this->autoReplies->paginate(
            $this->bot(),
            $request->get('page'),
            ['keyword' => $request->get('keyword')]
        );


        return $this->paginatorResponse($paginator);
    }

    /**
     * Create a new rule.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function create(Request $request)
    {
        $bot = $this->bot();

        $this->validate($request, $this->validationRules($bot));

        $rule = $this->autoReplies->create($request->all(), $bot);

        return $this->itemResponse($rule);
    }

    /**
     * Update a rule.
     * @param         $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update($id, Request $request)
    {
        $bot = $this->bot();

        $this->validate($request, $this->validationRules($bot, $id));

        $this->autoReplies->update($id, $request->all(), $bot);

        return $this->response->accepted();
    }

    /**
     * Delete a rule.
     * @param $id
     * @return \Dingo\Api\Http\Response
     */
    public function destroy($id)
    {
        $bot = $this->bot();

        $this->autoReplies->delete($id, $bot);

        return $this->response->accepted();
    }

    /**
     * Array of validation rules for creating a new auto reply rule.
     * @param Bot  $bot
     * @param null $ruleId
     * @return array
     */
    private function validationRules(Bot $bot, $ruleId = null)
    {
        $keywordUniqueRule = "unique:auto_reply_rules,keyword,";
        $keywordUniqueRule .= $ruleId? "{$ruleId},_id," : "NULL,NULL,";
        $keywordUniqueRule .= "bot_id,{$bot->id}";

        return [
            'mode'        => 'bail|required|in:is,contains,begins_with',
            'keyword'     => "bail|required|max:255|{$keywordUniqueRule}",
            'action'      => 'bail|required|in:send',
            'template'    => 'bail|required|array',
            'template.id' => 'bail|required|exists:templates,_id,bot_id,' . $bot->id
        ];
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new AutoReplyRuleTransformer();
    }
}
