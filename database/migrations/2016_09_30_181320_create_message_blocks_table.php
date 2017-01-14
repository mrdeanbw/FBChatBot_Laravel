<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessageBlocksTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_blocks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('order')->unsigned();
            $table->enum('type', ['text', 'image', 'card', 'button', 'card_container']);
            $table->integer('context_id')->unsigned();
            $table->string('context_type');
            $table->string('text', 320)->nullable();
            $table->string('image_url', 2083)->nullable();
            $table->string('title', 80)->nullable();
            $table->string('subtitle', 80)->nullable();
            $table->string('url', 2083)->nullable();
            $table->boolean('is_disabled')->default(0);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            $table->integer('template_id')->unsigned()->nullable();
            $table->foreign('template_id')->references('id')->on('templates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('message_blocks');
    }
}
