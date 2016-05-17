@extends('ctrl::form_fields.master')

@section('input')
	<input type="hidden" id="{{ $field['id'] }}" name="{{ $field['name'] }}" value="{{ $field['value'] }}">
@overwrite
{{-- Note that we need @overwrite because we include multiple instances of templates that extend form_fields.master: see https://github.com/laravel/framework/issues/1058 --}}