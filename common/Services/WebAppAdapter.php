<?php namespace Common\Services;

use Exception;
use Carbon\Carbon;
use Common\Models\Bot;
use Common\Models\Button;
use MongoDB\BSON\ObjectID;
use Common\Models\Message;
use Common\Models\Template;
use Common\Models\Subscriber;
use Common\Models\AutoReplyRule;
use Common\Models\MessageRevision;
use Common\Jobs\CarryOutCardButtonActions;
use Common\Jobs\CarryOutTextButtonActions;
use Common\Exceptions\InactiveBotException;
use Common\Exceptions\MessageNotSentException;
use Common\Repositories\Bot\BotRepositoryInterface;
use Common\Repositories\User\UserRepositoryInterface;
use Common\Repositories\Inbox\InboxRepositoryInterface;
use Common\Repositories\Template\TemplateRepositoryInterface;
use Common\Repositories\Broadcast\BroadcastRepositoryInterface;
use Common\Services\Facebook\MessengerSender as FacebookSender;
use Common\Repositories\Subscriber\SubscriberRepositoryInterface;
use Common\Repositories\SentMessage\SentMessageRepositoryInterface;
use Common\Repositories\AutoReplyRule\AutoReplyRuleRepositoryInterface;
use Common\Repositories\MessageRevision\MessageRevisionRepositoryInterface;

class WebAppAdapter
{

    use LoadsAssociatedModels;

    /**
     * @type FacebookSender
     */
    protected $FacebookSender;
    /**
     * @type SubscriberService
     */
    private $subscribers;
    /**
     * @type FacebookMessageSender
     */
    private $FacebookMessageSender;
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
     * @type FacebookAdapter
     */
    private $FacebookAdapter;
    /**
     * @type InboxRepositoryInterface
     */
    private $inboxRepo;

    /**
     * WebAppAdapter constructor.
     *
     * @param MessageService                     $messages
     * @param FacebookSender                     $FacebookSender
     * @param SubscriberService                  $subscribers
     * @param BotRepositoryInterface             $botRepo
     * @param FacebookAdapter                    $FacebookAdapter
     * @param UserRepositoryInterface            $userRepo
     * @param TemplateRepositoryInterface        $templateRepo
     * @param BroadcastRepositoryInterface       $broadcastRepo
     * @param FacebookMessageSender              $FacebookMessageSender
     * @param SubscriberRepositoryInterface      $subscriberRepo
     * @param SentMessageRepositoryInterface     $sentMessageRepo
     * @param AutoReplyRuleRepositoryInterface   $autoReplyRuleRepo
     * @param InboxRepositoryInterface           $inboxRepo
     * @param MessageRevisionRepositoryInterface $messageRevisionRepo
     */
    public function __construct(
        MessageService $messages,
        FacebookSender $FacebookSender,
        SubscriberService $subscribers,
        BotRepositoryInterface $botRepo,
        FacebookAdapter $FacebookAdapter,
        UserRepositoryInterface $userRepo,
        InboxRepositoryInterface $inboxRepo,
        TemplateRepositoryInterface $templateRepo,
        BroadcastRepositoryInterface $broadcastRepo,
        FacebookMessageSender $FacebookMessageSender,
        SubscriberRepositoryInterface $subscriberRepo,
        SentMessageRepositoryInterface $sentMessageRepo,
        AutoReplyRuleRepositoryInterface $autoReplyRuleRepo,
        MessageRevisionRepositoryInterface $messageRevisionRepo
    ) {
        $this->botRepo = $botRepo;
        $this->messages = $messages;
        $this->userRepo = $userRepo;
        $this->inboxRepo = $inboxRepo;
        $this->subscribers = $subscribers;
        $this->templateRepo = $templateRepo;
        $this->broadcastRepo = $broadcastRepo;
        $this->subscriberRepo = $subscriberRepo;
        $this->FacebookSender = $FacebookSender;
        $this->sentMessageRepo = $sentMessageRepo;
        $this->FacebookAdapter = $FacebookAdapter;
        $this->autoReplyRuleRepo = $autoReplyRuleRepo;
        $this->messageRevisionRepo = $messageRevisionRepo;
        $this->FacebookMessageSender = $FacebookMessageSender;
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
                $this->FacebookMessageSender->sendBotMessage(BotRepositoryInterface::MESSAGE_ALREADY_SUBSCRIBED, $bot, $subscriber);
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
            $this->FacebookMessageSender->sendFromTemplateWrapper($bot->welcome_message, $subscriber, $bot);
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

            try {
                $this->FacebookAdapter->sendMessage($bot, $message, false);
            } catch (MessageNotSentException $e) {
            }

            return;
        }

        // already unsubscribed
        if (! $subscriber->active) {
            $this->FacebookMessageSender->sendBotMessage(BotRepositoryInterface::MESSAGE_ALREADY_UNSUBSCRIBED, $bot, $subscriber);

            return;
        }
    }

    /**
     * User has confirmed his willingness to unsubscribe, so unsubscribe him!
     * @param Bot        $bot
     * @param Subscriber $subscriber
     */
    public function concludeUnsubscriptionProcess(Bot $bot, $subscriber)
    {
        // already unsubscribed
        if (! $subscriber || ! $subscriber->active) {
            $this->FacebookMessageSender->sendBotMessage(BotRepositoryInterface::MESSAGE_ALREADY_UNSUBSCRIBED, $bot, $subscriber);

            return;
        }

        $this->subscribers->unsubscribe($subscriber);
        $this->FacebookMessageSender->sendBotMessage(BotRepositoryInterface::MESSAGE_SUCCESSFUL_UNSUBSCRIPTION, $bot, $subscriber);
    }

    /**
     * Send the default reply.
     *
     * @param Bot        $bot
     * @param Subscriber $subscriber
     */
    public function sendDefaultReply(Bot $bot, Subscriber $subscriber)
    {
        $this->FacebookMessageSender->sendFromTemplateWrapper($bot->default_reply, $subscriber, $bot);
    }

    /**
     * Handle button click.
     * @param Bot        $bot
     * @param Subscriber $subscriber
     * @param string     $payload
     * @return string|null|false
     */
    public function handlePostbackButtonClick($payload, Bot $bot, Subscriber $subscriber)
    {
        if (strlen($payload) < 3) {
            return null;
        }

        switch (substr($payload, 0, 2)) {
            case 'tb':
                return $this->handleTextPostbackButtonClick(substr($payload, 3), $bot, $subscriber);
            case 'cb':
                return $this->handleCardPostbackButtonClick(substr($payload, 3), $bot, $subscriber);
            case 'mm':
                return $this->handlePostbackMainMenuButtonClick(substr($payload, 3), $bot, $subscriber);
            default:
                return null;
        }
    }

    /**
     * Get a bot by page facebook ID.
     * @param string $facebookId
     * @return Bot
     */
    public function bot($facebookId)
    {
        return $this->botRepo->findByFacebookId($facebookId);
    }

    /**
     * Get a subscriber by Facebook ID.
     * @param string $senderId
     * @param Bot    $bot
     * @return Subscriber|null
     */
    public function subscriber($senderId, Bot $bot)
    {
        return $this->subscriberRepo->findByFacebookIdForBot($senderId, $bot);
    }

    /**
     * Get matching AI Rules.
     * @param string $text
     * @param Bot    $bot
     * @return AutoReplyRule
     */
    public function matchingAutoReplyRule($text, Bot $bot)
    {
        return $this->autoReplyRuleRepo->getMatchingRuleForBot($text, $bot);
    }

    /**
     * Send an auto reply.
     * @param AutoReplyRule $rule
     * @param Subscriber    $subscriber
     */
    public function sendAutoReply(AutoReplyRule $rule, Subscriber $subscriber)
    {
        $this->loadModelsIfNotLoaded($rule, ['template']);
        $this->FacebookMessageSender->sendTemplate($rule->template, $subscriber);
    }

    /**
     * Mark all messages sent to a subscriber before a specific date as read.
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
     * Make a user (page admin) into "subscriber" for a page.
     * @param string $payload
     * @param Bot    $bot
     * @param string $senderId
     * @return Subscriber
     */
    public function subscribeBotUser($payload, Bot $bot, $senderId)
    {
        // The page admin payload has a special prefix, followed by his internal artificial ID.
        $prefix = 'SUBSCRIBE_OWNER_';
        if (! starts_with($payload, $prefix)) {
            return null;
        }

        // Parsing his ID.
        if (! ($userId = substr($payload, strlen($prefix)))) {
            return null;
        }

        // Getting user.
        if (! ($user = $this->userRepo->findByIdForBot($userId, $bot))) {
            return null;
        }

        // Subscribing user (message sender)
        $subscriber = $this->subscribeSilently($bot, $senderId);

        // Associating user with subscriber.
        $this->botRepo->setSubscriberForUser($user, $subscriber, $bot);

        notify_frontend("{$bot->id}_{$user->id}_subscriptions", 'subscribed', ['subscriber_id' => $subscriber->id]);

        return $subscriber;
    }

    /**
     * @param array           $event
     * @param Bot             $bot
     * @param Subscriber|null $subscriber
     */
    public function storeIncomingOptInMessage(array $event, Bot $bot, Subscriber $subscriber)
    {
        //        $data = [
        //            'optin'     => 1,
        //            'action_at' => mongo_date($event['timestamp'])
        //        ];
        //        $this->storeIncomingMessage($data, $bot, $subscriber);
    }

    /**
     * @param array           $event
     * @param Bot             $bot
     * @param Subscriber|null $subscriber
     */
    public function storeIncomingTextMessage(array $event, Bot $bot, Subscriber $subscriber = null)
    {
        //        $data = [
        //            'message'     => ['text' => $event['message']['text'], 'seq' => $event['message']['seq']],
        //            'facebook_id' => $event['message']['mid'],
        //            'action_at'   => mongo_date($event['timestamp'])
        //        ];
        //        $this->storeIncomingMessage($data, $bot, $subscriber);
    }

    /**
     * @param int             $timestamp
     * @param Bot             $bot
     * @param Subscriber|null $subscriber
     */
    public function storeIncomingGetStartedButtonClick($timestamp, $bot, $subscriber)
    {
        //        $data = [
        //            'message'   => ['get_started' => true],
        //            'action_at' => mongo_date($timestamp)
        //        ];
        //        $this->storeIncomingMessage($data, $bot, $subscriber);
    }

    /**
     * @param string          $title
     * @param int             $timestamp
     * @param Bot             $bot
     * @param Subscriber|null $subscriber
     */
    public function storeIncomingButtonClick($title, $timestamp, Bot $bot, Subscriber $subscriber = null)
    {
        //        $data = [
        //            'message'   => ['button' => $title],
        //            'action_at' => mongo_date($timestamp),
        //        ];
        //        $this->storeIncomingMessage($data, $bot, $subscriber);
    }

    /**
     * @param array           $data
     * @param Bot             $bot
     * @param Subscriber|null $subscriber
     * @internal param array $event
     * @internal param int $timestamp
     * @internal param Bot $bot
     * @internal param Subscriber|null $subscriber
     */
    public function storeIncomingMessage(array $data, Bot $bot, Subscriber $subscriber = null)
    {
        //        $data = array_merge($data, [
        //            'incoming'      => 1,
        //            'bot_id'        => $bot->_id,
        //            'subscriber_id' => null,
        //        ]);
        //
        //        if ($subscriber) {
        //            $data['subscriber_id'] = $subscriber->_id;
        //            $this->subscriberRepo->update($subscriber, ['last_interaction_at' => Carbon::now()]);
        //        }
        //
        //        $this->inboxRepo->create($data);
    }

    /**
     * @param string $payload
     * @return null|string
     */
    public function handleUrlMainMenuButtonClick($payload)
    {
        try {
            $payload = EncryptionService::Instance()->decrypt($payload);
            $revisionId =
                substr($payload, 00, 06) .
                substr($payload, 12, 06) .
                substr($payload, 18, 06) .
                substr($payload, 06, 06);
            $revisionId = new ObjectID($revisionId);
            /** @var MessageRevision $revision */
            $revision = $this->messageRevisionRepo->findById($revisionId);
            /** @var Bot $bot */
            $bot = $this->botRepo->findById($revision->bot_id);
            if (! $bot->enabled) {
                throw new InactiveBotException();
            }
            $this->messageRevisionRepo->recordMainMenuButtonClick($revisionId, $bot, null);

            return valid_url($revision->url)? $revision->url : "http://{$revision->url}";
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param string     $payload
     * @param Bot        $bot
     * @param Subscriber $subscriber
     * @return false|null
     */
    public function handlePostbackMainMenuButtonClick($payload, Bot $bot, Subscriber $subscriber)
    {
        try {
            $clean = [];
            $segmented = explode('|', $payload);
            foreach ($segmented as $section) {
                $temp = explode(':', $section);
                $clean[$temp[0]] = $temp[1];
            }
            $clean['r'] = new ObjectID($clean['r']);
            /** @var MessageRevision $revision */
            $revision = $this->messageRevisionRepo->findById($clean['r']);
            $this->carryOutButtonActions($revision, $bot, $subscriber);
            $this->messageRevisionRepo->recordMainMenuButtonClick($clean['r'], $bot, $subscriber);

            return false;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param $payload
     * @return null|string
     */
    public function handleCardClick($payload)
    {
        try {
            $clean = [];
            $decrypted = explode('|', EncryptionService::Instance()->decrypt($payload));
            foreach ($decrypted as $section) {
                $temp = explode(':', $section);
                $clean[$temp[0]] = $temp[1];
            }
            $clean['m'] = new ObjectID($clean['m']);
            $clean['i'] = new ObjectID($clean['i']);
            if (isset($clean['b'])) {
                $clean['b'] = new ObjectID($clean['b']);
                $clean['t'] = new ObjectID($clean['t']);
                $clean['o'] = new ObjectID($clean['o']);
                $clean['s'] = new ObjectID($clean['s']);
                /** @var Template $template */
                $template = $this->templateRepo->findById($clean['t']);
                /** @var Bot $bot */
                $bot = $this->botRepo->findById($template->bot_id);
                if (! $bot->enabled) {
                    throw new InactiveBotException();
                }
                $cardContainer = array_map(function (Message $message) use ($clean) {
                    return $message->id == $clean['o'];
                }, $template->messages);
                $card = null;
                $cardIndex = -1;
                foreach ($cardContainer->cards as $i => $revisionCard) {
                    if ($revisionCard->id == $clean['i'] && $revisionCard->url) {
                        $card = $revisionCard;
                        $cardIndex = $i;
                        break;
                    }
                }
                $this->sentMessageRepo->recordCardClick($clean['m'], $cardIndex);
                $this->broadcastRepo->recordClick($bot, $clean['b'], $clean['s']);

                return valid_url($card->url)? $card->url : "http://{$card->url}";
            }

            $clean['r'] = new ObjectID($clean['r']);
            /** @var MessageRevision $revision */
            $revision = $this->messageRevisionRepo->findById($clean['r']);
            /** @var Bot $bot */
            $bot = $this->botRepo->findById($revision->bot_id);
            if (! $bot->enabled) {
                throw new InactiveBotException();
            }
            $card = null;
            $cardIndex = -1;
            foreach ($revision->cards as $i => $revisionCard) {
                if ($revisionCard->id == $clean['i'] && $revisionCard->url) {
                    $card = $revisionCard;
                    $cardIndex = $i;
                    break;
                }
            }
            $this->sentMessageRepo->recordCardClick($clean['m'], $cardIndex);

            return valid_url($card->url)? $card->url : "http://{$card->url}";
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param string     $payload
     * @param Bot        $bot
     * @param Subscriber $subscriber
     * @return null|string
     */
    public function handleTextPostbackButtonClick($payload, Bot $bot, Subscriber $subscriber)
    {
        try {
            $clean = [];
            $segmented = explode('|', $payload);
            foreach ($segmented as $section) {
                $temp = explode(':', $section);
                $clean[$temp[0]] = $temp[1];
            }
            $clean['m'] = new ObjectID($clean['m']);
            $clean['i'] = new ObjectID($clean['i']);
            if (isset($clean['b'])) {
                $clean['b'] = new ObjectID($clean['b']);
                $clean['t'] = new ObjectID($clean['t']);
                $clean['o'] = new ObjectID($clean['o']);
                /** @var Template $template */
                $template = $this->templateRepo->findById($clean['t']);
                /** @var Bot $bot */
                $bot = $this->botRepo->findById($template->bot_id);
                if (! $bot->enabled) {
                    throw new InactiveBotException();
                }
                $text = array_map(function (Message $message) use ($clean) {
                    return $message->id == $clean['o'] && $message->buttons;
                }, $template->messages);
                $button = null;
                $buttonIndex = -1;
                foreach ($text->buttons as $i => $revisionButton) {
                    if ($revisionButton->id == $clean['i']) {
                        $button = $revisionButton;
                        $buttonIndex = $i;
                        break;
                    }
                }
                $this->carryOutButtonActions($button, $bot, $subscriber);
                $this->sentMessageRepo->recordTextButtonClick($clean['m'], $buttonIndex);
                $this->broadcastRepo->recordClick($bot, $clean['b'], $subscriber->_id);

                return $button->title;
            }

            $clean['r'] = new ObjectID($clean['r']);
            /** @var MessageRevision $revision */
            $revision = $this->messageRevisionRepo->findById($clean['r']);
            $button = null;
            $buttonIndex = -1;
            foreach ($revision->buttons as $i => $revisionButton) {
                if ($revisionButton->id == $clean['i']) {
                    $button = $revisionButton;
                    $buttonIndex = $i;
                    break;
                }
            }

            $this->carryOutButtonActions($button, $bot, $subscriber);
            $this->sentMessageRepo->recordTextButtonClick($clean['m'], $buttonIndex);

            return $button->title;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param string     $payload
     * @param Bot        $bot
     * @param Subscriber $subscriber
     * @return null|string
     */
    public function handleCardPostbackButtonClick($payload, Bot $bot, Subscriber $subscriber)
    {
        try {
            $clean = [];
            $segmented = explode('|', $payload);
            foreach ($segmented as $section) {
                $temp = explode(':', $section);
                $clean[$temp[0]] = $temp[1];
            }

            $clean['m'] = new ObjectID($clean['m']);
            $clean['i'] = new ObjectID($clean['i']);
            if (isset($clean['b'])) {
                $clean['b'] = new ObjectID($clean['b']);
                $clean['t'] = new ObjectID($clean['t']);
                $clean['o'] = new ObjectID($clean['o']);
                /** @var Template $template */
                $template = $this->templateRepo->findById($clean['t']);
                /** @var Bot $bot */
                $bot = $this->botRepo->findById($template->bot_id);
                if (! $bot->enabled) {
                    throw new InactiveBotException();
                }
                $cardContainer = array_map(function (Message $message) use ($clean) {
                    return $message->id == $clean['o'];
                }, $template->messages);
                $card = null;
                $cardIndex = -1;
                foreach ($cardContainer->cards as $i => $revisionCard) {
                    if ($revisionCard->id == $clean['i'] && $revisionCard->buttons) {
                        $card = $revisionCard;
                        $cardIndex = $i;
                        break;
                    }
                }
                /** @var Button $button */
                $button = null;
                $buttonIndex = -1;
                foreach ($card->buttons as $i => $cardButton) {
                    if ($cardButton->id == $clean['i']) {
                        $button = $cardButton;
                        $buttonIndex = $i;
                        break;
                    }
                }
                $this->carryOutButtonActions($button, $bot, $subscriber);
                $this->sentMessageRepo->recordCardButtonClick($clean['m'], $cardIndex, $buttonIndex);
                $this->broadcastRepo->recordClick($bot, $clean['b'], $subscriber->_id);

                return $button->title;
            }

            $clean['r'] = new ObjectID($clean['r']);
            /** @var MessageRevision $revision */
            $revision = $this->messageRevisionRepo->findById($clean['r']);
            $card = null;
            $cardIndex = -1;
            foreach ($revision->cards as $i => $revisionCard) {
                if ($revisionCard->id == $clean['c'] && $revisionCard->buttons) {
                    $card = $revisionCard;
                    $cardIndex = $i;
                    break;
                }
            }

            /** @var Button $button */
            $button = null;
            $buttonIndex = -1;
            foreach ($card->buttons as $i => $cardButton) {
                if ($cardButton->id == $clean['i']) {
                    $button = $cardButton;
                    $buttonIndex = $i;
                    break;
                }
            }

            $this->carryOutButtonActions($button, $bot, $subscriber);
            $this->sentMessageRepo->recordCardButtonClick($clean['m'], $cardIndex, $buttonIndex);

            return $button->title;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param $payload
     * @return null|string
     */
    public function handleTextUrlButtonClick($payload)
    {
        try {
            $clean = [];
            $decrypted = explode('|', EncryptionService::Instance()->decrypt($payload));
            foreach ($decrypted as $section) {
                $temp = explode(':', $section);
                $clean[$temp[0]] = $temp[1];
            }
            $clean['m'] = new ObjectID($clean['m']);
            $clean['i'] = new ObjectID($clean['i']);
            $clean['s'] = new ObjectID($clean['s']);
            if (isset($clean['b'])) {
                $clean['b'] = new ObjectID($clean['b']);
                $clean['t'] = new ObjectID($clean['t']);
                $clean['o'] = new ObjectID($clean['o']);
                /** @var Template $template */
                $template = $this->templateRepo->findById($clean['t']);
                /** @var Bot $bot */
                $bot = $this->botRepo->findById($template->bot_id);
                if (! $bot->enabled) {
                    throw new InactiveBotException();
                }
                $text = array_map(function (Message $message) use ($clean) {
                    return $message->id == $clean['o'] && $message->buttons;
                }, $template->messages);
                $button = null;
                $buttonIndex = -1;
                foreach ($text->buttons as $i => $revisionButton) {
                    if ($revisionButton->id == $clean['i'] && $revisionButton->url) {
                        $button = $revisionButton;
                        $buttonIndex = $i;
                        break;
                    }
                }
                dispatch(new CarryOutTextButtonActions($button, $buttonIndex, $bot, $clean['s'], $clean['m']));
                $this->broadcastRepo->recordClick($bot, $clean['b'], $clean['s']);

                return valid_url($button->url)? $button->url : "http://{$button->url}";
            }

            $clean['r'] = new ObjectID($clean['r']);
            /** @var MessageRevision $revision */
            $revision = $this->messageRevisionRepo->findById($clean['r']);
            /** @var Bot $bot */
            $bot = $this->botRepo->findById($revision->bot_id);
            $button = null;
            $buttonIndex = -1;
            foreach ($revision->buttons as $i => $revisionButton) {
                if ($revisionButton->id == $clean['i'] && $revisionButton->url) {
                    $button = $revisionButton;
                    $buttonIndex = $i;
                    break;
                }
            }
            dispatch(new CarryOutTextButtonActions($button, $buttonIndex, $bot, $clean['s'], $clean['m']));

            return valid_url($button->url)? $button->url : "http://{$button->url}";
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param $payload
     * @return null|string
     */
    public function handleCardUrlButtonClick($payload)
    {
        try {
            $clean = [];
            $decrypted = explode('|', EncryptionService::Instance()->decrypt($payload));
            foreach ($decrypted as $section) {
                $temp = explode(':', $section);
                $clean[$temp[0]] = $temp[1];
            }

            $clean['c'] = new ObjectID($clean['c']);
            $clean['m'] = new ObjectID($clean['m']);
            $clean['i'] = new ObjectID($clean['i']);
            $clean['s'] = new ObjectID($clean['s']);
            if (isset($clean['b'])) {
                $clean['b'] = new ObjectID($clean['b']);
                $clean['t'] = new ObjectID($clean['t']);
                $clean['o'] = new ObjectID($clean['o']);
                /** @var Template $template */
                $template = $this->templateRepo->findById($clean['t']);
                /** @var Bot $bot */
                $bot = $this->botRepo->findById($template->bot_id);
                if (! $bot->enabled) {
                    throw new InactiveBotException();
                }
                $cardContainer = array_map(function (Message $message) use ($clean) {
                    return $message->id == $clean['o'];
                }, $template->messages);
                $card = null;
                $cardIndex = -1;
                foreach ($cardContainer->cards as $i => $revisionCard) {
                    if ($revisionCard->id == $clean['c'] && $revisionCard->buttons) {
                        $card = $revisionCard;
                        $cardIndex = $i;
                        break;
                    }
                }
                /** @var Button $button */
                $button = null;
                $buttonIndex = -1;
                foreach ($card->buttons as $i => $cardButton) {
                    if ($cardButton->id == $clean['i'] && $cardButton->url) {
                        $button = $cardButton;
                        $buttonIndex = $i;
                        break;
                    }
                }
                dispatch(new CarryOutCardButtonActions($button, $buttonIndex, $cardIndex, $bot, $clean['s'], $clean['m']));
                $this->broadcastRepo->recordClick($bot, $clean['b'], $clean['s']);

                return valid_url($button->url)? $button->url : "http://{$button->url}";
            }

            $clean['r'] = new ObjectID($clean['r']);
            /** @var MessageRevision $revision */
            $revision = $this->messageRevisionRepo->findById($clean['r']);
            /** @var Bot $bot */
            $bot = $this->botRepo->findById($revision->bot_id);
            if (! $bot->enabled) {
                throw new InactiveBotException();
            }
            $card = null;
            $cardIndex = -1;
            foreach ($revision->cards as $i => $revisionCard) {
                if ($revisionCard->id == $clean['c'] && $revisionCard->buttons) {
                    $card = $revisionCard;
                    $cardIndex = $i;
                    break;
                }
            }
            /** @var Button $button */
            $button = null;
            $buttonIndex = -1;
            foreach ($card->buttons as $i => $cardButton) {
                if ($cardButton->id == $clean['i'] && $cardButton->url) {
                    $button = $cardButton;
                    $buttonIndex = $i;
                    break;
                }
            }
            dispatch(new CarryOutCardButtonActions($button, $buttonIndex, $cardIndex, $bot, $clean['s'], $clean['m']));

            return valid_url($button->url)? $button->url : "http://{$button->url}";

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param Button   $button
     * @param Bot      $bot
     * @param ObjectID $subscriberId
     * @param ObjectID $sentMessageId
     * @param int      $buttonIndex
     */
    public function carryOutTextButtonActions(Button $button, Bot $bot, ObjectID $subscriberId, ObjectID $sentMessageId, $buttonIndex)
    {
        /** @var Subscriber $subscriber */
        $subscriber = $this->subscriberRepo->findByIdForBot($subscriberId, $bot->_id);
        $this->carryOutButtonActions($button, $bot, $subscriber);
        $this->sentMessageRepo->recordTextButtonClick($sentMessageId, $buttonIndex);
    }

    /**
     * @param Button   $button
     * @param Bot      $bot
     * @param ObjectID $subscriberId
     * @param ObjectID $sentMessageId
     * @param int      $buttonIndex
     * @param int      $cardIndex
     */
    public function carryOutCardButtonActions(Button $button, Bot $bot, ObjectID $subscriberId, ObjectID $sentMessageId, $buttonIndex, $cardIndex)
    {
        /** @var Subscriber $subscriber */
        $subscriber = $this->subscriberRepo->findByIdForBot($subscriberId, $bot->_id);
        $this->carryOutButtonActions($button, $bot, $subscriber);
        $this->sentMessageRepo->recordCardButtonClick($sentMessageId, $cardIndex, $buttonIndex);
    }

    /**
     * @param Button|MessageRevision $button
     * @param Bot                    $bot
     * @param Subscriber             $subscriber
     */
    protected function carryOutButtonActions($button, Bot $bot, Subscriber $subscriber)
    {
        if ($button->add_tags || $button->remove_tags) {
            $this->subscriberRepo->bulkAddRemoveTagsAndSequences($bot, [$subscriber->_id], object_only($button, ['add_tags', 'remove_tags']));
        }

        if ($button->template_id) {
            $this->FacebookMessageSender->sendFromTemplateWrapper($button, $subscriber, $bot);
        } else {
            if ($button->messages) {
                $this->FacebookMessageSender->sendMessageArray($button->messages, $subscriber, $bot);
            }
        }

        if ($button->unsubscribe) {
            $this->concludeUnsubscriptionProcess($bot, $subscriber);
        }
    }


}