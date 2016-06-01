@extends('ctrl::master')


@section('js')
<script src="{{ asset('assets/vendor/ctrl/vendor/jquery.form/jquery.form.js') }}"></script>
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
		_margin-bottom: 20px;
		border-bottom: none;
	}
	.ctrl-edit-object form { /* Give the form a border, background colour, and some padding */
		padding: 15px;
		background-color: #f9f9f9;
		border: 1px solid #ddd;
		border-top-right-radius: 4px;
		border-bottom-right-radius: 4px;
		border-bottom-left-radius: 4px;
	}
	.ctrl-edit-object .nav-tabs>li.active>a, .ctrl-edit-object .nav-tabs>li.active>a:focus, .ctrl-edit-object .nav-tabs>li.active>a:hover {
		background-color: #f9f9f9; /* Give the tabs the same background colour as the form */
	}
	.cbx {
		background-color: #fff; /* Give the custom checkboxes a white background */
	}
	</style>
@stop

@section('content')

	<nav class="navbar navbar-default page-header">
	  <div class="container-fluid">
	    <!-- Brand and toggle get grouped for better mobile display -->
	    <div class="navbar-header">
	      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#list-options" aria-expanded="false">
	        <span class="sr-only">Toggle options</span>
	        <span class="icon-bar"></span>
	        <span class="icon-bar"></span>
	        <span class="icon-bar"></span>
	      </button>   
	      <a class="navbar-brand">@if ($icon = $ctrl_class->get_icon())<i class="{{ $icon }} fa-fw"></i> @endif
	      	{{ $page_title }}</a>   
	    </div>

	    <!-- Collect the nav links, forms, and other content for toggling -->
	    <div class="collapse navbar-collapse" id="list-options">
			@if ($page_description)
				<p class="navbar-text">{!! $page_description !!}</p>
			@endif
	      <ul class="nav navbar-nav navbar-right">
			<li><a href="{{ $back_link }}"><i class="fa fa-toggle-left"></i> Back</a></li>
			@if ($delete_link)
			<li><a href="#" rel="{{ $delete_link }}" class="delete-item"><i class="fa fa-trash"></i> Delete</a></li>
			@endif
	      </ul>      
	    </div><!-- /.navbar-collapse -->
	  </div><!-- /.container-fluid -->
	</nav>

	<div class="ctrl-edit-object">

	  <!-- Nav tabs -->
	  <ul class="nav nav-tabs" role="tablist">
	    <li role="presentation" class="active"><a href="#tab-1" aria-controls="tab-1" role="tab" data-toggle="tab"><i class="fa fa-th-list"></i> Details</a></li>

	    <li role="presentation"><a href="#tab-2" aria-controls="tab-2" role="tab" data-toggle="tab"><i class="fa fa-star-o"></i> Testt</a></li>
	  </ul>

	  <form class="ajax" method="post" action="{{ $save_link }}">

		  <!-- Tab panes -->
		  <div class="tab-content">
		    <div role="tabpanel" class="tab-pane fade in active" id="tab-1">
		    	
				{!! csrf_field() !!}	

				@include('ctrl::form_errors')

				@foreach ($form_fields as $form_field)

					@include('ctrl::form_fields.'.$form_field['template'], ['field' => $form_field])

				@endforeach
				
			</div>
			<div role="tabpanel" class="tab-pane fade in" id="tab-2">
				<div class="form-group">
				     <label for="form_id_test">A TEST FIELD</label>
				    	<input type="text" class="form-control" id="form_id_test" name="test" value="" placeholder="">
				</div>
			</div>
		  </div>
		  <hr />
		  <a class="btn btn-default" href="{{ route('ctrl::list_objects',$ctrl_class->id) }}"><i class="fa fa-remove"></i> Cancel</a>
		<button type="submit" class="btn btn-success"><i class="fa fa-check-square"></i> Save</button>
	  </form>

	</div>


@stop
