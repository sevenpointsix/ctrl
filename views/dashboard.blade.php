@extends('ctrl::master')

@section('js')
<script src="{{ asset('assets/vendor/ctrl/vendor/corejs-typeahead/typeahead.bundle.min.js') }}"></script>

<script>
var dashboard_search_tests = new Bloodhound({
  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('ctrl_class_name'),
  queryTokenizer: Bloodhound.tokenizers.whitespace,
  prefetch: {
  	url: '{!! route('ctrl::get_typeahead',4) !!}',
  	cache: false // Also from http://stackoverflow.com/questions/21998700/twitter-jquery-typeahead-how-to-remove-the-cache
  }
  	// 'ttl': 1 // Disable caching while testing, from http://stackoverflow.com/questions/21998700/twitter-jquery-typeahead-how-to-remove-the-cache
});

var dashboard_search_ones = new Bloodhound({
  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('ctrl_class_name'),
  queryTokenizer: Bloodhound.tokenizers.whitespace,
   prefetch: {
  	url: '{!! route('ctrl::get_typeahead',2) !!}',
  	cache: false // Also from http://stackoverflow.com/questions/21998700/twitter-jquery-typeahead-how-to-remove-the-cache
  }
});

$('#dashboard-search .typeahead').typeahead({
  highlight: true
},
{
  name: 'tests',
  display: 'ctrl_class_name',
  source: dashboard_search_tests,
  templates: {
    header: '<h3 class="ctrl-class-name">Tests</h3>'
  }
},
{
  name: 'ones',
  display: 'ctrl_class_name',
  source: dashboard_search_ones,
  templates: {
    header: '<h3 class="ctrl-class-name">Ones</h3>'
  }
});
</script>
@stop

@section('css')
<style>
#dashboard-search .ctrl-class-name {
  margin: 0 20px 5px 20px;
  padding: 3px 0;
  border-bottom: 1px solid #ccc;
}

/* Style typeahead for Bootstrap, from https://gist.github.com/joelhaasnoot/c7f3358726c22d489566 */
.twitter-typeahead .tt-query,
.twitter-typeahead .tt-hint {
	margin-bottom: 0;
}
.tt-hint {
	display: block;
	width: 100%;
	height: 38px;
	padding: 8px 12px;
	font-size: 14px;
	line-height: 1.428571429;
	color: #999;
	vertical-align: middle;
	background-color: #ffffff;
	border: 1px solid #cccccc;
	border-radius: 4px;
	-webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
	      box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
	-webkit-transition: border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
	      transition: border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
}
.tt-menu {
	min-width: 160px;
	margin-top: 2px;
	padding: 5px 0;
	background-color: #ffffff;
	border: 1px solid #cccccc;
	border: 1px solid rgba(0, 0, 0, 0.15);
	border-radius: 4px;
	-webkit-box-shadow: 0 6px 12px rgba(0, 0, 0, 0.175);
	      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.175);
	background-clip: padding-box;

}
.tt-suggestion {
	display: block;
	padding: 3px 20px;
}
tt-suggestion:hover {
	color: #fff;
	background-color: #428bca;
}
.tt-suggestion.tt-is-under-cursor a {
	color: #fff;
}
.tt-suggestion p {
	margin: 0;
}

</style>
@stop

@section('content')
	
	<div class="page-header">
	  <h1>CTRL <small>This is your CMS</small></h1>
	</div>

	<div class="row">

		<div class="col-md-6">
			
				<h4>Search for an existing item...</h4>
				<form id="dashboard-search">
				  <div class="form-group">
				    <label class="sr-only" for="exampleInputAmount">Amount (in dollars)</label>
				    <div class="input-group">
				      <div class="input-group-addon"><i class="fa fa-search"></i></div>
				      <input class="typeahead form-control" type="text" id="exampleInputAmount" placeholder="Search">			      
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
