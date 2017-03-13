{!! '<'.'?php' !!}

namespace App\Ctrl\Models;

use Illuminate\Database\Eloquent\Model;

@if (!empty($soft_deletes))
use Illuminate\Database\Eloquent\SoftDeletes;
@endif

use Hash;

class {{ $model_name }} extends Model
{

	@if (!empty($soft_deletes))
	   use SoftDeletes;
    @endif

	// TODO: use Revisionable: https://github.com/VentureCraft/revisionable

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = '{{ $table_name }}';

    @if (!empty($fillable))
    /**
     * The attributes that are mass assignable; required for firstOrNew when scaffolding the database
     *
     * @var array
     */
    protected $fillable = ['{!! implode("','",$fillable) !!}'];
    @endif

    @if (empty($timestamps))
    /**
     * We don't have timestamps for this model
     * @type {Boolean}
     */
    public $timestamps = false;
    @endif

    /**
     * RELATIONSHIPS:
     */

    @foreach ($belongsTo as $relationship)
/**
     * Define the {{ $relationship['name'] }} relationship
     */
    public function {{ $relationship['name'] }}()
    {
        return $this->belongsTo('App\Ctrl\Models\{{ $relationship['model'] }}', '{{ $relationship['foreign_key'] }}', '{{ $relationship['local_key'] }}');
    }
    @endforeach

    @foreach ($hasMany as $relationship)
/**
     * Define the {{ $relationship['name'] }} relationship
     */
    public function {{ $relationship['name'] }}()
    {
        return $this->hasMany('App\Ctrl\Models\{{ $relationship['model'] }}', '{{ $relationship['foreign_key'] }}', '{{ $relationship['local_key'] }}');
    }
    @endforeach


    @foreach ($belongsToMany as $relationship)
/**
     * Define the {{ $relationship['name'] }} relationship
     */
    public function {{ $relationship['name'] }}()
    {
        return $this->belongsToMany('App\Ctrl\Models\{{ $relationship['model'] }}','{{ $relationship['pivot_table'] }}', '{{ $relationship['local_key'] }}','{{ $relationship['foreign_key'] }}');
    }
    @endforeach    

    {{-- Set a password mutator so that we never show the password; is there any harm including this for *all* models? --}}
    /**
     * Don't retrieve the password
     *
     * @param  string  $value
     * @return string
     */
    public function getPasswordAttribute($value)
    {
        return '';
    }

    /**
     * Encrypt password when saving
     *
     * @param  string  $value
     * @return string
     */
    public function setPasswordAttribute($value)
    {
        // return Hash::make($value);
        if (!empty($value)) $this->attributes['password']  = Hash::make($value);
    }

    {{-- Mutators for the User class to handle groups --}}
    @if ($model_name == 'User')

    /**
     * Stick everyone in a "user" group by default; this needs refining, we've not really tackled "groups" on this version of CTRL yet
     *
     * @param  string  $value
     * @return string
     */
    public function setCtrlGroupAttribute($value)
    {
        if (empty($value)) $this->attributes['ctrl_group'] = 'user';
    }

    @endif
}
