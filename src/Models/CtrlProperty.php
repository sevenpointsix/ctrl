<?php

namespace Sevenpointsix\Ctrl\Models;

use Illuminate\Database\Eloquent\Model;

class CtrlProperty extends Model
{

	/**
     * The attributes that are mass assignable; required for firstOrNew when scaffolding the database
     *
     * @var array
     */
    protected $fillable = ['name','ctrl_class_id','related_to_id','relationship_type','foreign_key','local_key','pivot_table'];

    /**
     * Get the class for this property
     */
    public function ctrl_class()
    {
        return $this->belongsTo('Sevenpointsix\Ctrl\Models\CtrlClass');
    }

    /**
     * Get the name of the form field that corresponds to this property
     * This is very closely related to the "column name" in the database; universally perhaps?
     * It may be preferable to actually store this in the database, 
     * but the name and column_name are always identical apart from for relationships
     * This is TBC.     
     */
    public function get_field_name() {
        if ($this->related_to_id && $this->relationship_type == 'belongsTo') {
            // Possibly also true for belongsToMany?
            $column = $this->foreign_key;
        }
        else {
            $column = $this->name;
        }
        return $column;
    }
}
