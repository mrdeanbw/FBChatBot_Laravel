<?php
namespace App\Transformers;

use App\Models\PaymentPlan;

class PaymentPlanTransformer extends BaseTransformer
{

    public function transform(PaymentPlan $plan)
    {
        return [
            'id'          => (int)$plan->id,
            'subscribers' => (int)$plan->subscribers,
            'price'       => (int)$plan->price,
        ];
    }
}