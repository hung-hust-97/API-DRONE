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
            $table->bigIncrements('id');
            $table->string('user');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('avatar');
            $table->string('isadmin')->default('0');
            $table->string('address');
            $table->string('phone');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('flycam_owend')->nullable()->default(null);
            // token xác thực Api call từ mobile
            $table->string('token')->nullable()->default(null);
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
