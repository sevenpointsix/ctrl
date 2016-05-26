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

<div class="modal fade" id="help"  aria-labelledby="help" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Help</h4>
      </div>
      <div class="modal-body">
        
      	<ul class="list-group">
			@if ($can_reorder)
			<li class="list-group-item">
			    <h4 class="list-group-item-heading">
			    	<a class="btn btn-xs btn-default"><i class="fa fa-fw fa-reorder"></i></a> Reorder items
			    </h4>
			    <p class="list-group-item-text">Click to drag and drop items into a new order. The new order will be saved immediately.</p>
			</li>
		  	@endif
		  	<li class="list-group-item">
			    <h4 class="list-group-item-heading">
			    	<a class="btn btn-xs btn-info"><i class="fa fa-fw fa-pencil"></i></a>  Edit item
			    </h4>
			    <p class="list-group-item-text">Click to edit an item in the list.</p>
			</li>
			<li class="list-group-item">
			    <h4 class="list-group-item-heading">
			    	<a class="btn btn-xs btn-danger"><i class="fa fa-fw fa-trash"></i></a>  Delete item
			    </h4>
			    <p class="list-group-item-text">Click to delete an item in the list.</p>
			</li>
			@if ($filtered_list_links)
		  	
		  		@foreach ($filtered_list_links as $filtered_list_link)
			<li class="list-group-item">
			    <h4 class="list-group-item-heading">
			    	<a class="btn btn-xs btn-warning"><i class="fa fa-fw {{ $filtered_list_link['icon'] }}"></i></a> Manage {{ $filtered_list_link['title'] }}
			    </h4>
			    <p class="list-group-item-text">List the {{ strtolower($filtered_list_link['title']) }} for this item, or add a new one.</p>
			</li>
				@endforeach
			 @endif

		</ul>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>        
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->



<?php /* Old approach(es) 

<div class="row-buttons">

	<ul class="fa-ul" style="margin-left: 0.5em">
		@if ($can_reorder)
			<li>
				<span class="fa-stack fa-lg">
				  <i class="fa fa-square fa-stack-2x" style="color: #f9f9f9"></i>
				  <i class="fa fa-reorder fa-stack-1x"></i>
				</span>
				<strong>Reorder</strong> items; click to drag them into a new order</a>
			</li>
		@endif
		<li>
			<span class="fa-stack fa-lg">
			  <i class="fa fa-square fa-stack-2x true-text-info"></i>
			  <i class="fa fa-pencil fa-stack-1x fa-inverse"></i>
			</span>
			<strong>Edit</strong> this item</a>
		</li>
		<li>
			<span class="fa-stack fa-lg">
			  <i class="fa fa-square fa-stack-2x true-text-danger"></i>
			  <i class="fa fa-trash fa-stack-1x fa-inverse"></i>
			</span>
			<strong>Delete</strong> this item</a>
		</li>
	  
	  @if ($filtered_list_links)
  	
  		@foreach ($filtered_list_links as $filtered_list_link)
		
		  <li>
		  	<span class="fa-stack fa-lg">
			  <i class="fa fa-square fa-stack-2x true-text-warning"></i>
			  <i class="fa {{ $filtered_list_link['icon'] }} fa-stack-1x fa-inverse"></i>
			</span>		    
		    View <strong>{{ $filtered_list_link['title'] }}</strong> for this item, or add a new one
		  </li>		 
		@endforeach
	  @endif
	</ul>

	<ul class="fa-ul">
		@if ($can_reorder)
			<li><i class="fa-li fa fa-reorder"></i> <strong>Reorder items</strong>; click to drag and drop items into a new order.</a>
		@endif
	  <li><i class="fa-li fa fa-pencil text-info"></i> <strong>Edit</strong> this item</li>
	  <li><i class="fa-li fa fa-trash text-danger"></i> <strong>Delete</strong> this item</li>
	  @if ($filtered_list_links)
  	
  		@foreach ($filtered_list_links as $filtered_list_link)
		
		  <li>
		    @if ($filtered_list_link['icon'])<i class="fa-li {{ $filtered_list_link['icon'] }} text-warning"></i>@else<i class="fa-li fa fa-bars"></i>@endif
		    View <strong>{{ $filtered_list_link['title'] }}</strong> for this item, or add a new one
		  </li>		 
		@endforeach
	  @endif
	</ul>


	<strong>Key: </strong>
		@if ($can_reorder)
			<a class="btn btn-xs btn-default"><i class="fa fa-reorder"></i> Reorder</a>
		@endif
	  <a class="btn btn-xs btn-info"><i class="fa fa-pencil"></i> Edit</a>
	  <a class="btn btn-xs btn-danger"><i class="fa fa-trash"></i> Delete</a>
	  {{-- Can we filter on any related items? --}}
	 
	  @if ($filtered_list_links)
  	
  		@foreach ($filtered_list_links as $filtered_list_link)
		
		  <a class="btn btn-xs btn-warning">
		    @if ($filtered_list_link['icon'])<i class="{{ $filtered_list_link['icon'] }}"></i>@else<i class="fa fa-bars"></i>@endif
		    {{ $filtered_list_link['title'] }}
		  </a>			 
		@endforeach
	  @endif
</div>
*/ ?>	


