<?php namespace App\Services;

use App\Models\Bot;
use App\Models\User;
use App\Models\GreetingText;
use App\Jobs\UpdateGreetingTextOnFacebook;
use App\Services\Facebook\MessengerThread;
use App\Repositories\Bot\BotRepositoryInterface;

class GreetingTextService
{

    /**
     * @var MessengerThread
     */
    private $messengerThread;
    /**
     * @var BotRepositoryInterface
     */
    private $botRepo;

    /**
     * GreetingTextService constructor.
     *
     * @param BotRepositoryInterface $botRepo
     * @param MessengerThread        $FacebookThread
     */
    public function __construct(BotRepositoryInterface $botRepo, MessengerThread $FacebookThread)
    {
        $this->botRepo = $botRepo;
        $this->messengerThread = $FacebookThread;
    }

    /**
     * Persist greeting text, and update the Facebook page's greeting text.
     * @param array $input
     * @param Bot   $bot
     * @param User  $user
     */
    public function update(array $input, Bot $bot, User $user)
    {
        $this->botRepo->update($bot, ['greeting_text.text' => trim($input['text'])]);
        dispatch(new UpdateGreetingTextOnFacebook($bot, $user->id));
    }

    /**
     * Get the default greeting text.
     * @param string $pageName
     * @return array
     */
    public function defaultGreetingText($pageName)
    {
        return new GreetingText([
            'text' => "Hello {{first_name}}, Welcome to {$pageName}! - Powered By: MrReply.com"
        ]);
    }

}