<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAutoReplyRulesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auto_reply_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('page_id')->unsigned();
            $table->boolean('is_disabled')->default(0);
            $table->integer('template_id')->unsigned()->nullable();
            $table->enum('mode', ['is', 'contains', 'begins_with']);
            $table->string('keyword');
            $table->enum('action', ['subscribe', 'unsubscribe', 'send']);
//            $table->enum('action', ['unsubscribe', 'send']);
            $table->unique(['mode', 'keyword', 'page_id']);
            $table->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
            $table->timestamps();
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
