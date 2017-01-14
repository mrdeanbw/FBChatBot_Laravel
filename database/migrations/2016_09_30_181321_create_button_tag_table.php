<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateButtonTagTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('button_tag', function (Blueprint $table) {
            $table->integer('tag_id')->unsigned();
            $table->integer('button_id')->unsigned();
            $table->boolean('add')->default(1);
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
            $table->foreign('button_id')->references('id')->on('message_blocks')->onDelete('cascade');
            $table->primary(['button_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('button_tag');
    }
}
