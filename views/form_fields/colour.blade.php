@extends('ctrl::form_fields.master')

@section('input')
	

	<div class="input-group colorpicker-component">
	    <span class="input-group-addon"><i></i></span>
	    <input type="text" class="form-control" id="{{ $field['id'] }}" name="{{ $field['name'] }}" value="{{ $field['value'] }}" placeholder="">
	    
	</div>

@overwrite
{{-- Note that we need @overwrite because we include multiple instances of templates that extend form_fields.master: see https://github.com/laravel/framework/issues/1058 --}}

{{-- This allows us to push some JS to the JS stack only once; it's a hack, but appears to work --}}
@if (empty($GLOBALS['push_colour_js']))
    @push('js')
        <script src="{{ asset('assets/vendor/ctrl/vendor/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js') }}"></script>

        <!-- Initialize the colour picker -->
		<script>
		    $(function() {
		        $('.colorpicker-component').colorpicker({
		        	format: 'hex',
		        	align: 'left',        	
		        	customClass: 'colorpicker-2x', // "Customized widget size" from docs at https://mjolnic.com/bootstrap-colorpicker/ 
		            sliders: {
		                saturation: {
		                    maxLeft: 200,
		                    maxTop: 200
		                },
		                hue: {
		                    maxTop: 200
		                },
		                alpha: {
		                    maxTop: 200
		                }
		            }
		        });

		        $(".colorpicker-component input").click(function(e) {
		            e.preventDefault();
		            if ($(this).val() == '') {
		            	$(this).parent('.colorpicker-component').colorpicker('show');
		            }
		        });
		    });
		</script>
    @endpush
    <?php $GLOBALS['push_colour_js'] = true; ?>
@endif

@if (empty($GLOBALS['push_colour_css']))
    @push('css')    
    <link href="{{ asset('assets/vendor/ctrl/vendor/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css') }}" rel="stylesheet">

    <!-- "Customized widget size" from docs at https://mjolnic.com/bootstrap-colorpicker/ -->
    <style>
	    .colorpicker-2x .colorpicker-saturation {
	        width: 200px;
	        height: 200px;
	    }
	    
	    .colorpicker-2x .colorpicker-hue,
	    .colorpicker-2x .colorpicker-alpha {
	        width: 30px;
	        height: 200px;
	    }
	    
	    .colorpicker-2x .colorpicker-color,
	    .colorpicker-2x .colorpicker-color div {
	        height: 30px;
	    }
	</style>

    @endpush
    <?php $GLOBALS['push_colour_css'] = true; ?>
@endif

@push('js')

@endpush
@push('css')

@endpush