<?php namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Common\Services\SequenceService;
use Common\Transformers\BaseTransformer;
use Common\Transformers\SequenceTransformer;
use Common\Services\Validation\FilterAudienceRuleValidator;

class SequenceController extends APIController
{

    use FilterAudienceRuleValidator;

    /**
     * @type SequenceService
     */
    private $sequences;

    /**
     * SequenceController constructor.
     *
     * @param SequenceService $sequences
     */
    public function __construct(SequenceService $sequences)
    {
        $this->sequences = $sequences;
    }

    /**
     * List of sequences.
     *
     * @param Request $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function index(Request $request)
    {
        $paginator = $this->sequences->paginate(
            $this->enabledBot(),
            (int)$request->get('page', 1),
            ['name' => $request->get('name')],
            [],
            8
        );

        return $this->paginatorResponse($paginator);
    }

    /**
     * Return the details of a sequence.
     *
     * @param $id
     *
     * @return \Dingo\Api\Http\Response
     */
    public function show($id)
    {
        $page = $this->enabledBot();
        $sequence = $this->sequences->findByIdForBotOrFail($id, $page);

        return $this->itemResponse($sequence);
    }

    /**
     * Create a sequence.
     *
     * @param Request $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function store(Request $request)
    {
        $bot = $this->enabledBot();

        $nameUniqueRule = "ci_unique:sequences,name,_id,,bot_id,oi:{$bot->id}";

        $this->validate($request, [
            'name' => "bail|required|max:255|{$nameUniqueRule}"
        ]);

        $sequence = $this->sequences->create($request->all(), $bot);

        return $this->itemResponse($sequence);
    }

    /**
     * Update a sequence.
     *
     * @param         $id
     * @param Request $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function update($id, Request $request)
    {
        $bot = $this->enabledBot();

        $nameUniqueRule = "ci_unique:sequences,name,_id,{$id},bot_id,oi:{$bot->id}";

        $rules = [
            'name'                          => "bail|required|max:255|{$nameUniqueRule}",
            'filter'                        => 'bail|required|array',
            'filter.enabled'                => 'bail|required',
            'filter.join_type'              => 'bail|required_if:filter.enabled,true|in:and,or',
            'filter.groups'                 => 'bail|array',
            'filter.groups.*'               => 'bail|required|array',
            'filter.groups.*.join_type'     => 'bail|required|in:and,or,none',
            'filter.groups.*.rules'         => 'bail|required|array',
            'filter.groups.*.rules.*.key'   => 'bail|required|in:gender,tag',
            'filter.groups.*.rules.*.value' => 'bail|required',
        ];

        $this->validate($request, $rules, $this->filterGroupRuleValidationCallback($bot));

        $sequence = $this->sequences->update($id, $request->all(), $bot);

        return $this->itemResponse($sequence);
    }

    /**
     * Delete a sequence.
     *
     * @param $id
     *
     * @return \Dingo\Api\Http\Response
     */
    public function destroy($id)
    {
        $this->sequences->delete($id, $this->enabledBot());

        return $this->response->accepted();
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new SequenceTransformer();
    }
}
