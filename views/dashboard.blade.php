@extends('ctrl::master')


@section('content')
	
	<div class="page-header">
	  <h1>CTRL <small>This is your CMS</small></h1>
	</div>

	<div class="row">
		<div class="col-md-6">

			
			 <?php /* I didn't like this approach: it just duplicates the menu
				<div class="btn-group btn-group-vertical" role="group">
				{{-- Currently we put everything in a "Content" group but that's not right, we should allow "floating" items with no category
				  <button type="button" class="btn btn-default">1</button>
				  <button type="button" class="btn btn-default">2</button>
				 --}}
				 @foreach ($menu_links as $menu_title=>$links)

				  <div class="btn-group" role="group">	
				    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				      {{ $menu_title }}
				      <span class="caret"></span>
				    </button>
				    <ul class="dropdown-menu">
				      	@foreach ($links as $link)
							<li><a href="{{ route('ctrl::list_objects',$link['id']) }}">{{ $link['title'] }}</a></li>
						@endforeach
				    </ul>
				  </div>
				  @endforeach
				</div>
			*/ ?>
		
				@foreach ($menu_links as $menu_title=>$links)

					
					<div class="list-group">
						@if (count($links) > 1) 
					  <div class="list-group-item list-group-item-info"><strong>{{ $menu_title }}</strong></div>					  
					  	@endif
					  @foreach ($links as $link)
						<a href="{{ route('ctrl::list_objects',$link['id']) }}" class="list-group-item">
							{{-- Not keen on this <span class="badge">14</span> --}}
							{{ $link['title'] }}
						</a>
						@endforeach
					</div>
				
				   
				  @endforeach


		</div>
	</div>

@stop
