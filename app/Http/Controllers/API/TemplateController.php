<?php namespace App\Http\Controllers\API;

use App\Models\Bot;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
     *
     * @return \Dingo\Api\Http\Response
     */
    public function index()
    {
        $templates = $this->templates->explicitTemplates($this->bot());

        return $this->collectionResponse($templates);
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
        $rules = [
            'name'       => [
                'bail',
                'required',
                'max:255',
                Rule::unique('templates')->where(function ($query) use ($id, $bot) {
                    if ($id) {
                        $query->where('_id', '!=', $id);
                    }
                    $query->where('bot_id', $bot->_id);
                })
            ],
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
