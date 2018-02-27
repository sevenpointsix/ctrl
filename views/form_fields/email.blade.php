@extends('ctrl::form_fields.master')

@section('input')
	<div class="input-group">
    	<span class="input-group-addon">@</span>
		<input type="email" class="form-control" id="{{ $field['id'] }}" name="{{ $field['name'] }}" value="{{ $field['value'] }}" placeholder="" @if (!empty($field['readOnly'])) readonly @endif>
	</div>

@overwrite
{{-- Note that we need @overwrite because we include multiple instances of templates that extend form_fields.master: see https://github.com/laravel/framework/issues/1058 --}}