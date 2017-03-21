<?php namespace Common\Services;

use Carbon\Carbon;
use Common\Models\Bot;
use Common\Models\Page;
use Common\Models\Text;
use Common\Models\User;
use MongoDB\BSON\ObjectID;
use Common\Models\DefaultReply;
use Common\Models\WelcomeMessage;
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
     * @param array $pageIds
     * @return Collection
     */
    public function createBotForPages(User $user, $pageIds)
    {
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

            // In some cases, the bot may be created before (e.g. by another admin)
            // If the bot doesn't exist in our system, create it.
            $bot = isset($page->bot)? $page->bot : $this->createBot($page, $user);

            if ($this->userRepo->managesBotForFacebookPage($user, $bot)) {
                $this->botRepo->updateBotUser($bot, $user->_id, $page->access_token);
            } else {
                $this->botRepo->addUserToBot($bot, $user->_id, $page->access_token);
                $this->initializeBotOnFacebook($user, $bot);
            }

            $createdBots[] = $bot;
        }

        return new Collection($createdBots);
    }

    /**
     * Create a bot instance.
     * @param Page $page
     * @param User $user
     * @return Bot
     */
    protected function createBot(Page $page, User $user)
    {
        $id = new ObjectID(null);
        $data = [
            '_id'             => $id,
            'page'            => $page,
            'enabled'         => true,
            'timezone'        => 'UTC',
            'timezone_offset' => 0,
            'tags'            => $this->getDefaultTags(),
            'greeting_text'   => $this->getDefaultGreetingText($page->name),
            'welcome_message' => $this->getDefaultWelcomeMessage($id),
            'main_menu'       => $this->getDefaultMainMenu($id),
            'default_reply'   => $this->getDefaultDefaultReply($id),
            'access_token'    => $page->access_token,
            'users'           => [
                ['user_id' => $user->_id, 'subscriber_id' => null, 'access_token' => $page->access_token]
            ],
            'messages'        => $this->getDefaultMessages($id)
        ];

        /** @type Bot $bot */
        $bot = $this->botRepo->create($data);

        $this->createDefaultAutoReplyRules($bot);

        $this->initializeBotOnFacebook($user, $bot);

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
     * @param string $pageName
     * @return array
     */
    private function getDefaultGreetingText($pageName)
    {
        return $this->greetingTexts->defaultGreetingText($pageName);
    }

    /**
     * @param $botId
     * @return WelcomeMessage
     */
    private function getDefaultWelcomeMessage($botId)
    {
        return $this->welcomeMessages->defaultWelcomeMessage($botId);
    }

    /**
     * @param ObjectID $botId
     * @return DefaultReply
     */
    private function getDefaultDefaultReply(ObjectID $botId)
    {
        return $this->defaultReplies->defaultDefaultReply($botId);
    }

    /**
     * @param $botId
     * @return array
     */
    private function getDefaultMainMenu($botId)
    {
        return $this->mainMenus->defaultMainMenu($botId);
    }

    /**
     * @param Bot $bot
     * @return bool
     */
    private function createDefaultAutoReplyRules(Bot $bot)
    {
        return $this->autoReplyRules->createDefaultAutoReplyRules($bot);
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
    }

    /**
     * @param Bot $bot
     * @return Bot
     */
    public function disableBot(Bot $bot)
    {
        $this->terminateBotOnFacebook($bot);
        $this->botRepo->update($bot, ['enabled' => false]);
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
     * @param $botId
     * @return Bot
     */
    public function findById($botId)
    {
        return $this->botRepo->findById($botId);
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
     * @param string    $botId
     * @param User      $user
     * @param bool|null $enabled
     * @return Bot|null
     */
    public function findByIdAndStatusForUser($botId, User $user, $enabled)
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
     * @return array
     */
    private function getDefaultMessages(ObjectID $botId)
    {
        $alreadySubscribed = new Text(['text' => 'You are already subscribed to the page.']);
        $alreadyUnsubscribed = new Text(['text' => 'You have already unsubscribed from this page.']);
        $successfullyUnsubscribed = new Text(['text' => 'You have successfully unsubscribed. Use "start" to subscribe again.']);
        $unsubscriptionConfirmation = new Text([
            'text'    => 'Do you really want to unsubscribe from {{page_name}}?',
            'buttons' => [['type' => 'postback', 'title' => 'Unsubscribe', 'payload' => WebAppAdapter::UNSUBSCRIBE_PAYLOAD]]
        ]);

        $ret = [
            BotRepositoryInterface::MESSAGE_ALREADY_SUBSCRIBED        => ['template_id' => $this->templates->createImplicit([$alreadySubscribed], $botId, true)->_id],
            BotRepositoryInterface::MESSAGE_ALREADY_UNSUBSCRIBED      => ['template_id' => $this->templates->createImplicit([$alreadyUnsubscribed], $botId, true)->_id],
            BotRepositoryInterface::MESSAGE_SUCCESSFUL_UNSUBSCRIPTION => ['template_id' => $this->templates->createImplicit([$successfullyUnsubscribed], $botId, true)->_id],
            BotRepositoryInterface::MESSAGE_CONFIRM_UNSUBSCRIPTION    => ['template_id' => $this->templates->createImplicit([$unsubscriptionConfirmation], $botId, true)->_id],
        ];

        ksort($ret);

        return $ret;
    }

    /**
     * @param User $user
     * @param Bot  $bot
     */
    protected function initializeBotOnFacebook(User $user, Bot $bot)
    {
        dispatch(new SubscribeAppToFacebookPage($bot, $user->id));
        dispatch(new AddGetStartedButtonOnFacebook($bot, $user->id));
        dispatch(new UpdateMainMenuOnFacebook($bot, $user->id));
        dispatch(new UpdateGreetingTextOnFacebook($bot, $user->id));
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
}
