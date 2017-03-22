<?php namespace Common\Services;

use Common\Exceptions\MessageNotSentException;
use Exception;
use Carbon\Carbon;
use Common\Models\Bot;
use Common\Models\Text;
use Common\Models\Button;
use MongoDB\BSON\ObjectID;
use Common\Models\Message;
use Common\Models\Template;
use Common\Models\Broadcast;
use Common\Models\Subscriber;
use Common\Models\CardContainer;
use Common\Repositories\SentMessage\SentMessageRepositoryInterface;

class FacebookMessageSender
{

    use LoadsAssociatedModels;

    const NOTIFICATION_REGULAR = 0;
    const NOTIFICATION_SILENT_PUSH = 1;
    const NOTIFICATION_NO_PUSH = 2;
    const _NOTIFICATION_MAP = [
        self::NOTIFICATION_REGULAR     => 'REGULAR',
        self::NOTIFICATION_SILENT_PUSH => 'SILENT_PUSH',
        self::NOTIFICATION_NO_PUSH     => 'NO_PUSH',
    ];

    /**
     * @type SentMessageRepositoryInterface
     */
    private $sentMessageRepo;
    /**
     * @type FacebookAdapter
     */
    private $FacebookAdapter;

    /**
     * FacebookAPIAdapter constructor.
     *
     * @param FacebookAdapter                $FacebookAdapter
     * @param SentMessageRepositoryInterface $sentMessageRepo
     */
    public function __construct(FacebookAdapter $FacebookAdapter, SentMessageRepositoryInterface $sentMessageRepo)
    {
        $this->sentMessageRepo = $sentMessageRepo;
        $this->FacebookAdapter = $FacebookAdapter;
    }

    /**
     * Send broadcast to a subscriber, using Facebook API.
     * @param Broadcast  $broadcast
     * @param Subscriber $subscriber
     * @return \object[]
     */
    public function sendBroadcastMessages(Broadcast $broadcast, Subscriber $subscriber)
    {
        /** @type Bot $bot */
        $this->loadModelsIfNotLoaded($broadcast, ['bot', 'template']);

        $mapper = (new FacebookMessageMapper($broadcast->bot))->forSubscriber($subscriber);
        $mapper->payloadEncoder->setBroadcast($broadcast);

        return $this->sendMessages($mapper, $broadcast->template->messages, $subscriber, $broadcast->bot, $broadcast->notification);
    }

    /**
     * @param Template   $template
     * @param Subscriber $subscriber
     * @return \object[]
     */
    public function sendTemplate(Template $template, Subscriber $subscriber)
    {
        /** @type Bot $bot */
        $this->loadModelsIfNotLoaded($template, ['bot']);

        $mapper = (new FacebookMessageMapper($template->bot))->forSubscriber($subscriber);
        $mapper->payloadEncoder->setTemplate($template);

        return $this->sendMessages($mapper, $template->messages, $subscriber, $template->bot);
    }

    /**
     * @param Button     $button
     * @param Subscriber $subscriber
     * @param Bot        $bot
     * @param Template   $template
     * @param array      $buttonPath
     * @return \object[]
     */
    public function sendFromButton(Button $button, Subscriber $subscriber, Bot $bot, Template $template = null, array $buttonPath = [])
    {
        if ($button->template_id) {
            return $this->sendFromContext($button, $subscriber, $bot);
        }

        $mapper = (new FacebookMessageMapper($bot))->forSubscriber($subscriber);
        $mapper->payloadEncoder->setDeepButton($template, $buttonPath);

        return $this->sendMessages($mapper, $button->messages, $subscriber, $bot);
    }

    /**
     * @param array|object $context
     * @param Subscriber   $subscriber
     * @param Bot          $bot
     * @return \object[]
     */
    public function sendFromContext($context, Subscriber $subscriber, Bot $bot)
    {
        /** @type Template $template */
        $this->loadModelsIfNotLoaded($context, ['template']);

        $mapper = (new FacebookMessageMapper($bot))->forSubscriber($subscriber);
        $template = is_array($context)? $context['template'] : $context->template;
        $mapper->payloadEncoder->setTemplate($template);

        return $this->sendMessages($mapper, $template->messages, $subscriber, $bot);
    }

    /**
     * Send message blocks to a subscriber, using Facebook API.
     * @param FacebookMessageMapper $mapper
     * @param Message[]             $messages
     * @param Subscriber            $subscriber
     * @param Bot                   $bot
     * @param int                   $notificationType
     * @return \object[]
     * @throws Exception
     */
    public function sendMessages(FacebookMessageMapper $mapper, array $messages, Subscriber $subscriber, Bot $bot, $notificationType = self::NOTIFICATION_REGULAR)
    {
        $ret = [];

        foreach ($messages as $message) {
            $data = $this->buildSentMessageInstance($subscriber, $bot, $message);

            $mapper->payloadEncoder->setSentMessageInstanceId($data['_id']);

            $mappedMessage = $mapper->toFacebookMessage($message);

            try {
                $facebookMessageId = $this->send($mappedMessage, $subscriber, $bot, $notificationType);
                $ret[] = $this->sentMessageRepo->create(array_merge($data, [
                    'facebook_id' => $facebookMessageId,
                    'sent_at'     => Carbon::now()
                ]));
            } catch (MessageNotSentException $e) {
                // do nothing
            }
        }

        return $ret;
    }

    /**
     * @param int        $messageIndex
     * @param Bot        $bot
     * @param Subscriber $subscriber
     */
    public function sendBotMessage($messageIndex, Bot $bot, Subscriber $subscriber)
    {
        $context = $bot->messages[$messageIndex];
        $this->sendFromContext($context, $subscriber, $bot);
    }

    /**
     * Add recipient header, notification type and send the message through Facebook API.
     * @param array      $message
     * @param Subscriber $subscriber
     * @param Bot        $bot
     * @param int        $notificationType
     * @return \object[]
     * @throws Exception
     */
    protected function send(array $message, Subscriber $subscriber, Bot $bot, $notificationType = self::NOTIFICATION_REGULAR)
    {
        $message = $this->addRecipientHeader($message, $subscriber);
        $message = $this->addNotificationType($message, $notificationType);

        $response = $this->FacebookAdapter->sendMessage($bot, $message);

        return $response->message_id;
    }

    /**
     * Add recipient information to the message.
     * @param array      $message
     * @param Subscriber $subscriber
     * @return array
     */
    protected function addRecipientHeader(array $message, Subscriber $subscriber)
    {
        $message['recipient'] = ['id' => $subscriber->facebook_id];

        return $message;
    }

    /**
     * Add the notification type to the message.
     * @param $message
     * @param $notificationType
     * @return array
     */
    protected function addNotificationType($message, $notificationType)
    {
        $message['notification_type'] = self::_NOTIFICATION_MAP[$notificationType];

        return $message;
    }

    /**
     * @param Subscriber $subscriber
     * @param Bot        $bot
     * @param            $message
     * @return array
     */
    private function buildSentMessageInstance(Subscriber $subscriber, Bot $bot, Message $message)
    {
        $data = [
            '_id'           => new ObjectID(null),
            'bot_id'        => $bot->_id,
            'subscriber_id' => $subscriber->_id,
            'message_id'    => $message->id,
            'delivered_at'  => null,
            'read_at'       => null,
        ];


        if ($message->type == 'text') {
            /** @type Text $message */
            $data['buttons'] = [];
            foreach ($message->buttons as $button) {
                $data['buttons'][] = [
                    'id'     => $button->id,
                    'clicks' => []
                ];
            }
        }

        if ($message->type == 'card_container') {
            /** @type CardContainer $message */
            $data['cards'] = [];
            foreach ($message->cards as $card) {
                $cardStats = [
                    'id'      => $card->id,
                    'clicks'  => [],
                    'buttons' => []
                ];
                foreach ($card->buttons as $button) {
                    $cardStats['buttons'][] = [
                        'id'     => $button->id,
                        'clicks' => []
                    ];
                }
                $data['cards'][] = $cardStats;
            }
        }

        return $data;
    }
}