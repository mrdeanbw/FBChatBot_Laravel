<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSequenceMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sequence_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('sequence_id')->unsigned();
            $table->integer('order')->unsigned()->index();
            $table->string('name');
            $table->integer('days')->unsigned();
            $table->boolean('is_live')->default(0);
            $table->timestamp('deleted_at')->nullable();
            $table->foreign('sequence_id')->references('id')->on('sequences')->onDelete('cascade');
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
        Schema::dropIfExists('sequence_messages');
    }
}
