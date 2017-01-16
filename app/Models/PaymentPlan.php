<?php namespace App\Models;

/**
 * App\Models\PaymentPlan
 *
 * @property int $id
 * @property string $name
 * @property int $subscribers
 * @property int $price
 * @property string $stripe_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\PaymentPlan whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\PaymentPlan whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\PaymentPlan whereSubscribers($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\PaymentPlan wherePrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\PaymentPlan whereStripeId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\PaymentPlan whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\PaymentPlan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class PaymentPlan extends BaseModel
{

    protected static function boot()
    {
        parent::boot();
        static::updating(function ($model) {
            static::saveStripeId($model);
        });
        static::creating(function ($model) {
            static::saveStripeId($model);
        });
    }

    private static function saveStripeId(PaymentPlan $plan)
    {
        $plan->stripe_id = "{$plan->name}_{$plan->subscribers}";
    }
}
