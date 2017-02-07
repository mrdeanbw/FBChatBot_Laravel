<?php namespace App\Services;

use App\Models\Bot;
use App\Models\DefaultReply;
use App\Repositories\Bot\BotRepositoryInterface;

class DefaultReplyService
{

    /**
     * @type MessageService
     */
    private $messageBlocks;
    /**
     * @type TemplateService
     */
    private $templates;
    /**
     * @var BotRepositoryInterface
     */
    private $botRepo;

    /**
     * DefaultReplyService constructor.
     * @param BotRepositoryInterface $botRepo
     * @param MessageService         $messageBlockService
     * @param TemplateService        $templates
     */
    public function __construct(BotRepositoryInterface $botRepo, MessageService $messageBlockService, TemplateService $templates)
    {
        $this->botRepo = $botRepo;
        $this->templates = $templates;
        $this->messageBlocks = $messageBlockService;
    }

    /**
     * Update the default reply.
     * @param array $input
     * @param Bot   $bot
     * @return DefaultReply
     */
    public function update(array $input, Bot $bot)
    {
        $bot->default_reply->template = $this->templates->updateImplicit($bot->default_reply->template_id, $input['template'], $bot);

        return $bot->default_reply;
    }

    /**
     * @param $botId
     * @return DefaultReply
     */
    public function defaultDefaultReply($botId)
    {
        return new DefaultReply([
            'template_id' => $this->templates->createImplicit([], $botId)->id
        ]);
    }
}