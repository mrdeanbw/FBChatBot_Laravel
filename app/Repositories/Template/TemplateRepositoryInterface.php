<?php namespace App\Repositories\Template;

use App\Models\Bot;
use App\Models\Template;
use App\Models\Subscriber;
use App\Repositories\AssociatedWithBotRepositoryInterface;

interface TemplateRepositoryInterface extends AssociatedWithBotRepositoryInterface
{

    /**
     * Find an explicit template by id for bot.
     *
     * @param int $id
     * @param Bot $bot
     *
     * @return Template|null
     */
    public function findExplicitByIdForBot($id, Bot $bot);

    /**
     * @param Template   $templateId
     * @param Subscriber $subscriber
     * @param array      $buttonPath
     * @param int        $incrementBy
     *
     * @return
     */
    public function recordButtonClick(Template $templateId, Subscriber $subscriber, array $buttonPath, $incrementBy = 1);
}
