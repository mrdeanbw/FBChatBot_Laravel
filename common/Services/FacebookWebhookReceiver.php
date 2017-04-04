<?php namespace Common\Services;

use Common\Models\Bot;
use Common\Models\Subscriber;
use Common\Models\AutoReplyRule;
use Common\Services\Facebook\MessengerThread;

class FacebookWebhookReceiver
{

    /**
     * @type array
     */
    private $data;

    /**
     * @type WebAppAdapter
     */
    private $adapter;

    /**
     * AppVerifier constructor.
     * @param WebAppAdapter $adapter
     */
    public function __construct(WebAppAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Handles incoming facebook callback
     */
    public function handle()
    {
        if ($this->data['object'] != 'page') {
            return;
        }

        foreach ($this->data['entry'] as $entry) {
            $this->handleEntry($entry);
        }
    }

    /**
     * Handles a single entry.
     * @param $entry
     */
    private function handleEntry($entry)
    {
        foreach ($entry['messaging'] as $event) {
            $this->handleEvent($event);
        }
    }

    /**
     * Handles a single event.
     * @param $event
     */
    private function handleEvent($event)
    {
        $bot = $this->adapter->bot($event['recipient']['id']);

        // If the page is not in our system, not active, or doesn't have a valid access token then do nothing.
        if (! $bot || ! $bot->enabled || is_null($bot->access_token)) {
            return;
        }

        if (config('sentry.dsn')) {
            app('sentry')->user_context(['bot_id' => $bot->_id]);
        }


        // If echo, then do nothing.
        if (array_get($event, 'message.is_echo')) {
            return;
        }

        // Get the subscriber who sent the message.
        $subscriber = $this->adapter->subscriber($event['sender']['id'], $bot);

        // If it is a delivery notification, then mark messages as delivered.
        if (array_get($event, 'delivery')) {
            if ($subscriber) {
                $this->adapter->markMessagesAsDelivered($subscriber, $event['delivery']['watermark']);
            }

            return;
        }

        // If it is a read notification, then mark messages as read.
        if (array_get($event, 'read')) {
            if ($subscriber) {
                $this->adapter->markMessagesAsRead($subscriber, $event['read']['watermark']);
            }

            return;
        }

        // If a text message is received
        if ($text = array_get($event, 'message.text')) {

            // Find a matching auto reply rule.
            /** @var AutoReplyRule $rule */
            $rule = $this->adapter->matchingAutoReplyRule($text, $bot);

            // If found
            if ($rule) {

                // If the auto reply rule is a subscription message, subscribe the user.
                if ($rule->subscribe) {
                    $subscriber = $this->adapter->subscribe($bot, $event['sender']['id']);
                }

                // Otherwise, send the auto reply message.
                // But before then, if the current message sender is not a subscriber,
                // Subscribe them silently.
                if (! $subscriber) {
                    $subscriber = $this->adapter->subscribeSilently($bot, $event['sender']['id']);
                }

                // If the auto reply rule is a unsubscription message, send the "do you want to unsubscribe?" message .
                if ($rule->unsubscribe) {
                    $this->adapter->concludeUnsubscriptionProcess($bot, $subscriber);
                }

                $this->adapter->storeIncomingTextMessage($event, $bot, $subscriber);
                if ($rule->template_id) {
                    $this->adapter->sendAutoReply($rule, $subscriber, $bot);
                }

                return;
            }

            // If no matching auto reply rule is found, then send the default reply.
            // But before then, if the current message sender is not a subscriber,
            // or if inactive subscriber, subscribe them silently.
            if (! $subscriber || ! $subscriber->active) {
                $subscriber = $this->adapter->subscribeSilently($bot, $event['sender']['id']);
            }
            $this->adapter->storeIncomingTextMessage($event, $bot, $subscriber);
            $this->adapter->sendDefaultReply($bot, $subscriber);

            return;
        }

        // Handle postbacks (button clicks).
        if (array_get($event, 'postback')) {
            $this->handlePostbackEvent($bot, $subscriber, $event);

            return;
        }

        // Handle optin (send to messenger plugin)
        if (array_get($event, 'optin')) {
            $payload = array_get($event, 'optin.ref');
            $subscriber = $this->adapter->subscribeBotUser($payload, $bot, $event['sender']['id']);
            $this->adapter->storeIncomingOptInMessage($event, $bot, $subscriber);

            return;
        }

        // account_linking
        // referal
    }

    /**
     * Handle button clicks (postback)
     * @param Bot             $bot
     * @param Subscriber|null $subscriber
     * @param array           $event
     */
    private function handlePostbackEvent(Bot $bot, $subscriber, $event)
    {
        // If clicked on the get started button, then subscribe the user.
        if ($event['postback']['payload'] == MessengerThread::GET_STARTED_PAYLOAD) {
            $subscriber = $this->adapter->subscribe($bot, $event['sender']['id']);
            $this->adapter->storeIncomingGetStartedButtonClick($event['timestamp'], $bot, $subscriber);

            return;
        }

        // If the user clicks on any other button, then subscribe him silently!
        $subscriber = $subscriber?: $this->adapter->subscribeSilently($bot, $event['sender']['id']);
        $title = $this->adapter->handlePostbackButtonClick($event['postback']['payload'], $bot, $subscriber);
        // false on main menu buttons.
        if ($title !== false){
            $this->adapter->storeIncomingButtonClick($title, $event['timestamp'], $bot, $subscriber);
        }
    }
}

