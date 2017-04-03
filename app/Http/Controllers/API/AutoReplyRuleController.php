<?php namespace App\Http\Controllers\API;

use Common\Models\Bot;
use MongoDB\BSON\ObjectID;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Common\Transformers\BaseTransformer;
use Common\Services\AutoReplyRuleService;
use Common\Transformers\AutoReplyRuleTransformer;

class AutoReplyRuleController extends APIController
{

    /**
     * @type AutoReplyRuleService
     */
    private $autoReplies;

    /**
     * AIResponseController constructor.
     * @param AutoReplyRuleService $AutoReplies
     */
    public function __construct(AutoReplyRuleService $AutoReplies)
    {
        $this->autoReplies = $AutoReplies;
        parent::__construct();
    }

    /**
     * Return the list of auto reply rules.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request)
    {
        $paginator = $this->autoReplies->paginate(
            $this->enabledBot(),
            (int)$request->get('page', 1),
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
        $bot = $this->enabledBot();
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
        $id = new ObjectID($id);
        $bot = $this->enabledBot();
        $this->validate($request, $this->validationRules($bot));
        $rule = $this->autoReplies->update($id, $request->all(), $bot);

        return $this->itemResponse($rule);
    }

    /**
     * Delete a rule.
     * @param $id
     * @return \Dingo\Api\Http\Response
     */
    public function destroy($id)
    {
        $id = new ObjectID($id);
        $bot = $this->enabledBot();
        $this->autoReplies->delete($id, $bot);

        return $this->response->accepted();
    }

    /**
     * Array of validation rules for creating a new auto reply rule.
     * @param Bot $bot
     * @return array
     */
    private function validationRules(Bot $bot)
    {
        return [
            'mode'        => 'bail|required|in:is,contains,begins_with',
            'keywords'    => "bail|required|array",
            'keywords.*'  => "bail|required|string",
            'template'    => 'bail|required|array',
            'template.id' => [
                'bail',
                'required',
                'string',
                Rule::exists('templates', '_id')->where(function ($query) use ($bot) {
                    $query->where('bot_id', $bot->_id);
                }),
            ]
        ];
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new AutoReplyRuleTransformer();
    }
}
