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

use Common\Repositories\AutoReplyRule\AutoReplyRuleRepositoryInterface;
use Carbon\Carbon;
use Common\Models\Bot;

$factory->define(\Common\Models\Subscriber::class, function (Faker\Generator $faker) {

    $gender = $faker->randomElement(['male', 'female']);
    $firstName = $gender == 'male'? $faker->firstNameMale : $faker->firstNameFemale;

    $lastSubscribed = $faker->dateTimeThisMonth;
    $lastUnsubscribed = $faker->boolean(75)? null : $faker->dateTimeThisMonth;
    var_dump($lastUnsubscribed);
    if ($lastUnsubscribed && $lastUnsubscribed->getTimestamp() <= $lastSubscribed->getTimestamp()) {
        $temp = $lastSubscribed;
        $lastSubscribed = $lastUnsubscribed;
        $lastUnsubscribed = $temp;
    }

    $active = ! $lastUnsubscribed;;

    $history = [['action' => 'subscribed', 'action_at' => mongo_date($lastSubscribed)]];

    if (! $active) {
        $history[] = ['action' => 'unsubscribed', 'action_at' => mongo_date($lastUnsubscribed)];
    }

    $bot = Bot::latest()->firstOrFail();

    return [
        'first_name'           => $firstName,
        'last_name'            => $faker->lastName,
        'facebook_id'          => $faker->bankAccountNumber,
        'gender'               => $gender,
        'avatar_url'           => $faker->imageUrl(512, 512),
        'last_interaction_at'  => $faker->boolean(75)? $faker->dateTimeThisMonth : null,
        'created_at'           => $faker->dateTimeThisMonth,
        'updated_at'           => $faker->dateTimeThisMonth,
        'locale'               => $faker->locale,
        'active'               => $active,
        'bot_id'               => $bot->_id,
        'timezone'             => Carbon::now($faker->timezone)->offsetHours,
        'last_subscribed_at'   => $lastSubscribed,
        'last_unsubscribed_at' => $lastUnsubscribed,
        'tags'                 => [],
        'sequences'            => [],
        'removed_sequences'    => [],
        'history'              => $history,
    ];
});


$factory->define(\Common\Models\AutoReplyRule::class, function (Faker\Generator $faker) {
    return [
        'mode'        => $faker->randomElement([
            AutoReplyRuleRepositoryInterface::MATCH_MODE_IS,
            AutoReplyRuleRepositoryInterface::MATCH_MODE_PREFIX,
            AutoReplyRuleRepositoryInterface::MATCH_MODE_CONTAINS
        ]),
        'keyword'     => $faker->word,
        'action'      => 'send',
        'readonly'    => false,
        'bot_id'      => null,
        'template_id' => null
    ];
});
