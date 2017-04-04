<?php namespace Common\Services;

use Common\Models\Bot;
use Common\Models\User;
use Common\Jobs\SendTemplate;
use Common\Models\Subscriber;
use Common\Models\MessagePreview;
use Common\Repositories\Bot\BotRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Common\Repositories\MessagePreview\MessagePreviewRepositoryInterface;

class MessagePreviewService
{

    /**
     * @type TemplateService
     */
    private $templates;
    /**
     * @type FacebookMessageSender
     */
    private $FacebookMessageSender;
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
     * @param FacebookMessageSender             $FacebookMessageSender
     * @param MessagePreviewRepositoryInterface $messagePreviewRepo
     */
    public function __construct(
        TemplateService $templates,
        BotRepositoryInterface $botRepo,
        FacebookMessageSender $FacebookMessageSender,
        MessagePreviewRepositoryInterface $messagePreviewRepo
    ) {
        $this->botRepo = $botRepo;
        $this->templates = $templates;
        $this->messagePreviewRepo = $messagePreviewRepo;
        $this->FacebookMessageSender = $FacebookMessageSender;
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

        $job = (new SendTemplate($messagePreview->template, $subscriber, $bot))->onQueue('onetry');
        dispatch($job);

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

        $template = $this->templates->createImplicit($input['template']['messages'], $bot->_id, false, true);

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

            if (in_array($message['type'], ['text', 'card']) && $buttons = array_get($message, 'buttons', [])) {
                $message['buttons'] = $this->removeMessageIds($buttons);
            }

            if ($message['type'] == 'card_container') {
                $message['cards'] = $this->removeMessageIds($message['cards']);
            }

            if ($message['type'] == 'button' && $buttonMessages = array_get($message, 'messages', [])) {
                $message['messages'] = $this->removeMessageIds($buttonMessages);
            }

            return $message;
        }, $messages);
    }

}