@extends('ctrl::master')


@section('js')
<script src="{{ asset('assets/vendor/ctrl/vendor/jquery.form/jquery.form.js') }}"></script>
<script src="{{ asset('assets/vendor/ctrl/js/forms.js') }}"></script>

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
			@if (!empty($delete_link))
			<li><a href="#" rel="{{ $delete_link }}" class="delete-item"><i class="fa fa-trash"></i> Delete</a></li>
			@endif
	      </ul>      
	    </div><!-- /.navbar-collapse -->
	  </div><!-- /.container-fluid -->
	</nav>

	<div class="ctrl-form">

	  <form class="ajax" method="post" action="{{ $save_link }}">
	  		@include('ctrl::messages')
	  		@include('ctrl::form_errors')
	  		<p>Please select a CSV file from your computer by clicking "Browse", and then click "Import".</p>
			
			@include('ctrl::form_fields.'.$form_field['template'], ['field' => $form_field])
		  <hr />
		  <a class="btn btn-default" href="{{ $back_link }}"><i class="fa fa-remove"></i> Cancel</a>
		<button type="submit" class="btn btn-success"><i class="fa fa-check-square"></i> Import</button>
	  </form>

	</div>


@stop
