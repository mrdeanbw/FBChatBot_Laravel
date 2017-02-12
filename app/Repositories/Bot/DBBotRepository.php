<?php namespace App\Repositories\Bot;

use App\Models\Bot;
use App\Models\Button;
use App\Models\User;
use App\Models\Subscriber;
use Illuminate\Support\Collection;
use App\Repositories\DBBaseRepository;

class DBBotRepository extends DBBaseRepository implements BotRepositoryInterface
{

    public function model()
    {
        return Bot::class;
    }

    /**
     * Get the list of active pages that belong to a user.
     * @param User $user
     * @return Collection
     */
    public function getEnabledForUser(User $user)
    {
        $filter = [
            ['operator' => '=', 'key' => 'enabled', 'value' => true],
            ['operator' => '=', 'key' => 'users.user_id', 'value' => $user->_id]
        ];

        return $this->getAll($filter);
    }

    /**
     * Get the list of inactive pages that belong to a user.
     * @param User $user
     * @return Collection
     */
    public function getDisabledForUser(User $user)
    {
        $filter = [
            ['operator' => '=', 'key' => 'enabled', 'value' => false],
            ['operator' => '=', 'key' => 'users.user_id', 'value' => $user->_id]
        ];

        return $this->getAll($filter);
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
     * Find a page by its Facebook id.
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
     * @param array $botIds
     * @param User  $user
     */
    public function addUserToBots(array $botIds, User $user)
    {
        Bot::whereIn('_id', $botIds)->push('users', [
            'user_id'       => $user->_id,
            'subscriber_id' => null,
        ]);
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
