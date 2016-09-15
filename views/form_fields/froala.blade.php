@extends('ctrl::form_fields.master')

@section('input')
	<textarea class="form-control froala-editor" id="{{ $field['id'] }}" name="{{ $field['name'] }}">{{ $field['value'] }}</textarea>
@overwrite
{{-- Note that we need @overwrite because we include multiple instances of templates that extend form_fields.master: see https://github.com/laravel/framework/issues/1058 --}}

{{-- This allows us to push some JS to the JS stack only once; it's a hack, but appears to work --}}
@if (empty($GLOBALS['push_froala_js']))
	@push('js')
		<!-- Include JS files. -->
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/froala_editor.min.js') }}"></script>
		<script>
		  $.FroalaEditor.DEFAULTS.key = '{{ env('FROALA_KEY','') }}';
		</script>

		<!-- Include Code Mirror. -->
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.3.0/codemirror.min.js"></script>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.3.0/mode/xml/xml.min.js"></script>

		<!-- Include Plugins. -->
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/align.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/char_counter.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/code_beautifier.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/code_view.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/colors.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/emoticons.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/entities.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/file.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/font_family.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/font_size.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/fullscreen.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/image.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/image_manager.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/inline_style.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/line_breaker.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/link.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/lists.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/paragraph_format.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/paragraph_style.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/quick_insert.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/quote.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/table.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/save.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/url.min.js') }}"></script>
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala/js/plugins/video.min.js') }}"></script>	
	@endpush
	<?php $GLOBALS['push_froala_js'] = true; ?>
@endif

@push('js')
			<!-- Initialize the editor. -->
		<script>
		  $(function() {
		      $('#{{ $field['id'] }}').froalaEditor({
		      	/* Should be feasible to customise this on a per-field basis? */
		      	toolbarButtons: ['fullscreen', 'bold', 'italic', 'underline', '|', 'paragraphFormat', 'align', 'formatOL', 'formatUL', 'outdent', 'indent','|',  'insertLink', 'insertImage', 'insertVideo', 'insertFile', 'insertTable'],
		      	/* Select from:
		      	['fullscreen', 'bold', 'italic', 'underline', 'strikeThrough', 'subscript', 'superscript', 'fontFamily', 'fontSize', '|', 'color', 'emoticons', 'inlineStyle', 'paragraphStyle', '|', 'paragraphFormat', 'align', 'formatOL', 'formatUL', 'outdent', 'indent', 'quote', 'insertHR', '-', 'insertLink', 'insertImage', 'insertVideo', 'insertFile', 'insertTable', 'undo', 'redo', 'clearFormatting', 'selectAll', 'html']
		      	See: https://www.froala.com/wysiwyg-editor/docs/options#toolbarButtons
		      	*/
		      	charCounterCount: false,
		      	toolbarSticky: false, /* Was this causing layout glitches in Chrome? */

		      	// See https://www.froala.com/wysiwyg-editor/docs/server-integrations/php-image-upload
				imageUploadURL: '{{ route('ctrl::froala_upload') }}',
				imageUploadParams: {
					_token: '{{ csrf_token() }}',
					type: 'image'
				},
				fileUploadURL: '{{ route('ctrl::froala_upload') }}',
				fileUploadParams: {
					_token: '{{ csrf_token() }}',
					type: 'file'
				}
		      });
		  });
		</script>
@endpush
	

@if (empty($GLOBALS['push_froala_css']))
	@push('css')
		<!-- Include Editor style. -->
		<link href="{{ asset('assets/vendor/ctrl/vendor/froala/css/froala_editor.min.css') }}" rel="stylesheet" type="text/css" />
		<link href="{{ asset('assets/vendor/ctrl/vendor/froala/css/froala_style.min.css') }}" rel="stylesheet" type="text/css" />

		<!-- Include Code Mirror style -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.3.0/codemirror.min.css">

		<!-- Include Editor Plugins style. -->
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/char_counter.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/code_view.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/colors.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/emoticons.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/file.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/fullscreen.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/image.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/image_manager.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/line_breaker.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/quick_insert.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/table.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/video.css') }}">
	@endpush
	<?php $GLOBALS['push_froala_css'] = true; ?>
@endif

