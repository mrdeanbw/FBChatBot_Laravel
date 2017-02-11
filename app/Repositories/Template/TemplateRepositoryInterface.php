<?php namespace App\Repositories\Template;

use App\Models\Bot;
use App\Models\Subscriber;
use App\Models\Template;
use Illuminate\Support\Collection;
use App\Repositories\AssociatedWithBotRepositoryInterface;

interface TemplateRepositoryInterface extends AssociatedWithBotRepositoryInterface
{

    /**
     * Return a list of all explicit templates for a bot.
     * @param Bot $bot
     * @return Collection
     */
    public function explicitTemplatesForBot(Bot $bot);

    /**
     * Find an explicit template by id for bot.
     * @param int $id
     * @param Bot $bot
     * @return Template|null
     */
    public function findExplicitByIdForBot($id, Bot $bot);

    /**
     * @param Template   $templateId
     * @param Subscriber $subscriber
     * @param array      $buttonPath
     * @param int        $incrementBy
     * @return
     */
    public function recordButtonClick(Template $templateId, Subscriber $subscriber, array $buttonPath, $incrementBy = 1);
}
