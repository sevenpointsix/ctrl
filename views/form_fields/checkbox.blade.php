{{-- See: http://plugins.krajee.com/checkbox-x --}}

<div class="form-group">
	<input type="checkbox" id="{{ $field['id'] }}"  name="{{ $field['name'] }}" value="1" data-toggle="checkbox-x" data-three-state="false" data-size="lg" @if ($field['value']) checked="checked" @endif>
	<label for="{{ $field['id'] }}" class="cbx-label"><strong>{{ $field['label'] }}</strong></label>
    @if (!empty($field['tip']))
    <p class="help-block">{{ $field['tip'] }}</p>
    @endif
</div>

{{-- This allows us to push some JS to the JS stack only once; it's a hack, but appears to work --}}
@if (empty($GLOBALS['push_checkbox_js']))
    @push('js')
        <script src="{{ asset('assets/vendor/ctrl/vendor/bootstrap-checkbox-x/js/checkbox-x.min.js') }}"></script>
    @endpush
    <?php $GLOBALS['push_checkbox_js'] = true; ?>
@endif

@if (empty($GLOBALS['push_checkbox_css']))
    @push('css')
    <link href="{{ asset('assets/vendor/ctrl/vendor/bootstrap-checkbox-x/css/checkbox-x.min.css') }}" rel="stylesheet" />
    @endpush
    <?php $GLOBALS['push_checkbox_css'] = true; ?>
@endif

