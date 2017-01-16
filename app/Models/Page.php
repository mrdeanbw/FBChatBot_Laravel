<?php namespace App\Models;

use Laravel\Cashier\Billable;

/**
 * App\Models\Page
 *
 * @property int                                                                              $id
 * @property string                                                                           $facebook_id
 * @property string                                                                           $name
 * @property string                                                                           $url
 * @property string                                                                           $avatar_url
 * @property string                                                                           $access_token
 * @property bool                                                                             $bot_enabled
 * @property float                                                                            $bot_timezone
 * @property string                                                                           $bot_timezone_string
 * @property string                                                                           $stripe_id
 * @property string                                                                           $card_brand
 * @property string                                                                           $card_last_four
 * @property \Carbon\Carbon                                                                   $trial_ends_at
 * @property bool                                                                             $is_active
 * @property \Carbon\Carbon                                                                   $created_at
 * @property \Carbon\Carbon                                                                   $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[]                 $users
 * @property-read \App\Models\MainMenu                                                        $mainMenu
 * @property-read \App\Models\GreetingText                                                    $greetingText
 * @property-read \App\Models\WelcomeMessage                                                  $welcomeMessage
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessagePreview[]       $messagePreviews
 * @property-read \App\Models\DefaultReply                                                    $defaultReply
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Template[]             $templates
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[]                  $tags
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Subscriber[]           $subscribers
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AutoReplyRule[]        $autoReplyRules
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Broadcast[]            $broadcasts
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Sequence[]             $sequences
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Widget[]               $widgets
 * @property-read mixed                                                                       $plan
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SubscriptionHistory[]  $subscriptionHistory
 * @property-read mixed                                                                       $payment_plan
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageInstanceClick[] $messageClicks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageInstance[]      $messageInstances
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Cashier\Subscription[]    $subscriptions
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Page whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Page whereFacebookId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Page whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Page whereUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Page whereAvatarUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Page whereAccessToken($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Page whereBotEnabled($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Page whereBotTimezone($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Page whereBotTimezoneString($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Page whereStripeId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Page whereCardBrand($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Page whereCardLastFour($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Page whereTrialEndsAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Page whereIsActive($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Page whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Page whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class Page extends BaseModel
{

    use Billable;


    protected $dates = ['trial_ends_at'];

    protected $casts = ['bot_enabled' => 'boolean'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function mainMenu()
    {
        return $this->hasOne(MainMenu::class);
    }

    public function greetingText()
    {
        return $this->hasOne(GreetingText::class);
    }

    public function welcomeMessage()
    {
        return $this->hasOne(WelcomeMessage::class);
    }

    public function messagePreviews()
    {
        return $this->hasMany(MessagePreview::class);
    }

    public function defaultReply()
    {
        return $this->hasOne(DefaultReply::class);
    }

    public function templates()
    {
        return $this->hasMany(Template::class);
    }

    public function tags()
    {
        return $this->hasMany(Tag::class);
    }

    public function subscribers()
    {
        return $this->hasMany(Subscriber::class);
    }

    public function activeSubscribers()
    {
        return $this->subscribers()->whereIsActive(1);
    }

    public function autoReplyRules()
    {
        return $this->hasMany(AutoReplyRule::class);
    }

    public function broadcasts()
    {
        return $this->hasMany(Broadcast::class);
    }

    public function sequences()
    {
        return $this->hasMany(Sequence::class);
    }

    public function widgets()
    {
        return $this->hasMany(Widget::class);
    }

    public function getPlanAttribute()
    {
        return ($subscription = $this->subscription('main'))? "Pro {$subscription->stripe_plan}" : "Free";
    }

    public function subscriptionHistory()
    {
        return $this->hasMany(SubscriptionHistory::class);
    }


    public function getPaymentPlanAttribute()
    {
        $subscription = $this->subscription();
        if (! $subscription) {
            return null;
        }

        return PaymentPlan::where('stripe_id', $subscription->stripe_plan)->firstOrFail();
    }

    public function messageClicks()
    {
        return $this->hasManyThrough(MessageInstanceClick::class, MessageInstance::class);
    }

    public function messageInstances()
    {
        return $this->hasMany(MessageInstance::class);
    }

    protected static function boot()
    {
        static::deleting(function (Page $page) {
            $page->mainMenu->delete();
            $page->defaultReply->delete();
            $page->welcomeMessage->delete();

            // [Maybe] delete one by one, delete associated models
            $page->templates->delete();
            $page->broadcasts->delete();
            $page->sequences->delete();
            $page->widgets->delete();
            $page->messagePreviews->delete();
        });
    }

}
