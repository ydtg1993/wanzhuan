<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDynamicCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dynamic_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('dynamic_id')->comment('动态ID');
            $table->unsignedInteger('from_id')->default(0)->comment('评论/回复人ID');
            $table->unsignedInteger('to_id')->default(0)->comment('被评论/回复人ID');
            $table->string('type', 15)->default('dynamic')->comment('dynamic 评论动态 comments:回复评论');
            $table->string('content', 800)->comment('评论正文');
            $table->char('praise', 10)->default(0)->comment('赞');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dynamic_comments');
    }
}
