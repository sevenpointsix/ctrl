@extends('ctrl::template')


@section('js')
<script src="{{ asset('assets/vendor/ctrl/vendor/jquery.form/jquery.form.min.js') }}"></script>
<script src="{{ asset('assets/vendor/ctrl/js/forms.js') }}"></script>
@stop

@section('css')
	<style>
	ul.nav.nav-tabs {
		margin-bottom: 20px;
	}
	</style>
@stop

@section('content')

	<div class="page-header">
		<h1>@if ($ctrl_class->icon)<i class="fa fa-cog"></i>@endif
		{{ ($object->id)?'Edit':'Add' }} a {{ $ctrl_class->name }} <small>Description if necessary</small>
		<a href="{{ route('ctrl::list_objects',$ctrl_class->id) }}" class="btn btn-default pull-right"><i class="fa fa-toggle-left"></i> Back</a>
		</h1>
	</div>

	<div>

	  <!-- Nav tabs -->
	  <ul class="nav nav-tabs" role="tablist">
	    <li role="presentation" class="active"><a href="#details" aria-controls="details" role="tab" data-toggle="tab"><i class="fa fa-th-list"></i> Details</a></li>
	  </ul>

	  <form class="ajax" method="post" action="{{ route('ctrl::save_object',[$ctrl_class->id,$object->id])}}">

		  <!-- Tab panes -->
		  <div class="tab-content">
		    <div role="tabpanel" class="tab-pane fade in active" id="details">
		    	
				{!! csrf_field() !!}	

				@include('ctrl::form_errors')

				@foreach ($form_fields as $form_field)

					@include('ctrl::form_fields.'.$form_field['type'], ['field' => $form_field])

				@endforeach
				
			</div>
		  </div>
		  <hr />
		  <a class="btn btn-default" href="{{ route('ctrl::list_objects',$ctrl_class->id) }}"><i class="fa fa-remove"></i> Cancel</a>
		<button type="submit" class="btn btn-success"><i class="fa fa-check-square"></i> Save</button>
	  </form>

	</div>


@stop
