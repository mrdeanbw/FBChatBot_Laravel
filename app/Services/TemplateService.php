<?php namespace App\Services;

use App\Models\Bot;
use App\Models\Message;
use App\Models\Template;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Repositories\Template\TemplateRepositoryInterface;

class TemplateService
{

    /**
     * @type MessageService
     */
    private $messages;
    /**
     * @type TemplateRepositoryInterface
     */
    private $templateRepo;

    /**
     * TemplateService constructor.
     * @param TemplateRepositoryInterface $templateRepo
     * @param MessageService              $messages
     */
    public function __construct(TemplateRepositoryInterface $templateRepo, MessageService $messages)
    {
        $this->messages = $messages;
        $this->templateRepo = $templateRepo;
    }


    /**
     * @param Bot $page
     * @return \Illuminate\Support\Collection
     */
    public function explicitTemplates(Bot $page)
    {
        return $this->templateRepo->explicitTemplatesForBot($page);
    }

    /**
     * @param      $id
     * @param Bot  $page
     * @return Template
     */
    public function findExplicitOrFail($id, Bot $page)
    {
        if ($template = $this->templateRepo->findExplicitByIdForBot($id, $page)) {
            return $template;
        }
        throw new ModelNotFoundException;
    }

    /**
     * Create a new explicit template.
     * @param Bot   $bot
     * @param array $input
     * @return Template
     */
    public function createExplicit(array $input, Bot $bot)
    {
        $input['explicit'] = true;

        return $this->create($input, $bot->id);
    }

    /**
     * @param array $messages
     * @param       $botId
     * @return Template
     */
    public function createImplicit(array $messages, $botId)
    {
        $input['name'] = null;
        $input['explicit'] = false;
        $input['messages'] = $messages;

        return $this->create($input, $botId);
    }

    /**
     * @param array $input
     * @param       $botId
     * @return \App\Models\BaseModel
     */
    private function create(array $input, $botId)
    {
        return $this->templateRepo->create([
            'bot_id'   => $botId,
            'name'     => $input['name'],
            'explicit' => $input['explicit'],
            'messages' => $this->normalizeMessages($input['messages'], [], $botId)
        ]);
    }

    /**
     * Update a message template.
     * @param       $id
     * @param Bot   $bot
     * @param array $input
     * @return Template
     */
    public function updateExplicit($id, array $input, Bot $bot)
    {
        $template = $this->findExplicitOrFail($id, $bot);

        return $this->update($template, array_only($input, ['name', 'messages']), $bot);
    }

    /**
     * @param string $templateId
     * @param array  $data
     * @param Bot    $bot
     * @return Template
     */
    public function updateImplicit($templateId, array $data, Bot $bot)
    {
        /** @type Template $template */
        $template = $this->templateRepo->findByIdOrFail($templateId);

        return $this->update($template, array_only($data, 'messages'), $bot);
    }

    /**
     * @param Template $template
     * @param array    $input
     * @param Bot      $bot
     * @return Template
     */
    private function update(Template $template, array $input, Bot $bot)
    {
        $input['messages'] = $this->normalizeMessages($input['messages'], $template->messages, $bot->id);

        return $this->templateRepo->update($template, $input);
    }

    /**
     * @param array     $messages
     * @param Message[] $original
     * @param           $botId
     * @return \App\Models\Message[]
     */
    private function normalizeMessages(array $messages, array $original = [], $botId)
    {
        $messages = $this->messages->normalizeMessages($messages);
        $messages = $this->messages->makeMessages($messages, $original, $botId);

        return $messages;
    }

}