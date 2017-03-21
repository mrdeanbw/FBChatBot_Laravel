<?php namespace Common\Repositories\Bot;

use Common\Models\Bot;
use Common\Models\User;
use Common\Models\Button;
use MongoDB\BSON\ObjectID;
use Common\Models\Subscriber;
use Illuminate\Pagination\Paginator;
use Common\Repositories\BaseRepositoryInterface;

interface BotRepositoryInterface extends BaseRepositoryInterface
{

    const MESSAGE_ALREADY_SUBSCRIBED = 0;
    const MESSAGE_ALREADY_UNSUBSCRIBED = 1;
    const MESSAGE_CONFIRM_UNSUBSCRIPTION = 2;
    const MESSAGE_SUCCESSFUL_UNSUBSCRIPTION = 3;

    /**
     * Find a bot by its id.
     * @param      $botId
     * @param User $user
     * @return Bot|null
     */
    public function findByIdForUser($botId, User $user);

    /**
     * Find an enabled bot by its id.
     * @param      $botId
     * @param User $user
     * @return Bot|null
     */
    public function findEnabledByIdForUser($botId, User $user);

    /**
     * Find a disabled bot by its id.
     * @param      $botId
     * @param User $user
     * @return Bot|null
     */
    public function findDisabledByIdForUser($botId, User $user);

    /**
     * Find a page by its Facebook id.
     * @param $id
     * @return Bot
     */
    public function findByFacebookId($id);

    /**
     * Get the list of active pages that belong to a user.
     * @param User $user
     * @param int  $page
     * @param int  $perPage
     * @return Paginator
     */
    public function paginateEnabledForUser(User $user, $page, $perPage);

    /**
     * Get the list of inactive pages that belong to a user.
     * @param User $user
     * @param int  $page
     * @param int  $perPage
     * @return Paginator
     */
    public function paginateDisabledForUser(User $user, $page, $perPage);

    /**
     * @param Bot      $bot
     * @param ObjectID $userId
     * @param string   $accessToken
     */
    public function addUserToBot(Bot $bot, ObjectID $userId, $accessToken);

    /**
     * @param Bot      $bot
     * @param ObjectID $userId
     * @param string   $accessToken
     * @return bool
     * @throws \Exception
     */
    public function updateBotUser(Bot $bot, ObjectID $userId, $accessToken);

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
     * @param       $botId
     * @param array $tags
     */
    public function createTagsForBot($botId, array $tags);

    /**
     * @param Bot    $bot
     * @param Button $button
     */
    public function incrementMainMenuButtonClicks(Bot $bot, Button $button);
}
