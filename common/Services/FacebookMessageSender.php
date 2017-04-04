<?php namespace Common\Services;

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
use Common\Exceptions\MessageNotSentException;
use Common\Repositories\Inbox\InboxRepositoryInterface;
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
     * @type InboxRepositoryInterface
     */
    protected $inboxRepo;

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
     * @param InboxRepositoryInterface       $inboxRepo
     */
    public function __construct(FacebookAdapter $FacebookAdapter, SentMessageRepositoryInterface $sentMessageRepo, InboxRepositoryInterface $inboxRepo)
    {
        $this->inboxRepo = $inboxRepo;
        $this->sentMessageRepo = $sentMessageRepo;
        $this->FacebookAdapter = $FacebookAdapter;
    }

    /**
     * @param array      $messages
     * @param Subscriber $subscriber
     * @param Bot        $bot
     * @return \object[]
     */
    public function sendMessageArray(array $messages, Subscriber $subscriber, Bot $bot)
    {
        $mapper = (new FacebookMessageMapper($bot))->forSubscriber($subscriber);

        return $this->sendMessages($mapper, $messages, $subscriber, $bot);
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

        return $this->sendMessageArray($template->clean_messages, $subscriber, $template->bot);
    }

    /**
     * @param array|object $context
     * @param Subscriber   $subscriber
     * @param Bot          $bot
     * @return \object[]
     */
    public function sendFromTemplateWrapper($context, Subscriber $subscriber, Bot $bot)
    {
        /** @type Template $template */
        $this->loadModelsIfNotLoaded($context, ['template']);
        $template = is_array($context)? $context['template'] : $context->template;

        return $this->sendTemplate($template, $subscriber);
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

        return $this->sendMessages($mapper, $broadcast->template->clean_messages, $subscriber, $broadcast->bot, $broadcast->notification);
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
    protected function sendMessages(FacebookMessageMapper $mapper, array $messages, Subscriber $subscriber, Bot $bot, $notificationType = self::NOTIFICATION_REGULAR)
    {
        $ret = [];

        foreach ($messages as $message) {
            $data = $this->buildSentMessageInstance($subscriber, $bot, $message);

            $mapper->payloadEncoder->setSentMessageInstanceId($data['_id']);

            $mappedMessage = $mapper->toFacebookMessage($message);
            $mappedMessage['recipient'] = ['id' => $subscriber->facebook_id];
            $mappedMessage['notification_type'] = self::_NOTIFICATION_MAP[$notificationType];

            try {
                $response = $this->FacebookAdapter->sendMessage($bot, $mappedMessage);
                $facebookMessageId = $response->message_id;
                $sentAt = Carbon::now();
                $ret[] = $this->sentMessageRepo->create(array_merge($data, [
                    'facebook_id' => $facebookMessageId,
                    'sent_at'     => $sentAt
                ]));
//                $this->inboxRepo->create([
//                    'bot_id'        => $bot->_id,
//                    'subscriber_id' => $subscriber->_id,
//                    'action_at'     => $sentAt,
//                    'incoming'      => 0,
//                    'facebook_id'   => $facebookMessageId,
//                    'message'       => $mappedMessage['message'],
//                    'notification'  => $mappedMessage['notification_type']
//                ]);
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
        $context = $bot->templates[$messageIndex];
        $this->sendFromTemplateWrapper($context, $subscriber, $bot);
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
            'revision_id'   => $message->last_revision_id,
            'delivered_at'  => null,
            'read_at'       => null,
        ];


        /** @type Text $message */
        if ($message->type == 'text' && $message->buttons) {
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
                if ($card->buttons) {
                    foreach ($card->buttons as $button) {
                        $cardStats['buttons'][] = [
                            'id'     => $button->id,
                            'clicks' => []
                        ];
                    }
                }
                $data['cards'][] = $cardStats;
            }
        }

        return $data;
    }
}