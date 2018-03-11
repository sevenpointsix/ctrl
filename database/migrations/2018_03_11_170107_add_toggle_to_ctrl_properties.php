<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddToggleToCtrlProperties extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE `ctrl_properties` CHANGE `flags` `flags` SET('string','header','required','read_only','search','filtered_list','linked_list','toggle');");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE `ctrl_properties` CHANGE `flags` `flags` SET('string','header','required','read_only','search','filtered_list','linked_list');");
    }
}
