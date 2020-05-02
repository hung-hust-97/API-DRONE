<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHistoryMaps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('history_maps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('history_maps_id')->unsigned();
            $table->foreign('history_maps_id')->references("history_id")->on("history");
            $table->time("time")->nullable();
            $table->string("lat")->nullable();
            $table->string("lng")->nullable();
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
        Schema::dropIfExists('history_maps');
    }
}
