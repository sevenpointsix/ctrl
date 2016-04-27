<?php

namespace Sevenpointsix\Ctrl\Models;

use Illuminate\Database\Eloquent\Model;

class CtrlClass extends Model
{

	/**
     * The attributes that are mass assignable; required for firstOrNew when scaffolding the database
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * Return all properties for this class
     */
    public function ctrl_properties()
    {       
        return $this->hasMany('Sevenpointsix\Ctrl\Models\CtrlProperty');
    }

    /**
     * Return the name of the class defined by this ctrl_class
     * @return string
     */
    public function get_class() {
        $class = "\App\Ctrl\Models\\{$this->name}"; 
        return $class;
    }
}
