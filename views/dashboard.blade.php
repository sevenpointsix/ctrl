@extends('ctrl::master')

@section('js')
<script src="{{ asset('assets/vendor/ctrl/assets/vendor/corejs-typeahead/typeahead.bundle.min.js') }}"></script>
@stop

@section('content')
	
	<div class="page-header">
	  <h1>CTRL <small>This is your CMS</small></h1>
	</div>

	<div class="row">

		<div class="col-md-6">
			
				<h4>Search for an existing item...</h4>
				<form>
				  <div class="form-group">
				    <label class="sr-only" for="exampleInputAmount">Amount (in dollars)</label>
				    <div class="input-group">
				      <div class="input-group-addon"><i class="fa fa-search"></i></div>
				      <input type="text" class="form-control" id="exampleInputAmount" placeholder="Search">			      
				    </div>
				  </div>			  
				</form>
				<h4>... or choose from the list below.</h4>
				
				<div class="row">
				@foreach ($menu_links as $menu_title=>$links)

					<div class="col-md-6">
						<div class="list-group">
							@if (count($links) > 1) 
						  	<div class="list-group-item list-group-item-info"><strong>{{ $menu_title }}</strong></div>					  
						  	@endif
						  @foreach ($links as $link)
							<a href="{{ route('ctrl::list_objects',$link['id']) }}" class="list-group-item">							
								{{-- Not keen on this <span class="badge">14</span> --}}
								{{-- I would like to add an "add" button here though. How? --}}							
								<button class="btn btn-xs btn-success pull-right"><i class="fa fa-plus" onclick="document.location = '{{ route('ctrl::edit_object',$link['id']) }}'; return false"></i></button>
								{{ $link['title'] }}
							</a>
							

							@endforeach
						</div>
					</div>
				   
				  @endforeach


		</div>
	</div>

@stop
