<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBroadcastSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('broadcast_schedules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('broadcast_id')->unsigned();
            $table->double('timezone')->default(0);
            $table->timestamp('send_at')->nullable();
            $table->enum('status', ['pending', 'running', 'completed'])->default('pending');
            $table->foreign('broadcast_id')->references('id')->on('broadcasts')->onDelete('cascade');
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
        Schema::dropIfExists('broadcast_schedules');
    }
}
