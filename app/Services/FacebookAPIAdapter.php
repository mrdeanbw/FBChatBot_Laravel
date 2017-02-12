<?php namespace App\Services;

use App\Models\Button;
use Exception;
use Carbon\Carbon;
use App\Models\Bot;
use App\Models\Page;
use App\Models\Message;
use App\Models\Template;
use App\Models\Broadcast;
use App\Models\Subscriber;
use MongoDB\BSON\ObjectID;
use App\Services\Facebook\Sender;
use App\Repositories\MessageHistory\MessageHistoryRepositoryInterface;

class FacebookAPIAdapter
{

    use LoadsAssociatedModels;


    CONST NO_HASH_PLACEHOLDER = "MAIN_MENU";

    /**
     * @type Sender
     */
    private $FacebookSender;
    /**
     * @type MessageHistoryRepositoryInterface
     */
    private $messageHistoryRepo;

    /**
     * FacebookAPIAdapter constructor.
     *
     * @param Sender                            $FacebookSender
     * @param MessageHistoryRepositoryInterface $messageHistoryRepo
     */
    public function __construct(Sender $FacebookSender, MessageHistoryRepositoryInterface $messageHistoryRepo)
    {
        $this->FacebookSender = $FacebookSender;
        $this->messageHistoryRepo = $messageHistoryRepo;
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

        $mapper = (new FacebookMessageMapper($bot))->forSubscriber($subscriber)->forBroadcast($broadcast);

        return $this->sendMessages($mapper, $broadcast->template->messages, $subscriber, $bot, $broadcast->notification);
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

        $mapper = (new FacebookMessageMapper($template->bot))->forSubscriber($subscriber)->forTemplate($template);

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

        $mapper = (new FacebookMessageMapper($bot))->forSubscriber($subscriber)->setButtonPath($buttonPath);

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

        $mapper = (new FacebookMessageMapper($bot))->forSubscriber($subscriber)->forTemplate($context->tmeplate);

        return $this->sendMessages($mapper, $context->tmeplate->messages, $subscriber, $bot);
    }

    /**
     * Send message blocks to a subscriber, using Facebook API.
     * @param FacebookMessageMapper $mapper
     * @param Message[]             $messages
     * @param Subscriber            $subscriber
     * @param Bot                   $bot
     * @param string                $notificationType
     * @return \object[]
     * @throws Exception
     */
    public function sendMessages(FacebookMessageMapper $mapper, array $messages, Subscriber $subscriber, Bot $bot, $notificationType = 'REGULAR')
    {
        $ret = [];

        foreach ($messages as $message) {

            $data = $this->buildMessageHistoryInstance($subscriber, $bot, $message);

            $mappedMessage = $mapper->toFacebookMessage($message);

            $facebookMessageId = $this->send($mappedMessage, $subscriber, $bot->page, $notificationType);

            $ret[] = $this->messageHistoryRepo->create(
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
     * @param string     $notificationType
     * @return \object[]
     */
    public function send(array $message, Subscriber $subscriber, Page $page, $notificationType = 'REGULAR')
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
        $message['notification'] = $notificationType;

        return $message;
    }

    /**
     * @param Subscriber $subscriber
     * @param Bot        $bot
     * @param            $message
     * @return array
     */
    private function buildMessageHistoryInstance(Subscriber $subscriber, Bot $bot, Message $message)
    {
        $data = [
            '_id'           => new ObjectID(),
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