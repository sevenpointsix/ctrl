{{-- See: http://eonasdan.github.io/bootstrap-datetimepicker/ --}}
{{-- This handles date AND datetime inputs --}}

@extends('ctrl::form_fields.master')

@section('input')
    <?php
        // Convert the current database value into a readable string; we convert this back before saving it to the DB
        // This is a bit messy but the datetimepicker doesn't seem to do much in the way of date conversion...
        if ($field['type'] == 'date') {
            $convert_date_format = 'jS F Y'; // Convert the date FROM this format as it comes from the databse
            $clientside_date_format = 'Do MMMM YYYY'; // Display the date usin this format, based on Moment.js, http://momentjs.com/docs/#/displaying/format/
            $icon = 'fa fa-calendar';
            $col_width = 'col-md-3 col-sm-4 col-xs-12';
        }
        elseif ($field['type'] == 'datetime') {
            $convert_date_format = 'jS F Y H:i';
            $clientside_date_format = 'Do MMMM YYYY HH:mm';
            $icon = 'fa fa-calendar';
            $col_width = 'col-md-3 col-sm-4 col-xs-12';
        }
        elseif ($field['type'] == 'time') {
            $convert_date_format = 'H:i';
            $clientside_date_format = 'LT';
            $icon = 'fa fa-calendar';
            $col_width = 'col-md-2 col-sm-3 col-xs-12';
        }
        $date_value = ($field['value'] && strtotime($field['value']) > 0) ? date($convert_date_format,strtotime($field['value'])) : '';
    ?>
    <div class="input-group {{ $col_width }}">
        <span class="input-group-addon"><i class="{{ $icon }}"></i></span>
        <input type="text" class="form-control" id="{{ $field['id'] }}" name="{{ $field['name'] }}" value="{{ $date_value }}" placeholder="">
    </div>

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
            },
            showClose: true, // Has no effect when sideBySide is true, but fine for date/time fields
            toolbarPlacement:'bottom' // Aha! Fixes showClose for sideBySide, see https://github.com/Eonasdan/bootstrap-datetimepicker/issues/1267
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
