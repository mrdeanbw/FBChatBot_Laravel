<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

use App\Models\Page;
use Carbon\Carbon;

$factory->define(\App\Models\Subscriber::class, function (Faker\Generator $faker) {

    $gender = $faker->randomElement(['male', 'female']);
    $firstName = $gender == 'male'? $faker->firstNameMale : $faker->firstNameFemale;

    $active = 1;
    $lastSubscribed = $faker->dateTimeThisMonth;
    $lastUnsubscribed = $faker->boolean(75)? null : $faker->dateTimeThisMonth;

    if ($lastUnsubscribed) {
        $active = $lastUnsubscribed->getTimestamp() <= $lastSubscribed->getTimestamp();
    }

    $pageId = Page::firstOrFail()->id;

    return [
        'first_name'           => $firstName,
        'last_name'            => $faker->lastName,
        'facebook_id'          => $faker->bankAccountNumber,
        'gender'               => $gender,
        'avatar_url'           => $faker->imageUrl(512, 512),
        'last_contacted_at'    => $faker->boolean(75)? $faker->dateTimeThisMonth : null,
        'created_at'           => $faker->dateTimeThisMonth,
        'updated_at'           => $faker->dateTimeThisMonth,
        'is_active'            => $active,
        'page_id'              => $pageId,
        'timezone'             => Carbon::now($faker->timezone)->offsetHours,
        'last_subscribed_at'   => $lastSubscribed,
        'last_unsubscribed_at' => $lastUnsubscribed,
    ];
});
