<?php namespace App\Transformers;

use App\Models\Bot;
use MongoDB\BSON\ObjectID;

class BotTransformer extends BaseTransformer
{

    protected $defaultIncludes = ['page'];

    protected $availableIncludes = [
        'main_menu',
        'greeting_text',
        'default_reply',
        'welcome_message'
    ];

    public function transform(Bot $bot)
    {
        $ret = [
            'id'              => $bot->id,
            'enabled'         => $bot->enabled,
            'timezone'        => $bot->timezone,
            'timezone_offset' => $bot->timezone_offset,
            'tags'            => $bot->tags,
        ];

        if ($subscriberId = $this->getSubscriberId($bot)) {
            $ret['subscriber_id'] = $subscriberId;
        }

        return $ret;
    }

    /**
     * @param Bot $bot
     * @return \League\Fractal\Resource\Item
     */
    public function includePage(Bot $bot)
    {
        return $this->item($bot->page, new PageTransformer(), false);
    }

    /**
     * @param Bot $bot
     * @return \League\Fractal\Resource\Item
     */
    public function includeGreetingText(Bot $bot)
    {
        return $this->item($bot->greeting_text, new GreetingTextTransformer(), false);
    }

    /**
     * @param Bot $bot
     * @return \League\Fractal\Resource\Item
     */
    public function includeWelcomeMessage(Bot $bot)
    {
        return $this->item($bot->welcome_message, new WelcomeMessageTransformer(), false);
    }

    /**
     * @param Bot $bot
     * @return \League\Fractal\Resource\Item
     */
    public function includeMainMenu(Bot $bot)
    {
        return $this->item($bot->main_menu, new MainMenuTransformer(), false);
    }

    /**
     * @param Bot $bot
     * @return \League\Fractal\Resource\Item
     */
    public function includeDefaultReply(Bot $bot)
    {
        return $this->item($bot->default_reply, new DefaultReplyTransformer(), false);
    }


    /**
     * @param Bot $bot
     * @return null|string
     */
    private function getSubscriberId(Bot $bot)
    {
        if (empty($bot->current_user)) {
            return null;
        }

        foreach ($bot->users as $user) {
            if ($user['user_id'] === $bot->current_user->_id) {
                return $user['subscriber_id']->__toString();
            }
        }

        return null;
    }
}