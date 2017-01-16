<?php namespace App\Services;

use DB;
use App\Models\Page;
use App\Models\User;
use App\Models\MessagePreview;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MessagePreviewService
{

    /**
     * @type MessageBlockService
     */
    private $messageBlocks;
    /**
     * @type FacebookAPIAdapter
     */
    private $FacebookAdapter;

    /**
     * MessagePreviewService constructor.
     * @param MessageBlockService $messageBlockService
     * @param FacebookAPIAdapter  $FacebookAdapter
     */
    public function __construct(MessageBlockService $messageBlockService, FacebookAPIAdapter $FacebookAdapter)
    {
        $this->messageBlocks = $messageBlockService;
        $this->FacebookAdapter = $FacebookAdapter;
    }

    /**
     * @param      $input
     * @param User $user
     * @param Page $page
     * @return MessagePreview
     */
    public function createAndSend($input, User $user, Page $page)
    {
        $subscriber = $user->isSubscribedTo($page);

        if (! $subscriber) {
            throw new ModelNotFoundException;
        }

        $messagePreview = DB::transaction(function () use ($input, $page, $subscriber) {
            $messagePreview = $this->create($input, $page);
            $this->FacebookAdapter->sendBlocks($messagePreview->fresh(), $subscriber);

            return $messagePreview;
        });

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

        $this->messageBlocks->persist($messagePreview, $input['message_blocks']);

        return $messagePreview;
    }

}