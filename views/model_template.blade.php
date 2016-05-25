{!! '<'.'?php' !!}

namespace App\Ctrl\Models;

use Illuminate\Database\Eloquent\Model;

@if (!empty($soft_deletes))
use Illuminate\Database\Eloquent\SoftDeletes;
@endif

@if ($model_name == 'User')
use Hash;
@endif

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

    {{-- Experimenting with mutators for the User class --}}
    @if ($model_name == 'User')
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
        return Hash::make($value);
    }
    @endif
}
