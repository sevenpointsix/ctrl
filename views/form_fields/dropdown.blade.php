@extends('ctrl::form_fields.master')

@section('input')

    @if (is_array($field['value'])) {{-- Indicates a multiple select --}}

    <select class="form-control" id="{{ $field['id'] }}" style="width: 100%" name="{{ $field['name'] }}[]" multiple>  
        {{-- See "Responsive Design" here for a note on the width/100%: https://select2.github.io/examples.html --}}      
        @foreach ($field['value'] as $value=>$text)
            <option value="{{ $value }}" selected="selected">{{ $text }}</option>
        @endforeach
    </select>

    @else

    <select class="form-control" id="{{ $field['id'] }}" style="width: 100%" name="{{ $field['name'] }}">
        {{-- <option value="">None</option> // Replaced by a placeholder value in the select2 config --}}
        @if ($field['value'])
            <option value="{{ $field['value'] }}" selected="selected">{{ $field['values'][$field['value']] }}</option>
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
        // var select2_{{ $field['id'] }} = $('#{{ $field['id'] }}').select2({
        var select2_{{ $field['id'] }} = $('#{{ $field['id'] }}').select2({
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
            allowClear: true,
            placeholder: 'None',
            theme: "bootstrap"
        });   
        $(document).ready(function() {   

            $('#{{ $field['id'] }}').next('span.select2').find('input.select2-search__field').bind('paste', function(e) {
                e.preventDefault();
                var text = (e.originalEvent || e).clipboardData.getData('text/plain') || prompt('Paste something..');                    
                var values = text.split(',');
                // We now have a list of pasted values; for example, 1384828,1075670 
                values.forEach(function(v) {                        
                    // Now, use Ajax to look up the ID of each matching catalogue number:
                    // We could configure @get_select2 to accept a comma-delimited string and return multiple values, which would speed this up (as we'd only have one ajax call)
                    $.ajax ({
                        url: "{{ route('ctrl::get_select2',['ctrl_class_name'=>$field['related_ctrl_class_name']]) }}",
                        dataType: 'json',
                        data: {q: v }
                    }).done(function(d) {
                        if (d[0].id) {
                            var $option = $("<option selected></option>").val(d[0].id).text(v);
                            $('#{{ $field['id'] }}').append($option).trigger('change');
                        }
                    });                            
                });
                select2_{{ $field['id'] }}.select2('close');
            });
        });
   
    </script>
    @endpush

    @if (empty($GLOBALS['push_dropdown_css']))
        @push('css')
        <link href="{{ asset('assets/vendor/ctrl/vendor/select2/css/select2.min.css') }}" rel="stylesheet" />
        <link href="{{ asset('assets/vendor/ctrl/vendor/select2/css/select2-bootstrap.min.css') }}" rel="stylesheet" />
        <style>
        /*
            This replaces the default small grey 'x' with a larger red Font Awesome one; this is a bit of a hack, because we hide the small x by making it white.
            Actually changing this would mean hacking the JS, I think, as the select2-selection__clear element doesn't seem to be customisable.
         */
        .select2-container--bootstrap .select2-selection__clear:before {
            content: "\f00d";
            color: #a00;
        }
        .select2-container--bootstrap .select2-selection__clear,
            .select2-container--bootstrap .select2-selection__clear:hover {
            font: normal normal normal 18px/1 FontAwesome;
            color: #fff;
            margin-top: 1px;
        }
        </style>
        @endpush
        <?php $GLOBALS['push_dropdown_css'] = true; ?>
    @endif


