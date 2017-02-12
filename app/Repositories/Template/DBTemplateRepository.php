<?php namespace App\Repositories\Template;

use App\Models\Bot;
use App\Models\Subscriber;
use App\Models\Template;
use Illuminate\Support\Collection;
use App\Repositories\DBAssociatedWithBotRepository;

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
     * @param Template   $templateId
     * @param Subscriber $subscriber
     * @param array      $buttonPath
     * @param int        $incrementBy
     */
    public function recordButtonClick(Template $templateId, Subscriber $subscriber, array $buttonPath, $incrementBy = 1)
    {
        $key = 'messages.' . implode('.', $buttonPath) . '.clicks';

        Template::where('_id', $templateId)->update([
            '$inc'      => ["{$key}.total" => $incrementBy],
            '$addToSet' => ["{$key}.unique" => $subscriber->id]
        ]);
    }
}
