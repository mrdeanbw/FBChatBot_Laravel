<?php

use Common\Models\PaymentPlan;
use Illuminate\Database\Seeder;

class PaymentPlanSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        PaymentPlan::truncate();
        foreach ([500 => 900, 1000 => 1700, 2500 => 2900, 5000 => 4500, 10000 => 6900, 20000 => 12500, 999999999 => 20000] as $subscriberCount => $price) {
            $plan = new PaymentPlan();
            $plan->name = 'pro';
            $plan->subscribers = $subscriberCount;
            $plan->price = $price;
            $plan->save();
        }
    }
}