<?php namespace App\Services;

use App\Repositories\Subscriber\SubscriberRepositoryInterface;
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
use App\Repositories\MessageHistory\MessageHistoryRepositoryInterface;

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
     * @type SubscriberRepositoryInterface
     */
    private $subscriberRepo;

    /**
     * WebAppAdapter constructor.
     *
     * @param SubscriberService                 $subscribers
     * @param WelcomeMessageService             $welcomeMessage
     * @param FacebookAPIAdapter                $FacebookAdapter
     * @param Sender                            $FacebookSender
     * @param SubscriberRepositoryInterface     $subscriberRepo
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
        SubscriberRepositoryInterface $subscriberRepo,
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
        $this->subscriberRepo = $subscriberRepo;
    }

    /**
     * Subscribe a message sender to the page.
     *
     * @param Bot    $bot
     * @param string $senderId
     * @param bool   $silentMode
     *
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
                $this->FacebookAdapter->send($message, $subscriber, $bot->page);
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
            $this->FacebookAdapter->sendFromContext($bot->welcome_message, $subscriber, $bot);
        }

        return $subscriber;
    }

    /**
     * Subscribe a user without actually sending him the welcome message.
     *
     * @param Bot  $bot
     * @param      $senderId
     *
     * @return Subscriber
     */
    public function subscribeSilently(Bot $bot, $senderId)
    {
        return $this->subscribe($bot, $senderId, true);
    }

    /**
     * Send a message to the user, asking if he really wants to unsubscribe.
     *
     * @param Bot             $bot
     * @param Subscriber|null $subscriber
     * @param                 $facebookId
     */
    public function initiateUnsubscripingProcess(Bot $bot, $subscriber, $facebookId)
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
            $this->FacebookSender->send($bot->page->access_token, $message, false);

            return;
        }

        // already unsubscribed
        if (! $subscriber->active) {
            $message = [
                'message' => [
                    'text' => 'You have already unsubscribed from this page.'
                ],
            ];
            $this->FacebookAdapter->send($message, $subscriber, $bot->page);

            return;
        }

        // Send asking for confirmation message
        $message = [
            'message' => [
                'attachment' => [
                    'type'    => 'template',
                    'payload' => [
                        'template_type' => 'button',
                        'text'          => "Do you really want to unsubscribe from {$bot->page->name}?",
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

        $this->FacebookAdapter->send($message, $subscriber, $bot->page);
    }

    /**
     * User has confirmed his willingness to unsubscribe, so unsubscribe him!
     *
     * @param Bot        $bot
     * @param Subscriber $subscriber
     */
    public function concludeUnsubscriptionProcess(Bot $bot, $subscriber)
    {
        // already unsubscribed
        if (! $subscriber || ! $subscriber->active) {
            $message = [
                'message' => [
                    'text' => 'You have already unsubscribed from this page.'
                ],
            ];
            $this->FacebookAdapter->send($message, $subscriber, $bot->page);

            return;
        }

        $this->subscribers->unsubscribe($subscriber);

        $message = [
            'message' => [
                'text' => 'You have successfully unsubscribed. Use "start" to subscribe again.'
            ],
        ];

        $this->FacebookAdapter->send($message, $subscriber, $bot->page);
    }

    /**
     * Send the default reply.
     *
     * @param Bot        $bot
     * @param Subscriber $subscriber
     */
    public function sendDefaultReply(Bot $bot, Subscriber $subscriber)
    {
        $this->FacebookAdapter->sendFromContext($bot->default_reply, $subscriber, $bot);
    }

    /**
     * Handle button click.
     *
     * @param Bot        $bot
     * @param Subscriber $subscriber
     * @param            $payload
     *
     * @return bool
     */
    public function handleButtonClick($bot, $subscriber, $payload)
    {
        $payload = explode('|', $payload);
        $broadcastId = isset($payload[1]) ? $payload[1] : null;

        $payload = explode(':', $payload[0]);

        if ($payload[0] == 'MM') {
            return $this->handleMainMenuButtonClick($bot, $payload);
        }

        $payloadSize = count($payload);

        if (! $bot || ! $subscriber || $payloadSize < 7 || $bot->id != $payload[0] || $subscriber->id != $payload[1]) {
            return false;
        }

        /** @type Template $template */
        $template = $this->templateRepo->findByIdForBot($payload[2], $bot);
        if (! $template) {
            return false;
        }

        $buttonPath = array_slice($payload, 2);
        $button = $this->navigateToButton($template, $buttonPath);
        if (! $button) {
            return false;
        }

        $this->templateRepo->recordButtonClick($template, $subscriber, array_slice($payload, 4));

        if ($broadcastId) {
            $this->broadcastRepo->recordClick();
        }

        $this->carryOutButtonActions($button, $subscriber, $bot, $buttonPath);
    }

    /**
     * Execute the actions associate with a button.
     *
     * @param array      $buttonPath
     * @param Button     $button
     * @param Subscriber $subscriber
     * @param Bot        $bot
     */
    private function carryOutButtonActions(Button $button, Subscriber $subscriber, Bot $bot, array $buttonPath)
    {
        $this->subscriberRepo->bulkAddRemoveTagsAndSequences($bot, [$subscriber->_id], $button->actions);
        $this->FacebookAdapter->sendFromButton($button, $buttonPath, $subscriber, $bot);
    }

    /**
     * @param Template $template
     * @param array    $buttonPath
     *
     * @return Button|null
     */
    private function navigateToButton(Template $template, array $buttonPath)
    {
        $ret = $template;

        foreach ($buttonPath as $section) {

            if (is_object($ret) && isset($ret->{$section})) {
                $ret = $ret->{$section};
                continue;
            }

            if (is_array($ret) && isset($container[$section])) {
                $ret = $ret[$section];
                continue;
            }

            return null;
        }

        return is_object($ret) && is_a($ret, Button::class) ? $ret : null;
    }

    /**
     * Increase the number of clicks for a given message instance
     *
     * @param array $payload
     */
    private function incrementButtonClicks(array $payload)
    {
    }

    /**
     * Handle a main menu button click.
     *
     * @param Bot        $page
     * @param Subscriber $subscriber
     * @param            $payload
     *
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
     * @param string $botId
     * @param string $buttonId
     *
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
     *
     * @param $messageBlockHash
     * @param $subscriberHash
     *
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

        $this->incrementButtonClicks($messageInstance);

        $messageBlock = $messageInstance->message_block;

        if ($messageBlock->type == 'button') {
            $this->carryOutButtonActions($messageBlock, $subscriber);
        }

        return $messageBlock->url;
    }

    /**
     * Get a bot by page facebook ID.
     *
     * @param $facebookId
     *
     * @return Bot
     */
    public function bot($facebookId)
    {
        return $this->bots->findByFacebookId($facebookId);
    }

    /**
     * Get a subscriber by Facebook ID.
     *
     * @param      $senderId
     * @param Bot  $page
     *
     * @return Subscriber|null
     */
    public function subscriber($senderId, Bot $page)
    {
        return $this->subscribers->findByFacebookId($senderId, $page);
    }

    /**
     * Get matching AI Rules.
     *
     * @param      $message
     * @param Bot  $page
     *
     * @return AutoReplyRule
     */
    public function matchingAutoReplyRule($message, Bot $page)
    {
        return $this->AIResponses->getMatchingRule($message, $page);
    }

    /**
     * Send an auto reply.
     *
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
     *
     * @param Subscriber $subscriber
     * @param int        $timestamp
     */
    public function markMessageBlocksAsDelivered(Subscriber $subscriber, $timestamp)
    {
        //        $timestamp = $this->normalizeTimestamp($timestamp);

        //        $this->messageInstanceRepo->markAsDelivered($subscriber, $timestamp);
        //        $this->updateBroadcastDeliveredStats($subscriber, $timestamp);
    }

    /**
     * Mark all messages sent to a subscriber before a specific date as read.
     *
     * @param Subscriber $subscriber
     * @param            $timestamp
     */
    public function markMessageBlocksAsRead(Subscriber $subscriber, $timestamp)
    {
        // if a message is read, then it is definitely delivered.
        // this is to handle Facebook sometimes not sending the delivery callback.
        //        $this->markMessageBlocksAsDelivered($subscriber, $timestamp);

        //        $timestamp = $this->normalizeTimestamp($timestamp);

        //        $this->messageInstanceRepo->markAsRead($subscriber, $timestamp);
        //        $this->updateBroadcastReadStats($subscriber, $timestamp);
    }

    /**
     * Increase the number of clicks for a given message instance
     *
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
     *
     * @param AutoReplyRule $rule
     *
     * @return bool
     */
    public function isUnsubscriptionMessage(AutoReplyRule $rule)
    {
        return $rule->action == 'unsubscribe';
    }

    /**
     * If the auto reply rule should trigger subscription action
     *
     * @param AutoReplyRule $rule
     *
     * @return bool
     */
    public function isSubscriptionMessage(AutoReplyRule $rule)
    {
        return $rule->action == 'subscribe';
    }

    /**
     * Convert the timestamp sent by Facebook (in milliseconds) to date-time string.
     *
     * @param int $timestamp
     *
     * @return string
     */
    private function normalizeTimestamp($timestamp)
    {
        $timestamp = Carbon::createFromTimestamp((int)($timestamp / 1000))->toDateTimeString();

        return $timestamp;
    }

    /**
     * Mark the broadcast as delivered to subscriber
     *
     * @param Subscriber $subscriber
     * @param            $dateTime
     */
    private function updateBroadcastDeliveredStats(Subscriber $subscriber, $dateTime)
    {
        $this->broadcasts->updateBroadcastSubscriberDeliveredAt($subscriber, $dateTime);
    }

    /**
     * Mark the broadcast as read by subscriber
     *
     * @param Subscriber $subscriber
     * @param            $dateTime
     */
    private function updateBroadcastReadStats(Subscriber $subscriber, $dateTime)
    {
        $this->broadcasts->updateBroadcastSubscriberReadAt($subscriber, $dateTime);
    }

    /**
     * Make a user (page admin) into "subscriber" for a page.
     *
     * @param      $payload
     * @param Bot  $bot
     * @param      $senderId
     *
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
     *
     * @return Subscriber|null
     */
    public function persistSubscriber(Bot $page, $senderId, $isActive)
    {
        return $this->subscribers->getByFacebookIdOrCreate($senderId, $page, $isActive);
    }

}