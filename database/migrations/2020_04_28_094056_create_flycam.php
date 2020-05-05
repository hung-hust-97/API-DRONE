<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFlycam extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcam', function (Blueprint $table) {
            $table->bigIncrements('fid');
            $table->bigInteger('owner_id')->unsigned();
            // Owner là khóa ngoại của fcam với cha là users
            $table->foreign('owner_id')->references("id")->on("users");
            $table->string("model");
            $table->string("camera")->nullable()->default(null);
            $table->string("maximum_altitude")->nullable()->default(null);
            $table->string("maximum_range")->nullable()->default(null);
            $table->string("speed")->nullable()->default(null);
            $table->string("pin")->nullable()->default(null);
            $table->text("images")->nullable()->default(null);
            $table->string("guarantee")->nullable()->default(null);
            $table->text("specifications")->nullable()->default(null);
            $table->string("history")->nullable()->default(null);
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
        Schema::dropIfExists('fcam');
    }
}
