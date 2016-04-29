<div class="form-group">
    <label for="{{ $field['id'] }}">{{ $field['label'] }}</label>
    
    @if (is_array($field['value'])) {{-- Indicates a multiple select --}}

    <select class="form-control" id="{{ $field['id'] }}" name="{{ $field['name'] }}[]" multiple>    	
    	@foreach ($field['values'] as $value=>$text)
	  		<option value="{{ $value }}" @if (array_key_exists($value,$field['value'])) selected="selected" @endif>{{ $text }}</option>
	  	@endforeach
	</select>

    @else

    <select class="form-control" id="{{ $field['id'] }}" name="{{ $field['name'] }}">
    	<option value="">None</option>
    	@foreach ($field['values'] as $value=>$text)
	  		<option value="{{ $value }}" @if ($field['value'] == $value) selected="selected" @endif>{{ $text }}</option>
	  	@endforeach
	</select>

	@endif

    @if (!empty($field['tip']))
    <p class="help-block">{{ $field['tip'] }}</p>
    @endif
</div>

@if (is_array($field['value'])) {{-- Indicates a multiple select --}}

    {{-- This allows us to push some JS to the JS stack only once; it's a hack, but appears to work --}}
    @if (empty($GLOBALS['push_dropdown_js']))
        @push('js')
            <script src="{{ asset('assets/vendor/ctrl/vendor/select2/js/select2.min.js') }}"></script>
        @endpush
        <?php $GLOBALS['push_dropdown_js'] = true; ?>
    @endif

    @push('js')
    <script type="text/javascript">
      $('#{{ $field['id'] }}').select2();
    </script>
    @endpush

    @if (empty($GLOBALS['push_dropdown_css']))
        @push('css')
        <link href="{{ asset('assets/vendor/ctrl/vendor/select2/css/select2.min.css') }}" rel="stylesheet" />

        @endpush
        <?php $GLOBALS['push_dropdown_css'] = true; ?>
    @endif
@endif

