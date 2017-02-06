<?php namespace App\Services;

use App\Models\Bot;
use App\Models\Message;
use App\Models\Template;
use App\Repositories\Template\TemplateRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
        $input['bot_id'] = $bot->id;

        return $this->create($input);
    }

    /**
     * @param array $messages
     * @param       $botId
     * @return Template
     */
    public function createImplicit(array $messages, $botId)
    {
        $input['bot_id'] = $botId;
        $input['explicit'] = false;
        $input['messages'] = $messages;

        return $this->create($input);
    }

    /**
     * @param array $input
     * @return \App\Models\BaseModel
     */
    private function create(array $input)
    {
        return $this->templateRepo->create([
            'bot_id'   => $input['bot_id'],
            'name'     => $input['name'],
            'explicit' => $input['explicit'],
            'messages' => $this->normalizeMessages($input['messages'])
        ]);
    }

    /**
     * Update a message template.
     * @param       $id
     * @param Bot   $page
     * @param array $input
     * @return Template
     */
    public function updateExplicit($id, array $input, Bot $page)
    {
        $template = $this->findExplicitOrFail($id, $page);

        return $this->templateRepo->update(array_only($input, ['input', 'messages']), $template);
    }

    /**
     * @param string $templateId
     * @param array  $data
     * @return Template
     */
    public function updateImplicit($templateId, array $data)
    {
        /** @type Template $template */
        $template = $this->templateRepo->findByIdOrFail($templateId);

        return $this->update(array_only($data, 'messages'), $template);
    }

    /**
     * @param array    $input
     * @param Template $template
     * @return Template
     */
    private function update(array $input, Template $template)
    {
        $template->messages = $input['messages'] = $this->normalizeMessages($input['messages'], $template->messages);

        $this->templateRepo->update($template, $input);

        return $template;
    }

    /**
     * @param array     $messages
     * @param Message[] $original
     * @return Message[]
     */
    private function normalizeMessages(array $messages, array $original = [])
    {
        $messages = $this->messages->normalizeMessages($messages);
        $messages = $this->messages->makeMessages($messages, $original);

        return $messages;
    }

}