<?php

namespace Sevenpointsix\Ctrl\Models;

use Illuminate\Database\Eloquent\Model;

class One extends Model
{
    /**
     * Get a One to Many relationship
     */
    public function test()
    {       
        return $this->hasMany('Sevenpointsix\Ctrl\Models\Test', 'one_id', 'id');
    }

}
