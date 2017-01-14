<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBroadcastsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->enum('timezone', ['same_time', 'time_travel', 'limit_time']);
            $table->enum('notification', ['regular', 'silent_push', 'no_push']);
            $table->timestamp('send_at')->nullable();
            $table->string('date');
            $table->string('time');
            $table->tinyInteger('send_from')->default(9);
            $table->tinyInteger('send_to')->default(21);
            $table->integer('page_id')->unsigned();
            $table->integer('sent')->default(0);
            $table->integer('read')->default(0);
            $table->integer('clicked')->default(0);
            $table->boolean('filter_enabled')->default(0);
            $table->enum('filter_type', ['and', 'or']);
            $table->enum('status', ['pending', 'running', 'completed'])->default('pending');
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
        Schema::dropIfExists('broadcasts');
    }
}
