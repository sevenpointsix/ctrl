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
    protected $fillable = ['name','ctrl_class_id','related_to_id','relationship_type','foreign_key','local_key','pivot_table','field_type','label','fieldset'];

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



    /**
     * Add a value to a "SET" field
     * Lifted from the CI version!
     * @param  string $field The SET field we're adding to
     * @param  string $value The value we're adding
     * @return NULL
     */
    public function add_to_set($field,$value) { 
        $set = array_filter(explode(',',$this->$field));
        array_push($set,$value);
        $this->$field = implode(',', $set);     
    }

    /**
     * Have a guess at the kind of form field type we should use to represent a database column
     * Also lifted from CI CTRL!
     * @param  string $column_type The column type (eg, VARCHAR)
     * @return string              The form field type
     */
    public function get_field_type_from_column($column_type) {
        $column_type = explode('(',$column_type); // Lazy! Use preg_match properly...
        $column_type = $column_type[0];     
        switch ($column_type) {
            case 'varchar':
            case 'varbinary': // Is 'text' the best type here?
            case 'char':
            case 'int':
            case 'smallint':
            case 'bigint':
            case 'mediumint':
            case 'decimal':
            case 'float':
                // 'int' could be a relationship, but we check this separately
                $type = 'text';
                break;
            case 'text':
            case 'mediumtext':
            case 'longtext':
            case 'blob':
                $type = 'textarea';
                break;
            case 'enum':
            case 'set':
                // NOTE: not sure that we handle 'set' values, but with DMZ we're unlikely to use them
                $type = 'dropdown';
                break;
            case 'tinyint':
                $type = 'checkbox';
                break;
            case 'date':
                $type = 'date';
                break;
            case 'time':
                $type = 'time';
                break;
            case 'datetime':
            case 'timestamp': // We'd never edit a timestamp directly, surely?
                $type = 'datetime';
                break;
            default:
                $type = 'text';
                trigger_error("Unhandled type $column_type");
        }
        return $type;
    }

    /**
     * A shortcut function to quickly check whether a class is flagged $flag; saves calling in_array() etc all the time
     * @param  string $flag THe flag we want to check
     * @return boolean          Whether or not the flag is set
     */
    public function flagged($flag) {
        return (in_array($flag, explode(',',$this->flags)));
    }

}
