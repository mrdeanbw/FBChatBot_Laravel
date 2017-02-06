<?php

use App\Models\Bot;
use App\Models\AutoReplyRule;
use Illuminate\Database\Seeder;

class AutoReplyRuleSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AutoReplyRule::truncate();

        for($i = 0; $i < 10; $i++){
            $bot = Bot::create([]);

            factory(AutoReplyRule::class, 500)->create([
                'bot_id' => $bot->id
            ]);

            $bot->delete();
        }
    }
}