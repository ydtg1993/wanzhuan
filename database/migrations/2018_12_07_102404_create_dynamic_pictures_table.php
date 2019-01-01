<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDynamicPicturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dynamic_pictures', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('dynamic_id')->comment('所属动态');
            $table->string('url', 200)->comment('图片路径');
            $table->unsignedTinyInteger('sort')->comment('图片排序');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dynamic_pictures');
    }
}
