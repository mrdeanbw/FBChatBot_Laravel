<?php namespace Common\Repositories\Bot;

use Common\Models\Bot;
use Common\Models\User;
use Common\Models\Button;
use Illuminate\Pagination\Paginator;
use MongoDB\BSON\ObjectID;
use Common\Models\Subscriber;
use Illuminate\Support\Collection;
use Common\Repositories\DBBaseRepository;

class DBBotRepository extends DBBaseRepository implements BotRepositoryInterface
{

    public function model()
    {
        return Bot::class;
    }

    /**
     * Find a bot by its id.
     * @param      $botId
     * @param User $user
     * @return Bot|null
     */
    public function findByIdForUser($botId, User $user)
    {
        $filter = [
            ['operator' => '=', 'key' => '_id', 'value' => $botId],
            ['operator' => '=', 'key' => 'users.user_id', 'value' => $user->_id]
        ];

        return $this->getOne($filter);
    }

    /**
     * Find an enabled bot by its id.
     * @param      $botId
     * @param User $user
     * @return Bot|null
     */
    public function findEnabledByIdForUser($botId, User $user)
    {
        $filter = [
            ['operator' => '=', 'key' => '_id', 'value' => $botId],
            ['operator' => '=', 'key' => 'enabled', 'value' => true],
            ['operator' => '=', 'key' => 'users.user_id', 'value' => $user->_id]
        ];

        return $this->getOne($filter);
    }

    /**
     * Find a disabled bot by its id.
     * @param      $botId
     * @param User $user
     * @return Bot|null
     */
    public function findDisabledByIdForUser($botId, User $user)
    {
        $filter = [
            ['operator' => '=', 'key' => '_id', 'value' => $botId],
            ['operator' => '=', 'key' => 'enabled', 'value' => false],
            ['operator' => '=', 'key' => 'users.user_id', 'value' => $user->_id]
        ];

        return $this->getOne($filter);
    }

    /**
     * Get the list of active pages that belong to a user.
     * @param User $user
     * @param int  $page
     * @param int  $perPage
     * @return Paginator
     */
    public function paginateEnabledForUser(User $user, $page, $perPage)
    {
        $filter = [
            ['operator' => '=', 'key' => 'enabled', 'value' => true],
            ['operator' => '=', 'key' => 'users.user_id', 'value' => $user->_id]
        ];

        return $this->paginate($page, $filter, [], $perPage);
    }

    /**
     * Get the list of inactive pages that belong to a user.
     * @param User $user
     * @param int  $page
     * @param int  $perPage
     * @return Paginator
     */
    public function paginateDisabledForUser(User $user, $page, $perPage)
    {
        $filter = [
            ['operator' => '=', 'key' => 'enabled', 'value' => false],
            ['operator' => '=', 'key' => 'users.user_id', 'value' => $user->_id]
        ];

        return $this->paginate($page, $filter, [], $perPage);
    }

    /**
     * Find a page by its Facebook id.
     * @param $id
     * @return Bot
     */
    public function findByFacebookId($id)
    {
        $filter = [['operator' => '=', 'key' => 'page.id', 'value' => $id]];

        return $this->getOne($filter);
    }

    /**
     * @param Bot      $bot
     * @param ObjectID $userId
     * @param string   $accessToken
     */
    public function addUserToBot(Bot $bot, ObjectID $userId, $accessToken)
    {
        $update = ['$push' => ['users' => ['user_id' => $userId, 'subscriber_id' => null, 'access_token' => $accessToken]]];

        // If the bot doesn't have an active access token, then use the new one.
        if (is_null($bot->access_token)) {
            $update['$set'] = ['access_token' => $accessToken];
        }

        $this->update($bot, $update);
    }

    /**
     * @param Bot      $bot
     * @param ObjectID $userId
     * @param string   $accessToken
     * @return bool
     * @throws \Exception
     */
    public function updateBotUser(Bot $bot, ObjectID $userId, $accessToken)
    {
        foreach ($bot->users as $i => $user) {
            if ($user['user_id'] == $userId) {
                $update = ["users.{$i}.access_token" => $accessToken];
                if (is_null($bot->access_token)) {
                    $update['access_token'] = $accessToken;
                }

                return $this->update($bot, $update);
            }
        }

        throw new \Exception("User not found");
    }

    /**
     * @param User $user
     * @param Bot  $bot
     * @return Subscriber
     */
    public function getSubscriberForUser(User $user, Bot $bot)
    {
        $admin = array_first($bot->users, function ($admin) use ($user) {
            return $admin['user_id'] == $user->_id;
        });

        if (! $admin) {
            return null;
        }

        return Subscriber::find($admin['subscriber_id']);
    }

    /**
     * @param User       $user
     * @param Subscriber $subscriber
     * @param Bot        $bot
     */
    public function setSubscriberForUser(User $user, Subscriber $subscriber, Bot $bot)
    {
        Bot::where('_id', $bot->_id)->where('users.user_id', $user->_id)->update([
            'users.$.subscriber_id' => $subscriber->_id
        ]);
    }

    /**
     * @param       $botId
     * @param array $tags
     */
    public function createTagsForBot($botId, array $tags)
    {
        Bot::where('_id', $botId)->push('tags', $tags, true);
    }

    /**
     * @param Bot    $bot
     * @param Button $button
     */
    public function incrementMainMenuButtonClicks(Bot $bot, Button $button)
    {
        Bot::where('_id', $bot->_id)->where('main_menu.buttons.id', $button->id)->increment('main_menu.buttons.$.clicks.total');
    }
}
