<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDefaultUserToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $exists = DB::table('users')->where('email', 'chris@phoenixdigital.agency')->first();        
        if ($exists) {
            DB::table('users')
                ->where('email', 'chris@phoenixdigital.agency')
                ->update(['ctrl_group' => 'root']);            
        }
        else {
            DB::table('users')->insert([
                    'name' => 'Chris Gibson',
                    'email' => 'chris@phoenixdigital.agency',
                    'password' => '$2y$10$vM40/hHkYiaRAfCDJoFVCesP1uUaSEKy9WbDqQtsU4pBWV84aoX1S',
                    'ctrl_group' => 'root'
                ]
            );
        }
    }  

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Not strictly the correct behaviour here, if we've added an existing user to the ctrl_group -- but this doesn't matter.
        DB::table('users')->where('email', 'chris@phoenixdigital.agency')->delete();
    }
}
