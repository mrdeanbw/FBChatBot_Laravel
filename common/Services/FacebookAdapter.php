<?php namespace Common\Services;

use Closure;
use Common\Models\Bot;
use Common\Models\User;
use Illuminate\Support\Collection;
use Common\Services\Facebook\Users;
use Common\Services\Facebook\Pages;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\ClientException;
use Common\Exceptions\InactiveBotException;
use Common\Services\Facebook\MessengerThread;
use Common\Exceptions\DisallowedBotOperation;
use Common\Services\Facebook\MessengerSender;
use Common\Exceptions\MessageNotSentException;
use Common\Repositories\Bot\BotRepositoryInterface;
use Common\Repositories\User\UserRepositoryInterface;
use Common\Exceptions\InvalidBotAccessTokenException;
use Common\Services\Facebook\Auth as FacebookAuthService;

class FacebookAdapter
{

    /**
     * @type FacebookAuthService
     */
    private $auth;
    /**
     * @type MessengerThread
     */
    private $messengerThreads;
    /**
     * @type UserRepositoryInterface
     */
    private $userRepo;
    /**
     * @type UserService
     */
    private $userService;
    /**
     * @type BotRepositoryInterface
     */
    private $botRepo;
    /**
     * @type Users
     */
    private $users;
    /**
     * @type Pages
     */
    private $pages;
    /**
     * @type MessengerSender
     */
    private $messengerSender;

    /**
     * FacebookAdapter constructor.
     * @param Users                   $users
     * @param Pages                   $pages
     * @param UserService             $userService
     * @param FacebookAuthService     $auth
     * @param BotRepositoryInterface  $botRepo
     * @param MessengerThread         $messengerThreads
     * @param MessengerSender         $messengerSender
     * @param UserRepositoryInterface $userRepo
     */
    public function __construct(
        Users $users,
        Pages $pages,
        UserService $userService,
        FacebookAuthService $auth,
        BotRepositoryInterface $botRepo,
        MessengerSender $messengerSender,
        MessengerThread $messengerThreads,
        UserRepositoryInterface $userRepo
    ) {
        $this->auth = $auth;
        $this->pages = $pages;
        $this->users = $users;
        $this->botRepo = $botRepo;
        $this->userRepo = $userRepo;
        $this->userService = $userService;
        $this->messengerSender = $messengerSender;
        $this->messengerThreads = $messengerThreads;
    }

    /**
     * @param User $user
     * @return false|object
     */
    public function getManagedPageList(User $user)
    {
        try {
            $response = $this->pages->getManagedPageList($user->access_token);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            if ($this->isRevokedAccess($response)) {
                $permissions = $this->auth->getGrantedPermissionList($user->access_token);
                if (! $this->userService->hasAllManagingPagePermissions($permissions)) {
                    $this->userRepo->update($user, ['granted_permissions' => $permissions]);

                    return false;
                }
            }
            throw  $e;
        }

        return $response;
    }

    /**
     * @param Bot    $bot
     * @param string $text
     * @return object
     * @throws DisallowedBotOperation
     */
    public function addGreetingText(Bot $bot, $text)
    {
        $facebookCallback = function ($accessToken) use ($text) {
            return $this->messengerThreads->addGreetingText($accessToken, $text);
        };

        $retryCallback = function (Bot $bot) use ($text) {
            return $this->addGreetingText($bot, $text);
        };

        $message = "Failed to add the greeting text because of denied Facebook permissions!";

        return $this->makeHttpRequestToFacebook($facebookCallback, $retryCallback, $bot, $message);
    }

    /**
     * @param Bot $bot
     * @return object|false
     * @throws DisallowedBotOperation
     */
    public function addGetStartedButton(Bot $bot)
    {
        $facebookCallback = function ($accessToken) {
            return $this->messengerThreads->addGetStartedButton($accessToken);
        };

        $retryCallback = function (Bot $bot) {
            return $this->addGetStartedButton($bot);
        };

        $message = "Failed to add Get Started button because of denied Facebook permissions!";

        return $this->makeHttpRequestToFacebook($facebookCallback, $retryCallback, $bot, $message);
    }

    /**
     * @param Bot   $bot
     * @param array $buttons
     * @return object|false
     * @throws DisallowedBotOperation
     */
    public function setPersistentMenu(Bot $bot, array $buttons)
    {
        $facebookCallback = function ($accessToken) use ($buttons) {
            return $this->messengerThreads->setPersistentMenu($accessToken, $buttons);
        };

        $retryCallback = function (Bot $bot) use ($buttons) {
            return $this->setPersistentMenu($bot, $buttons);
        };

        $message = "We couldn't update the main menu on Facebook because of denied Facebook permissions!";

        return $this->makeHttpRequestToFacebook($facebookCallback, $retryCallback, $bot, $message);
    }

    /**
     * @param Bot    $bot
     * @param string $id
     * @return object
     * @throws DisallowedBotOperation
     */
    public function publicUserProfile(Bot $bot, $id)
    {
        $facebookCallback = function ($accessToken) use ($id) {
            return $this->users->publicProfile($id, $accessToken);
        };

        $retryCallback = function (Bot $bot) use ($id) {
            return $this->publicUserProfile($bot, $id);
        };

        return $this->makeHttpRequestToFacebook($facebookCallback, $retryCallback, $bot);
    }

    /**
     * @param Bot $bot
     * @return object
     * @throws DisallowedBotOperation
     */
    public function subscribeToPage(Bot $bot)
    {
        $facebookCallback = function ($accessToken) {
            return $this->pages->subscribeToPage($accessToken);
        };

        $retryCallback = function (Bot $bot) {
            return $this->subscribeToPage($bot);
        };

        return $this->makeHttpRequestToFacebook($facebookCallback, $retryCallback, $bot);
    }

    /**
     * @param Bot   $bot
     * @param array $message
     * @param bool  $asyncMode
     * @return object
     * @throws DisallowedBotOperation
     */
    public function sendMessage(Bot $bot, array $message, $asyncMode = false)
    {
        $facebookCallback = function ($accessToken) use ($message, $asyncMode) {
            return $this->messengerSender->send($accessToken, $message, $asyncMode);
        };

        $retryCallback = function (Bot $bot) use ($message, $asyncMode) {
            return $this->sendMessage($bot, $message, $asyncMode);
        };

        return $this->makeHttpRequestToFacebook($facebookCallback, $retryCallback, $bot);
    }


    /**
     * @param Closure $FacebookCallback
     * @param Closure $retryCallback
     * @param Bot     $bot
     * @param string  $message
     * @return object
     * @throws InactiveBotException
     * @throws InvalidBotAccessTokenException
     * @throws MessageNotSentException
     */
    protected function makeHttpRequestToFacebook(Closure $FacebookCallback, Closure $retryCallback, Bot $bot, $message = null)
    {
        if (! $bot->access_token) {
            throw new InvalidBotAccessTokenException;
        }

        if (! $bot->enabled) {
            throw new InactiveBotException;
        }

        try {
            $response = $FacebookCallback($bot->access_token);
        } catch (ClientException $e) {
            $response = $e->getResponse();

            if ($this->hasFailedToSendMessage($response)) {
                throw new MessageNotSentException;
            }

            if ($this->isRevokedAccess($response)) {

                $currentMeta = array_first($bot->users, function ($user) use ($bot) {
                    return $user['access_token'] == $bot->access_token;
                });

                /** @type User $current */
                $current = null;
                $currentIndex = null;
                $this->getBotUsers($bot)->each(function (User $user, $index) use ($currentMeta, &$current, &$currentIndex) {
                    if ($user->_id == $currentMeta['user_id']) {
                        $current = $user;
                        $currentIndex = $index;
                    }
                });

                $permissions = $this->auth->getGrantedPermissionList($current->access_token);

                if (! $this->userService->hasAllManagingPagePermissions($permissions)) {
                    // user has revoked access,
                    // Get a new user with an access token.
                    $newUserMeta = array_first($bot->users, function ($user) use ($current) {
                        return $user['user_id'] != $current->_id && ! is_null($user['access_token']);
                    });

                    // Update the bot's active access token.
                    // Set user's page access token to null, update granted permissions.
                    $this->userRepo->update($current, ['granted_permissions' => $permissions]);

                    // deactivate bot!
                    $currentKey = "users.{$currentIndex}.access_token";

                    $update = [
                        $currentKey    => null,
                        'access_token' => $newUserMeta? $newUserMeta['access_token'] : null
                    ];

                    if (! $newUserMeta) {
                        $update['enabled'] = false;
                    }

                    $this->botRepo->update($bot, $update);

                    if (is_null($message)) {
                        notify_frontend("{$current->id}_notifications", 'error', [
                            'type'    => 'unauthorized',
                            'title'   => "Unauthorized!",
                            'message' => $message
                        ]);
                    }

                    return $retryCallback($bot);
                }
            }

            throw  $e;
        }

        return $response;
    }

    /**
     * @param ResponseInterface $response
     * @return bool
     */
    private function isRevokedAccess(ResponseInterface $response)
    {
        $body = json_decode($response->getBody());

        if ($body && isset($body->error) && isset($body->error->error_subcode) && $body->error->error_subcode == 2018065) {
            return true;
        }

        return $body && isset($body->error->type) && isset($body->error->code) && $body->error->type === 'OAuthException' && in_array($body->error->code, [190, 230]);
    }

    /**
     * @param Bot $bot
     * @return Collection
     */
    protected function getBotUsers(Bot $bot)
    {
        $ids = array_pluck($bot->users, 'user_id');

        return $this->userRepo->findByIds($ids);
    }

    /**
     * @param ResponseInterface $response
     * @return bool
     */
    protected function hasFailedToSendMessage($response)
    {
        $body = json_decode($response->getBody());

        return $body && isset($body->error) && isset($body->error->error_subcode) && in_array($body->error->error_subcode, [2018108, 2018028, 1545041, 2018027]);
    }
}