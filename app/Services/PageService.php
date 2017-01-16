<?php namespace App\Services;

use App\Models\AutoReplyRule;
use App\Models\DefaultReply;
use App\Models\GreetingText;
use App\Models\MainMenu;
use App\Models\Page;
use App\Models\WelcomeMessage;
use App\Services\Facebook\Subscription;
use App\Services\Facebook\Thread;
use App\Models\User;
use App\Services\Facebook\PageService as FacebookPage;
use DB;
use Illuminate\Database\Eloquent\Collection;

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
     * PageService constructor.
     *
     * @param FacebookPage          $FacebookPages
     * @param Subscription          $FacebookSubscriptions
     * @param Thread                $FacebookThreads
     * @param WelcomeMessageService $welcomeMessages
     * @param GreetingTextService   $greetingTexts
     * @param MainMenuService       $mainMenus
     * @param AutoReplyRuleService  $autoReplyRules
     * @param TagService            $tags
     */
    public function __construct(
        FacebookPage $FacebookPages,
        Subscription $FacebookSubscriptions,
        Thread $FacebookThreads,
        WelcomeMessageService $welcomeMessages,
        GreetingTextService $greetingTexts,
        MainMenuService $mainMenus,
        AutoReplyRuleService $autoReplyRules,
        TagService $tags
    ) {
        $this->FacebookPages = $FacebookPages;
        $this->FacebookSubscriptions = $FacebookSubscriptions;
        $this->FacebookThreads = $FacebookThreads;
        $this->welcomeMessages = $welcomeMessages;
        $this->greetingTexts = $greetingTexts;
        $this->mainMenus = $mainMenus;
        $this->autoReplyRules = $autoReplyRules;
        $this->tags = $tags;
    }

    /**
     * @param User $user
     * @param      $pageIds
     */
    public function createBotForPages(User $user, $pageIds)
    {
        DB::transaction(function () use ($user, $pageIds) {

            $remotePages = $this->getUnmanagedPages($user)->keyBy('facebook_id');

            $createdPageIds = [];

            foreach ($pageIds as $facebookId) {
                $page = $remotePages->get($facebookId);

                if (! $page) {
                    continue;
                }

                if (! $page->exists) {
                    $this->persistPage($page);
                }

                $createdPageIds[] = $page->id;
            }

            $user->pages()->sync($createdPageIds, false);
        });
    }

    /**
     * @param Page $page
     */
    protected function persistPage(Page $page)
    {
        $page->save();

        $this->createDefaultTags($page);
        $this->createDefaultGreetingText($page);
        $this->createDefaultWelcomeMessage($page);
        $this->createDefaultMainMenu($page);
        $this->createDefaultDefaultReply($page);
        $this->createDefaultAutoReplyRules($page);
        $this->initializeFacebookBot($page);
    }


    /**
     * @param User $user
     *
     * @return Collection
     */
    public function getUnmanagedPages(User $user)
    {
        $remotePages = $this->FacebookPages->getManagePageList($user->access_token);

        return $this->normalizeRemotePages($user, $remotePages);
    }

    /**
     * @param User $user
     *
     * @return Collection
     */
    public function activePageList(User $user)
    {
        return $user->pages()->whereIsActive(1)->get();
    }


    /**
     * @param User $user
     *
     * @return Collection
     */
    public function inactivePageList($user)
    {
        return $user->pages()->whereIsActive(0)->get();
    }


    /**
     * @param User  $user
     * @param array $remotePages
     * @return Collection
     */
    private function normalizeRemotePages(User $user, $remotePages)
    {
        $ret = new Collection();
        foreach ((array)$remotePages as $remotePage) {

            if ($user->pages()->whereFacebookId($remotePage->id)->exists()) {
                continue;
            }

            if (! ($page = Page::whereFacebookId($remotePage->id)->first())) {
                $page = new Page();
                $page->facebook_id = $remotePage->id;
                $page->name = $remotePage->name;
                $page->access_token = $remotePage->access_token;
                $page->avatar_url = $remotePage->picture->data->url;
                $page->url = $remotePage->link;
            }

            $ret->add($page);
        }

        return $ret;
    }

    /**
     * @param Page $page
     *
     * @return array
     */
    private function createDefaultTags(Page $page)
    {
        return $this->tags->getOrCreateTags(['new'], $page);
    }

    /**
     * @param Page $page
     *
     * @return GreetingText
     */
    private function createDefaultGreetingText(Page $page)
    {
        $greetingText = $this->greetingTexts->defaultGreetingText($page);
        $page->greetingText()->save($greetingText);

        return $greetingText;
    }

    /**
     * @param Page $page
     *
     * @return WelcomeMessage
     */
    private function createDefaultWelcomeMessage(Page $page)
    {
        $welcomeMessage = new WelcomeMessage();
        $page->welcomeMessage()->save($welcomeMessage);

        $this->welcomeMessages->attachDefaultMessageBlocks($welcomeMessage);

        return $welcomeMessage;
    }


    /**
     * @param Page $page
     *
     * @return MainMenu
     */
    private function createDefaultMainMenu(Page $page)
    {
        $mainMenu = new MainMenu();
        $page->mainMenu()->save($mainMenu);

        $this->mainMenus->attachDefaultButtonsToMainMenu($mainMenu);

        return $mainMenu;
    }

    /**
     * @param Page $page
     *
     * @return DefaultReply
     */
    private function createDefaultDefaultReply(Page $page)
    {
        $defaultReply = new DefaultReply();
        $page->defaultReply()->save($defaultReply);

        return $defaultReply;
    }

    /**
     * @param Page $page
     */
    private function createDefaultAutoReplyRules(Page $page)
    {
        $data = [
            'subscribe'   => ['start', 'subscribe'],
            'unsubscribe' => ['stop', 'unsubscribe']
        ];

        $rules = [];

        foreach ($data as $action => $keywords) {
            foreach ($keywords as $keyword) {
                $rule = new AutoReplyRule();
                $rule->mode = 'is';
                $rule->keyword = $keyword;
                $rule->action = $action;
                $rule->is_disabled = true;
                $rules[] = $data;
                $page->autoReplyRules()->save($rule);
            }
        }
    }

    /**
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
        $page->is_active = false;
        $page->save();

        return $page;
    }

    /**
     * @param Page $page
     * @return Page
     */
    public function enableBot(Page $page)
    {
        $page->is_active = true;
        $page->save();

        return $page;
    }

}