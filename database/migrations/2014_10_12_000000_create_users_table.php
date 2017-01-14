<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('facebook_id')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name');
            $table->string('email')->unique()->nullable();
            $table->string('gender')->nullable();
            $table->string('avatar_url', 2047);
            $table->string('access_token', 511);
            $table->text('granted_permissions');
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
        Schema::dropIfExists('users');
    }
}
