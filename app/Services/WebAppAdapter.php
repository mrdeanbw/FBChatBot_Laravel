<?php namespace App\Services;

use App\Repositories\User\UserRepository;
use DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Page;
use App\Models\Button;
use App\Models\MainMenu;
use App\Models\Broadcast;
use App\Models\Subscriber;
use App\Models\AutoReplyRule;
use App\Models\MessageInstance;
use App\Services\Facebook\Sender;
use App\Repositories\MessageInstance\MessageInstanceRepository;

class WebAppAdapter
{

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
     * @type MessageInstanceRepository
     */
    private $messageInstanceRepo;
    /**
     * @type PageService
     */
    private $pages;
    /**
     * @type BroadcastService
     */
    private $broadcasts;
    /**
     * @type UserRepository
     */
    private $userRepo;

    /**
     * WebAppAdapter constructor.
     *
     * @param AudienceService           $audience
     * @param WelcomeMessageService     $welcomeMessage
     * @param FacebookAPIAdapter        $FacebookAdapter
     * @param Sender                    $FacebookSender
     * @param MessageBlockService       $messageBlocks
     * @param DefaultReplyService       $defaultReplies
     * @param AutoReplyRuleService      $AIResponses
     * @param MessageInstanceRepository $messageInstanceRepo
     * @param PageService               $pages
     * @param BroadcastService          $broadcasts
     * @param UserRepository            $userRepo
     */
    public function __construct(
        AudienceService $audience,
        WelcomeMessageService $welcomeMessage,
        FacebookAPIAdapter $FacebookAdapter,
        Sender $FacebookSender,
        MessageBlockService $messageBlocks,
        DefaultReplyService $defaultReplies,
        AutoReplyRuleService $AIResponses,
        MessageInstanceRepository $messageInstanceRepo,
        PageService $pages,
        BroadcastService $broadcasts,
        UserRepository $userRepo
    ) {
        $this->audience = $audience;
        $this->welcomeMessage = $welcomeMessage;
        $this->FacebookAdapter = $FacebookAdapter;
        $this->messageBlocks = $messageBlocks;
        $this->defaultReplies = $defaultReplies;
        $this->AIResponses = $AIResponses;
        $this->FacebookSender = $FacebookSender;
        $this->messageInstanceRepo = $messageInstanceRepo;
        $this->pages = $pages;
        $this->broadcasts = $broadcasts;
        $this->userRepo = $userRepo;
    }

    /**
     * Subscribe a message sender to the page.
     * @param Page $page
     * @param      $senderId
     * @param bool $silentMode
     * @return Subscriber
     */
    public function subscribe(Page $page, $senderId, $silentMode = false)
    {
        $subscriber = $this->subscriber($senderId, $page);

        // If already subscribed
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

        // If first time, then create subscriber.
        if (! $subscriber) {
            $subscriber = $this->persistSubscriber($page, $senderId, true);
        }

        // If not first time (unsubscribed before), resubscribe him.
        if (! $subscriber->is_active) {
            $this->audience->resubscribe($senderId, $page);
        }

        // If not silent mode, send the welcome message!
        if (! $silentMode) {
            $welcomeMessage = $this->welcomeMessage->getOrFail($page);
            $this->FacebookAdapter->sendBlocks($welcomeMessage, $subscriber);
        }

        return $subscriber;
    }

    /**
     * Subscribe a user without actually sending him the welcome message.
     * @param Page $page
     * @param      $senderId
     * @return Subscriber
     */
    public function subscribeSilently(Page $page, $senderId)
    {
        return $this->subscribe($page, $senderId, true);
    }


    /**
     * Send a message to the user, asking if he really wants to unsubscribe.
     * @param Page            $page
     * @param Subscriber|null $subscriber
     * @param                 $facebookId
     */
    public function initiateUnsubscripingProcess(Page $page, $subscriber, $facebookId)
    {
        // If a non-subscribed user, tries to initiate the unsubscription process, handle it properly.
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

        // already unsubscribed
        if (! $subscriber->is_active) {
            $message = [
                'message' => [
                    'text' => 'You have already unsubscribed from this page.'
                ],
            ];
            $this->FacebookAdapter->sendMessage($message, $subscriber, $page);

            return;
        }

        // Send asking for confirmation message
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
     * User has confirmed his willingness to unsubscribe, so unsubscribe him!
     * @param Page       $page
     * @param Subscriber $subscriber
     */
    public function concludeUnsubscriptionProcess(Page $page, $subscriber)
    {
        // already unsubscribed
        if (! $subscriber || ! $subscriber->is_active) {
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
     * Send the default reply.
     * @param Page       $page
     * @param Subscriber $subscriber
     */
    public function sendDefaultReply(Page $page, Subscriber $subscriber)
    {
        $defaultReply = $this->defaultReplies->get($page);
        $this->FacebookAdapter->sendBlocks($defaultReply, $subscriber);
    }

    /**
     * Handle button click.
     * @param Page       $page
     * @param Subscriber $subscriber
     * @param            $payload
     * @return bool
     */
    public function handleButtonClick($page, $subscriber, $payload)
    {
        if (! $page || ! $subscriber) {
            return false;
        }

        // If main menu button
        if (starts_with($payload, "MAIN_MENU_")) {
            return $this->handleMainMenuButtonClick($page, $subscriber, $payload);
        }

        return $this->handleNonMainMenuButtonClick($page, $subscriber, $payload);
    }

    /**
     * Handle a main menu button click.
     * @param Page       $page
     * @param Subscriber $subscriber
     * @param            $payload
     * @return bool
     */
    private function handleMainMenuButtonClick(Page $page, Subscriber $subscriber, $payload)
    {
        $payload = substr($payload, strlen("MAIN_MENU_"));

        if (! ($id = SimpleEncryptionService::decode($payload))) {
            return false;
        }

        $block = $this->messageBlocks->findMessageBlockForPage($id, $page);

        // Make sure that the message block is button
        if (! $block || $block->type != 'button') {
            return false;
        }

        $this->carryOutButtonActions($block, $subscriber);

        return true;
    }

    /**
     * @param Page       $page
     * @param Subscriber $subscriber
     * @param            $payload
     * @return bool
     */
    private function handleNonMainMenuButtonClick(Page $page, Subscriber $subscriber, $payload)
    {
        // decrypt to the model id.
        if (! ($id = SimpleEncryptionService::decode($payload))) {
            return false;
        }

        if (! ($instance = $this->messageInstanceRepo->findByIdForPage($id, $page))) {
            return false;
        }

        $block = $instance->message_block;

        // Make sure that the message block is button
        if (! $block || $block->type != 'button') {
            return false;
        }

        $this->incrementMessageInstanceClicks($instance);

        $this->carryOutButtonActions($block, $subscriber);

        return true;
    }

    /**
     * Return the redirect URL from a button/card, via the message block hash.
     * @param $messageBlockHash
     * @param $subscriberHash
     * @return bool|string
     */
    public function getMessageBlockRedirectURL($messageBlockHash, $subscriberHash)
    {
        if (! ($blockId = SimpleEncryptionService::decode($messageBlockHash))) {
            return false;
        }

        // If the subscriber hash is that placeholder hash for main menu
        // then the message block is a main menu button, validate this assumption and
        if ($subscriberHash == FacebookAPIAdapter::NO_HASH_PLACEHOLDER) {
            $mainMenuButton = $this->messageBlocks->findMessageBlock($blockId);
            if (! $mainMenuButton || $mainMenuButton->type != 'button' || $mainMenuButton->context_type != MainMenu::class) {
                return false;
            }

            return $mainMenuButton->url;
        }

        if (! ($subscriberId = SimpleEncryptionService::decode($subscriberHash))) {
            // Invalid subscriber hash
            return false;
        }

        if (! ($subscriber = $this->audience->find($subscriberId))) {
            // Invalid subscriber hash
            return false;
        }

        if (! ($messageInstance = $this->messageInstanceRepo->findById($blockId))) {
            // Invalid message block hash
            return false;
        }

        $this->incrementMessageInstanceClicks($messageInstance);

        $messageBlock = $messageInstance->message_block;

        if ($messageBlock->type == 'button') {
            $this->carryOutButtonActions($messageBlock, $subscriber);
        }

        return $messageBlock->url;
    }

    /**
     * Execute the actions associate with a button.
     * @param Button     $button
     * @param Subscriber $subscriber
     */
    private function carryOutButtonActions(Button $button, Subscriber $subscriber)
    {
        if ($button->addTags->count()) {
            $this->audience->syncTags($subscriber, $button->addTags, false);
        }

        if ($button->removeTags->count()) {
            $this->audience->detachTags($subscriber, $button->removeTags);
        }

        if ($template = $button->template) {
            $this->FacebookAdapter->sendBlocks($template, $subscriber);
        }
    }


    /**
     * Get a page by facebook ID.
     * @param $pageId
     * @return Page
     */
    public function page($pageId)
    {
        return $this->pages->findByFacebookId($pageId);
    }

    /**
     * Get a subscriber by Facebook ID.
     * @param      $senderId
     * @param Page $page
     * @return Subscriber|null
     */
    public function subscriber($senderId, Page $page)
    {
        return $this->audience->findByFacebookId($senderId, $page);
    }

    /**
     * Get matching AI Rules.
     * @param      $message
     * @param Page $page
     * @return AutoReplyRule
     */
    public function matchingAutoReplyRule($message, Page $page)
    {
        return $this->AIResponses->getMatchingRule($message, $page);
    }

    /**
     * Send an auto reply.
     * @param AutoReplyRule $rule
     * @param Subscriber    $subscriber
     */
    public function sendAutoReply(AutoReplyRule $rule, Subscriber $subscriber)
    {
        $this->FacebookAdapter->sendBlocks($rule->template, $subscriber);
    }

    /**
     * Mark all messages sent to a subscriber before a specific date as read.
     * @param Subscriber $subscriber
     * @param int        $timestamp
     */
    public function markMessageBlocksAsDelivered(Subscriber $subscriber, $timestamp)
    {
        $timestamp = $this->normalizeTimestamp($timestamp);

        $this->messageInstanceRepo->markAsDelivered($subscriber, $timestamp);
        $this->updateBroadcastDeliveredStats($subscriber, $timestamp);
    }

    /**
     * Mark all messages sent to a subscriber before a specific date as read.
     * @param Subscriber $subscriber
     * @param            $timestamp
     */
    public function markMessageBlocksAsRead(Subscriber $subscriber, $timestamp)
    {
        // if a message is read, then it is definitely delivered.
        // this is to handle Facebook sometimes not sending the delivery callback.
        $this->markMessageBlocksAsDelivered($subscriber, $timestamp);

        $timestamp = $this->normalizeTimestamp($timestamp);

        $this->messageInstanceRepo->markAsRead($subscriber, $timestamp);
        $this->updateBroadcastReadStats($subscriber, $timestamp);
    }

    /**
     * Increase the number of clicks for a given message instance
     * @param MessageInstance $instance
     * @param int             $incrementBy
     */
    private function incrementMessageInstanceClicks(MessageInstance $instance, $incrementBy = 1)
    {
        $this->messageInstanceRepo->update($instance, ['clicks' => $instance->clicks + $incrementBy]);
        $this->messageInstanceRepo->createMessageInstanceClick($instance);
        $this->incrementBroadcastClicks($instance, $incrementBy);
    }

    /**
     * If the auto reply rule should trigger unsubscription action
     * @param AutoReplyRule $rule
     * @return bool
     */
    public function isUnsubscriptionMessage(AutoReplyRule $rule)
    {
        return $rule->action == 'unsubscribe';
    }

    /**
     * If the auto reply rule should trigger subscription action
     * @param AutoReplyRule $rule
     * @return bool
     */
    public function isSubscriptionMessage(AutoReplyRule $rule)
    {
        return $rule->action == 'subscribe';
    }

    /**
     * Convert the timestamp sent by Facebook (in milliseconds) to date-time string.
     * @param int $timestamp
     * @return string
     */
    private function normalizeTimestamp($timestamp)
    {
        $timestamp = Carbon::createFromTimestamp((int)($timestamp / 1000))->toDateTimeString();

        return $timestamp;
    }

    /**
     * Increment the number of clicks for a broadcast/subscriber.
     * @param MessageInstance $instance
     * @param int             $incrementBy
     */
    private function incrementBroadcastClicks(MessageInstance $instance, $incrementBy = 1)
    {
        $rootContext = $this->messageBlocks->getRootContext($instance->message_block);

        if ($rootContext && is_a($rootContext, Broadcast::class)) {
            $this->broadcasts->incrementBroadcastSubscriberClicks($rootContext, $instance->subscriber, $incrementBy);
        }
    }

    /**
     * Mark the broadcast as delivered to subscriber
     * @param Subscriber $subscriber
     * @param            $dateTime
     */
    private function updateBroadcastDeliveredStats(Subscriber $subscriber, $dateTime)
    {
        $this->broadcasts->updateBroadcastSubscriberDeliveredAt($subscriber, $dateTime);
    }

    /**
     * Mark the broadcast as read by subscriber
     * @param Subscriber $subscriber
     * @param            $dateTime
     */
    private function updateBroadcastReadStats(Subscriber $subscriber, $dateTime)
    {
        $this->broadcasts->updateBroadcastSubscriberReadAt($subscriber, $dateTime);
    }

    /**
     * Make a user (page admin) into "subscriber" for a page.
     * @param      $payload
     * @param Page $page
     * @param      $senderId
     * @return bool
     */
    public function subscribePageUser($payload, Page $page, $senderId)
    {
        // The page admin payload has a special prefix, followed by his internal artificial ID.
        $prefix = 'SUBSCRIBE_OWNER_';
        if (! starts_with($payload, $prefix)) {
            return false;
        }

        // Parsing his ID.
        if (! ($userId = (int)substr($payload, strlen($prefix)))) {
            return false;
        }
        
        // Getting user.
        if (! ($user = $this->userRepo->findForPage($userId, $page))) {
            return false;
        }

        // Subscribing user (message sender)
        $subscriber = $this->subscribeSilently($page, $senderId);

        // Associating user with subscriber.
        $this->userRepo->associateWithPageAsSubscriber($user, $page, $subscriber);

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
        return $this->audience->getByFacebookIdOrCreate($senderId, $page, $isActive);
    }

}