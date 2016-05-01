<div class="form-group">
    <label for="{{ $field['id'] }}">{{ $field['label'] }}</label>
    @yield('input')    
    @if (!empty($field['tip']))
    <p class="help-block">{{ $field['tip'] }}</p>
    @endif
</div>