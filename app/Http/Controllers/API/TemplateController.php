<?php namespace App\Http\Controllers\API;

use Common\Models\Bot;
use Illuminate\Http\Request;
use Common\Services\TemplateService;
use Common\Transformers\BaseTransformer;
use Common\Transformers\TemplateTransformer;

class TemplateController extends APIController
{

    /**
     * @type TemplateService
     */
    private $templates;

    /**
     * TemplateController constructor.
     *
     * @param TemplateService $templates
     */
    public function __construct(TemplateService $templates)
    {
        $this->templates = $templates;
    }

    /**
     * Return a list of message trees.
     *
     * @param Request $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function index(Request $request)
    {
        $paginator = $this->templates->paginateExplicit(
            $this->bot(),
            $request->get('page'),
            ['name' => $request->get('name')]
        );


        return $this->paginatorResponse($paginator);
    }

    /**
     * Return the details of a message tree.
     *
     * @param         $id
     *
     * @return \Dingo\Api\Http\Response
     */
    public function show($id)
    {
        $template = $this->templates->findExplicitOrFail($id, $this->bot());

        return $this->itemResponse($template);
    }

    /**
     * Create a new message tree.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(Request $request)
    {
        $bot = $this->bot();
        $this->validate($request, $this->validationRules(null, $bot));
        $template = $this->templates->createExplicit($request->all(), $bot);

        return $this->itemResponse($template);
    }

    /**
     * Update a message tree.
     *
     * @param         $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update($id, Request $request)
    {
        $bot = $this->bot();
        $this->validate($request, $this->validationRules($id, $bot));
        $template = $this->templates->updateExplicit($id, $request->all(), $bot);

        return $this->itemResponse($template);
    }

    /**
     * Make the validator for message trees.
     *
     * @param     $id
     * @param Bot $bot
     *
     * @return array
     */
    protected function validationRules($id, Bot $bot)
    {
        $nameUniqueRule = "ci_unique:templates,name,_id,{$id},bot_id,oi:{$bot->id}";

        $rules = [
            'name'       => "bail|required|max:255|{$nameUniqueRule}",
            'messages'   => 'bail|required|array|max:10',
            'messages.*' => 'bail|required|message',
        ];

        return $rules;
    }


    /** @return BaseTransformer */
    protected function transformer()
    {
        return new TemplateTransformer();
    }
}
