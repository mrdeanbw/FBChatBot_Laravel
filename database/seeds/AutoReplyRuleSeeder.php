<?php

use Common\Models\Bot;
use Common\Models\AutoReplyRule;
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

        for($i = 0; $i < 1000; $i++){
            $bot = Bot::create([]);

            factory(AutoReplyRule::class, 1000)->create([
                'bot_id' => $bot->_id
            ]);

            $bot->delete();
        }
    }
}