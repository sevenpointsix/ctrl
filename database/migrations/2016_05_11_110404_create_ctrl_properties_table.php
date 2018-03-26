<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCtrlPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('ctrl_properties')) {
            Schema::create('ctrl_properties', function (Blueprint $table) {
                $table->increments('id');

                $table->string('name');
                $table->integer('ctrl_class_id')->nullable();
                $table->integer('related_to_id')->nullable();
                $table->enum('relationship_type', array('belongsTo','hasMany','belongsToMany'))->nullable();
                $table->string('foreign_key')->nullable();
                $table->string('local_key')->nullable();
                $table->string('pivot_table')->nullable();
                $table->enum('flags',['string','header','required','read_only','search','filtered_list','linked_list'])->nullable(); // *** SEE BELOW
                $table->string('label')->nullable();
                $table->enum('field_type', array('text','textarea','redactor','dropdown','checkbox','date','datetime','image','file','email','froala'))->nullable();
                $table->string('fieldset')->nullable();
                $table->text('tip')->nullable();
                $table->integer('order');

                $table->timestamps();

                $table->index('ctrl_class_id');
                $table->index('related_to_id');
            });

            // A good solution to creating SET columns, from http://laravel.io/forum/06-18-2014-what-is-the-mysql-datatype-set-equivalent-in-laravel-schema
            DB::statement("ALTER TABLE `ctrl_properties` CHANGE `flags` `flags` SET('string','header','required','read_only','search','filtered_list','linked_list');");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('ctrl_properties');
    }
}
