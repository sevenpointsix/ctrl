<?php

namespace Sevenpointsix\Ctrl\Models;

use Illuminate\Database\Eloquent\Model;

class Many extends Model
{
    /**
     * Get a Many to One relationship
     */
    public function test()
    {       
        return $this->belongsTo('Sevenpointsix\Ctrl\Models\Test', 'test_id', 'id'); // I think?
    }
}
