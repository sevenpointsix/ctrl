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
        return $this->hasMany('Sevenpointsix\Ctrl\Models\CtrlProperty')->orderBy('order');
    }

    /**
     * Return the name of the class defined by this ctrl_class
     * @return string
     */
    public function get_class() {
        $class = "\App\Ctrl\Models\\{$this->name}"; 
        return $class;
    }

     /**
     * Return the name of the icon for this ctrl_class
     * @return string
     */
    public function get_icon() {
        $icon = '';
        if ($this->icon) {
            $icon = $this->icon;
        }
        else {
            $icon = 'fa-toggle-right';
        }        
        if (strpos($icon,'fa') === 0) { // Identify Font Awesome icons automatically
            $icon = "fa $icon fa-fw"; // Will we always want fa-fw?
        }
        return $icon;
    }

    /**
     * Return the plural name of this class
     * @return string
     */
    public function get_plural() {
        $plural = '';
        if ($this->plural) {
            $plural = $this->plural;
        }
        else if ($this->singular) {
            $plural = str_plural($this->singular);
        }
        else {
            $plural = str_plural($this->name);
        }
        return strtolower($plural);
    }
}
