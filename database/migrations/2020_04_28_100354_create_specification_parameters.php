<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSpecificationParameters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('specification_parameters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('specification_parameters_id')->unsigned();
            $table->foreign('specification_parameters_id')->references("specifications_id")->on("specifications");
            $table->string("name");
            $table->string("value");
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
        Schema::dropIfExists('specification_parameters');
    }
}
