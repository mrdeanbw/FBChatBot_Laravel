<?php namespace App\Repositories\Bot;

use App\Models\Bot;
use App\Models\User;
use App\Models\Subscriber;
use Illuminate\Support\Collection;
use App\Repositories\CommonRepositoryInterface;

interface BotRepositoryInterface extends CommonRepositoryInterface
{

    /**
     * Find a page by its Facebook id.
     * @param      $botId
     * @param User $user
     * @return Bot|null
     */
    public function findByIdForUser($botId, User $user);

    /**
     * Find a page by its Facebook id.
     * @param $id
     * @return Bot
     */
    public function findByFacebookId($id);

    /**
     * Get the list of active pages that belong to a user.
     * @param User $user
     * @return Collection
     */
    public function getEnabledForUser(User $user);

    /**
     * Get the list of inactive pages that belong to a user.
     * @param User $user
     * @return Collection
     */
    public function getDisabledForUser(User $user);

    /**
     * @param array $botIds
     * @param User  $user
     */
    public function addUserToBots(array $botIds, User $user);

    /**
     * @param User $user
     * @param Bot  $bot
     * @return Subscriber
     */
    public function getSubscriberForUser(User $user, Bot $bot);


    /**
     * @param User       $user
     * @param Subscriber $subscriber
     * @param Bot        $bot
     */
    public function setSubscriberForUser(User $user, Subscriber $subscriber, Bot $bot);

    /**
     * @param Bot   $bot
     * @param array $tags
     */
    public function createTagsForBot(Bot $bot, array $tags);
}
