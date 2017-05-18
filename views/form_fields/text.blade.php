@extends('ctrl::form_fields.master')

@section('input')
	<input type="text" class="form-control" id="{{ $field['id'] }}" name="{{ $field['name'] }}" value="{{ $field['value'] }}" placeholder="" @if (!empty($field['readOnly'])) readonly @endif>
@overwrite
{{-- Note that we need @overwrite because we include multiple instances of templates that extend form_fields.master: see https://github.com/laravel/framework/issues/1058 --}}