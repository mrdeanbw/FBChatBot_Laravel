<?php

namespace App\Services;

use App\Models\MessagePreview;
use App\Models\Page;
use App\Models\User;
use App\Services\Facebook\Makana\MakanaAdapter;
use DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MessagePreviewService
{

    /**
     * @type MessageBlockService
     */
    private $messageBlocks;
    /**
     * @type MakanaAdapter
     */
    private $Makana;

    /**
     * MessagePreviewService constructor.
     * @param MessageBlockService $messageBlockService
     * @param MakanaAdapter       $Makana
     */
    public function __construct(MessageBlockService $messageBlockService, MakanaAdapter $Makana)
    {
        $this->messageBlocks = $messageBlockService;
        $this->Makana = $Makana;
    }

    /**
     * @param      $input
     * @param User $user
     * @param Page $page
     * @return MessagePreview
     */
    public function createAndSend($input, User $user, Page $page)
    {
        $subscriber = $user->subscriber($page);

        if (! $subscriber) {
            throw new ModelNotFoundException;
        }

        DB::beginTransaction();
        $messagePreview = $this->create($input, $page);
        $this->Makana->sendBlocks($messagePreview->fresh(), $subscriber);
        DB::commit();

        return $messagePreview;
    }

    /**
     * @param      $input
     * @param Page $page
     * @return MessagePreview
     */
    private function create($input, Page $page)
    {
        $messagePreview = new MessagePreview();
        $page->messagePreviews()->save($messagePreview);
        $input['message_blocks'] = array_map(function ($block) {
            unset($block['id']);

            return $block;
        }, $input['message_blocks']);

        $this->messageBlocks->persist($messagePreview, $input['message_blocks'], $page);

        return $messagePreview;
    }

}