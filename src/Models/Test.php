<?php

namespace Sevenpointsix\Ctrl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Test extends Model
{

	// *IF* we have a deleted_at column...
	use SoftDeletes;

	// TODO: use Revisionable: https://github.com/VentureCraft/revisionable

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tests';

    /**
	 * A dummy function to check that I can load models from within my package
	 * @return string
	 */
    public function hello_world() {
    	return 'Hello world';
    }

    // Relationships:

    /*
    	Aha. Finally nailed hasOne and belongsTo etc. From http://advancedlaravel.com/eloquent-relationships-examples:

    	- When using the various belongsTo* relationship functions, the second argument (foreign key) will always reference a field in the current model's table.
		- When using the various has*  relationship functions, the second argument (foreign key) will always reference a field in the foreign model's table.

     */
    
    // Note: do we ever really have one to one relationships? If we have table A and B, and A has a column B_id, then A to B is effectively Many to One, unless we ensure that B_id is always unique in the table, which would rarely happen...?

    /**
     * Get a Many to One relationship
     */
    public function one()
    {
        return $this->belongsTo('Sevenpointsix\Ctrl\Models\One', 'one_id', 'id');
        // The inverse of this relationship (on One) is:
        // return $this->hasMany('Sevenpointsix\Ctrl\Models\Test', 'one_id', 'id');
    }

    /**
     * Get a One to Many relationship
     */
    public function many()
    {
        return $this->hasMany('Sevenpointsix\Ctrl\Models\Many', 'test_id', 'id');        
        // The inverse of this relationship (on the Many model, which has a test_id field) is:
        // return $this->belongsTo('Sevenpointsix\Ctrl\Models\Test', 'test_id', 'id');
        
    }

    /**
     * Get a Many to Many relationship
     */
    public function pivot()
    {
        return $this->belongsToMany('Sevenpointsix\Ctrl\Models\Pivot','pivot_test','test_id','pivot_id');
        // The inverse of this relationship (on Pivot) is:
        // return $this->belongsToMany('Sevenpointsix\Ctrl\Models\Test','pivot_test','test_id','pivot_id');        
    }
}
