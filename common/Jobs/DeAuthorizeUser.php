<?php namespace Common\Jobs;

use Common\Models\Bot;
use Common\Repositories\Bot\BotRepositoryInterface;
use Common\Repositories\User\UserRepositoryInterface;

class DeAuthorizeUser extends BaseJob
{

    protected $userFacebookId;

    /**
     * DeAuthorizeUser constructor.
     * @param $facebookId
     */
    public function __construct($facebookId)
    {
        $this->userFacebookId = $facebookId;
    }

    /**
     * Execute the job.
     * @param UserRepositoryInterface $userRepo
     * @param BotRepositoryInterface  $botRepo
     */
    public function handle(UserRepositoryInterface $userRepo, BotRepositoryInterface $botRepo)
    {
        $user = $userRepo->findByFacebookId($this->userFacebookId);
        if (! $user) {
            return;
        }

        $bots = $botRepo->getAllForUser($user);

        $bots->each(function (Bot $bot) use ($user, $botRepo) {
            $update = ['users' => []];
            $userMeta = null;
            foreach ($bot->users as $botUser) {
                if ($botUser['user_id'] == $user->_id) {
                    $userMeta = $botUser;
                } else {
                    $update['users'][] = $botUser;
                }
            }

            if ($bot->access_token == $userMeta['access_token']) {
                $newUserMeta = array_first($update['users'], function ($userMeta) {
                    return ! is_null($userMeta['access_token']);
                });

                if ($newUserMeta) {
                    $update['access_token'] = $newUserMeta['access_token'];
                } else {
                    $update['access_token'] = null;
                    $update['enabled'] = false;
                }
            }

            $botRepo->update($bot, $update);
        });

        $userRepo->delete($user);
    }
}