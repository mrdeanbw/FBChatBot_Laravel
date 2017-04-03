<?php namespace Common\Services;

use Common\Models\Bot;
use Common\Models\Text;
use Common\Models\Template;
use Common\Models\WelcomeMessage;
use Common\Repositories\Bot\BotRepositoryInterface;
use MongoDB\BSON\ObjectID;

class WelcomeMessageService
{

    /**
     * @type TemplateService
     */
    private $templates;
    /**
     * @var BotRepositoryInterface
     */
    private $botRepo;

    /**
     * WelcomeMessageService constructor.
     * @param BotRepositoryInterface $botRepo
     * @param TemplateService        $templates
     */
    public function __construct(BotRepositoryInterface $botRepo, TemplateService $templates)
    {
        $this->botRepo = $botRepo;
        $this->templates = $templates;
    }

    /**
     *
     * @param array $input
     * @param Bot   $bot
     * @return WelcomeMessage
     */
    public function update(array $input, Bot $bot)
    {
        $bot->welcome_message->template = $this->templates->updateImplicit($bot->welcome_message->template_id, $input['template'], $bot);

        return $bot->welcome_message;
    }

    /**
     * Create the default welcome message for a page.
     * @param ObjectID $templateId
     * @return WelcomeMessage
     */
    public function defaultWelcomeMessage(ObjectID $templateId)
    {
        return new WelcomeMessage(['template_id' => $templateId]);
    }
}