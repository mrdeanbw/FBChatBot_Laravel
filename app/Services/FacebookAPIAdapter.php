<?php namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\Bot;
use App\Models\Page;
use App\Models\Button;
use App\Models\Message;
use App\Models\Template;
use App\Models\Broadcast;
use App\Models\Subscriber;
use MongoDB\BSON\ObjectID;
use App\Services\Facebook\Sender;
use App\Repositories\SentMessage\SentMessageRepositoryInterface;

class FacebookAPIAdapter
{

    use LoadsAssociatedModels;

    const NOTIFICATION_REGULAR = 0;
    const NOTIFICATION_SILENT_PUSH = 1;
    const NOTIFICATION_NO_PUSH = 2;

    /**
     * @type Sender
     */
    private $FacebookSender;
    /**
     * @type SentMessageRepositoryInterface
     */
    private $sentMessageRepo;

    /**
     * FacebookAPIAdapter constructor.
     *
     * @param Sender                         $FacebookSender
     * @param SentMessageRepositoryInterface $sentMessageRepo
     */
    public function __construct(Sender $FacebookSender, SentMessageRepositoryInterface $sentMessageRepo)
    {
        $this->FacebookSender = $FacebookSender;
        $this->sentMessageRepo = $sentMessageRepo;
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
     * @param array      $buttonPath
     * @param Subscriber $subscriber
     * @param Bot        $bot
     * @return \object[]
     */
    public function sendFromButton(Button $button, array $buttonPath, Subscriber $subscriber, Bot $bot)
    {
        if ($button->template_id) {
            return $this->sendFromContext($button, $subscriber, $bot);
        }

        $mapper = (new FacebookMessageMapper($bot))->forSubscriber($subscriber);
        $mapper->payloadEncoder->setButtonPath($buttonPath);

        return $this->sendMessages($mapper, $button->messages, $subscriber, $bot);
    }

    /**
     * @param            $context
     * @param Subscriber $subscriber
     * @param Bot        $bot
     * @return \object[]
     */
    public function sendFromContext($context, Subscriber $subscriber, Bot $bot)
    {
        /** @type Template $template */
        $this->loadModelsIfNotLoaded($context, ['template']);

        $mapper = (new FacebookMessageMapper($bot))->forSubscriber($subscriber);
        $mapper->payloadEncoder->setTemplate($context->template);

        return $this->sendMessages($mapper, $context->template->messages, $subscriber, $bot);
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

            $facebookMessageId = $this->send($mappedMessage, $subscriber, $bot->page, $notificationType);

            $ret[] = $this->sentMessageRepo->create(
                array_merge($data, ['facebook_id' => $facebookMessageId])
            );
        }

        return $ret;
    }

    /**
     * Add recipient header, notification type and send the message through Facebook API.
     * @param array      $message
     * @param Subscriber $subscriber
     * @param Page       $page
     * @param int        $notificationType
     * @return \object[]
     */
    public function send(array $message, Subscriber $subscriber, Page $page, $notificationType = self::NOTIFICATION_REGULAR)
    {
        $message = $this->addRecipientHeader($message, $subscriber);
        $message = $this->addNotificationType($message, $notificationType);

        $response = $this->FacebookSender->send($page->access_token, $message, false);

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
        $message['recipient'] = [
            'id' => $subscriber->facebook_id
        ];

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
        switch ($notificationType) {
            case self::NOTIFICATION_REGULAR:
                $message['notification'] = 'REGULAR';
                break;
            case self::NOTIFICATION_SILENT_PUSH:
                $message['notification'] = 'SILENT_PUSH';
                break;
            case self::NOTIFICATION_NO_PUSH:
                $message['notification'] = 'NO_PUSH';
                break;
        }
        $message['notification'] = $notificationType;

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
            'sent_at'       => Carbon::now(),
            'delivered_at'  => null,
            'read_at'       => null,
        ];

        if ($message->type == 'text') {
            $data['buttons'] = [];
            foreach ($message->buttons as $button) {
                $data['buttons'][$button->id->__toString()] = [];
            }
        }

        if ($message->type == 'card_container') {
            $data['cards'] = [];
            foreach ($message->cards as $card) {
                $cardId = $card->id->__toString();
                $data['cards'][$cardId] = ['clicks' => [], 'buttons' => []];
                foreach ($card->buttons as $button) {
                    $data['cards'][$cardId]['buttons'][$button->id->__toString()] = [];
                }
            }
        }

        return $data;
    }
}