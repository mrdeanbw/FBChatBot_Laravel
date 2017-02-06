<?php namespace App\Repositories\Bot;

use App\Models\Bot;
use App\Models\User;
use App\Models\Subscriber;
use Illuminate\Support\Collection;
use App\Repositories\BaseDBRepository;

class DBBotRepository extends BaseDBRepository implements BotRepositoryInterface
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
        return Bot::where('users.user_id', $user->id)->whereEnabled(true)->get();
    }

    /**
     * Get the list of inactive pages that belong to a user.
     * @param User $user
     * @return Collection
     */
    public function getDisabledForUser(User $user)
    {
        return Bot::where('users.user_id', $user->id)->whereEnabled(false)->get();
    }

    /**
     * Find a page by its Facebook id.
     * @param $id
     * @return Bot
     */
    public function findByFacebookId($id)
    {
        return Bot::where('page.id', $id)->first();
    }

    /**
     * Find a page by its Facebook id.
     * @param      $botId
     * @param User $user
     * @return Bot|null
     */
    public function findByIdForUser($botId, User $user)
    {
        return Bot::where('users.user_id', $user->id)->find($botId);
    }

    /**
     * @param array $botIds
     * @param User  $user
     */
    public function addUserToBots(array $botIds, User $user)
    {
        Bot::whereIn('_id', $botIds)->push('users', [
            'user_id'       => $user->id,
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
            return $admin['user_id'] === $user->id;
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
        Bot::where('_id', $bot->id)->where('users.user_id', $user->id)->update([
            'users.$.subscriber_id' => $subscriber->id
        ]);
    }

    /**
     * @param Bot   $bot
     * @param array $tags
     */
    public function createTagsForBot(Bot $bot, array $tags)
    {
        $bot->push('tags', $tags, true);
    }
}
