<?php namespace App\Services;

use DB;
use App\Models\User;
use App\Models\Page;
use App\Models\MainMenu;
use App\Models\GreetingText;
use App\Models\DefaultReply;
use App\Models\WelcomeMessage;
use App\Services\Facebook\Thread;
use App\Services\Facebook\Subscription;
use App\Repositories\Page\PageRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Services\Facebook\PageService as FacebookPage;

class PageService
{

    /**
     * @type FacebookPage
     */
    private $FacebookPages;
    /**
     * @type Subscription
     */
    private $FacebookSubscriptions;
    /**
     * @type Thread
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
     * @var TagService
     */
    private $tags;
    /**
     * @type UserRepository
     */
    private $userRepo;
    /**
     * @type PageRepository
     */
    private $pageRepo;
    /**
     * @type DefaultReplyService
     */
    private $defaultReplies;

    /**
     * PageService constructor.
     *
     * @param FacebookPage          $FacebookPages
     * @param Subscription          $FacebookSubscriptions
     * @param Thread                $FacebookThreads
     * @param WelcomeMessageService $welcomeMessages
     * @param GreetingTextService   $greetingTexts
     * @param MainMenuService       $mainMenus
     * @param DefaultReplyService   $defaultReplies
     * @param AutoReplyRuleService  $autoReplyRules
     * @param TagService            $tags
     * @param UserRepository        $userRepo
     * @param PageRepository        $pageRepository
     */
    public function __construct(
        FacebookPage $FacebookPages,
        Subscription $FacebookSubscriptions,
        Thread $FacebookThreads,
        WelcomeMessageService $welcomeMessages,
        GreetingTextService $greetingTexts,
        MainMenuService $mainMenus,
        DefaultReplyService $defaultReplies,
        AutoReplyRuleService $autoReplyRules,
        TagService $tags,
        UserRepository $userRepo,
        PageRepository $pageRepository
    ) {
        $this->FacebookPages = $FacebookPages;
        $this->FacebookSubscriptions = $FacebookSubscriptions;
        $this->FacebookThreads = $FacebookThreads;
        $this->welcomeMessages = $welcomeMessages;
        $this->greetingTexts = $greetingTexts;
        $this->mainMenus = $mainMenus;
        $this->autoReplyRules = $autoReplyRules;
        $this->tags = $tags;
        $this->userRepo = $userRepo;
        $this->pageRepo = $pageRepository;
        $this->defaultReplies = $defaultReplies;
    }

    /**
     * Create bots for Facebook pages.
     * @param User $user
     * @param      $pageIds
     */
    public function createBotForPages(User $user, $pageIds)
    {
        DB::transaction(function () use ($user, $pageIds) {

            // Get the list of Facebook pages, that don't have bots associated with them.
            // Index them by Facebook ID.
            $unmanagedPages = $this->getUnmanagedPages($user)->keyBy('facebook_id');

            // Array to hold the created page IDs .
            $createdPageIds = [];

            foreach ($pageIds as $facebookId) {
                // Get the page from the collection, if not found,
                // then it means the page id is invalid. Skip it.
                if (! ($page = $unmanagedPages->get($facebookId))) {
                    continue;
                }

                // In some cases, the page may be created before (e.g. by another admin)
                // If the page doesn't exist in our system, create it.
                if (! $page->exists) {
                    $this->persistPage($page);
                }

                $createdPageIds[] = $page->id;
            }

            // Add the pages to the list of user pages.
            $this->userRepo->syncPages($user, $createdPageIds, false);
        });
    }

    /**
     * Save a page that has been "made", but not persisted.
     * @param Page $page
     */
    protected function persistPage(Page $page)
    {
        $this->pageRepo->saveMadePage($page);

        $this->createDefaultTags($page);
        $this->createDefaultGreetingText($page);
        $this->createDefaultWelcomeMessage($page);
        $this->createDefaultMainMenu($page);
        $this->createDefaultDefaultReply($page);
        $this->createDefaultAutoReplyRules($page);
        $this->initializeFacebookBot($page);
    }


    /**
     * Return a list of Facebook pages that don't have bots created for them.
     * @param User $user
     * @return Collection
     */
    public function getUnmanagedPages(User $user)
    {
        $remotePages = $this->FacebookPages->getManagePageList($user->access_token);

        return $this->normalizeUnmanagedPages($user, (array)$remotePages);
    }

    /**
     * @param User $user
     * @return Collection
     */
    public function activePageList(User $user)
    {
        return $this->pageRepo->getActiveForUser($user);
    }

    /**
     * @param User $user
     * @return Collection
     */
    public function inactivePageList($user)
    {
        return $this->pageRepo->getInactiveForUser($user);
    }

    /**
     * Normalize the unmanaged pages by:
     * 1. Removing the pages that have bots from the list.
     * 2. Converting the unmanaged pages to our Page model (without actually saving them).
     * @param User  $user
     * @param array $FacebookPages
     * @return Collection
     */
    private function normalizeUnmanagedPages(User $user, array $FacebookPages)
    {
        $clean = new Collection();

        foreach ($FacebookPages as $FacebookPage) {

            // If the page is already managed by this user,
            // then remove it from the list.
            if ($this->userRepo->managesFacebookPage($user, $FacebookPage->id)) {
                continue;
            }

            // The page may be already in our system. (created by another admin).
            // If so, add it to the list, otherwise, normalize it (without saving),
            // then add it to the list.
            $page = $this->findByFacebookId($FacebookPage->id);
            if (! $page) {
                $page = $this->pageRepo->makePage([
                    'facebook_id'  => $FacebookPage->id,
                    'name'         => $FacebookPage->name,
                    'access_token' => $FacebookPage->access_token,
                    'avatar_url'   => $FacebookPage->picture->data->url,
                    'url'          => $FacebookPage->link
                ]);
            }

            $clean->add($page);
        }

        return $clean;
    }

    /**
     * Create the default tags for a page.
     * Currently every pages ships off with a "new" tag.
     * @param Page $page
     * @return array
     */
    private function createDefaultTags(Page $page)
    {
        return $this->tags->getOrCreateTags(['new'], $page);
    }

    /**
     * @param Page $page
     * @return GreetingText
     */
    private function createDefaultGreetingText(Page $page)
    {
        return $this->greetingTexts->createDefaultGreetingText($page);
    }

    /**
     * @param Page $page
     * @return WelcomeMessage
     */
    private function createDefaultWelcomeMessage(Page $page)
    {
        return $this->welcomeMessages->createDefaultWelcomeMessage($page);
    }

    /**
     * @param Page $page
     * @return MainMenu
     */
    private function createDefaultMainMenu(Page $page)
    {
        return $this->mainMenus->createDefaultMainMenu($page);
    }

    /**
     * @param Page $page
     * @return DefaultReply
     */
    private function createDefaultDefaultReply(Page $page)
    {
        return $this->defaultReplies->createDefaultDefaultReply($page);
    }

    /**
     * @param Page $page
     */
    private function createDefaultAutoReplyRules(Page $page)
    {
        $this->autoReplyRules->createDefaultAutoReplyRules($page);
    }

    /**
     * Initialize Facebook Bot through Facebook API.
     * @param Page $page
     */
    private function initializeFacebookBot(Page $page)
    {
        $this->FacebookSubscriptions->subscribe($page->access_token);

        $this->greetingTexts->updateGreetingTextOnFacebook($page);

        $this->FacebookThreads->addGetStartedButton($page->access_token);

        $this->mainMenus->setupFacebookPagePersistentMenu($page->mainMenu, $page);
    }

    /**
     * @param Page $page
     * @return Page
     */
    public function disableBot(Page $page)
    {
        $this->pageRepo->update($page, ['is_active' => false]);

        return $page;
    }

    /**
     * @param Page $page
     * @return Page
     */
    public function enableBot(Page $page)
    {
        $this->pageRepo->update($page, ['is_active' => false]);

        return $page;
    }

    /**
     * @param $pageId
     * @return Page
     */
    public function findByFacebookId($pageId)
    {
        return $this->pageRepo->findByFacebookId($pageId);
    }

}