<?php namespace Common\Services;

use Common\Models\Bot;
use Common\Models\User;
use Common\Models\Button;
use Common\Models\MainMenu;
use Common\Jobs\UpdateMainMenuOnFacebook;
use Common\Repositories\Bot\DBBotRepository;
use Common\Services\Facebook\MessengerThread;
use Dingo\Api\Exception\ValidationHttpException;
use MongoDB\BSON\ObjectID;

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
        $buttons = $this->normalizeButtons($input['buttons'], $bot);

        $this->botRepo->update($bot, ['main_menu.buttons' => $buttons]);

        dispatch(new UpdateMainMenuOnFacebook($bot, $user->_id));

        $this->messages->persistMessageRevisions();

        return $bot->main_menu;
    }

    /**
     * Create the default main menu.
     * @param Button[] $buttons
     * @return MainMenu
     */
    public function defaultMainMenu(array $buttons)
    {
        return new MainMenu(['buttons' => $buttons]);
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
            throw new ValidationHttpException(["buttons" => ["Invalid Messages"]]);
        }

        if (count($buttons) > 5) {
            throw new ValidationHttpException(["buttons" => ["The main menu may not have more than 5 buttons."]]);
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
            $ret = new Button([]);
            $ret->title = $button['title'];
            if ($id = array_get($button, 'id')) {
                $ret->id = $button['id'];
            }
            if ($button['main_action'] == 'url') {
                $ret->url = $button['url'];
            } else {
                $ret->template_id = new ObjectID($button['template']['id']);
                if ($addTags = array_get($button, 'add_tags')) {
                    $ret->add_tags = $addTags;
                }
                if ($removeTags = array_get($button, 'remove_tags')) {
                    $ret->remove_tags = $removeTags;
                }
            }

            return $ret;
        }, $buttons);
    }

}
