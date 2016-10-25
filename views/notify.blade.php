{{-- Draw the JS to flash messages and errors(?) to notify --}}
@push('js')
<script>
@if(Session::has('errors'))    
$(function() {	
    @foreach (session('errors') as $error)
	    $.notify({
			icon: 'fa fa-exclamation-triangle fa-fw',				
			message: '{{ addslashes($error) }}',					
		},{
			type: "error",
			newest_on_top: true,
			delay: 2500,					
		});
    @endforeach
</div>
});
@endif

@if(Session::has('messages'))    
$(function() {	
    @foreach (session('messages') as $message)
	    $.notify({
			icon: 'fa fa-check fa-fw',				
			message: '{{ addslashes($message) }}',					
		},{
			type: "success",
			newest_on_top: true,
			delay: 2500,					
		});
    @endforeach
});
@endif
</script>
@endpush