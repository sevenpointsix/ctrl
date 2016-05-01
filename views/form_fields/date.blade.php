{{-- See: http://eonasdan.github.io/bootstrap-datetimepicker/ --}}
{{-- This handles date AND datetime inputs --}}

@extends('ctrl::form_fields.master')

@section('input')    
    <?php
        // Convert the current database value into a readable string; we convert this back before saving it to the DB
        // This is a bit messy but the datetimepicker doesn't seem to do much in the way of date conversion...
        if ($field['type'] == 'date') {
            $date_format = 'jS F Y';
            $clientside_date_format = 'Do MMMM YYYY'; // Based on Moment.js, http://momentjs.com/docs/#/displaying/format/
        }
        elseif ($field['type'] == 'datetime') {
            $date_format = 'jS F Y H:i';
            $clientside_date_format = 'Do MMMM YYYY HH:mm';
        }
        $date_value = ($field['value'] && strtotime($field['value']) > 0) ? date($date_format,strtotime($field['value'])) : '';
    ?>
    <input type="text" class="form-control" id="{{ $field['id'] }}" name="{{ $field['name'] }}" value="{{ $date_value }}" placeholder="">    
@overwrite
{{-- Note that we need @overwrite because we include multiple instances of templates that extend form_fields.master: see https://github.com/laravel/framework/issues/1058 --}}

{{-- This allows us to push some JS to the JS stack only once; it's a hack, but appears to work --}}
@if (empty($GLOBALS['push_date_js']))
    @push('js')
        <script src="{{ asset('assets/vendor/ctrl/vendor/moment/moment.min.js') }}"></script>
        <script src="{{ asset('assets/vendor/ctrl/vendor/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js') }}"></script>
    @endpush
    <?php $GLOBALS['push_date_js'] = true; ?>
@endif

@push('js')
 <script type="text/javascript">
    $(function () {
        $('#{{ $field['id'] }}').datetimepicker({            
            format: '{{ $clientside_date_format }}',
            sideBySide: true,
            // Auto positioning seems to be a bit buggy; v4 is in beta though 
            widgetPositioning: {
                horizontal: 'left',
                vertical: 'bottom'
            }
            // showClose: true // Has no effect when sideBySide is true
            // inline: true // Nice but needs wrapping in columns, otherwise it has 100% width
        });
    });
</script>
@endpush

@if (empty($GLOBALS['push_date_css']))
    @push('css')
    <link href="{{ asset('assets/vendor/ctrl/vendor/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css') }}" rel="stylesheet" />
    @endpush
    <?php $GLOBALS['push_date_css'] = true; ?>
@endif
