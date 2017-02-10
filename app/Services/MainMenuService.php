<?php namespace App\Services;

use App\Models\Bot;
use App\Models\User;
use App\Models\Button;
use App\Models\MainMenu;
use MongoDB\BSON\ObjectID;
use App\Jobs\UpdateMainMenuOnFacebook;
use App\Repositories\Bot\DBBotRepository;
use App\Services\Facebook\MessengerThread;
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
     * @type FacebookAPIAdapter
     */
    private $FacebookAdapter;
    /**
     * @type DBBotRepository
     */
    private $botRepo;

    /**
     * MainMenuService constructor.
     * @param MessageService     $messages
     * @param MessengerThread    $facebookThread
     * @param FacebookAPIAdapter $FacebookAdapter
     * @param DBBotRepository    $botRepo
     */
    public function __construct(
        MessageService $messages,
        MessengerThread $facebookThread,
        FacebookAPIAdapter $FacebookAdapter,
        DBBotRepository $botRepo
    ) {
        $this->botRepo = $botRepo;
        $this->messages = $messages;
        $this->FacebookThread = $facebookThread;
        $this->FacebookAdapter = $FacebookAdapter;
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
        $buttons = $this->normalizeButtons($input, $bot);

        $this->validateCopyrightButton($buttons);

        $this->botRepo->update($bot, ['main_menu.buttons' => $buttons]);

        dispatch(new UpdateMainMenuOnFacebook($bot, $user->id));

        return $bot->main_menu;
    }

    /**
     * Create the default main menu.
     * @return Button[]
     */
    public function defaultMainMenu()
    {
        return new MainMenu([
            'buttons' => [$this->copyrightedButton()]
        ]);
    }

    /**
     * The button array for the button.
     * @return Button
     */
    private function copyrightedButton()
    {
        return new Button([
            'id'       => new ObjectID(),
            'title'    => 'Powered By Mr. Reply',
            'readonly' => true,
            'url'      => 'https://www.mrreply.com',
        ]);
    }

    /**
     * @param Button[] $buttons
     */
    private function validateCopyrightButton(array $buttons)
    {
        $lastButton = array_last($buttons);

        // @todo: and on a free plan.
        if (! $lastButton || ! $lastButton->id || ! $lastButton->readonly || ! $this->sameAsCopyrightButton($lastButton)) {
            throw new ValidationHttpException([
                "buttons" => ["Missing copyright button."]
            ]);
        }
    }

    /**
     * @param Button $button
     * @return bool
     */
    private function sameAsCopyrightButton(Button $button)
    {
        return $this->buttonAttributes($button) == $this->buttonAttributes($this->copyrightedButton());
    }

    /**
     * @param Button $button
     * @return array
     */
    private function buttonAttributes(Button $button)
    {
        $attributes = get_object_vars($button);
        unset($attributes['id']);
        unset($attributes['template']);

        return $attributes;
    }

    /**
     * @param array $input
     * @param Bot   $bot
     * @return Button[]
     */
    private function normalizeButtons(array $input, Bot $bot)
    {
        $buttons = array_map(function (array $button) {
            return new Button($button);
        }, $input['buttons']);

        $buttons = $this->messages->makeMessages($buttons, $bot->main_menu->buttons, $bot->id);
        if (! $buttons) {
            throw new ValidationHttpException(["messages" => ["Invalid Messages"]]);
        }

        return $buttons;
    }

}