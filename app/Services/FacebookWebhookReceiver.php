<?php namespace App\Services;

use Log;
use Carbon\Carbon;
use App\Models\Bot;
use App\Models\Subscriber;
use App\Services\Facebook\MessengerThread;

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
     * @return void
     */
    private function handleEvent($event)
    {
        $bot = $this->adapter->bot($event['recipient']['id']);

        // If the page is not in our system, then do nothing.
        if (! $bot) {
            return;
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
                $this->adapter->markMessageBlocksAsDelivered($subscriber, $event['delivery']['watermark']);
            }

            return;
        }

        // If it is a read notification, then mark messages as read.
        if (array_get($event, 'read')) {
            if ($subscriber) {
                $this->adapter->markMessageBlocksAsRead($subscriber, $event['read']['watermark']);
            }

            return;
        }

        // If a text message is received
        if ($text = array_get($event, 'message.text')) {

            // Find a matching auto reply rule.
            $rule = $this->adapter->matchingAutoReplyRule($text, $bot);

            // If found
            if ($rule) {

                // If the auto reply rule is a subscription message, subscribe the user.
                if ($this->adapter->isSubscriptionMessage($rule)) {
                    $subscriber = $this->adapter->subscribe($bot, $event['sender']['id']);
                    $this->updateLastContactedAt($subscriber);

                    return;
                }

                // If the auto reply rule is a unsubscription message, send the "do you want to unsubscribe?" message .
                if ($this->adapter->isUnsubscriptionMessage($rule)) {
                    $this->updateLastContactedAt($subscriber);
                    $this->adapter->initiateUnsubscripingProcess($bot, $subscriber, $event['sender']['id']);

                    return;
                }

                // Otherwise, send the auto reply message.
                // But before then, if the current message sender is not a subscriber,
                // Subscribe them silently.
                if (! $subscriber) {
                    $subscriber = $this->adapter->subscribeSilently($bot, $event['sender']['id']);
                }
                $this->updateLastContactedAt($subscriber);
                $this->adapter->sendAutoReply($rule, $subscriber);

                return;
            }

            // If no matching auto reply rule is found, then send the default reply.
            // But before then, if the current message sender is not a subscriber,
            // or if inactive subscriber, subscribe them silently.
            if (! $subscriber || ! $subscriber->active) {
                $subscriber = $this->adapter->subscribeSilently($bot, $event['sender']['id']);
            }
            $this->updateLastContactedAt($subscriber);
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
            $this->adapter->subscribeBotUser($payload, $bot, $event['sender']['id']);

            return;
        }

        // account_linking
        // optin
        // referal
    }

    /**
     * Handle button clicks (postback)
     * @param Bot             $page
     * @param Subscriber|null $subscriber
     * @param                 $event
     * @return bool
     */
    private function handlePostbackEvent(Bot $page, $subscriber, $event)
    {
        // If clicked on the get started button, then subscribe the user.
        if ($event['postback']['payload'] == MessengerThread::GET_STARTED_PAYLOAD) {
            $this->adapter->subscribe($page, $event['sender']['id']);

            return;
        }

        // If the user clicks on the button to confirm unsubscription, then unsubscribe him.
        if ($event['postback']['payload'] == WebAppAdapter::UNSUBSCRIBE_PAYLOAD) {
            $this->adapter->concludeUnsubscriptionProcess($page, $subscriber);

            return;
        }

        // If the user clicks on any other button, then subscribe him silently!
        $this->adapter->subscribeSilently($page, $event['sender']['id']);

        // payload is a hashed button.
        if (! $this->adapter->handleButtonClick($page, $subscriber, $event['postback']['payload'])) {
            Log::debug("Unknown postback payload: " . $event['postback']['payload']);
        }
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @param Subscriber $subscriber
     */
    private function updateLastContactedAt($subscriber)
    {
        if ($subscriber) {
            $subscriber->last_interaction_at = Carbon::now();
            $subscriber->save();
        }
    }

}

