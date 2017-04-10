{{-- Draw the JS to flash messages and errors(?) to notify --}}
@push('js')
<script>
@if(Session::has('notify'))
$(function() {
    @foreach (session('notify') as $notification=>$type)
    	<?php // Hack
    		switch ($type) {
    			case 'success';
    				$icon = 'fa-check';
    				break;
    			case 'info';
    			case 'warning';
    				$icon = 'fa-info-circle';
    				break;
    			case 'danger';
    			case 'error';
    				$icon = 'fa-exclamation-circle';
    				break;
    			default;
    				$icon = '';
    		}
    		if ($icon) $icon .= 'fa fa-fw';
    	?>
	    $.notify({
			icon: '{{ $icon }}',
			message: '{!! addslashes($notification) !!}',
		},{
			placement: {
				from: 'top',
				align: 'right'
			},
			type: "{{ $type }}",
			newest_on_top: true,
			delay: 0,
		});
    @endforeach
});
@endif
</script>
@endpush