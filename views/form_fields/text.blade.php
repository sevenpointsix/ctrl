<div class="form-group">
    <label for="{{ $field['id'] }}">{{ $field['label'] }}</label>
    <input type="text" class="form-control" id="{{ $field['id'] }}" name="{{ $field['name'] }}" value="{{ $field['value'] }}" placeholder="">
    @if (!empty($field['tip']))
    <p class="help-block">{{ $field['tip'] }}</p>
    @endif
</div>