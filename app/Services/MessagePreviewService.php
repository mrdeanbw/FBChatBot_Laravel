<?php namespace App\Services;

use DB;
use App\Models\Page;
use App\Models\User;
use App\Models\Subscriber;
use App\Models\MessagePreview;
use App\Repositories\User\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Repositories\MessagePreview\MessagePreviewRepository;

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
     * @type MessagePreviewRepository
     */
    private $messagePreviewRepo;
    /**
     * @type UserRepository
     */
    private $userRepo;

    /**
     * MessagePreviewService constructor.
     * @param MessagePreviewRepository $messagePreviewRepo
     * @param UserRepository           $userRepo
     * @param MessageBlockService      $messageBlockService
     * @param FacebookAPIAdapter       $FacebookAdapter
     */
    public function __construct(
        MessagePreviewRepository $messagePreviewRepo,
        UserRepository $userRepo,
        MessageBlockService $messageBlockService,
        FacebookAPIAdapter $FacebookAdapter
    ) {
        $this->messageBlocks = $messageBlockService;
        $this->FacebookAdapter = $FacebookAdapter;
        $this->messagePreviewRepo = $messagePreviewRepo;
        $this->userRepo = $userRepo;
    }

    /**
     * Create a message preview model, and send it to the user.
     * @param array $input
     * @param User  $user
     * @param Page  $page
     * @return MessagePreview
     */
    public function createAndSend(array $input, User $user, Page $page)
    {
        $subscriber = $this->userToSubscriber($user, $page);

        $messagePreview = DB::transaction(function () use ($input, $page, $subscriber) {
            $messagePreview = $this->create($input, $page);
            $this->FacebookAdapter->sendBlocks($messagePreview, $subscriber);

            return $messagePreview;
        });

        return $messagePreview;
    }

    /**
     * Create a message preview.
     * @param array $input
     * @param Page  $page
     * @return MessagePreview
     */
    private function create(array $input, Page $page)
    {
        $messagePreview = $this->messagePreviewRepo->create($page);

        $input['message_blocks'] = $this->removeMessageBlockIds($input['message_blocks']);

        $this->messageBlocks->persist($messagePreview, $input['message_blocks']);

        return $messagePreview;
    }

    /**
     * Return the to-a-certain-page subscriber model out of a user.
     * @param User $user
     * @param Page $page
     * @return Subscriber
     */
    private function userToSubscriber(User $user, Page $page)
    {
        if (! $this->userRepo->isSubscribedToPage($user, $page)) {
            throw new ModelNotFoundException;
        }

        $subscriber = $this->userRepo->asSubscriber($user, $page);

        return $subscriber;
    }

    /**
     * Message previews can be created from existing message blocks. The message preview
     * is like a "snapshot" of current message blocks. This method removes "ids" from message blocks,
     * so that they can be treated as if they were totally independent (clone).
     * @param array $messageBlocks
     * @return array
     */
    private function removeMessageBlockIds(array $messageBlocks)
    {
        return array_map(function ($block) {
            unset($block['id']);

            return $block;
        }, $messageBlocks);
    }

}