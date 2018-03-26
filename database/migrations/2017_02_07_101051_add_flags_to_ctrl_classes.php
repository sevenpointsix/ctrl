<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFlagsToCtrlClasses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('ctrl_classes', function (Blueprint $table) {
            $table->enum('flags', ['dashboard'])->nullable(); // *** SEE BELOW
        });

        // A good solution to creating SET columns, from http://laravel.io/forum/06-18-2014-what-is-the-mysql-datatype-set-equivalent-in-laravel-schema
        DB::statement("ALTER TABLE `ctrl_classes` CHANGE `flags` `flags` SET('dashboard');");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ctrl_classes', function (Blueprint $table) {
           $table->dropColumn('flags');
        });
    }
}
