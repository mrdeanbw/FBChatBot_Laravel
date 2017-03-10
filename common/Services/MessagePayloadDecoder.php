<?php namespace Common\Services;

use Common\Models\Bot;
use Common\Models\Card;
use Common\Models\Button;
use Common\Models\Message;
use Common\Models\Template;
use Common\Models\Subscriber;
use MongoDB\BSON\ObjectID;
use Common\Models\SentMessage;
use Common\Repositories\Bot\BotRepositoryInterface;
use Common\Repositories\Template\TemplateRepositoryInterface;
use Common\Repositories\Subscriber\SubscriberRepositoryInterface;
use Common\Repositories\SentMessage\SentMessageRepositoryInterface;

class MessagePayloadDecoder
{

    /**
     * @type string
     */
    protected $payload;

    /**
     * @type bool
     */
    protected $isValid;
    /**
     * @type Message
     */
    protected $message;
    /**
     * @type Bot
     */
    protected $bot;
    /**
     * @type Subscriber
     */
    protected $subscriber;
    /**
     * @type SentMessage
     */
    protected $sentMessage;
    /**
     * @type string
     */
    protected $templatePath;
    /**
     * @type string
     */
    protected $sentMessagePath;
    /**
     * @type ObjectID
     */
    protected $broadcastId;
    /**
     * @type bool
     */
    protected $isMainMenuButton = false;
    /**
     * @type ObjectID
     */
    protected $mainMenuButtonRevisionId;
    /**
     * @type Template
     */
    protected $template;
    /**
     * @type BotRepositoryInterface
     */
    private $botRepo;
    /**
     * @type SubscriberRepositoryInterface
     */
    private $subscriberRepo;
    /**
     * @type SentMessageRepositoryInterface
     */
    private $sentMessageRepo;
    /**
     * @type TemplateRepositoryInterface
     */
    private $templateRepo;

    /**
     * MessagePayloadDecoder constructor.
     * @param BotRepositoryInterface         $botRepo
     * @param TemplateRepositoryInterface    $templateRepo
     * @param SubscriberRepositoryInterface  $subscriberRepo
     * @param SentMessageRepositoryInterface $sentMessageRepo
     */
    public function __construct(
        BotRepositoryInterface $botRepo,
        TemplateRepositoryInterface $templateRepo,
        SubscriberRepositoryInterface $subscriberRepo,
        SentMessageRepositoryInterface $sentMessageRepo
    ) {
        $this->botRepo = $botRepo;
        $this->templateRepo = $templateRepo;
        $this->subscriberRepo = $subscriberRepo;
        $this->sentMessageRepo = $sentMessageRepo;
    }

    /**
     * @param                 $payload
     * @param Bot|null        $bot
     * @param Subscriber|null $subscriber
     * @return MessagePayloadDecoder
     */
    public static function factory($payload, Bot $bot = null, Subscriber $subscriber = null)
    {
        /** @type MessagePayloadDecoder $instance */
        $instance = app(self::class);
        $instance->payload = $payload;
        $instance->bot = $bot;
        $instance->subscriber = $subscriber;

        return $instance;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        if ($this->isValid === null) {
            $this->process();
        }

        return $this->isValid;
    }

    /**
     * @return Card|Button|null
     */
    public function getClickedMessage()
    {
        if ($this->isValid()) {
            return $this->message;
        }

        return null;
    }

    /**
     * @return SentMessage|null
     */
    public function getSentMessageInstance()
    {
        if ($this->isValid()) {
            return $this->sentMessage;
        }

        return null;
    }

    /**
     * @return Bot|null
     */
    public function getBot()
    {
        if ($this->isValid()) {
            return $this->bot;
        }

        return null;
    }

    /**
     * @return Subscriber|null
     */
    public function getSubscriber()
    {
        if ($this->isValid()) {
            return $this->subscriber;
        }

        return null;
    }

    /**
     * @return null|string
     */
    public function getSentMessagePath()
    {
        if ($this->isValid()) {
            return $this->sentMessagePath;
        }

        return null;
    }

    /**
     * @return null|string
     */
    public function getTemplatePath()
    {
        if ($this->isValid()) {
            return $this->templatePath;
        }

        return null;
    }

    /**
     * @return ObjectID|null
     */
    public function getBroadcastId()
    {
        if ($this->isValid()) {
            return $this->broadcastId;
        }

        return null;
    }


    protected function process()
    {
        list($fullMessagePath, $sentMessageId, $broadcastId) = $this->slicePayload();

        if (array_get($fullMessagePath, 0, null) == 'MM') {
            return $this->processMainMenuButton($fullMessagePath, $sentMessageId);
        }

        $cnt = count($fullMessagePath);

        if ($cnt < 7 || ! $sentMessageId) {
            return $this->invalid();
        }

        $this->bot = $this->bot?: $this->botRepo->findById($fullMessagePath[0]);
        if (! $this->bot) {
            return $this->invalid();
        }

        $this->subscriber = $this->subscriber?: $this->subscriberRepo->findById($fullMessagePath[1]);
        if (! $this->subscriber) {
            return $this->invalid();
        }

        if ($this->bot->id != $fullMessagePath[0] || $this->subscriber->id != $fullMessagePath[1]) {
            return $this->invalid();
        }

        $reversedMessagePath = array_reverse($fullMessagePath);
        $this->sentMessage = $this->sentMessageRepo->findById($sentMessageId);
        if (
            ! $this->sentMessage ||
            $this->sentMessage->bot_id != $this->bot->_id ||
            $this->sentMessage->subscriber_id != $this->subscriber->_id ||
            (string)$this->sentMessage->message_id != $reversedMessagePath[2]
        ) {
            return $this->invalid();
        }

        /** @type Template $template */
        $template = $this->templateRepo->findByIdForBot($fullMessagePath[2], $this->bot);
        if (! $template) {
            return $this->invalid();
        }

        $this->templatePath = array_slice($fullMessagePath, 3);
        $this->message = $this->navigateThroughMessagePath($template, $this->templatePath);
        if (! $this->message || (! is_a($this->message, Button::class) && ! is_a($this->message, Card::class))) {
            return $this->invalid();
        }

        $temp = [];
        for ($i = count($fullMessagePath) - 1; $i >= 0; $i--) {
            if ($fullMessagePath[$i] == 'messages') {
                break;
            }
            $temp[] = $fullMessagePath[$i];
        }
        array_pop($temp);

        $this->sentMessagePath = implode('.', array_reverse($temp));

        if (is_null(array_get($this->sentMessage->toArray(), $this->sentMessagePath))) {
            return $this->invalid();
        }

        $this->isValid = true;
        $this->template = $template;
        $this->broadcastId = new ObjectID($broadcastId);
    }

    /**
     * @param $payload
     * @param $revisionId
     */
    protected function processMainMenuButton($payload, $revisionId)
    {
        $botId = array_get($payload, 1);
        $buttonId = array_get($payload, 2);
        if ($botId != $this->bot->id) {
            return $this->invalid();
        }

        $this->message = array_first($this->bot->main_menu->buttons, function (Button $button) use ($buttonId) {
            return (string)$button->id == $buttonId;
        });
        if (! $this->message) {
            $this->invalid();
        }

        $this->isValid = true;
        $this->isMainMenuButton = true;
        $this->mainMenuButtonRevisionId = new ObjectID($revisionId);
    }

    /**
     * @param Template $template
     * @param array    $messagePath
     *
     * @return Message|null
     */
    private function navigateThroughMessagePath(Template $template, array $messagePath)
    {
        $ret = $template;

        foreach ($messagePath as $section) {

            if (in_array($section, ['messages', 'buttons', 'cards']) && is_object($ret) && isset($ret->{$section})) {
                $ret = $ret->{$section};
                continue;
            }

            if (is_array($ret)) {
                $ret = array_first($ret, function ($message) use ($section) {
                    return (isset($message->id) && (string)$message->id == $section);
                });

                if ($ret) {
                    continue;
                }
            }

            return null;
        }

        return is_object($ret)? $ret : null;
    }

    /**
     * @return array|null
     */
    private function slicePayload()
    {
        $payload = explode('|', $this->payload);

        return [explode(':', $payload[0]), array_get($payload, 1, null), array_get($payload, 2, null)];
    }

    /**
     * @return void
     */
    private function invalid()
    {
        $this->isValid = false;
    }

    /**
     * @return bool
     */
    public function isMainMenuButton()
    {
        return $this->isMainMenuButton;
    }

    /**
     * @return ObjectID
     */
    public function getMainMenuButtonRevisionId()
    {
        return $this->mainMenuButtonRevisionId;
    }

    /**
     * @return Template
     */
    public function getTemplate()
    {
        return $this->template;
    }
}