@extends('ctrl::form_fields.master')

@section('input')

    <?php // Not nice to define variables within a view, but this improves clarity:
        /* Get this working before we start dicking about though:
        $multiple_select = is_array($field['value']);
        $ajax_source     = (count($field['values']) >= 20);
        */
    ?>

    @if (is_array($field['value'])) {{-- Indicates a multiple select --}}

    <select class="form-control" id="{{ $field['id'] }}" style="width: 100%" name="{{ $field['name'] }}[]" multiple>  
        {{-- See "Responsive Design" here for a note on the width/100%: https://select2.github.io/examples.html --}}      

        {{-- We also want to patch in an Ajax-driven Select2 box, if necessary. More than 20 values perhaps? --}}

        @if (count($field['values']) >= 20)
            @foreach ($field['value'] as $value=>$text)
                <option value="{{ $value }}" selected="selected">{{ $text }}</option>
            @endforeach
        @else
            @foreach ($field['values'] as $value=>$text)
                <option value="{{ $value }}" @if (array_key_exists($value,$field['value'])) selected="selected" @endif>{{ $text }}</option>
            @endforeach
        @endif
    </select>

    @else

    <select class="form-control" id="{{ $field['id'] }}" style="width: 100%" name="{{ $field['name'] }}">
        <option value="">None</option>
        @if (count($field['values']) >= 20)
            <option value="{{ $field['value'] }}" selected="selected">{{ $field['values'][$field['value']] }}</option>
        @else
            @foreach ($field['values'] as $value=>$text)
                <option value="{{ $value }}" @if ($field['value'] == $value) selected="selected" @endif>{{ $text }}</option>
            @endforeach
        @endif
    </select>

    @endif
@overwrite
{{-- Note that we need @overwrite because we include multiple instances of templates that extend form_fields.master: see https://github.com/laravel/framework/issues/1058 --}}


{{--
    Previously we only used select2 for multiple selects, but why? It's an improvement on a standard select, and will allow us to use Ajax loading...
--}}

    {{-- This allows us to push some JS to the JS stack only once; it's a hack, but appears to work --}}
    {{-- Could potentially use Radic assignments here instead; http://robin.radic.nl/blade-extensions/directives/assignment.html --}}
    @if (empty($GLOBALS['push_dropdown_js']))
        @push('js')
            <script src="{{ asset('assets/vendor/ctrl/vendor/select2/js/select2.min.js') }}"></script>
        @endpush
        <?php $GLOBALS['push_dropdown_js'] = true; ?>
    @endif

    @push('js')
    <script type="text/javascript">
        {{-- Based on http://www.southcoastweb.co.uk/jquery-select2-v4-ajaxphp-tutorial --}}
        @if (count($field['values']) >= 10)
            $('#{{ $field['id'] }}').select2({
                ajax: {
                    url: "{{ route('ctrl::get_select2',['ctrl_class_name'=>$field['related_ctrl_class_name']]) }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term // search term
                        };
                    },
                    processResults: function (data) {
                        // parse the results into the format expected by Select2.
                        // since we are using custom formatting functions we do not need to
                        // alter the remote JSON data
                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                //minimumInputLength: 1
            });   
        @else
            $('#{{ $field['id'] }}').select2();   
        @endif
    </script>
    @endpush

    @if (empty($GLOBALS['push_dropdown_css']))
        @push('css')
        <link href="{{ asset('assets/vendor/ctrl/vendor/select2/css/select2.min.css') }}" rel="stylesheet" />

        @endpush
        <?php $GLOBALS['push_dropdown_css'] = true; ?>
    @endif


