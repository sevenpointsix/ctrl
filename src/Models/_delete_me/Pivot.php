<?php

namespace Sevenpointsix\Ctrl\Models;

use Illuminate\Database\Eloquent\Model;

class Pivot extends Model
{
    /**
     * Get a Many to Many relationship
     */
    public function test()
    {
        return $this->belongsToMany('Sevenpointsix\Ctrl\Models\Test','pivot_test','pivot_id','test_id');        
    }
}
