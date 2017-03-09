<?php namespace App\Services;

use Common\Models\Page;
use Common\Models\User;
use Illuminate\Support\Collection;
use Common\Repositories\Bot\BotRepositoryInterface;
use Common\Repositories\User\UserRepositoryInterface;
use App\Services\Facebook\PageService as FacebookPage;

class PageService
{

    /**
     * @type UserRepositoryInterface
     */
    private $userRepo;
    /**
     * @type BotRepositoryInterface
     */
    private $botRepo;
    /**
     * @type FacebookPage
     */
    private $FacebookPages;

    /**
     * PageService constructor.
     *
     * @param FacebookPage            $FacebookPages
     * @param UserRepositoryInterface $userRepo
     * @param BotRepositoryInterface  $botRepo
     */
    public function __construct(FacebookPage $FacebookPages, UserRepositoryInterface $userRepo, BotRepositoryInterface $botRepo)
    {
        $this->botRepo = $botRepo;
        $this->userRepo = $userRepo;
        $this->FacebookPages = $FacebookPages;
    }


    /**
     * Return a list of all Facebook pages for user.
     * @param User $user
     * @return Collection
     */
    public function getAllPages(User $user)
    {
        $remotePages = $this->FacebookPages->getManagePageList($user->access_token);

        return $this->normalizePages($user, (array)$remotePages, false);
    }

    /**
     * Return a list of Facebook pages that don't have bots managed by a user.
     * @param User $user
     * @return Collection
     */
    public function getUnmanagedPages(User $user)
    {
        $remotePages = $this->FacebookPages->getManagePageList($user->access_token);

        return $this->normalizePages($user, (array)$remotePages, true);
    }

    /**
     * Normalize the unmanaged pages by:
     * 1. Removing the pages that have bots from the list.
     * 2. Converting the unmanaged pages to our Page model (without actually saving them).
     * @param User  $user
     * @param array $FacebookPages
     * @param bool  $removeUnmanagedPages
     * @return Collection
     */
    private function normalizePages(User $user, array $FacebookPages, $removeUnmanagedPages)
    {
        $clean = new Collection();

        foreach ($FacebookPages as $FacebookPage) {

            $page = new Page([
                'id'           => $FacebookPage->id,
                'name'         => $FacebookPage->name,
                'access_token' => $FacebookPage->access_token,
                'avatar_url'   => $FacebookPage->picture->data->url,
                'url'          => $FacebookPage->link
            ]);

            // The page may already have a bot in our system. (created by another admin).
            if ($bot = $this->botRepo->findByFacebookId($FacebookPage->id)) {

                // If the page is already managed by this user,
                // then remove it from the list.
                if ($removeUnmanagedPages && $this->userRepo->managesBotForFacebookPage($user, $bot)) {
                    continue;
                }

                $page->bot = $bot;
            }

            $clean->push($page);
        }

        return $clean;
    }
}