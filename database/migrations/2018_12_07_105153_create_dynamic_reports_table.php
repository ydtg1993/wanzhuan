<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDynamicReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dynamic_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('举报人ID');
            $table->unsignedInteger('report_id')->comment('举报对象id');
            $table->string('type')->comment('类型 dynamic comments');
            $table->string('describe')->nullable()->commetn('描述');
            $table->timestamps();//举报时间
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dynamic_reports');
    }
}
