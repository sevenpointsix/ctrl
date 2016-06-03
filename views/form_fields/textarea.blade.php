@extends('ctrl::form_fields.master')

@section('input')
	<textarea class="form-control" rows="3" id="{{ $field['id'] }}" name="{{ $field['name'] }}">{{ $field['value'] }}</textarea>	
@overwrite
{{-- Note that we need @overwrite because we include multiple instances of templates that extend form_fields.master: see https://github.com/laravel/framework/issues/1058 --}}