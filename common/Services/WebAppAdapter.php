<?php namespace Common\Services;

use Carbon\Carbon;
use Common\Models\Bot;
use Common\Models\Button;
use Common\Models\Subscriber;
use Common\Models\AutoReplyRule;
use Common\Models\Template;
use Common\Repositories\Bot\BotRepositoryInterface;
use Common\Repositories\User\UserRepositoryInterface;
use Common\Services\Facebook\Sender as FacebookSender;
use Common\Repositories\Template\TemplateRepositoryInterface;
use Common\Repositories\Broadcast\BroadcastRepositoryInterface;
use Common\Repositories\Subscriber\SubscriberRepositoryInterface;
use Common\Repositories\SentMessage\SentMessageRepositoryInterface;
use Common\Repositories\AutoReplyRule\AutoReplyRuleRepositoryInterface;
use Common\Repositories\MessageRevision\MessageRevisionRepositoryInterface;
use MongoDB\BSON\ObjectID;

class WebAppAdapter
{

    use LoadsAssociatedModels;

    const UNSUBSCRIBE_PAYLOAD = "UNSUBSCRIBE";
    /**
     * @type FacebookSender
     */
    protected $FacebookSender;
    /**
     * @type SubscriberService
     */
    private $subscribers;
    /**
     * @type FacebookAPIAdapter
     */
    private $FacebookAdapter;
    /**
     * @type MessageService
     */
    private $messages;
    /**
     * @type SentMessageRepositoryInterface
     */
    private $sentMessageRepo;
    /**
     * @type UserRepositoryInterface
     */
    private $userRepo;
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
     * @type AutoReplyRuleRepositoryInterface
     */
    private $autoReplyRuleRepo;
    /**
     * @type BroadcastRepositoryInterface
     */
    private $broadcastRepo;
    /**
     * @type MessageRevisionRepositoryInterface
     */
    private $messageRevisionRepo;

    /**
     * WebAppAdapter constructor.
     *
     * @param MessageService                     $messages
     * @param FacebookSender                     $FacebookSender
     * @param SubscriberService                  $subscribers
     * @param BotRepositoryInterface             $botRepo
     * @param UserRepositoryInterface            $userRepo
     * @param FacebookAPIAdapter                 $FacebookAdapter
     * @param TemplateRepositoryInterface        $templateRepo
     * @param BroadcastRepositoryInterface       $broadcastRepo
     * @param SubscriberRepositoryInterface      $subscriberRepo
     * @param SentMessageRepositoryInterface     $sentMessageRepo
     * @param AutoReplyRuleRepositoryInterface   $autoReplyRuleRepo
     * @param MessageRevisionRepositoryInterface $messageRevisionRepo
     */
    public function __construct(
        MessageService $messages,
        FacebookSender $FacebookSender,
        SubscriberService $subscribers,
        BotRepositoryInterface $botRepo,
        UserRepositoryInterface $userRepo,
        FacebookAPIAdapter $FacebookAdapter,
        TemplateRepositoryInterface $templateRepo,
        BroadcastRepositoryInterface $broadcastRepo,
        SubscriberRepositoryInterface $subscriberRepo,
        SentMessageRepositoryInterface $sentMessageRepo,
        AutoReplyRuleRepositoryInterface $autoReplyRuleRepo,
        MessageRevisionRepositoryInterface $messageRevisionRepo
    ) {
        $this->botRepo = $botRepo;
        $this->messages = $messages;
        $this->userRepo = $userRepo;
        $this->subscribers = $subscribers;
        $this->templateRepo = $templateRepo;
        $this->broadcastRepo = $broadcastRepo;
        $this->subscriberRepo = $subscriberRepo;
        $this->FacebookSender = $FacebookSender;
        $this->FacebookAdapter = $FacebookAdapter;
        $this->sentMessageRepo = $sentMessageRepo;
        $this->autoReplyRuleRepo = $autoReplyRuleRepo;
        $this->messageRevisionRepo = $messageRevisionRepo;
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
            $subscriber = $this->subscribers->getByFacebookIdOrCreate($senderId, $bot, true);
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
     * @param string $botId
     * @param string $buttonId
     * @param string $revisionId
     * @return null|string
     */
    public function handleUrlMainMenuButtonClick($botId, $buttonId, $revisionId)
    {
        try {
            $botId = new ObjectID($botId);
            $buttonId = new ObjectID($buttonId);
            $revisionId = new ObjectID($revisionId);
        } catch (\Exception $e) {
            return null;
        }

        /** @type Bot $bot */
        $bot = $this->botRepo->findById($botId);
        if (! $bot) {
            return null;
        }

        /** @type Button $button */
        $button = array_first($bot->main_menu->buttons, function (Button $button) use ($buttonId) {
            return (string)$button->id == $buttonId;
        });

        if (! $button || ! $button->url) {
            return null;
        }

        $this->messageRevisionRepo->recordMainMenuButtonClick($revisionId, $bot, null);

        if (valid_url($button->url)) {
            return $button->url;
        }

        return "http://{$button->url}";
    }

    /**
     * Return the redirect URL from a button/card, via the message block hash.
     *
     * @param $payload
     * @return null|string
     */
    public function handleUrlMessageClick($payload)
    {
        $decoder = MessagePayloadDecoder::factory($payload);
        $message = $decoder->getClickedMessage();
        if (! $message || ! $message->url) {
            return null;
        }

        $this->handleClick($decoder);

        if (valid_url($message->url)) {
            return $message->url;
        }

        return "http://{$message->url}";
    }

    /**
     * Handle button click.
     *
     * @param Bot        $bot
     * @param Subscriber $subscriber
     * @param string     $payload
     *
     * @return bool
     */
    public function handlePostbackButtonClick($payload, Bot $bot, Subscriber $subscriber = null)
    {
        $decoder = MessagePayloadDecoder::factory($payload, $bot, $subscriber);
        $message = $decoder->getClickedMessage();
        if (! $message || $message->type != 'button') {
            return false;
        }

        $this->handleClick($decoder);

        return true;
    }

    /**
     * @param MessagePayloadDecoder $decoder
     */
    protected function handleClick(MessagePayloadDecoder $decoder)
    {
        $bot = $decoder->getBot();
        $message = $decoder->getClickedMessage();
        $subscriber = $decoder->getSubscriber();

        if ($message->type == 'button') {
            $this->carryOutButtonActions($message, $subscriber, $bot, $decoder->getTemplate(), $decoder->getTemplatePath());
        }

        if ($decoder->isMainMenuButton()) {
            return $this->messageRevisionRepo->recordMainMenuButtonClick($decoder->getMainMenuButtonRevisionId(), $bot, $subscriber);
        }

        $sentMessage = $decoder->getSentMessageInstance();
        $sentMessagePath = $decoder->getSentMessagePath();
        $this->sentMessageRepo->recordClick($sentMessage, $sentMessagePath, mongo_date());

        if ($broadcastId = $decoder->getBroadcastId()) {
            $this->broadcastRepo->recordClick($bot, $broadcastId, $subscriber->_id);
        }
    }

    /**
     * Execute the actions associate with a button.
     *
     * @param Button     $button
     * @param Subscriber $subscriber
     * @param Bot        $bot
     * @param Template   $buttonTemplate
     * @param array      $buttonPath
     */
    protected function carryOutButtonActions(Button $button, Subscriber $subscriber, Bot $bot, Template $buttonTemplate = null, array $buttonPath = null)
    {
        if ($button->actions['add_tags'] || $button->actions['remove_tags'] || $button->actions['add_sequences'] || $button->actions['remove_sequences']) {
            $this->subscriberRepo->bulkAddRemoveTagsAndSequences($bot, [$subscriber->_id], $button->actions);
        }

        if ($button->template_id || $button->messages) {
            $this->FacebookAdapter->sendFromButton($button, $subscriber, $bot, $buttonTemplate, (array)$buttonPath);
        }
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
        return $this->botRepo->findByFacebookId($facebookId);
    }

    /**
     * Get a subscriber by Facebook ID.
     *
     * @param      $senderId
     * @param Bot  $bot
     *
     * @return Subscriber|null
     */
    public function subscriber($senderId, Bot $bot)
    {
        return $this->subscriberRepo->findByFacebookIdForBot($senderId, $bot);
    }

    /**
     * Get matching AI Rules.
     *
     * @param      $message
     * @param Bot  $bot
     *
     * @return AutoReplyRule
     */
    public function matchingAutoReplyRule($message, Bot $bot)
    {
        return $this->autoReplyRuleRepo->getMatchingRuleForBot($message, $bot);
    }

    /**
     * Send an auto reply.
     *
     * @param AutoReplyRule $rule
     * @param Subscriber    $subscriber
     */
    public function sendAutoReply(AutoReplyRule $rule, Subscriber $subscriber)
    {
        $this->loadModelsIfNotLoaded($rule, ['template']);
        $this->FacebookAdapter->sendTemplate($rule->template, $subscriber);
    }

    /**
     * Mark all messages sent to a subscriber before a specific date as read.
     *
     * @param Subscriber $subscriber
     * @param int        $timestamp
     */
    public function markMessagesAsDelivered(Subscriber $subscriber, $timestamp)
    {
        $timestamp = mongo_date($timestamp);
        $this->sentMessageRepo->markAsDelivered($subscriber, $timestamp);
    }

    /**
     * Mark all messages sent to a subscriber before a specific date as read.
     *
     * @param Subscriber $subscriber
     * @param            $timestamp
     */
    public function markMessagesAsRead(Subscriber $subscriber, $timestamp)
    {
        // if a message is read, then it is definitely delivered.
        // this is to handle Facebook sometimes not sending the delivery callback.
        $timestamp = mongo_date($timestamp);
        $this->sentMessageRepo->markAsRead($subscriber, $timestamp);
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
     * Make a user (page admin) into "subscriber" for a page.
     *
     * @param      $payload
     * @param Bot  $bot
     * @param      $senderId
     *
     * @return bool
     */
    public function subscribeBotUser($payload, Bot $bot, $senderId)
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
     * @param Subscriber $subscriber
     */
    public function storeIncomingInteraction($subscriber)
    {
        if ($subscriber) {
            $subscriber->last_interaction_at = Carbon::now();
            $subscriber->save();
        }
    }
}