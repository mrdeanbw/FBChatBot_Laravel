<?php namespace App\Services;

use Log;
use Carbon\Carbon;
use App\Models\Page;
use App\Models\Subscriber;

class FacebookWebhookReceiver
{

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
        $page = $this->adapter->page($event['recipient']['id']);

        if (! $page) {
            return;
        }

        if (array_get($event, 'message.is_echo')) {
            return;
        }

        $subscriber = $this->adapter->subscriber($event['sender']['id'], $page);

        if (array_get($event, 'delivery')) {
            $this->adapter->markMessageBlocksAsDelivered($subscriber, $event['delivery']['watermark']);

            return;
        }

        if (array_get($event, 'read')) {
            $this->adapter->markMessageBlocksAsRead($subscriber, $event['read']['watermark']);

            return;
        }


        if ($text = array_get($event, 'message.text')) {

            $rule = $this->adapter->matchingAutoReplyRule($text, $page);

            if ($rule) {

                if ($this->adapter->isSubscriptionMessage($rule)) {
                    $subscriber = $this->adapter->subscribe($page, $event['sender']['id']);
                    $this->updateLastContactedAt($subscriber);

                    return;
                }

                if ($this->adapter->isUnsubscriptionMessage($rule)) {
                    $this->updateLastContactedAt($subscriber);
                    $this->adapter->initiateUnsubscripingProcess($page, $subscriber, $event['sender']['id']);

                    return;
                }

                if (! $subscriber) {
                    $subscriber = $this->adapter->subscribeSilently($page, $event['sender']['id']);
                }
                $this->updateLastContactedAt($subscriber);

                $this->adapter->autoReply($rule, $subscriber);

                return;
            }


            if (! $subscriber) {
                $subscriber = $this->adapter->subscribeSilently($page, $event['sender']['id']);
            }
            $this->updateLastContactedAt($subscriber);
            $this->adapter->sendDefaultReply($page, $subscriber);

            return;
        }


        if (array_get($event, 'postback')) {
            $this->handlePostbackEvent($page, $subscriber, $event);

            return;
        }

        if (array_get($event, 'optin')) {
            $payload = array_get($event, 'optin.ref');
            $this->adapter->subscribeOwner($payload, $page, $event['sender']['id']);

            return;
        }

        // account_linking
        // optin
        // referal
    }

    /**
     * @param Page            $page
     * @param Subscriber|null $subscriber
     * @param                 $event
     * @return bool
     */
    private function handlePostbackEvent(Page $page, $subscriber, $event)
    {
        if ($event['postback']['payload'] == Thread::GET_STARTED_PAYLOAD) {
            $this->adapter->subscribe($page, $event['sender']['id']);

            return;
        }

        //        if ($event['postback']['payload'] == WebAppAdapter::SUBSCRIBE_PAYLOAD) {
        //            $this->adapter->concludeSubscriptionProcess($event['recipient']['id'], $event['sender']['id']);
        //
        //            return;
        //        }

        if ($event['postback']['payload'] == WebAppAdapter::UNSUBSCRIBE_PAYLOAD) {
            $this->adapter->concludeUnsubscriptionProcess($page, $subscriber);

            return;
        }

        $this->adapter->subscribeSilently($page, $event['sender']['id']);
        // payload is a hashed button.
        if (! $this->adapter->clickButton($page, $subscriber, $event['postback']['payload'])) {
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
            $subscriber->last_contacted_at = Carbon::now();
            $subscriber->save();
        }
    }

}

