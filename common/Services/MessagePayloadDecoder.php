<?php namespace Common\Services;

use Common\Models\Bot;
use Common\Models\Card;
use Common\Models\Button;
use MongoDB\BSON\ObjectID;
use Common\Models\Message;
use Common\Models\Template;
use Common\Models\Subscriber;
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
     * @type Button|Card
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
     * @type array
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
     * @return Button|Card|null
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
     * @return null|array
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


    public function processMessagePostback()
    {
        $type = substr($this->payload, 2);
        $payloadChunks = explode('|', substr($this->payload, 3));
        $clean = [];
        foreach ($payloadChunks as $chunk) {
            $arr = explode(':', $chunk);
            $clean[$arr[0]] = $arr[1];
        }


        $id = $clean['i'];
        $path = $clean['p'];
        $revisionId = $clean['r'];
        $broadcastId = $clean['b']? new ObjectID($clean['b']) : null;
        $templateId = $clean['tp'];
        $sentMessageId = $clean['s'];



        $this->sentMessage = $this->sentMessageRepo->findById($sentMessageId);
        $this->template = $this->templateRepo->findByIdForBot($templateId, $this->bot->_id);


        $this->navigateThroughMessagePath($template, $this->templatePath);
        $this->isValidMessagePath($fullMessagePath);

        if ($type == 'tb') {
            $messageId = $textId = $clean['t'];
        } else {
            if ($type == 'cb') {
                $cardId = $clean['c'];
                $messageId = $cardContainerId = $clean['cc'];
            } else {
                throw new \Exception("Invalid message type");
            }
        }

        if ($this->sentMessage->message_id != $messageId) {
            throw new \Exception("Invalid sent message id");
        }
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
     * @param Template|SentMessage $template
     * @param array                $messagePath
     * @return Message|null
     */
    private function navigateThroughMessagePath($template, array $messagePath)
    {
        $ret = $template;

        foreach ($messagePath as $section) {

            if (
                in_array($section, ['messages', 'buttons', 'cards']) &&
                (
                    (is_object($ret) && isset($ret->{$section})) ||
                    (is_array($ret) && isset($ret[$section]))
                )
            ) {
                $ret = is_array($ret)? $ret[$section] : $ret->{$section};
                continue;
            }

            if (is_array($ret)) {
                $ret = array_first($ret, function ($message) use ($section) {
                    return
                        (is_object($message) && isset($message->id) && (string)$message->id == $section) ||
                        (is_array($message) && isset($message['id']) && (string)$message['id'] == $section);
                });

                if ($ret) {
                    continue;
                }
            }

            return null;
        }

        return $ret;
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

    /**
     * @param $fullMessagePath
     * @return bool
     */
    protected function isValidMessagePath($fullMessagePath)
    {
        $temp = [];
        for ($i = count($fullMessagePath) - 1; $i >= 0; $i--) {
            if ($fullMessagePath[$i] == 'messages') {
                break;
            }
            $temp[] = $fullMessagePath[$i];
        }
        array_pop($temp);

        $this->sentMessagePath = array_reverse($temp);

        if (! is_array($this->navigateThroughMessagePath($this->sentMessage, $this->sentMessagePath))) {
            $this->invalid();

            return false;
        }

        return true;
    }
}
