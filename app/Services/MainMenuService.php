<?php

namespace App\Services;

use App\Models\MainMenu;
use App\Models\Page;
use App\Services\Facebook\Makana\MakanaAdapter;
use App\Services\Facebook\Makana\Thread;
use DB;
use Log;

class MainMenuService
{

    /**
     * @type MessageBlockService
     */
    private $messageBlocks;

    /**
     * @type Thread
     */
    private $facebookThread;
    /**
     * @type MakanaAdapter
     */
    private $MakanaAdapter;

    /**
     * MainMenuService constructor.
     * @param MessageBlockService $messageBlocks
     * @param Thread              $facebookThread
     * @param MakanaAdapter       $MakanaAdapter
     */
    public function __construct(MessageBlockService $messageBlocks, Thread $facebookThread, MakanaAdapter $MakanaAdapter)
    {
        $this->messageBlocks = $messageBlocks;
        $this->facebookThread = $facebookThread;
        $this->MakanaAdapter = $MakanaAdapter;
    }

    /**
     * @param Page $page
     * @return MainMenu
     */
    public function get(Page $page)
    {
        return $page->mainMenu()->firstOrFail();
        //        return $page->mainMenu()->with('message_blocks')->firstOrFail();
    }

    /**
     * @param Page $page
     * @param      $input
     * @return bool
     */
    public function persist($input, Page $page)
    {
        DB::beginTransaction();

        $blocks = $input['message_blocks'];

        $mainMenu = $this->get($page);

        $this->messageBlocks->persist($mainMenu, $blocks, $page);

        $success = $this->createFacebookMenu($mainMenu, $page);

        DB::commit();

        return $success;
    }

    /**
     * @param MainMenu $mainMenu
     * @param Page     $page
     * @return bool
     */
    public function createFacebookMenu(MainMenu $mainMenu, Page $page)
    {
        $blocks = $this->MakanaAdapter->mapButtons($mainMenu->message_blocks);

        $response = $this->facebookThread->setPersistentMenu($page->access_token, $blocks);
        
        $success = isset($response->result) && starts_with($response->result, "Successfully");

        if (! $success) {
            \Log::error("Failed to create menu [$mainMenu->id]");
            \Log::error(json_encode($blocks));
            \Log::error(json_encode($response));
        }

        return $success;
    }

    /**
     * @param $mainMenu
     */
    public function attachDefaultMenuItems($mainMenu)
    {
        $this->messageBlocks->persist($mainMenu, [$this->copyrightedButton()]);
        $this->messageBlocks->disableLastMessageBlock($mainMenu);
    }

    /**
     * @return array
     */
    private function copyrightedButton()
    {
        return [
            'type'  => 'button',
            'title' => 'Powered By ' . config('app.name'),
            'url'   => 'http://www.mrreply.com',
        ];
    }

}