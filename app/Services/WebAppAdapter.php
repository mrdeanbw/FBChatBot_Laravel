<?php namespace App\Services;

use Carbon\Carbon;
use App\Models\Bot;
use App\Models\Button;
use App\Models\MainMenu;
use App\Models\Template;
use App\Models\Broadcast;
use App\Models\Subscriber;
use App\Models\AutoReplyRule;
use App\Services\Facebook\Sender;
use App\Repositories\Bot\BotRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\Template\TemplateRepositoryInterface;
use App\Repositories\MessageInstance\MessageHistoryRepositoryInterface;

class WebAppAdapter
{

    const UNSUBSCRIBE_PAYLOAD = "UNSUBSCRIBE";
    /**
     * @type Sender
     */
    protected $FacebookSender;
    /**
     * @type SubscriberService
     */
    private $subscribers;
    /**
     * @type WelcomeMessageService
     */
    private $welcomeMessage;
    /**
     * @type FacebookAPIAdapter
     */
    private $FacebookAdapter;
    /**
     * @type MessageService
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
     * @type MessageHistoryRepositoryInterface
     */
    private $messageInstanceRepo;
    /**
     * @type BotService
     */
    private $bots;
    /**
     * @type BroadcastService
     */
    private $broadcasts;
    /**
     * @type UserRepositoryInterface
     */
    private $userRepo;
    /**
     * @type TemplateService
     */
    private $templates;
    /**
     * @type BotRepositoryInterface
     */
    private $botRepo;
    /**
     * @type TemplateRepositoryInterface
     */
    private $templateRepo;

    /**
     * WebAppAdapter constructor.
     *
     * @param SubscriberService                 $subscribers
     * @param WelcomeMessageService             $welcomeMessage
     * @param FacebookAPIAdapter                $FacebookAdapter
     * @param Sender                            $FacebookSender
     * @param MessageService                    $messageBlocks
     * @param DefaultReplyService               $defaultReplies
     * @param AutoReplyRuleService              $AIResponses
     * @param MessageHistoryRepositoryInterface $messageInstanceRepo
     * @param BotService                        $pages
     * @param BroadcastService                  $broadcasts
     * @param TemplateService                   $templates
     * @param UserRepositoryInterface           $userRepo
     * @param BotRepositoryInterface            $botRepo
     * @param TemplateRepositoryInterface       $templateRepo
     */
    public function __construct(
        SubscriberService $subscribers,
        WelcomeMessageService $welcomeMessage,
        FacebookAPIAdapter $FacebookAdapter,
        Sender $FacebookSender,
        MessageService $messageBlocks,
        DefaultReplyService $defaultReplies,
        AutoReplyRuleService $AIResponses,
        MessageHistoryRepositoryInterface $messageInstanceRepo,
        BotService $pages,
        BroadcastService $broadcasts,
        TemplateService $templates,
        UserRepositoryInterface $userRepo,
        BotRepositoryInterface $botRepo,
        TemplateRepositoryInterface $templateRepo
    ) {
        $this->subscribers = $subscribers;
        $this->welcomeMessage = $welcomeMessage;
        $this->FacebookAdapter = $FacebookAdapter;
        $this->messageBlocks = $messageBlocks;
        $this->defaultReplies = $defaultReplies;
        $this->AIResponses = $AIResponses;
        $this->FacebookSender = $FacebookSender;
        $this->messageInstanceRepo = $messageInstanceRepo;
        $this->bots = $pages;
        $this->broadcasts = $broadcasts;
        $this->userRepo = $userRepo;
        $this->templates = $templates;
        $this->botRepo = $botRepo;
        $this->templateRepo = $templateRepo;
    }

    /**
     * Subscribe a message sender to the page.
     * @param Bot    $bot
     * @param string $senderId
     * @param bool   $silentMode
     * @return Subscriber
     */
    public function subscribe(Bot $bot, $senderId, $silentMode = false)
    {
        $subscriber = $this->subscriber($senderId, $bot);

        // If already subscribed
        if ($subscriber && $subscriber->active) {
            if (! $silentMode) {
                $message = [
                    'message' => [
                        'text' => 'You are already subscribed to the page.'
                    ],
                ];
                $this->FacebookAdapter->send($message, $subscriber, $bot);
            }

            return $subscriber;
        }

        // If first time, then create subscriber.
        if (! $subscriber) {
            $subscriber = $this->persistSubscriber($bot, $senderId, true);
        }

        // If not first time (unsubscribed before), resubscribe him.
        if (! $subscriber->active) {
            $this->subscribers->resubscribe($senderId, $bot);
        }

        // If not silent mode, send the welcome message!
        if (! $silentMode) {
            /** @type Template $template */
            $template = $this->templateRepo->findByIdOrFail($bot->welcome_message->template_id);
            // @todo dispatch a new job for this.
            $this->FacebookAdapter->sendMessages($template, $subscriber, $bot);
        }

        return $subscriber;
    }

    /**
     * Subscribe a user without actually sending him the welcome message.
     * @param Bot  $bot
     * @param      $senderId
     * @return Subscriber
     */
    public function subscribeSilently(Bot $bot, $senderId)
    {
        return $this->subscribe($bot, $senderId, true);
    }


    /**
     * Send a message to the user, asking if he really wants to unsubscribe.
     * @param Bot             $page
     * @param Subscriber|null $subscriber
     * @param                 $facebookId
     */
    public function initiateUnsubscripingProcess(Bot $page, $subscriber, $facebookId)
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
        if (! $subscriber->active) {
            $message = [
                'message' => [
                    'text' => 'You have already unsubscribed from this page.'
                ],
            ];
            $this->FacebookAdapter->send($message, $subscriber, $page);

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

        $this->FacebookAdapter->send($message, $subscriber, $page);
    }

    /**
     * User has confirmed his willingness to unsubscribe, so unsubscribe him!
     * @param Bot        $page
     * @param Subscriber $subscriber
     */
    public function concludeUnsubscriptionProcess(Bot $page, $subscriber)
    {
        // already unsubscribed
        if (! $subscriber || ! $subscriber->active) {
            $message = [
                'message' => [
                    'text' => 'You have already unsubscribed from this page.'
                ],
            ];
            $this->FacebookAdapter->send($message, $subscriber, $page);

            return;
        }

        $this->subscribers->unsubscribe($subscriber);

        $message = [
            'message' => [
                'text' => 'You have successfully unsubscribed. Use "start" to subscribe again.'
            ],
        ];

        $this->FacebookAdapter->send($message, $subscriber, $page);
    }

    /**
     * Send the default reply.
     * @param Bot        $bot
     * @param Subscriber $subscriber
     */
    public function sendDefaultReply(Bot $bot, Subscriber $subscriber)
    {
        // @todo dispatch a new job for this.
        $this->FacebookAdapter->sendMessages($bot->default_reply, $subscriber);
    }

    /**
     * Handle button click.
     * @param Bot        $page
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
     * @param Bot        $page
     * @param Subscriber $subscriber
     * @param            $payload
     * @return bool
     */
    private function handleMainMenuButtonClick(Bot $page, Subscriber $subscriber, $payload)
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
     * @param Bot        $page
     * @param Subscriber $subscriber
     * @param            $payload
     * @return bool
     */
    private function handleNonMainMenuButtonClick(Bot $page, Subscriber $subscriber, $payload)
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
     * @param string $botId
     * @param string $buttonId
     * @return null
     */
    public function getMainMenuButtonUrl($botId, $buttonId)
    {
        /** @type Bot $bot */
        $bot = $this->botRepo->findById($botId);
        if (! $bot) {
            return null;
        }

        $button = array_first($bot->main_menu->buttons, function (Button $button) use ($buttonId) {
            return $button->id->__toString() === $buttonId;
        });

        if (! $button || ! $button->url) {
            return null;
        }

        $this->botRepo->incrementMainMenuButtonClicks($bot, $button);

        return $button->url;
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

        if (! ($subscriber = $this->subscribers->find($subscriberId))) {
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
            $tags = $button->addTags->pluck('id')->toArray();
            $this->subscribers->syncTags($subscriber, $tags, false);
        }

        if ($button->removeTags->count()) {
            $tags = $button->removeTags->pluck('id')->toArray();
            $this->subscribers->detachTags($subscriber, $tags);
        }

        if ($template = $button->template) {
            // @todo dispatch a new job for this.
            $this->FacebookAdapter->sendMessages($template, $subscriber);
        }
    }


    /**
     * Get a bot by page facebook ID.
     * @param $facebookId
     * @return Bot
     */
    public function bot($facebookId)
    {
        return $this->bots->findByFacebookId($facebookId);
    }

    /**
     * Get a subscriber by Facebook ID.
     * @param      $senderId
     * @param Bot  $page
     * @return Subscriber|null
     */
    public function subscriber($senderId, Bot $page)
    {
        return $this->subscribers->findByFacebookId($senderId, $page);
    }

    /**
     * Get matching AI Rules.
     * @param      $message
     * @param Bot  $page
     * @return AutoReplyRule
     */
    public function matchingAutoReplyRule($message, Bot $page)
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
        // @todo dispatch a new job for this.
        $this->FacebookAdapter->sendMessages($rule->template, $subscriber);
    }

    /**
     * Mark all messages sent to a subscriber before a specific date as read.
     * @param Subscriber $subscriber
     * @param int        $timestamp
     */
    public function markMessageBlocksAsDelivered(Subscriber $subscriber, $timestamp)
    {
        $timestamp = $this->normalizeTimestamp($timestamp);

        //        $this->messageInstanceRepo->markAsDelivered($subscriber, $timestamp);
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
        // @todo the root context Should be identified from the payload itself.
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
     * @param Bot  $bot
     * @param      $senderId
     * @return bool
     */
    public function subscribePageUser($payload, Bot $bot, $senderId)
    {
        // The page admin payload has a special prefix, followed by his internal artificial ID.
        $prefix = 'SUBSCRIBE_OWNER_';
        if (! starts_with($payload, $prefix)) {
            return false;
        }

        // Parsing his ID.
        if (! ($userId = substr($payload, strlen($prefix)))) {
            return false;
        }

        // Getting user.
        if (! ($user = $this->userRepo->findByIdForBot($userId, $bot))) {
            return false;
        }

        // Subscribing user (message sender)
        $subscriber = $this->subscribeSilently($bot, $senderId);

        // Associating user with subscriber.
        $this->botRepo->setSubscriberForUser($user, $subscriber, $bot);

        notify_frontend("{$bot->id}_{$user->id}_subscriptions", 'subscribed', ['subscriber_id' => $subscriber->id]);

        return true;
    }

    /**
     * @param Bot  $page
     * @param      $senderId
     * @param      $isActive
     * @return Subscriber|null
     */
    public function persistSubscriber(Bot $page, $senderId, $isActive)
    {
        return $this->subscribers->getByFacebookIdOrCreate($senderId, $page, $isActive);
    }
}