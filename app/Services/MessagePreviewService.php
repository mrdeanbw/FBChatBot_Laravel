<?php namespace App\Services;

use App\Models\Bot;
use App\Models\User;
use App\Jobs\SendTemplate;
use App\Models\Subscriber;
use App\Models\MessagePreview;
use App\Repositories\Bot\BotRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Repositories\MessagePreview\MessagePreviewRepositoryInterface;

class MessagePreviewService
{

    /**
     * @type TemplateService
     */
    private $templates;
    /**
     * @type FacebookAPIAdapter
     */
    private $FacebookAdapter;
    /**
     * @type MessagePreviewRepositoryInterface
     */
    private $messagePreviewRepo;
    /**
     * @type BotRepositoryInterface
     */
    private $botRepo;

    /**
     * MessagePreviewService constructor.
     * @param TemplateService                   $templates
     * @param BotRepositoryInterface            $botRepo
     * @param FacebookAPIAdapter                $FacebookAdapter
     * @param MessagePreviewRepositoryInterface $messagePreviewRepo
     */
    public function __construct(
        TemplateService $templates,
        BotRepositoryInterface $botRepo,
        FacebookAPIAdapter $FacebookAdapter,
        MessagePreviewRepositoryInterface $messagePreviewRepo
    ) {
        $this->botRepo = $botRepo;
        $this->templates = $templates;
        $this->FacebookAdapter = $FacebookAdapter;
        $this->messagePreviewRepo = $messagePreviewRepo;
    }

    /**
     * Create a message preview model, and send it to the user.
     * @param array $input
     * @param User  $user
     * @param Bot   $bot
     * @return MessagePreview
     */
    public function createAndSend(array $input, User $user, Bot $bot)
    {
        $subscriber = $this->getSubscriberForUserOrFail($user, $bot);

        $messagePreview = $this->create($input, $user, $bot);

        dispatch(new SendTemplate($messagePreview->template, $subscriber));

        return $messagePreview;
    }

    /**
     * Create a message preview.
     * @param array $input
     * @param User  $user
     * @param Bot   $bot
     * @return MessagePreview
     */
    private function create(array $input, User $user, Bot $bot)
    {
        $input['template']['messages'] = $this->removeMessageIds($input['template']['messages']);

        $template = $this->templates->createImplicit($input['template']['messages'], $bot->_id);

        /** @type MessagePreview $messagePreview */
        $messagePreview = $this->messagePreviewRepo->create([
            'user_id'     => $user->_id,
            'bot_id'      => $bot->_id,
            'template_id' => $template->_id
        ]);

        $messagePreview->template = $template;

        return $messagePreview;
    }

    /**
     * Return the to-a-certain-bot subscriber model out of a user.
     * @param User $user
     * @param Bot  $bot
     * @return Subscriber
     */
    private function getSubscriberForUserOrFail(User $user, Bot $bot)
    {
        $subscriber = $this->botRepo->getSubscriberForUser($user, $bot);
        if (! $subscriber) {
            throw new NotFoundHttpException;
        }

        return $subscriber;
    }

    /**
     * Message previews can be created from existing message blocks. The message preview
     * is like a "snapshot" of current message blocks. This method removes "ids" from message blocks,
     * so that they can be treated as if they were totally independent (clone).
     * @param array $messages
     * @return array
     */
    private function removeMessageIds(array $messages)
    {
        return array_map(function ($message) {
            unset($message['id']);

            if (in_array($message['type'], ['text', 'card'])) {
                $message['buttons'] = $this->removeMessageIds($message['buttons']);
            }

            if ($message['type'] == 'card_container') {
                $message['cards'] = $this->removeMessageIds($message['cards']);
            }

            if ($message['type'] == 'button') {
                $message['messages'] = $this->removeMessageIds($message['messages']);
            }

            return $message;
        }, $messages);
    }

}