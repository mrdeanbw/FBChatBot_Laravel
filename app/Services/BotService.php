<?php namespace App\Services;

use App\Jobs\AddGetStartedButtonOnFacebook;
use App\Jobs\UpdateGreetingTextOnFacebook;
use App\Jobs\UpdateMainMenuOnFacebook;
use Common\Models\Bot;
use Common\Models\Page;
use Common\Models\User;
use Common\Models\WelcomeMessage;
use MongoDB\BSON\ObjectID;
use Illuminate\Support\Collection;
use App\Services\Facebook\MessengerThread;
use App\Jobs\SubscribeAppToFacebookPage;
use Common\Repositories\Bot\BotRepositoryInterface;
use Common\Repositories\User\UserRepositoryInterface;
use App\Services\Facebook\PageService as FacebookPage;

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
     * @param BotRepositoryInterface  $botRepo
     */
    public function __construct(
        FacebookPage $FacebookPages,
        MessengerThread $FacebookThreads,
        WelcomeMessageService $welcomeMessages,
        DefaultReplyService $defaultReplies,
        GreetingTextService $greetingTexts,
        MainMenuService $mainMenus,
        AutoReplyRuleService $autoReplyRules,
        UserRepositoryInterface $userRepo,
        PageService $pages,
        BotRepositoryInterface $botRepo
    ) {
        $this->pages = $pages;
        $this->botRepo = $botRepo;
        $this->userRepo = $userRepo;
        $this->mainMenus = $mainMenus;
        $this->FacebookPages = $FacebookPages;
        $this->greetingTexts = $greetingTexts;
        $this->autoReplyRules = $autoReplyRules;
        $this->FacebookThreads = $FacebookThreads;
        $this->welcomeMessages = $welcomeMessages;
        $this->defaultReplies = $defaultReplies;
    }

    /**
     * @param User $user
     * @return Collection
     */
    public function enabledBots(User $user)
    {
        return $this->botRepo->getEnabledForUser($user);
    }

    /**
     * @param User $user
     * @return Collection
     */
    public function disabledBots($user)
    {
        return $this->botRepo->getDisabledForUser($user);
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

            // If the user doesn't already manages the bot, append the bot id to the list.
            if (! $this->userRepo->managesBotForFacebookPage($user, $bot)) {
                $createdBots[] = $bot;
            }
        }

        if ($createdBots) {
            $this->botRepo->addUserToBots(array_pluck($createdBots, 'id'), $user);
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
            'users'           => []
        ];

        /** @type Bot $bot */
        $bot = $this->botRepo->create($data);

        $this->createDefaultAutoReplyRules($bot);

        dispatch(new SubscribeAppToFacebookPage($bot, $user->id));
        dispatch(new AddGetStartedButtonOnFacebook($bot, $user->id));
        dispatch(new UpdateMainMenuOnFacebook($bot, $user->id));
        dispatch(new UpdateGreetingTextOnFacebook($bot, $user->id));

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
     * @param $botId
     * @return mixed
     */
    private function getDefaultDefaultReply($botId)
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
     * @param Bot $bot
     * @return Bot
     */
    public function enableBot(Bot $bot)
    {
        return $this->botRepo->update($bot, ['enabled' => true]);
    }

    /**
     * @param Bot $bot
     * @return Bot
     */
    public function disableBot(Bot $bot)
    {
        return $this->botRepo->update($bot, ['enabled' => false]);
    }

    /**
     * @param array $input
     * @param Bot   $bot
     */
    public function updateTimezone(array $input, Bot $bot)
    {
        return $this->botRepo->update($bot, array_only($input, ['timezone', 'timezone_offset']));
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
     * @param      $botId
     * @param User $user
     * @return mixed
     */
    public function findByIdForUser($botId, User $user)
    {
        return $this->botRepo->findByIdForUser($botId, $user);
    }
}