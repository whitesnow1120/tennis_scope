<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $table_name = "t_users";
        if (!Schema::hasTable($table_name)) {
            Schema::create($table_name, function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 100);
                $table->string('email', 100);
                $table->string('password', 100);
                $table->integer('created_at');
                $table->integer('updated_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
