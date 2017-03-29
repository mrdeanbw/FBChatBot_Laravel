<?php namespace Common\Services;

use Common\Models\Bot;
use Common\Models\User;
use Common\Models\Button;
use Common\Models\MainMenu;
use Common\Jobs\UpdateMainMenuOnFacebook;
use Common\Repositories\Bot\DBBotRepository;
use Common\Services\Facebook\MessengerThread;
use Dingo\Api\Exception\ValidationHttpException;

class MainMenuService
{

    /**
     * @type MessageService
     */
    private $messages;

    /**
     * @type MessengerThread
     */
    private $FacebookThread;
    /**
     * @type FacebookMessageSender
     */
    private $FacebookMessageSender;
    /**
     * @type DBBotRepository
     */
    private $botRepo;

    /**
     * MainMenuService constructor.
     *
     * @param MessageService        $messages
     * @param MessengerThread       $facebookThread
     * @param FacebookMessageSender $FacebookMessageSender
     * @param DBBotRepository       $botRepo
     */
    public function __construct(
        DBBotRepository $botRepo,
        MessageService $messages,
        MessengerThread $facebookThread,
        FacebookMessageSender $FacebookMessageSender
    ) {
        $this->botRepo = $botRepo;
        $this->FacebookThread = $facebookThread;
        $this->messages = $messages->forMainMenuButtons();
        $this->FacebookMessageSender = $FacebookMessageSender;
    }

    /**
     * Updates the main menu (buttons).
     * @param array $input
     * @param Bot   $bot
     * @param User  $user
     * @return MainMenu
     */
    public function update(array $input, Bot $bot, User $user)
    {
        $buttons = $input['buttons'];

        // remove the last element in the main menu buttons (the copyrighted block).
        array_pop($buttons);

        $buttons = $this->normalizeButtons($buttons, $bot);
        $buttons[] = array_last($bot->main_menu->buttons);

        $this->botRepo->update($bot, ['main_menu.buttons' => $buttons]);

        dispatch(new UpdateMainMenuOnFacebook($bot, $user->id));

        return $bot->main_menu;
    }

    /**
     * Create the default main menu.
     * @param $botId
     * @return MainMenu
     */
    public function defaultMainMenu($botId)
    {
        return new MainMenu([
            'buttons' => $this->messages->correspondInputMessagesToOriginal([$this->copyrightedButton()], [], $botId, true)
        ]);
    }

    /**
     * The button array for the button.
     * @return Button
     */
    private function copyrightedButton()
    {
        return new Button([
            'title'    => 'Powered By Mr. Reply',
            'readonly' => true,
            'url'      => 'https://www.mrreply.com',
        ]);
    }

    /**
     * @param array $buttons
     * @param Bot   $bot
     * @return Button[]
     */
    private function normalizeButtons(array $buttons, Bot $bot)
    {
        $buttons = $this->cleanButtons($buttons);
        if (! $buttons) {
            return [];
        }

        /** @var Button[] $buttons */
        $buttons = $this->messages->correspondInputMessagesToOriginal($buttons, $bot->main_menu->buttons, $bot->_id);

        if (! $buttons) {
            throw new ValidationHttpException(["messages" => ["Invalid Messages"]]);
        }

        return $buttons;
    }

    /**
     * @param array $buttons
     * @return array
     */
    private function cleanButtons(array $buttons)
    {
        return array_map(function (array $button) {

            if ($button['main_action'] == 'url') {
                $ret = new Button([]);
                $ret->title = $button['title'];
                $ret->url = $button['url'];
            } else {
                $ret = new Button($button, true);
                $ret->url = "";
            }

            return $ret;

        }, $buttons);
    }

}
