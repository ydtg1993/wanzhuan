<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDynamicRemindTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dynamic_remind', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('dynamic_id')->comment('动态ID');
            $table->unsignedInteger('from_id')->comment('事件发起人');
            $table->unsignedInteger('to_id')->comment('事件接收人');
            $table->unsignedTinyInteger('type')->comment('通知类型 0:点赞,1:评论 2:回复');
            $table->string('message',800)->comment('评论或回复内容')->default('');
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
        Schema::dropIfExists('dynamic_remind');
    }
}
