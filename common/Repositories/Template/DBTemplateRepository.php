<?php namespace Common\Repositories\Template;

use Common\Models\Bot;
use Common\Models\Subscriber;
use Common\Models\Template;
use Illuminate\Support\Collection;
use Common\Repositories\DBAssociatedWithBotRepository;

class DBTemplateRepository extends DBAssociatedWithBotRepository implements TemplateRepositoryInterface
{

    /**
     * @return string
     */
    public function model()
    {
        return Template::class;
    }

    /**
     * Return a list of all explicit templates for a bot.
     * @param Bot $bot
     * @return Collection
     */
    public function explicitTemplatesForBot(Bot $bot)
    {
        $filter = [
            ['operator' => '=', 'key' => 'explicit', 'value' => true],
            ['operator' => '=', 'key' => 'bot_id', 'value' => $bot->_id],
        ];

        return $this->getAll($filter);
    }

    /**
     * Find an explicit template by id for bot.
     * @param int $id
     * @param Bot $bot
     * @return Template|null
     */
    public function findExplicitByIdForBot($id, Bot $bot)
    {
        $filter = [
            ['operator' => '=', 'key' => '_id', 'value' => $id],
            ['operator' => '=', 'key' => 'explicit', 'value' => true],
            ['operator' => '=', 'key' => 'bot_id', 'value' => $bot->_id],
        ];

        return $this->getOne($filter);
    }
}
