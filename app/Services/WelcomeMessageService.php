<?php

namespace App\Services;

use App\Models\Page;
use App\Models\WelcomeMessage;
use DB;

class WelcomeMessageService
{

    /**
     * @type MessageBlockService
     */
    private $messageBlocks;

    /**
     * WelcomeMessageService constructor.
     * @param MessageBlockService $messageBlockService
     */
    public function __construct(MessageBlockService $messageBlockService)
    {
        $this->messageBlocks = $messageBlockService;
    }

    /**
     * @param WelcomeMessage $welcomeMessage
     */
    public function attachDefaultMessageBlocks(WelcomeMessage $welcomeMessage)
    {
        $this->messageBlocks->persist($welcomeMessage, $this->getDefaultBlocks());
        $this->messageBlocks->disableLastMessageBlock($welcomeMessage);
    }

    /**
     * @return array
     */
    private function getDefaultBlocks()
    {
        return [
            $this->initialTextMessage(),
            $this->copyrightedMessage()
        ];

    }

    /**
     * @return array
     */
    private function initialTextMessage()
    {
        return [
            'type' => 'text',
            'text' => "Welcome {{first_name}}! Thank you for subscribing. The next post is coming soon, stay tuned!\n\nP.S. If you ever want to unsubscribe just type \"stop\"."
        ];
    }


    /**
     * @return array
     */
    private function copyrightedMessage()
    {
        return [
            'type' => 'text',
            'text' => 'Want to create your own bot? Go to: http://www.mrreply.com',
        ];
    }

    /**
     * @param Page $page
     * @return WelcomeMessage
     */
    public function get(Page $page)
    {
        //        return $page->welcomeMessage()->with('blocks.blocks')->firstOrFail();
        return $page->welcomeMessage()->firstOrFail();
    }

    /**
     * @param array $input
     * @param       $page
     */
    public function persist($input, $page)
    {
        DB::beginTransaction();

        $welcomeMessage = $this->get($page);

        $blocks = $input['message_blocks'];
        
        $this->messageBlocks->persist($welcomeMessage, $blocks, $page);
        
        DB::commit();

    }
}