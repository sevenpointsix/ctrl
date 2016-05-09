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
	  	@if (count($filtered_list_links) == 1)
	  		<a class="btn btn-sm btn-default" href="{!! $filtered_list_links[0]['link'] !!}"  data-toggle="tooltip" data-placement="right" title="{{ $filtered_list_links[0]['title'] }}">@if ($filtered_list_links[0]['icon'])<i class="{{ $filtered_list_links[0]['icon'] }}"></i>@else<i class="fa fa-bars"></i>@endif</a>
	  	@else 
	  		{{-- To be honest, if there's just one related list, let's just use a button -- the code below will draw a dropdown if necessary --}}
			{{--
			<div class="btn-group xflex">
			  <a class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			    <i class="fa fa-bars"></i>
			  </a>
			  <ul class="dropdown-menu">
			    <li><a href="#">Action</a></li>
			    <li><a href="#">Another action</a></li>
			    <li><a href="#">Something else here</a></li>
			    <li role="separator" class="divider"></li>
			    <li><a href="#">Separated link</a></li>
			  </ul>
			</div>
			--}}
	  	@endif
	  @endif
	
	 
	
</div>

