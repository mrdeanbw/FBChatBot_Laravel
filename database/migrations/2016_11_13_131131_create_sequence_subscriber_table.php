<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSequenceSubscriberTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sequence_subscriber', function (Blueprint $table) {
            $table->integer('sequence_id')->unsigned();
            $table->integer('subscriber_id')->unsigned();
            $table->primary(['sequence_id', 'subscriber_id']);
            $table->foreign('sequence_id')->references('id')->on('sequences')->onDelete('cascade');
            $table->foreign('subscriber_id')->references('id')->on('subscribers')->onDelete('cascade');
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
        Schema::dropIfExists('sequence_subscriber');
    }
}
