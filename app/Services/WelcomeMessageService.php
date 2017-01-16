<?php namespace App\Services;

use DB;
use App\Models\Page;
use App\Models\WelcomeMessage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Repositories\WelcomeMessage\WelcomeMessageRepository;

class WelcomeMessageService
{

    /**
     * @type MessageBlockService
     */
    private $messageBlocks;
    /**
     * @type WelcomeMessageRepository
     */
    private $welcomeMessageRepo;

    /**
     * WelcomeMessageService constructor.
     * @param WelcomeMessageRepository $welcomeMessageRepo
     * @param MessageBlockService      $messageBlockService
     */
    public function __construct(WelcomeMessageRepository $welcomeMessageRepo, MessageBlockService $messageBlockService)
    {
        $this->messageBlocks = $messageBlockService;
        $this->welcomeMessageRepo = $welcomeMessageRepo;
    }

    /**
     * Attach the default message blocks to the welcome message,
     * The "copyright message" / second message blocks which contains
     * "Powered By Mr. Reply" sentence is then disabled, to prevent editing
     * or removing it.
     * @param WelcomeMessage $welcomeMessage
     */
    public function attachDefaultMessageBlocks(WelcomeMessage $welcomeMessage)
    {
        $messageBlocks = $this->messageBlocks->persist($welcomeMessage, $this->getDefaultBlocks());
        $copyrightBlock = $messageBlocks->get(1);
        $this->messageBlocks->update($copyrightBlock, ['is_disabled' => true]);
    }

    /**
     * @return array
     */
    private function getDefaultBlocks()
    {
        return [
            $this->initialTextMessage(),
            $this->copyrightMessage()
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
    private function copyrightMessage()
    {
        return [
            'type' => 'text',
            'text' => 'Want to create your own bot? Go to: https://www.mrreply.com',
        ];
    }

    /**
     * Get the welcome message associated with the page,
     * if it doesn't exist, throw an exception.
     * @param Page $page
     * @return WelcomeMessage
     */
    public function getOrFail(Page $page)
    {
        if ($mainMenu = $this->welcomeMessageRepo->getForPage($page)) {
            return $mainMenu;
        }
        throw new ModelNotFoundException;
    }

    /**
     *
     * @param array $input
     * @param Page  $page
     */
    public function update(array $input, Page $page)
    {
        DB::transaction(function () use ($input, $page) {
            $welcomeMessage = $this->getOrFail($page);
            $blocks = $input['message_blocks'];
            $this->messageBlocks->persist($welcomeMessage, $blocks);
        });

    }
}