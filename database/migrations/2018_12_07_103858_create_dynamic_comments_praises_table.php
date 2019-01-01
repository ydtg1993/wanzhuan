<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDynamicCommentsPraisesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dynamic_comments_praises', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('dynamic_comments_id')->comment('动态评论ID');
            $table->unsignedInteger('user_id')->comment('点赞用户id');
            $table->timestamps();//点赞时间
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dynamic_praises');
    }
}
