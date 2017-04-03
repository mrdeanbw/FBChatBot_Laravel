<?php namespace Common\Models;

use Laravel\Cashier\Billable;

/**
 * @property Page           $page
 * @property MainMenu       $main_menu
 * @property bool           $enabled
 * @property string         $timezone
 * @property double         $timezone_offset
 * @property array          $tags
 * @property GreetingText[] $greeting_text
 * @property WelcomeMessage $welcome_message
 * @property DefaultReply   $default_reply
 * @property array          $users
 * @property User           $current_user
 * @property string         $access_token
 * @property array          $templates
 */
class Bot extends BaseModel
{

    use HasEmbeddedArrayModels;
    use Billable {
        subscriptions as relationSubscription; //overwrite the original relation subscription
    }

    /**
     * Get all of the subscriptions for the Stripe model.
     * @return \Jenssegers\Mongodb\Eloquent\Model
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, $this->getForeignKey())->orderBy('created_at', 'desc');
    }

    /**
     * @param array $attributes
     * @param bool  $sync
     * @return BaseModel
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        $attributes['page'] = new Page($attributes['page']);
        $attributes['default_reply'] = new DefaultReply($attributes['default_reply']);
        $attributes['welcome_message'] = new WelcomeMessage($attributes['welcome_message']);
        $attributes['main_menu']['buttons'] = array_map(function ($button) {
            return new Button($button);
        }, $attributes['main_menu']['buttons']);
        $attributes['main_menu'] = new MainMenu($attributes['main_menu']);
        $attributes['greeting_text'] = array_map(function ($greetingText) {
            return new GreetingText($greetingText);
        }, $attributes['greeting_text']);

        return parent::setRawAttributes($attributes, $sync);
    }
}
