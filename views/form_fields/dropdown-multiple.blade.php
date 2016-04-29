<div class="form-group">
    <label for="{{ $field['id'] }}">{{ $field['label'] }}</label>
    
    @if (is_array($field['value'])) {{-- Indicates a multiple select --}}

    <select class="form-control" id="{{ $field['id'] }}" name="{{ $field['name'] }}[]" multiple>    	
    	@foreach ($field['values'] as $value=>$text)
	  		<option value="{{ $value }}" @if (array_key_exists($value,$field['value'])) selected="selected" @endif>{{ $text }}</option>
	  	@endforeach
	</select>

    @else

    <select class="form-control" id="{{ $field['id'] }}" name="{{ $field['name'] }}">
    	<option value="">None</option>
    	@foreach ($field['values'] as $value=>$text)
	  		<option value="{{ $value }}" @if ($field['value'] == $value) selected="selected" @endif>{{ $text }}</option>
	  	@endforeach
	</select>

	@endif

    @if (!empty($field['tip']))
    <p class="help-block">{{ $field['tip'] }}</p>
    @endif
</div>
