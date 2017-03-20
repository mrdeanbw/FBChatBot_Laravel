<?php namespace Common\Models;

use Laravel\Cashier\Billable;

/**
 * @property Page           $page
 * @property MainMenu       $main_menu
 * @property bool           $enabled
 * @property string         $timezone
 * @property double         $timezone_offset
 * @property array          $tags
 * @property GreetingText   $greeting_text
 * @property WelcomeMessage $welcome_message
 * @property DefaultReply   $default_reply
 * @property array          $users
 * @property User           $current_user
 * @property string         $access_token
 * @property array          $messages
 */
class Bot extends BaseModel
{

    use HasEmbeddedArrayModels;
    use Billable {
        subscriptions as relationSubscribtion; //overwrite the original relation subscription
    }

    public $arrayModels = [
        'page'            => Page::class,
        'main_menu'       => MainMenu::class,
        'greeting_text'   => GreetingText::class,
        'default_reply'   => DefaultReply::class,
        'welcome_message' => WelcomeMessage::class,
    ];

    /**
     * Get all of the subscriptions for the Stripe model.
     * @return \Jenssegers\Mongodb\Eloquent\Model
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, $this->getForeignKey())->orderBy('created_at', 'desc');
    }
}
