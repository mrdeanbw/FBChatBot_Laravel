<?php namespace Common\Services;

use Common\Models\Bot;
use Common\Models\Text;
use Common\Models\Template;
use Common\Models\WelcomeMessage;
use Common\Repositories\Bot\BotRepositoryInterface;

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
     * @param $botId
     * @return WelcomeMessage
     */
    public function defaultWelcomeMessage($botId)
    {
        return new WelcomeMessage([
            'template_id' => $this->newDefaultTemplateInstance($botId)->_id
        ]);
    }

    /**
     * Attach the default message blocks to the welcome message,
     * The "copyright message" / second message blocks which contains
     * "Powered By Mr. Reply" sentence is then disabled, to prevent editing
     * or removing it.
     * @param $botId
     * @return Template
     */
    private function newDefaultTemplateInstance($botId)
    {
        $messages = $this->defaultMessages();

        return $this->templates->createImplicit($messages, $botId, true);
    }

    /**
     * @return array
     */
    private function defaultMessages()
    {
        return [
            $this->initialTextMessage(),
            $this->copyrightMessage()
        ];

    }

    /**
     * @return Text
     */
    private function initialTextMessage()
    {
        return new Text([
            'text' => "Welcome {{first_name}}! Thank you for subscribing. The next post is coming soon, stay tuned!\n\nP.S. If you ever want to unsubscribe just type \"stop\"."
        ]);
    }

    /**
     * @return Text
     */
    private function copyrightMessage()
    {
        return new Text([
            'text'     => 'Want to create your own bot? Go to: https://www.mrreply.com',
            'readonly' => true
        ]);
    }
}