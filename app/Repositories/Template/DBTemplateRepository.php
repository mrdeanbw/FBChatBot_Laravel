<?php namespace App\Repositories\Template;

use App\Models\Bot;
use App\Models\Template;
use Illuminate\Support\Collection;
use App\Repositories\BaseDBRepository;

class DBTemplateRepository extends BaseDBRepository implements TemplateRepositoryInterface
{

    /**
     * @return string
     */
    public function model()
    {
        return Template::class;
    }

    /**
     * Find a template for a given bot
     * @param int $id
     * @param Bot $bot
     * @return Template|null
     */
    public function findByIdForPage($id, Bot $bot)
    {
        return Template::where('bot_id', $bot->id)->find($id);
    }

    /**
     * Return a list of all explicit templates for a bot.
     * @param Bot $bot
     * @return Collection
     */
    public function explicitTemplatesForBot(Bot $bot)
    {
        return Template::where('bot_id', $bot->id)->where('explicit', true)->get();
    }

    /**
     * Find an explicit template by id for bot.
     * @param int $id
     * @param Bot $bot
     * @return Template|null
     */
    public function findExplicitByIdForBot($id, Bot $bot)
    {
        return Template::where('bot_id', $bot->id)->where('explicit', true)->find($id);
    }
}
