<?php namespace Common\Services;

use Common\Models\Bot;
use MongoDB\BSON\ObjectID;
use Common\Models\DefaultReply;
use Common\Repositories\Bot\BotRepositoryInterface;

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
     * @param ObjectID $templateId
     * @return DefaultReply
     */
    public function defaultDefaultReply(ObjectID $templateId)
    {
        return new DefaultReply([
            'enabled'     => true,
            'always'      => true,
            'template_id' => $templateId,
        ]);
    }
}