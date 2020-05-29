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

	  @if ($view_link) <a class="btn btn-sm btn-info" href="{!! $view_link !!}" data-toggle="tooltip" data-placement="bottom" title="View"><i class="fa fa-eye"></i></a> @endif

      @if ($edit_link) <a class="btn btn-sm btn-info" href="{!! $edit_link !!}" data-toggle="tooltip" data-placement="bottom" title="Edit"><i class="fa fa-pencil"></i></a> @endif
	  @if ($delete_link) <a class="btn btn-sm btn-danger delete-item" rel="{!! $delete_link !!}" data-toggle="tooltip" data-placement="bottom" title="Delete"><i class="fa fa-trash"></i></a> @endif
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
				{{-- Another change of approach -- is the dropdown menu just too confusing? --}}
				<?php /*
			  <a class="btn btn-sm @if ($filtered_list_link['count'] == 0) btn-default @else btn-warning @endif dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			    @if ($filtered_list_link['icon'])<i class="{{ $filtered_list_link['icon'] }}"></i>@else<i class="fa fa-bars"></i>@endif
			  </a>
			  <ul class="dropdown-menu dropdown-menu-right">
			  	<li><a @if ($filtered_list_link['count'] == 0) class="disabled" @else href="{{ $filtered_list_link['list_link'] }}" @endif><i class="fa fa-list fa-fw"></i> {{ $filtered_list_link['list_title'] }}</a></li>
			    <li><a href="{{ $filtered_list_link['add_link'] }}"><i class="fa fa-plus fa-fw"></i> {{ $filtered_list_link['add_title'] }}</a></li>
			  </ul>
			  */ ?>
				<a href="{{ $filtered_list_link['list_link'] }}" class="btn btn-sm @if ($filtered_list_link['count'] == 0) btn-default @else btn-warning @endif"  data-toggle="tooltip" data-placement="bottom" title="{{ $filtered_list_link['title'] }}">
			    @if ($filtered_list_link['icon'])<i class="{{ $filtered_list_link['icon'] }}"></i>@else<i class="fa fa-bars"></i>@endif
			  </a>


			</div>
			@endforeach
	  	@endif
	  @endif

	@forelse ($toggleLinks as $toggleLink)
		<div class="btn-group">
			<a href="{{ $toggleLink['link'] }}" class="btn btn-sm {{ $toggleLink['class'] }} update-item"  rel="{{ $toggleLink['rel'] }}"  data-toggle="tooltip" data-placement="bottom" title="{{ $toggleLink['title'] }}">
		    <i class="{{ $toggleLink['icon'] }}"></i>
		  </a>
		</div>
	@empty
		<!-- No custom buttons -->
	@endforelse

	@forelse ($custom_buttons as $custom_button)
		<div class="btn-group">
			<a @if (!empty($custom_button['link'])) href="{{ $custom_button['link'] }}" @endif class="btn btn-sm @if (empty($custom_button['count'])) btn-default @else btn-warning @endif {{ $custom_button['class'] }}" rel="{{ $custom_button['rel'] }}"  data-toggle="tooltip" data-placement="bottom" title="{{ $custom_button['title'] }}" @if (!empty($custom_button['target'])) target="{{ $custom_button['target'] }}" @endif>
		    @if ($custom_button['icon'])<i class="{{ $custom_button['icon'] }}"></i>@else<i class="fa fa-bars"></i>@endif
		  </a>
		</div>
	@empty
		<!-- No custom buttons -->
	@endforelse

</div>

