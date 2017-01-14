<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAudienceFilterGroupsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audience_filter_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('context_id')->unsigned();
            $table->string('context_type');
            $table->enum('type', ['and', 'or', 'none']);
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
        Schema::dropIfExists('audience_filter_groups');
    }
}
