<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscribersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('facebook_id');
            $table->integer('page_id')->unsigned();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('avatar_url', 2047);
            $table->string('locale')->nullable();
            $table->double('timezone')->default(0);
            $table->string('gender')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->boolean('is_active')->default(1);
            $table->unique(['page_id', 'facebook_id']);
            $table->timestamp('last_subscribed_at')->nullable();
            $table->timestamp('last_unsubscribed_at')->nullable();
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
        Schema::dropIfExists('subscribers');
    }
}
