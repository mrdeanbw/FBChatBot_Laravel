<?php namespace App\Services;

use DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Page;
use App\Models\Button;
use App\Models\Template;
use App\Models\MainMenu;
use App\Models\Broadcast;
use App\Models\Subscriber;
use App\Models\AutoReplyRule;
use App\Models\MessageInstance;
use App\Services\Facebook\Sender;
use App\Models\MessageInstanceClick;

class WebAppAdapter
{

    //    const SUBSCRIBE_PAYLOAD = "SUBSCRIBE";
    const UNSUBSCRIBE_PAYLOAD = "UNSUBSCRIBE";
    /**
     * @type Sender
     */
    protected $FacebookSender;
    /**
     * @type AudienceService
     */
    private $audience;
    /**
     * @type WelcomeMessageService
     */
    private $welcomeMessage;
    /**
     * @type FacebookAPIAdapter
     */
    private $FacebookAdapter;
    /**
     * @type MessageBlockService
     */
    private $messageBlocks;
    /**
     * @type DefaultReplyService
     */
    private $defaultReplies;
    /**
     * @type AutoReplyRuleService
     */
    private $AIResponses;

    /**
     * WebAppAdapter constructor.
     *
     * @param AudienceService       $audience
     * @param WelcomeMessageService $welcomeMessage
     * @param FacebookAPIAdapter    $FacebookAdapter
     * @param Sender                $FacebookSender
     * @param MessageBlockService   $messageBlocks
     * @param DefaultReplyService   $defaultReplies
     * @param AutoReplyRuleService  $AIResponses
     */
    public function __construct(
        AudienceService $audience,
        WelcomeMessageService $welcomeMessage,
        FacebookAPIAdapter $FacebookAdapter,
        Sender $FacebookSender,
        MessageBlockService $messageBlocks,
        DefaultReplyService $defaultReplies,
        AutoReplyRuleService $AIResponses
    ) {
        $this->audience = $audience;
        $this->welcomeMessage = $welcomeMessage;
        $this->FacebookAdapter = $FacebookAdapter;
        $this->messageBlocks = $messageBlocks;
        $this->defaultReplies = $defaultReplies;
        $this->AIResponses = $AIResponses;
        $this->FacebookSender = $FacebookSender;
    }

    /**
     * @param Page $page
     * @param      $senderId
     * @param bool $silentMode
     *
     * @return Subscriber
     */
    public function subscribe(Page $page, $senderId, $silentMode = false)
    {
        $subscriber = $this->subscriber($senderId, $page);

        if ($subscriber && $subscriber->is_active) {
            if (! $silentMode) {
                $message = [
                    'message' => [
                        'text' => 'You are already subscribed to the page.'
                    ],
                ];
                $this->FacebookAdapter->sendMessage($message, $subscriber, $page);
            }

            return $subscriber;
        }

        if (! $subscriber) {
            $subscriber = $this->persistSubscriber($page, $senderId, true);
        }

        if (! $subscriber->is_active) {
            $this->audience->resubscribe($senderId, $page);
        }

        if (! $silentMode) {
            $welcomeMessage = $this->welcomeMessage->getOrFail($page);
            $this->FacebookAdapter->sendBlocks($welcomeMessage, $subscriber);
        }

        return $subscriber;
    }

    /**
     * @param Page $page
     * @param      $senderId
     *
     * @return Subscriber
     */
    public function subscribeSilently(Page $page, $senderId)
    {
        return $this->subscribe($page, $senderId, true);
    }


    /**
     * @param Page       $page
     * @param Subscriber $subscriber
     * @param            $facebookId
     */
    public function initiateUnsubscripingProcess(Page $page, $subscriber, $facebookId)
    {
        if (! $subscriber) {
            $message = [
                'message'   => [
                    'text' => "You can't unsubscribe from this chat-bot since you haven't subscribed to in the first place! Send \"start\" to subscribe!"
                ],
                'recipient' => [
                    'id' => $facebookId,
                ]
            ];
            $this->FacebookSender->send($page->access_token, $message, false);

            return;
        }

        if (! $subscriber->is_active) {
            // already unsubscribed
            $message = [
                'message' => [
                    'text' => 'You have already unsubscribed from this page.'
                ],
            ];
            $this->FacebookAdapter->sendMessage($message, $subscriber, $page);

            return;
        }

        $message = [
            'message' => [
                'attachment' => [
                    'type'    => 'template',
                    'payload' => [
                        'template_type' => 'button',
                        'text'          => "Do you really want to unsubscribe from {$page->name}?",
                        'buttons'       => [
                            [
                                'type'    => "postback",
                                'title'   => "Unsubscribe",
                                'payload' => self::UNSUBSCRIBE_PAYLOAD,
                            ]
                        ]
                    ]
                ]
            ]
        ];


        $this->FacebookAdapter->sendMessage($message, $subscriber, $page);
    }

    /**
     * @param Page       $page
     * @param Subscriber $subscriber
     */
    public function concludeUnsubscriptionProcess(Page $page, $subscriber)
    {
        if (! $subscriber || ! $subscriber->is_active) {
            // already unsubscribed
            $message = [
                'message' => [
                    'text' => 'You have already unsubscribed from this page.'
                ],
            ];
            $this->FacebookAdapter->sendMessage($message, $subscriber, $page);

            return;
        }

        $this->audience->unsubscribe($subscriber);

        $message = [
            'message' => [
                'text' => 'You have successfully unsubscribed. Use "start" to subscribe again.'
            ],
        ];

        $this->FacebookAdapter->sendMessage($message, $subscriber, $page);
    }

    /**
     * @param Page       $page
     * @param Subscriber $subscriber
     */
    public function sendDefaultReply(Page $page, Subscriber $subscriber)
    {
        $defaultReply = $this->defaultReplies->get($page);

        $this->FacebookAdapter->sendBlocks($defaultReply, $subscriber);
    }

    /**
     * @param Page       $page
     * @param Subscriber $subscriber
     * @param            $hash
     *
     * @return bool
     */
    public function clickButton($page, $subscriber, $hash)
    {
        if (! $page || ! $subscriber) {
            return false;
        }

        $isMainMenuButton = false;

        if (starts_with($hash, "MAIN_MENU_")) {
            $isMainMenuButton = true;
            $hash = substr($hash, strlen("MAIN_MENU_"));
        }

        if (! ($id = SimpleEncryptionService::decode($hash))) {
            return false;
        }

        if ($isMainMenuButton) {
            $button = Button::find($id);
        } else {
            $instance = MessageInstance::find($id);
            $button = $instance->message_block;
        }

        if (! $button || $button->type != 'button') {
            return false;
        }

        if (isset($instance)) {
            $this->updateMessageBlockClicked($instance);
        }

        $this->handleButtonClick($button, $subscriber);

        return true;
    }

    /**
     * @param $messageBlockHash
     * @param $subscriberHash
     *
     * @return bool|string
     */
    public function messageBlockUrl($messageBlockHash, $subscriberHash)
    {
        if (! ($modelId = SimpleEncryptionService::decode($messageBlockHash))) {
            return false;
        }

        if ($subscriberHash == FacebookAPIAdapter::NO_HASH_PLACEHOLDER) {
            $mainMenuButton = Button::find($modelId);
            if (! $mainMenuButton || $mainMenuButton->context_type != MainMenu::class) {
                return false;
            }

            return $mainMenuButton->url;
        }

        if (! ($subscriberId = SimpleEncryptionService::decode($subscriberHash))) {
            return false;
        }

        $subscriber = Subscriber::find($subscriberId);
        if (! $subscriber) {
            return false;
        }

        $messageInstance = MessageInstance::find($modelId);
        if (! $messageInstance) {
            return false;
        }

        $messageBlock = $messageInstance->message_block;

        $this->updateMessageBlockClicked($messageInstance);

        if ($messageBlock->type == 'button') {
            $this->handleButtonClick($messageBlock, $subscriber);
        }

        return $messageBlock->url;
    }


    /**
     * @param Button     $button
     * @param Subscriber $subscriber
     */
    private function handleButtonClick(Button $button, Subscriber $subscriber)
    {
        if ($button->addTags->count()) {
            $subscriber->syncTags($button->addTags, false);
        }

        if ($button->removeTags->count()) {
            $subscriber->detachTags($button->removeTags);
        }

        /** @type Template $template */
        if ($template = $button->template) {
            $this->FacebookAdapter->sendBlocks($template, $subscriber);
        }
    }


    /**
     * @param $pageId
     *
     * @return Page
     */
    public function page($pageId)
    {
        return Page::whereFacebookId($pageId)->first();
    }

    /**
     * @param      $senderId
     * @param Page $page
     *
     * @return Subscriber|null
     */
    public function subscriber($senderId, Page $page)
    {
        return $this->audience->findByFacebookId($senderId, $page);
    }

    /**
     * @param      $message
     * @param Page $page
     *
     * @return AutoReplyRule
     */
    public function matchingAutoReplyRule($message, Page $page)
    {
        return $this->AIResponses->getMatchingRule($message, $page);
    }

    /**
     * @param AutoReplyRule $rule
     * @param Subscriber    $subscriber
     */
    public function autoReply(AutoReplyRule $rule, Subscriber $subscriber)
    {
        $this->FacebookAdapter->sendBlocks($rule->template, $subscriber);
    }

    /**
     * @param Subscriber $subscriber
     * @param            $timestamp
     */
    public function markMessageBlocksAsDelivered($subscriber, $timestamp)
    {
        if (! $subscriber) {
            return;
        }

        $timestamp = $this->normalizeTimestamp($timestamp);
        $subscriber->messageInstances()->where('delivered_at', null)->where('sent_at', '<=', $timestamp)->update(['delivered_at' => $timestamp]);
        $this->updateBroadcastDeliveredStats($subscriber, $timestamp);
    }

    /**
     * @param Subscriber $subscriber
     * @param            $timestamp
     */
    public function markMessageBlocksAsRead($subscriber, $timestamp)
    {
        if (! $subscriber) {
            return;
        }
        // if a message is read, then it is definitely delivered.
        // this is to handle facebook sometimes not sending the delivery callback.
        $this->markMessageBlocksAsDelivered($subscriber, $timestamp);

        $timestamp = $this->normalizeTimestamp($timestamp);
        $subscriber->messageInstances()->where('read_at', null)->where('sent_at', '<=', $timestamp)->update(['read_at' => $timestamp]);
        $this->updateBroadcastReadStats($subscriber, $timestamp);
    }

    /**
     * @param MessageInstance $instance
     */
    private function updateMessageBlockClicked(MessageInstance $instance)
    {
        $instance->clicks = $instance->clicks + 1;
        $instance->save();

        $click = new MessageInstanceClick();
        $click->message_instance_id = $instance->id;
        $click->save();

        $this->updateBroadcastClicksStats($instance);
    }

    /**
     * @param AutoReplyRule $rule
     *
     * @return bool
     */
    public function isUnsubscriptionMessage(AutoReplyRule $rule)
    {
        return $rule->action == 'unsubscribe';
    }

    /**
     * @param AutoReplyRule $rule
     *
     * @return bool
     */
    public function isSubscriptionMessage(AutoReplyRule $rule)
    {
        return $rule->action == 'subscribe';
    }

    /**
     * @param $timestamp
     *
     * @return string
     */
    private function normalizeTimestamp($timestamp)
    {
        $timestamp = Carbon::createFromTimestamp((int)($timestamp / 1000))->toDateTimeString();

        return $timestamp;
    }

    /**
     * @param MessageInstance $instance
     */
    private function updateBroadcastClicksStats(MessageInstance $instance)
    {
        $broadcast = $instance->message_block->superContext();
        if ($broadcast && is_a($broadcast, Broadcast::class)) {
            DB::statement("update `broadcast_subscriber` SET `clicks` = `clicks` + 1 WHERE `subscriber_id` = {$instance->subscriber->id} AND `broadcast_id` = {$broadcast->id}");
        }
    }

    /**
     * @param Subscriber $subscriber
     * @param            $timestamp
     */
    private function updateBroadcastReadStats(Subscriber $subscriber, $timestamp)
    {
        DB::statement("update `broadcast_subscriber` SET `read_at` = '{$timestamp}' WHERE `subscriber_id` = {$subscriber->id} AND  `read_at` IS NULL AND `sent_at` <= '{$timestamp}'");
    }

    /**
     * @param Subscriber $subscriber
     * @param            $timestamp
     */
    private function updateBroadcastDeliveredStats(Subscriber $subscriber, $timestamp)
    {
        DB::statement("update `broadcast_subscriber` SET `delivered_at` = '{$timestamp}' WHERE `subscriber_id` = {$subscriber->id} AND `delivered_at` IS NULL AND `sent_at` <= '{$timestamp}'");
    }

    public function subscribeOwner($payload, Page $page, $senderId)
    {
        $prefix = 'SUBSCRIBE_OWNER_';

        if (! starts_with($payload, $prefix)) {
            return false;
        }

        $ownerId = (int)substr($payload, strlen($prefix));

        /** @type User $owner */
        $owner = $page->users()->whereId($ownerId)->first();
        if (! $owner) {
            return false;
        }

        $subscriber = $this->subscribeSilently($page, $senderId);

        $owner->pages()->updateExistingPivot($page->id, ['subscriber_id' => $subscriber->id]);

        return true;
    }

    /**
     * @param Page $page
     * @param      $senderId
     * @param      $isActive
     * @return Subscriber|null
     */
    public function persistSubscriber(Page $page, $senderId, $isActive)
    {
        return $this->audience->persist($senderId, $page, $isActive);
    }

}