@extends('ctrl::master')


@section('js')
<script src="{{ asset('assets/vendor/ctrl/vendor/jquery.form/jquery.form.min.js') }}"></script>
<script src="{{ asset('assets/vendor/ctrl/js/forms.js') }}"></script>

<script>
	$(function() {
		$('a.delete-item').on('click',function() {
			
			var that = this; // Nicked this idea from Datatables; see below. Preserve $this for the bootbox callback
	   		bootbox.confirm("Are you sure you want to delete this item?", function(result) {
	   			if (result) {
	   				var delete_link = $(that).attr('rel');
			   		var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
					$.ajax({
					    url: delete_link,
					    type: 'POST',
					    data: {_token: CSRF_TOKEN },
					    dataType: 'JSON',
					    success: function (data) {
					    	/* For future reference... I'd like to redirect and THEN notify on the next page. How?!
					    	// Take a look at this: https://github.com/AlexChittock/JQuery-Session-Plugin
					       $.notify({
								icon: 'fa fa-check-square-o fa-fw',				
								message: 'Item deleted',					
							},{
								type: "success",
								newest_on_top: true,
								delay: 2500,					
							});
							table.draw(); // Redraw the table (to reflect fact that a row has been removed/deleted)
							*/
							// Note: this should also go "back" to the dashboard, or a related list, if appropriate.
							document.location = '{{ route('ctrl::list_objects',$ctrl_class->id) }}';
					    }
					});
	   			}		   				 
			});
		});
	});
</script>
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
		<div class="pull-right">
			<a href="{{ route('ctrl::list_objects',$ctrl_class->id) }}" class="btn btn-default"><i class="fa fa-toggle-left"></i> Back</a>
			@if ($object->id)
			<a href="#" rel="{{ route('ctrl::delete_object',[$ctrl_class->id,$object->id]) }}" class="btn btn-danger delete-item"><i class="fa fa-trash"></i> Delete</a>
			@endif
			
		</div>
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

					@include('ctrl::form_fields.'.$form_field['template'], ['field' => $form_field])

				@endforeach
				
			</div>
		  </div>
		  <hr />
		  <a class="btn btn-default" href="{{ route('ctrl::list_objects',$ctrl_class->id) }}"><i class="fa fa-remove"></i> Cancel</a>
		<button type="submit" class="btn btn-success"><i class="fa fa-check-square"></i> Save</button>
	  </form>

	</div>


@stop
