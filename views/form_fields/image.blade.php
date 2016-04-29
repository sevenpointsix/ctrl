<div class="form-group">
    <label for="{{ $field['id'] }}">{{ $field['label'] }}</label>
    <input type="file" _class="form-control" id="{{ $field['id'] }}" name="{{ $field['name'] }}" _value="{{ $field['value'] }}">
    @if (!empty($field['tip']))
    <p class="help-block">{{ $field['tip'] }}</p>
    @endif
</div>

 {{-- This allows us to push some JS to the JS stack only once; it's a hack, but appears to work --}}
@if (empty($GLOBALS['push_image_js']))
    @push('js')
        <script src="{{ asset('assets/vendor/ctrl/vendor/bootstrap-fileinput/js/fileinput.min.js') }}"></script>
    @endpush
    <?php $GLOBALS['push_image_js'] = true; ?>
@endif

@push('js')
<script type="text/javascript">
  $('#{{ $field['id'] }}').fileinput({
  	/* Set the Ajaz upload URL and pass in some extra details (file type, _token) */
	  	uploadUrl:'{{ route('ctrl::krajee_upload') }}',
	  	uploadExtraData: function() {
	        return {
	            _token: '{{ csrf_token() }}',
	            type: 'image'
	        };
	    },
	/* Remove the close button, and the upload button (we automatically upload) */
	  	showClose: false,
	  	showUpload: false,	  	
	/* Limit this to only allow one image; from http://plugins.krajee.com/file-auto-replace-demo#1-file-limit-alt */	
    	maxFileCount: 1,
    	autoReplace: true,
    	overwriteInitial: true,
    	showUploadedThumbs: false,
    /* Only allow images for now */
  		allowedFileTypes: ['image'],
  	/* Load the current image */
	  	initialPreview: [
		    '<img src="/uploads/14420.jpg" class="file-preview-image">'
		],
		initialCaption: '14420.jpg',
	/* Hide the small "delete" button on each thumbnail, I don't like it; we have a large Remove button instead */
		layoutTemplates: {actionDelete: ''},
	/* Don't allow images to be dragged, dropped into the upload widget */
  		dropZoneEnabled: false,
  	/* Hide the progess bar on completion: not working, use a callback here instead to set .kv-upload-progress to .kv-upload-progress.hide:
  		progressCompleteClass: 'progress-bar-hidden',
  	*/
  	
  }).on("filebatchselected", function(event, files) {
	// trigger upload method immediately after files are selected
	$('#{{ $field['id'] }}').fileinput("upload");
  }).on('fileuploaded', function(event, data, previewId, index) {
    var form = data.form, files = data.files, extra = data.extra,
        response = data.response, reader = data.reader;
    console.log('File uploaded triggered');
    console.log(response.uploaded);
  });
</script>
@endpush

@if (empty($GLOBALS['push_image_css']))
    @push('css')
    <link href="{{ asset('assets/vendor/ctrl/vendor/bootstrap-fileinput/css/fileinput.min.css') }}" rel="stylesheet" />
    <style>
    	.progress-bar-hidden {
    		display: none;
    	}
    </style>
    @endpush
    <?php $GLOBALS['push_image_css'] = true; ?>
@endif