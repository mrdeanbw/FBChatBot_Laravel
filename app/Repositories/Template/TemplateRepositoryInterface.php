<?php namespace App\Repositories\Template;

use App\Models\Bot;
use App\Models\Template;
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
}
