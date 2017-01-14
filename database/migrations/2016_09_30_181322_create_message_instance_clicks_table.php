<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessageInstanceClicksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_instance_clicks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('message_instance_id')->unsigned();
            $table->foreign('message_instance_id')->references('id')->on('message_instances')->onDelete('cascade');
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
        Schema::dropIfExists('message_instance_clicks');
    }
}
