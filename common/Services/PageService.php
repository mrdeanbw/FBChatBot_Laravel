<?php namespace Common\Services;

use HttpException;
use Common\Models\Page;
use Common\Models\User;
use Illuminate\Support\Collection;
use Common\Repositories\Bot\BotRepositoryInterface;
use Common\Services\Facebook\Pages as FacebookPage;
use Common\Repositories\User\UserRepositoryInterface;
use Common\Exceptions\InvalidBotAccessTokenException;

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
     * @type FacebookAdapter
     */
    private $FacebookAdapter;

    /**
     * PageService constructor.
     *
     * @param FacebookPage            $FacebookPages
     * @param UserRepositoryInterface $userRepo
     * @param BotRepositoryInterface  $botRepo
     * @param FacebookAdapter         $FacebookAdapter
     */
    public function __construct(
        FacebookPage $FacebookPages,
        FacebookAdapter $FacebookAdapter,
        UserRepositoryInterface $userRepo,
        BotRepositoryInterface $botRepo
    ) {
        $this->botRepo = $botRepo;
        $this->userRepo = $userRepo;
        $this->FacebookPages = $FacebookPages;
        $this->FacebookAdapter = $FacebookAdapter;
    }

    /**
     * @param User $user
     * @return array
     * @throws HttpException
     */
    public function getAdministratedFacebookPages(User $user)
    {
        try {
            $pages = $this->FacebookAdapter->getManagedPageList($user);
        } catch (InvalidBotAccessTokenException $e) {
            throw new HttpException(403, "missing_permissions");
        }

        return array_filter((array)$pages, function ($page) {
            return in_array('ADMINISTER', $page->perms);
        });
    }

    /**
     * Return a list of all Facebook pages for user.
     * @param User $user
     * @return Collection
     */
    public function getAllPages(User $user)
    {
        $remotePages = $this->getAdministratedFacebookPages($user);

        return $this->normalizePages($user, $remotePages, false);
    }

    /**
     * Return a list of Facebook pages that don't have bots managed by a user.
     * @param User $user
     * @return Collection
     */
    public function getUnmanagedPages(User $user)
    {
        $remotePages = $this->getAdministratedFacebookPages($user);

        return $this->normalizePages($user, $remotePages, true);
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