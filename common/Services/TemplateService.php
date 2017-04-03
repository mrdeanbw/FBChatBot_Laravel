<?php namespace Common\Services;

use Common\Models\Bot;
use MongoDB\BSON\ObjectID;
use Common\Models\Message;
use Common\Models\Template;
use Common\Models\ImageFile;
use Dingo\Api\Exception\ValidationHttpException;
use Common\Repositories\Template\TemplateRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TemplateService
{

    /**
     * @type MessageService
     */
    public $messages;
    /**
     * @type TemplateRepositoryInterface
     */
    private $templateRepo;

    CONST ALLOWED_MESSAGE_FIELDS = [
        'text'           => ['text', 'buttons'],
        'image'          => ['image_url', 'file'],
        'card_container' => ['cards'],
        'card'           => ['title', 'subtitle', 'url', 'image_url', 'file', 'buttons'],
        'button'         => ['title', 'url', 'messages', 'template', 'add_tags', 'remove_tags', 'add_sequences', 'remove_sequences', 'subscribe', 'unsubscribe'],
    ];

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
     * @param Bot   $bot
     * @param array $input
     * @return Template
     */
    public function createExplicit(array $input, Bot $bot)
    {
        $input['explicit'] = true;
        $input['bot_id'] = $bot->_id;

        return $this->create($input, false, true);
    }

    /**
     * @param array    $messages
     * @param ObjectID $botId
     * @param bool     $allowReadOnly
     *
     * @param bool     $allowButtonMessages
     * @return Template
     */
    public function createImplicit(array $messages, ObjectID $botId, $allowReadOnly = false, $allowButtonMessages = false)
    {
        $input = [
            'messages' => $messages,
            'bot_id'   => $botId
        ];

        return $this->create($input, $allowReadOnly, $allowButtonMessages);
    }

    /**
     * @param array $input
     * @param bool  $allowReadOnly
     * @param bool  $allowButtonMessages
     * @return Template
     */
    public function create(array $input, $allowReadOnly = false, $allowButtonMessages = false)
    {
        $messages = $this->normalizeMessages($input['messages'], [], $input['bot_id'], $allowReadOnly, $allowButtonMessages);
        if ($input['messages'] && ! $messages) {
            throw new ValidationHttpException(["messages" => ["Invalid Messages"]]);
        }
        $input['messages'] = $messages;

        /** @var Template $ret */
        $ret = $this->templateRepo->create($input);

        return $ret;
    }

    /**
     * @param array $data
     * @param bool  $allowReadOnly
     * @return bool
     */
    public function bulkCreate(array $data, $allowReadOnly = false)
    {
        foreach ($data as &$input) {
            $messages = $this->normalizeMessages($input['messages'], [], $input['bot_id'], $allowReadOnly);
            if ($input['messages'] && ! $messages) {
                throw new ValidationHttpException(["messages" => ["Invalid Messages"]]);
            }
            $input['messages'] = $messages;
        }

        return $this->templateRepo->bulkCreate($data);
    }

    /**
     * Update a message template.
     * @param ObjectID $id
     * @param Bot      $bot
     * @param array    $input
     * @return Template
     */
    public function updateExplicit($id, array $input, Bot $bot)
    {
        $template = $this->findExplicitOrFail($id, $bot);
        $template = $this->update($template, array_only($input, ['name', 'messages']), $bot, false, true);

        return $template;
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
     * @param bool     $allowReadOnly
     * @param bool     $allowButtonMessages
     * @return Template
     */
    private function update(Template $template, array $input, Bot $bot, $allowReadOnly = false, $allowButtonMessages = false)
    {
        $input['messages'] = $this->normalizeMessages($input['messages'], $template->messages, $bot->_id, $allowReadOnly, $allowButtonMessages);
        if (! $input['messages']) {
            throw new ValidationHttpException(["messages" => ["Invalid Messages"]]);
        }

        $this->templateRepo->update($template, $input);

        return $template;
    }

    /**
     * @param array     $messages
     * @param Message[] $original
     * @param ObjectID  $botId
     * @param bool      $allowReadOnly
     * @param bool      $allowButtonMessages
     * @return Message[]
     */
    private function normalizeMessages(array $messages, array $original, ObjectID $botId, $allowReadOnly = false, $allowButtonMessages = false)
    {
        $messages = $this->recursivelyConstructMessage($messages, $allowButtonMessages);

        $ret = $this->messages->correspondInputMessagesToOriginal($messages, $original, $botId, $allowReadOnly);

        $this->messages->persistMessageRevisions();

        return $ret;
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

    private function recursivelyConstructMessage(array $messages, $allowButtonMessages)
    {
        foreach ($messages as $i => $message) {

            $message = array_filter(array_only($message, array_merge(['id', 'readonly', 'type'], self::ALLOWED_MESSAGE_FIELDS[$message['type']])));

            if (in_array($message['type'], ['text', 'card']) && $buttons = array_get($message, 'buttons')) {
                $message['buttons'] = $this->recursivelyConstructMessage($buttons, $allowButtonMessages);
            }

            if ($message['type'] === 'card_container' && $cards = array_get($message, 'cards')) {
                $message['cards'] = $this->recursivelyConstructMessage($cards, $allowButtonMessages);
            }

            if (in_array($message['type'], ['image', 'card']) && $file = array_get($message, 'file')) {
                $message['file'] = new ImageFile($file);
            }

            if ($message['type'] == 'button') {
                if ($template = array_get($message, 'template')) {
                    $message['template_id'] = new ObjectID($template['id']);
                    unset($message['template']);
                } else {
                    unset($message['template']);
                    if ($allowButtonMessages && $buttonMessages = array_get($message, 'messages')) {
                        $message['messages'] = $this->recursivelyConstructMessage($buttonMessages, $allowButtonMessages);
                    } else {
                        unset($message['messages']);
                    }
                }
            }

            $messages[$i] = Message::factory($message);
        }

        return $messages;
    }

}