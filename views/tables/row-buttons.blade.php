<!-- Split button -->
<?php /* PLaying around with this, the following looks fine but let's try separate buttons...
<div class="btn-group flex">
  <a class="btn btn-sm btn-info" href="{!! $edit_link !}}"><i class="fa fa-pencil"></i> Edit</a>
  <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    <i class="fa fa-caret-down"></i>
    <span class="sr-only">Toggle Dropdown</span>
  </button>
  <ul class="dropdown-menu dropdown-menu-right">
    <li><a href="#" rel="{!! $delete_link !!]" class="delete-item text-danger"><i class="fa fa-trash"></i> Delete</a></li>
    <!--
    <li><a href="#">Delete</a></li>
    <li><a href="#">Delete</a></li>
    <li role="separator" class="divider"></li>
    <li><a href="#">Separated link</a></li>
    -->
  </ul>
</div>
*/ ?>
<div class="row-buttons">

	  <a class="btn btn-sm btn-info" href="{!! $edit_link !!}"><i class="fa fa-pencil"></i></a>
	  <a class="btn btn-sm btn-danger delete-item" rel="{!! $delete_link !!}"><i class="fa fa-trash"></i></a>
	  {{-- Can we filter on any related items? --}}
	 
	  @if ($filtered_list_links)
	  	@if (count($filtered_list_links) == 1 && false) {{-- I'm undecided whether to go for this approach, or the dropdown below; I think the latter. Suspend this for now. --}}
	  		@if ($filtered_list_links[0]['count'] > 0) 
	  			<a class="btn btn-sm btn-default" href="{!! $filtered_list_links[0]['link'] !!}"  data-toggle="tooltip" data-placement="top" title="{{ $filtered_list_links[0]['title'] }}">@if ($filtered_list_links[0]['icon'])<i class="{{ $filtered_list_links[0]['icon'] }}"></i>@else<i class="fa fa-bars"></i>@endif</a>
	  		@else
	  			<div class="tooltip-wrapper disabled" data-toggle="tooltip" data-placement="top" data-title="{{ $filtered_list_links[0]['title'] }}">					
					<a class="btn btn-sm btn-default disabled">@if ($filtered_list_links[0]['icon'])<i class="{{ $filtered_list_links[0]['icon'] }}"></i>@else<i class="fa fa-bars"></i>@endif</a>
				</div>
	  			
	  		@endif
	  	@else 
	  		@foreach ($filtered_list_links as $filtered_list_link)
			<div class="btn-group">
			  <a class="btn btn-sm @if ($filtered_list_link['count'] == 0) btn-default @else btn-warning @endif dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			    @if ($filtered_list_link['icon'])<i class="{{ $filtered_list_link['icon'] }}"></i>@else<i class="fa fa-bars"></i>@endif
			  </a>
			  <ul class="dropdown-menu dropdown-menu-right">
			  	<li><a @if ($filtered_list_link['count'] == 0) class="disabled" @else href="{{ $filtered_list_link['list_link'] }}" @endif><i class="fa fa-list fa-fw"></i> {{ $filtered_list_link['list_title'] }}</a></li>			  	
			    <li><a href="{{ $filtered_list_link['add_link'] }}"><i class="fa fa-plus fa-fw"></i> {{ $filtered_list_link['add_title'] }}</a></li>
			  </ul>
			</div>
			@endforeach
	  	@endif
	  @endif
	
	 
	
</div>

