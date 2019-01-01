<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDynamicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dynamics', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('发表人ID');
            $table->string('content', 800)->comment('动态正文');
            $table->unsignedInteger('praise')->default(0)->comment('赞数');
            $table->unsignedInteger('comments_num')->default(0)->comment('评论数');
            $table->timestamps();//发表时间
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dynamics');
    }
}
