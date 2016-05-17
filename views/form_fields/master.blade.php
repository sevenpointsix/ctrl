<div class="form-group">
	@if (!empty($field['label'])) {{-- No labels for hidden fields, for example --}}
    <label for="{{ $field['id'] }}">{{ $field['label'] }}</label>
    @endif
    @yield('input')    
    @if (!empty($field['tip']))
    <p class="help-block">{{ $field['tip'] }}</p>
    @endif
</div>