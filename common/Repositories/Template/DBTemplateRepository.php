<?php namespace Common\Repositories\Template;

use Common\Models\Bot;
use Common\Models\Subscriber;
use Common\Models\Template;
use Illuminate\Support\Collection;
use Common\Repositories\DBAssociatedWithBotRepository;
use MongoDB\BSON\ObjectID;

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

    /**
     * @param string        $value
     * @param Bot           $bot
     * @param ObjectID|null $exception
     * @return bool
     */
    public function nameExists($value, Bot $bot, ObjectID $exception = null)
    {
        $query = Template::where('name', $value)->where('bot_id', $bot->_id);
        if ($exception) {
            $query->where('_id', '!=', $exception);
        }

        return $query->exists();
    }
}
