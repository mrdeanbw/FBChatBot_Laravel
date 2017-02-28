<?php namespace App\Services;

use App\Models\Bot;
use App\Models\Message;
use App\Models\Template;
use MongoDB\BSON\ObjectID;
use Dingo\Api\Exception\ValidationHttpException;
use App\Repositories\Template\TemplateRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
     *
     * @param TemplateRepositoryInterface $templateRepo
     * @param MessageService              $messages
     */
    public function __construct(TemplateRepositoryInterface $templateRepo, MessageService $messages)
    {
        $this->messages = $messages;
        $this->templateRepo = $templateRepo;
    }


    /**
     * @param Bot     $bot
     * @param Bot|int $page
     * @param array   $filter
     * @param array   $orderBy
     * @param int     $perPage
     *
     * @return \Illuminate\Pagination\Paginator
     */
    public function paginateExplicit(Bot $bot, $page = 1, $filter = [], $orderBy = [], $perPage = 20)
    {
        $filterBy = [['operator' => '=', 'key' => 'explicit', 'value' => true]];

        if ($name = array_get($filter, 'name')) {
            $filterBy[] = ['operator' => 'prefix', 'key' => 'name', 'value' => $name];
        }

        $orderBy = $orderBy?: ['_id' => 'desc'];

        return $this->templateRepo->paginateForBot($bot, $page, $filterBy, $orderBy, $perPage);
    }

    /**
     * @param      $id
     * @param Bot  $page
     *
     * @return Template
     */
    public function findExplicitOrFail($id, Bot $page)
    {
        if ($template = $this->templateRepo->findExplicitByIdForBot($id, $page)) {
            return $template;
        }
        throw new NotFoundHttpException;
    }

    /**
     * Create a new explicit template.
     *
     * @param Bot   $bot
     * @param array $input
     *
     * @return Template
     */
    public function createExplicit(array $input, Bot $bot)
    {
        $input['explicit'] = true;

        return $this->create($input, $bot->_id);
    }

    /**
     * @param array $messages
     * @param       $botId
     * @param bool  $allowReadOnly
     *
     * @return Template
     */
    public function createImplicit(array $messages, ObjectID $botId, $allowReadOnly = false)
    {
        $input['name'] = null;
        $input['explicit'] = false;
        $input['messages'] = $messages;

        return $this->create($input, $botId, $allowReadOnly);
    }

    /**
     * @param array $input
     * @param       $botId
     *
     * @param bool  $allowReadOnly
     *
     * @return \App\Models\BaseModel|Template
     */
    private function create(array $input, ObjectID $botId, $allowReadOnly = false)
    {
        $messages = $this->normalizeMessages($input['messages'], [], $botId, $allowReadOnly);
        if ($input['messages'] && ! $messages) {
            throw new ValidationHttpException(["messages" => ["Invalid Messages"]]);
        }

        return $this->templateRepo->create([
            'bot_id'   => $botId,
            'name'     => $input['name'],
            'explicit' => $input['explicit'],
            'messages' => $messages
        ]);
    }

    /**
     * Update a message template.
     *
     * @param       $id
     * @param Bot   $bot
     * @param array $input
     *
     * @return Template
     */
    public function updateExplicit($id, array $input, Bot $bot)
    {
        $template = $this->findExplicitOrFail($id, $bot);
        $template = $this->update($template, array_only($input, ['name', 'messages']), $bot);

        return $template;
    }

    /**
     * @param string $templateId
     * @param array  $data
     * @param Bot    $bot
     *
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
     *
     * @return Template
     */
    private function update(Template $template, array $input, Bot $bot)
    {
        $input['messages'] = $this->normalizeMessages($input['messages'], $template->messages, $bot->_id);
        if (! $input['messages']) {
            throw new ValidationHttpException(["messages" => ["Invalid Messages"]]);
        }

        $this->templateRepo->update($template, $input);

        return $template;
    }

    /**
     * @param array     $messages
     * @param Message[] $original
     * @param           $botId
     *
     * @param bool      $allowReadOnly
     *
     * @return \App\Models\Message[]
     */
    private function normalizeMessages(array $messages, array $original = [], $botId, $allowReadOnly = false)
    {
        $messages = $this->messages->normalizeMessages($messages);

        return $this->messages->correspondInputMessagesToOriginal($messages, $original, $botId, $allowReadOnly);
    }

    /**
     * @param mixed $versioning
     * @return TemplateService
     */
    public function setVersioning($versioning)
    {
        $this->messages->setVersioning($versioning);

        return $this;
    }

}