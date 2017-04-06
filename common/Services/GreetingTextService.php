<?php namespace Common\Services;

use Common\Models\Bot;
use Common\Models\User;
use Common\Models\GreetingText;
use Common\Jobs\UpdateGreetingTextOnFacebook;
use Common\Services\Facebook\MessengerThread;
use Common\Repositories\Bot\BotRepositoryInterface;

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
     * @return Bot
     */
    public function update(array $input, Bot $bot, User $user)
    {
        $this->botRepo->update($bot, ['greeting_text.0.text' => trim($input['text'])]);
        dispatch(new UpdateGreetingTextOnFacebook($bot, $user->_id));

        return $bot;
    }

    /**
     * Get the default greeting text.
     * @return GreetingText
     */
    public function defaultGreetingText()
    {
        return new GreetingText([
            'locale' => 'default',
            'text'   => "Hello {{user_first_name}}, Welcome to {{page_name|fallback:our page}}!"
        ]);
    }

}