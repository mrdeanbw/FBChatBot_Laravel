<?php namespace Common\Services;

use Carbon\Carbon;
use Common\Models\Bot;
use Common\Models\Button;
use Common\Models\Page;
use Common\Models\Template;
use Common\Models\User;
use MongoDB\BSON\ObjectID;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Common\Jobs\UpdateMainMenuOnFacebook;
use Common\Jobs\RemoveMainMenuFromFacebook;
use Common\Jobs\SubscribeAppToFacebookPage;
use Common\Jobs\UpdateGreetingTextOnFacebook;
use Common\Services\Facebook\MessengerThread;
use Common\Jobs\AddGetStartedButtonOnFacebook;
use Common\Jobs\RemoveGreetingTextFromFacebook;
use Common\Jobs\UnsubscribeAppFromFacebookPage;
use Common\Jobs\RemoveGetStartedButtonFromFacebook;
use Common\Repositories\Bot\BotRepositoryInterface;
use Common\Services\Facebook\Pages as FacebookPage;
use Common\Repositories\User\UserRepositoryInterface;

class BotService
{

    /**
     * @type FacebookPage
     */
    private $FacebookPages;
    /**
     * @type MessengerThread
     */
    private $FacebookThreads;
    /**
     * @type WelcomeMessageService
     */
    private $welcomeMessages;
    /**
     * @type GreetingTextService
     */
    private $greetingTexts;
    /**
     * @type MainMenuService
     */
    private $mainMenus;
    /**
     * @type AutoReplyRuleService
     */
    private $autoReplyRules;
    /**
     * @type UserRepositoryInterface
     */
    private $userRepo;
    /**
     * @type BotRepositoryInterface
     */
    private $botRepo;
    /**
     * @type PageService
     */
    private $pages;
    /**
     * @type DefaultReplyService
     */
    private $defaultReplies;
    /**
     * @type TemplateService
     */
    private $templates;

    /**
     * BotService constructor.
     *
     * @param FacebookPage            $FacebookPages
     * @param MessengerThread         $FacebookThreads
     * @param WelcomeMessageService   $welcomeMessages
     * @param DefaultReplyService     $defaultReplies
     * @param GreetingTextService     $greetingTexts
     * @param MainMenuService         $mainMenus
     * @param AutoReplyRuleService    $autoReplyRules
     * @param UserRepositoryInterface $userRepo
     * @param PageService             $pages
     * @param TemplateService         $templates
     * @param BotRepositoryInterface  $botRepo
     */
    public function __construct(
        PageService $pages,
        MainMenuService $mainMenus,
        TemplateService $templates,
        FacebookPage $FacebookPages,
        BotRepositoryInterface $botRepo,
        MessengerThread $FacebookThreads,
        UserRepositoryInterface $userRepo,
        GreetingTextService $greetingTexts,
        DefaultReplyService $defaultReplies,
        AutoReplyRuleService $autoReplyRules,
        WelcomeMessageService $welcomeMessages
    ) {
        $this->pages = $pages;
        $this->botRepo = $botRepo;
        $this->userRepo = $userRepo;
        $this->mainMenus = $mainMenus;
        $this->templates = $templates;
        $this->FacebookPages = $FacebookPages;
        $this->greetingTexts = $greetingTexts;
        $this->autoReplyRules = $autoReplyRules;
        $this->defaultReplies = $defaultReplies;
        $this->FacebookThreads = $FacebookThreads;
        $this->welcomeMessages = $welcomeMessages;
    }

    /**
     * @param User $user
     * @param int  $page
     * @param int  $perPage
     * @return Paginator
     */
    public function enabledBots(User $user, $page = 1, $perPage = 20)
    {
        return $this->botRepo->paginateEnabledForUser($user, $page, $perPage);
    }

    /**
     * @param User $user
     * @param int  $page
     * @param int  $perPage
     * @return Paginator
     */
    public function disabledBots($user, $page = 1, $perPage = 12)
    {
        return $this->botRepo->paginateDisabledForUser($user, $page, $perPage);
    }

    /**
     * Create bots for Facebook pages.
     * @param User  $user
     * @param array $input
     * @return Collection
     */
    public function createBotForPages(User $user, array $input)
    {
        $pageIds = $input['pages'];
        $timezone = $input['timezone'];
        $timezoneOffset = Carbon::now($input['timezone'])->offsetHours;

        // Get the list of Facebook pages, that don't have bots associated with them.
        // Index them by Facebook ID.
        $unmanagedPages = $this->pages->getUnmanagedPages($user)->keyBy('id');

        // Array to hold the ids of created bots.
        $createdBots = [];

        foreach ($pageIds as $facebookId) {
            // Get the page from the collection, if not found,
            // then it means the page id is invalid. Skip it.
            /** @type Page $page */
            if (! ($page = $unmanagedPages->get($facebookId))) {
                continue;
            }

            $accessToken = $page->access_token;
            // In some cases, the bot may be created before (e.g. by another admin)
            // If the bot doesn't exist in our system, create it.
            $bot = isset($page->bot)? $page->bot : $this->createBot($page, $user, $timezone, $timezoneOffset);

            if ($this->userRepo->managesBotForFacebookPage($user, $bot)) {
                $this->botRepo->updateBotUser($bot, $user->_id, $accessToken);
            } else {
                $this->botRepo->addUserToBot($bot, $user->_id, $accessToken);
                $this->initializeBotOnFacebook($user, $bot);
            }

            $createdBots[] = $bot;
        }

        return new Collection($createdBots);
    }

    /**
     * Create a bot instance.
     * @param Page   $page
     * @param User   $user
     * @param string $timezone
     * @param float  $timezoneOffset
     * @return Bot
     */
    protected function createBot(Page $page, User $user, $timezone, $timezoneOffset)
    {
        $id = new ObjectID(null);

        $templates = $this->createDefaultTemplates($id);
        $defaultReplyTemplateId = array_pull($templates, 'default_reply');
        $welcomeMessageTemplateId = array_pull($templates, 'welcome_message');
        $confirmUnsubscriptionTemplateId = array_pull($templates, 'confirm_unsubscription');
        $data = [
            '_id'             => $id,
            'page'            => $page,
            'enabled'         => true,
            'timezone'        => $timezone,
            'timezone_offset' => $timezoneOffset,
            'tags'            => $this->getDefaultTags(),
            'greeting_text'   => [$this->greetingTexts->defaultGreetingText()],
            'default_reply'   => $this->defaultReplies->defaultDefaultReply($defaultReplyTemplateId),
            'welcome_message' => $this->welcomeMessages->defaultWelcomeMessage($welcomeMessageTemplateId),
            'main_menu'       => $this->getDefaultMainMenu($id),
            'access_token'    => $page->access_token,
            'users'           => [
                ['user_id' => $user->_id, 'access_token' => $page->access_token]
            ],
            'templates'       => $templates,
            'created_by'      => $user->_id
        ];

        unset($data['page']->access_token);

        /** @type Bot $bot */
        $bot = $this->botRepo->create($data);
        Template::where('_id', $welcomeMessageTemplateId)->update(['messages.1.deleted_at' => mongo_date()]);

        $this->createDefaultAutoReplyRules($bot, $confirmUnsubscriptionTemplateId);

        $this->initializeBotOnFacebook($user, $bot);

        $this->templates->messages->persistMessageRevisions();

        return $bot;
    }

    /**
     * Get the default tags for newly created bots.
     * Currently every bot ships off with a "new" tag.
     * @return array
     */
    private function getDefaultTags()
    {
        return ['new'];
    }

    /**
     * @param Bot      $bot
     * @param ObjectID $confirmUnsubscriptionTemplateId
     * @return bool
     */
    private function createDefaultAutoReplyRules(Bot $bot, ObjectID $confirmUnsubscriptionTemplateId)
    {
        return $this->autoReplyRules->createDefaultAutoReplyRules($bot, $confirmUnsubscriptionTemplateId);
    }

    /**
     * @param Bot  $bot
     * @param User $user
     * @return Bot
     */
    public function enableBot(Bot $bot, User $user)
    {
        $this->botRepo->update($bot, ['enabled' => true]);
        $this->initializeBotOnFacebook($user, $bot);

        return $bot;
    }

    /**
     * @param Bot $bot
     * @return Bot
     */
    public function disableBot(Bot $bot)
    {
        $this->terminateBotOnFacebook($bot);
        $this->botRepo->update($bot, ['enabled' => false]);

        return $bot;
    }

    /**
     * @param array $input
     * @param Bot   $bot
     * @return Bot
     */
    public function updateTimezone(array $input, Bot $bot)
    {
        $data = [
            'timezone'        => $input['timezone'],
            'timezone_offset' => Carbon::now($input['timezone'])->offsetHours,
        ];

        $this->botRepo->update($bot, $data);

        return $bot;
    }

    /**
     * @param ObjectID $botId
     * @return Bot
     */
    public function findById(ObjectID $botId)
    {
        /** @var Bot $ret */
        $ret = $this->botRepo->findById($botId);

        return $ret;
    }

    /**
     * @param $pageId
     * @return Bot
     */
    public function findByFacebookId($pageId)
    {
        return $this->botRepo->findByFacebookId($pageId);
    }

    /**
     * @param ObjectID  $botId
     * @param User      $user
     * @param bool|null $enabled
     * @return Bot|null
     */
    public function findByIdAndStatusForUser(ObjectID $botId, User $user, $enabled)
    {
        if (is_null($enabled)) {
            return $this->botRepo->findByIdForUser($botId, $user);
        }

        if ($enabled) {
            return $this->botRepo->findEnabledByIdForUser($botId, $user);
        }

        return $this->botRepo->findDisabledByIdForUser($botId, $user);
    }

    /**
     * @param ObjectID $botId
     * @return \Common\Models\MainMenu
     */
    private function getDefaultMainMenu(ObjectID $botId)
    {
        $button = new Button([
            'title'    => 'Powered By Mr. Reply',
            'readonly' => true,
            'url'      => 'https://www.mrreply.com',
        ]);

        $this->templates->messages->forMainMenuButtons(true);
        $buttons = $this->templates->messages->correspondInputMessagesToOriginal([$button], [], $botId, true);
        $this->templates->messages->forMainMenuButtons(false);

        return $this->mainMenus->defaultMainMenu($buttons);
    }

    /**
     * @param ObjectID $botId
     * @return array
     */
    private function createDefaultTemplates(ObjectID $botId)
    {
        $defaultReply = [
            '_id'      => new ObjectID(),
            'messages' => [],
            'bot_id'   => $botId
        ];

        $welcomeMessage = [
            '_id'      => new ObjectID(),
            'messages' => [
                [
                    'type' => 'text',
                    'text' => "Welcome {{first_name|fallback:}}! Thank you for subscribing. The next post is coming soon, stay tuned!\n\nP.S. If you ever want to unsubscribe just type \"stop\"."
                ],
                [
                    'type'       => 'text',
                    'text'       => 'Want to create your own bot? Go to: https://www.mrreply.com',
                    'readonly'   => true,
                ]
            ],
            'bot_id'   => $botId
        ];

        $alreadySubscribed = [
            '_id'      => new ObjectID(),
            'messages' => [['type' => 'text', 'text' => 'You are already subscribed to the page.']],
            'readonly' => true,
            'name'     => 'Already Subscribed',
            'bot_id'   => $botId
        ];

        $alreadyUnsubscribed = [
            '_id'      => new ObjectID(),
            'messages' => [['type' => 'text', 'text' => 'You have already unsubscribed from this page.']],
            'readonly' => true,
            'name'     => 'Already Unsubscribed',
            'bot_id'   => $botId
        ];

        $successfullyUnsubscribed = [
            '_id'      => new ObjectID(),
            'messages' => [['type' => 'text', 'text' => 'You have successfully unsubscribed. Use "start" to subscribe again.']],
            'readonly' => true,
            'name'     => 'Already Unsubscribed',
            'bot_id'   => $botId
        ];

        $unsubscriptionConfirmation = [
            '_id'      => new ObjectID(),
            'messages' => [
                [
                    'type'    => 'text',
                    'text'    => 'Do you really want to unsubscribe from {{page_name|fallback:our page}}?',
                    'buttons' => [['type' => 'button', 'title' => 'Unsubscribe', 'unsubscribe' => true]]
                ]
            ],
            'readonly' => true,
            'name'     => 'Confirm Unsubscription',
            'bot_id'   => $botId
        ];

        $this->templates->bulkCreate([
            $defaultReply,
            $welcomeMessage,
            $alreadySubscribed,
            $alreadyUnsubscribed,
            $successfullyUnsubscribed,
            $unsubscriptionConfirmation,
        ], true);

        $ret = [
            BotRepositoryInterface::MESSAGE_ALREADY_SUBSCRIBED        => $alreadySubscribed['_id'],
            BotRepositoryInterface::MESSAGE_ALREADY_UNSUBSCRIBED      => $alreadyUnsubscribed['_id'],
            BotRepositoryInterface::MESSAGE_SUCCESSFUL_UNSUBSCRIPTION => $successfullyUnsubscribed['_id'],
            'confirm_unsubscription'                                  => $unsubscriptionConfirmation['_id'],
            'welcome_message'                                         => $welcomeMessage['_id'],
            'default_reply'                                           => $defaultReply['_id'],
        ];

        return $ret;
    }

    /**
     * @param User $user
     * @param Bot  $bot
     */
    protected function initializeBotOnFacebook(User $user, Bot $bot)
    {
        dispatch(new SubscribeAppToFacebookPage($bot, $user->_id));
        dispatch(new AddGetStartedButtonOnFacebook($bot, $user->_id));
        dispatch(new UpdateMainMenuOnFacebook($bot, $user->_id));
        dispatch(new UpdateGreetingTextOnFacebook($bot, $user->_id));
    }

    /**
     * @param Bot $bot
     */
    public function terminateBotOnFacebook(Bot $bot)
    {
        dispatch(new UnsubscribeAppFromFacebookPage($bot));
        dispatch(new RemoveMainMenuFromFacebook($bot));
        dispatch(new RemoveGreetingTextFromFacebook($bot));
        dispatch(new RemoveGetStartedButtonFromFacebook($bot));
    }

    /**
     * @param User $user
     * @return int
     */
    public function countEnabledForUser(User $user)
    {
        return $this->botRepo->countEnabledForUser($user);
    }

    /**
     * @param User $user
     * @return int
     */
    public function countDisabledForUser(User $user)
    {
        return $this->botRepo->countDisabledForUser($user);
    }

    /**
     * @param Bot    $bot
     * @param string $tag
     * @return array
     */
    public function createTag(Bot $bot, $tag)
    {
        $this->botRepo->createTagsForBot($bot, $tag);

        return $bot->tags;
    }
}
