<?php namespace App\Services;

use DB;
use Log;
use App\Models\Page;
use App\Models\MainMenu;
use App\Services\Facebook\Thread;
use App\Repositories\MainMenu\MainMenuRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MainMenuService
{

    /**
     * @type MessageBlockService
     */
    private $messageBlocks;

    /**
     * @type Thread
     */
    private $FacebookThread;
    /**
     * @type FacebookAPIAdapter
     */
    private $FacebookAdapter;
    /**
     * @type MainMenuRepository
     */
    private $mainMenuRepo;

    /**
     * MainMenuService constructor.
     * @param MainMenuRepository  $mainMenuRepo
     * @param MessageBlockService $messageBlocks
     * @param Thread              $facebookThread
     * @param FacebookAPIAdapter  $FacebookAdapter
     */
    public function __construct(
        MainMenuRepository $mainMenuRepo,
        MessageBlockService $messageBlocks,
        Thread $facebookThread,
        FacebookAPIAdapter $FacebookAdapter
    ) {
        $this->messageBlocks = $messageBlocks;
        $this->FacebookThread = $facebookThread;
        $this->FacebookAdapter = $FacebookAdapter;
        $this->mainMenuRepo = $mainMenuRepo;
    }

    /**
     * @param Page $page
     * @return MainMenu
     */
    public function getOrFail(Page $page)
    {
        if ($mainMenu = $this->mainMenuRepo->getForPage($page)) {
            return $mainMenu;
        }
        throw new ModelNotFoundException;
    }

    /**
     * Updates the main menu (buttons).
     * @param Page $page
     * @param      $input
     * @return bool
     */
    public function update($input, Page $page)
    {
        $success = DB::transaction(function () use ($input, $page) {
            $blocks = $input['message_blocks'];
            $mainMenu = $this->getOrFail($page);
            $this->messageBlocks->persist($mainMenu, $blocks);

            return $this->setupFacebookPagePersistentMenu($mainMenu, $page);
        });

        return $success;
    }

    /**
     * Use Facebook API to actually setup and display the main menu.
     * @param MainMenu $mainMenu
     * @param Page     $page
     * @return bool
     */
    public function setupFacebookPagePersistentMenu(MainMenu $mainMenu, Page $page)
    {
        $blocks = $this->FacebookAdapter->mapButtons($mainMenu->message_blocks);

        $response = $this->FacebookThread->setPersistentMenu($page->access_token, $blocks);

        $success = isset($response->result) && starts_with($response->result, "Successfully");

        if (! $success) {
            Log::error("Failed to create menu [$mainMenu->id]");
            Log::error(json_encode($blocks));
            Log::error(json_encode($response));
        }

        return $success;
    }

    /**
     * Attach the default "Powered By: Mr. Reply button" to the main menu,
     * and make it "disabled", so that it cannot be edited/removed.
     * @param $mainMenu
     */
    public function attachDefaultButtonsToMainMenu(MainMenu $mainMenu)
    {
        $defaultButtons = [$this->copyrightedButton()];
        $messageBlocks = $this->messageBlocks->persist($mainMenu, $defaultButtons);
        $copyrightBlock = $messageBlocks->get(0);
        $this->messageBlocks->update($copyrightBlock, ['is_disabled' => true]);
    }

    /**
     * The button array for the button.
     * @return array
     */
    private function copyrightedButton()
    {
        return [
            'type'  => 'button',
            'title' => 'Powered By ' . config('app.name'),
            'url'   => 'https://www.mrreply.com',
        ];
    }

}