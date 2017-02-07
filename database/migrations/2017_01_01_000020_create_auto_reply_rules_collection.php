<?php

use Jenssegers\Mongodb\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAutoReplyRulesCollection extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auto_reply_rules', function (Blueprint $table) {
//            $table->index(['bot_id', 'mode', 'keyword', 'mode_priority']);
            $table->index(['bot_id', 'mode', 'keyword']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('auto_reply_rules');
    }
}