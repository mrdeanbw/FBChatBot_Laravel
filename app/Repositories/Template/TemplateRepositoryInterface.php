<?php namespace App\Repositories\Template;

use App\Models\Bot;
use App\Models\Template;
use Illuminate\Support\Collection;
use App\Repositories\CommonRepositoryInterface;

interface TemplateRepositoryInterface extends CommonRepositoryInterface
{

    /**
     * Find a template for a given bot
     * @param int $id
     * @param Bot $bot
     * @return Template|null
     */
    public function findByIdForPage($id, Bot $bot);
    
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
}
