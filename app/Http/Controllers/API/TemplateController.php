<?php namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Services\TemplateService;
use App\Transformers\BaseTransformer;
use App\Transformers\TemplateTransformer;

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
     * @return \Dingo\Api\Http\Response
     */
    public function index()
    {
        $templates = $this->templates->explicitTemplates($this->bot());

        return $this->collectionResponse($templates);
    }

    /**
     * Return the details of a message tree.
     * @param         $id
     * @return \Dingo\Api\Http\Response
     */
    public function show($id)
    {
        $template = $this->templates->findExplicitOrFail($id, $this->bot());

        return $this->itemResponse($template);
    }

    /**
     * Create a new message tree.
     * @param Request $request
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
     * @param         $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update($id, Request $request)
    {
        $bot = $this->bot();
        $this->validate($request, $this->validationRules(null, $bot));
        $template = $this->templates->updateExplicit($id, $request->all(), $bot);

        return $this->itemResponse($template);
    }
    
    /**
     * Make the validator for message trees.
     * @param $id
     * @param $bot
     * @return array
     */
    protected function validationRules($id, $bot)
    {
        $idString = $id? "{$id}" : "NULL";
        $rules = [
            'name' => "bail|required|max:255|unique:templates,name,{$idString},id,bot_id,{$bot->id}"
        ];

        return $rules;
    }


    /** @return BaseTransformer */
    protected function transformer()
    {
        return new TemplateTransformer();
    }
}
