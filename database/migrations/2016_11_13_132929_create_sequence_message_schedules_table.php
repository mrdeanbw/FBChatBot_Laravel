<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSequenceMessageSchedulesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sequence_message_schedules', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('subscriber_id')->unsigned();
            $table->integer('sequence_id')->unsigned();
            $table->integer('sequence_message_id')->unsigned();
            $table->enum('status', ['pending', 'running', 'completed']);
            $table->timestamp('send_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->foreign('sequence_id')->references('id')->on('sequences')->onDelete('cascade');
            $table->foreign('subscriber_id')->references('id')->on('subscribers')->onDelete('cascade');
            $table->foreign('sequence_message_id')->references('id')->on('sequence_messages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sequence_message_schedules');
    }
}
