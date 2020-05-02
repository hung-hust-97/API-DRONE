<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImageUrl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::enableForeignKeyConstraints();
        Schema::create('image_url', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('image_url_id')->unsigned()->nullable()->default(null);
            $table->foreign('image_url_id')->references("fid")->on("fcam");
            $table->bigInteger('image_url_id_user')->unsigned()->nullable()->default(null);
            $table->foreign('image_url_id_user')->references("id")->on("users");
            $table->string("url")->unique();
            $table->string("store")->unique();
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
        Schema::dropIfExists('image_url');
    }
}
