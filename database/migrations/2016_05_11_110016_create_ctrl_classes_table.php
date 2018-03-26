<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCtrlClassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('ctrl_classes')) {
            Schema::create('ctrl_classes', function (Blueprint $table) {
                $table->increments('id');

                $table->string('name');
                $table->string('table_name');
                $table->string('singular');
                $table->string('plural');
                $table->string('description');
                $table->enum('permissions', ['list','add','edit','delete','view','copy','export','import','preview'])->nullable(); // *** SEE BELOW
                $table->string('menu_title')->nullable();
                $table->string('icon');
                $table->integer('order');

                $table->timestamps();
            });

            // A good solution to creating SET columns, from http://laravel.io/forum/06-18-2014-what-is-the-mysql-datatype-set-equivalent-in-laravel-schema
            DB::statement("ALTER TABLE `ctrl_classes` CHANGE `permissions` `permissions` SET('list','add','edit','delete','view','copy','export','import','preview');");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('ctrl_classes');
    }
}
