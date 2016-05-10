@extends('ctrl::master')

@section('js')
<script src="{{ asset('assets/vendor/ctrl/vendor/corejs-typeahead/typeahead.bundle.min.js') }}"></script>
<script src="{{ asset('assets/vendor/ctrl/vendor/handlebars/handlebars-v4.0.5.js') }}"></script>

<script>
	var dashboard_search = new Bloodhound({
	  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('title'),
	  queryTokenizer: Bloodhound.tokenizers.whitespace,
	  /* I don't think prefetch is valid when we're searching through all known items; what would we prefetch?
	  prefetch: {
	  	url: '{!! route('ctrl::get_typeahead') !!}',
	  	cache: false // While testing, from http://stackoverflow.com/questions/21998700/twitter-jquery-typeahead-how-to-remove-the-cache
	  },
	  */
	  remote: {
	    url: '{!! route('ctrl::get_typeahead','%QUERY') !!}',
	    wildcard: '%QUERY',
	    cache: false // While testing, from http://stackoverflow.com/questions/21998700/twitter-jquery-typeahead-how-to-remove-the-cache
	  }
	});


	$('#dashboard-search .typeahead').typeahead({
	  highlight: true,
	  hint: false
	},
	{
	  name: 'tests',
	  display: 'title',
	  source: dashboard_search,  
	  templates: {
		suggestion: Handlebars.compile('<div><i class="\{\{icon\}\}"></i> \{\{title\}\}</div>')
	  }

	}).bind("typeahead:select", function(obj, datum, name) {
		document.location = datum.edit_link; // Jump to the "Edit" link		
	});;
</script>
@stop

@section('css')
<style>

/* Style typeahead for Bootstrap, from https://gist.github.com/mixisLv/f7872a90a8a31157e80364f08c955102 */
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
	/* padding: 3px 20px; */
		/* Custom: */
		padding: 3px 10px;
}
.tt-suggestion.tt-cursor,
.tt-suggestion:hover {
	color: #fff;
	background-color: #428bca;
}
.tt-suggestion.tt-is-under-cursor a {
	color: #fff;
}
.tt-suggestion p {
	margin: 0;
}
/* Full width from http://stackoverflow.com/questions/17957513/extending-the-width-of-bootstrap-typeahead-to-match-input-field */
.twitter-typeahead, .tt-hint, .tt-input, .tt-menu { width: 100%; }	


/* HIghlight current item */
.tt-cursor {
	background-color: #f00;
}
/* Adjust borders of the main text input to fit with the addon */
span.input-group-addon+span.twitter-typeahead input.tt-input {
	border-top-left-radius: 0px;
	border-bottom-left-radius: 0px;
	border-top-right-radius: 4px;
	border-bottom-right-radius: 4px;
}

/* very much WIP */
/* Might not need any of this:;
#dashboard-grid {
	xoverflow: auto;
}

#dashboard-grid a {
	display: inline-block;
	text-align: center;
	float: left;
	xwidth: 100%;
	padding: 10px 10px 8px;
	border: 1px solid #eee;
	color: #666;
	cursor: pointer;	
}
#dashboard-grid a:hover, #dashboard-grid a:focus {
	text-decoration: none;
	background-color: #f0f0f0;	
}
*/
</style>
@stop

@section('content')
	
	<div class="page-header">
	  <h1><img src="http://www.argos-support.co.uk/assets/img/design/argos-logo.png" alt="ARGOS" style="height: 50px"> <small>Content Management System</small></h1>
	</div>

	<div class="row">

		<div class="col-md-6">
			
			<div class="panel panel-default">
			  <div class="panel-heading"><h3 class="panel-title"><i class="fa fa-list-alt fa-fw" style="font-weight: normal"></i> Dashboard</h3></div>
			  <div class="panel-body">
			    
			  	<form id="dashboard-search">
				  <div class="form-group">				    
				    <div class="input-group">
				      <span class="input-group-addon"><i class="fa fa-search"></i></span>
				      <input class="typeahead form-control input-lg" type="text" id="exampleInputAmount" placeholder="Search" style="float: none;">		
				      {{-- float: none aligns the addon in Chrome but apparently not IE? https://github.com/twitter/typeahead.js/issues/847 --}}	      
				    </div>
				  </div>			  
				</form>
				<hr />

				{{-- Just playing around with this --}}
				<div id="dashboard-grid">
					
					@foreach ($menu_links as $menu_title=>$links)

						@if (count($links) > 1) 
							{{-- Could echo $menu_title here, but is it really necessary? I quite like the idea of just one big grid... --}}
						@endif
						@foreach ($links as $link)
	  					<div class="btn-group">

						  <a class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						    <i class="{{ $link['icon_only'] }} fa-3x"></i><br><span class="label label-primary">{{ $link['title'] }}</span>			  
						  </a>
						  <ul class="dropdown-menu">
						  	<li><a href="{{ $link['list_link'] }}"><i class="fa fa-list fa-fw"></i> {{ $link['list_title'] }}</a></li>			  	
						    <li><a href="{{ $link['add_link'] }}"><i class="fa fa-plus fa-fw"></i> {{ $link['add_title'] }}</a></li>
						  </ul>
						</div>
						@endforeach
					@endforeach

				</div>
				<?php /* Think the above is better:
				<div class="row no-gutter">
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
								<button class="btn btn-xs btn-success pull-right"  onclick="document.location = '{{ $link['add_link'] }}'; return false"  data-toggle="tooltip" data-placement="bottom" title="{{ $link['add_title'] }}"><i class="fa fa-plus"></i></button>
								{!! $link['icon'] !!} {{ $link['title'] }}
							</a>
							

							@endforeach
						</div>
					</div>
				   
				  @endforeach
				</div>
				*/ ?>

			  </div>
			</div>
		</div>

	</div>

	
@stop
