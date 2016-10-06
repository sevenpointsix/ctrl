@extends('ctrl::form_fields.master')

@section('input')
  <input type="file" id="{{ $field['id'] }}_krajee" name="{{ $field['name'] }}_krajee" @if (!empty($field['allow-multiple'])) multiple="multiple" @endif>
  <input type="hidden" id="{{ $field['id'] }}" name="{{ $field['name'] }}@if (!empty($field['allow-multiple']))[]@endif" value="{{ $field['value'] }}">
@overwrite
{{-- Note that we need @overwrite because we include multiple instances of templates that extend form_fields.master: see https://github.com/laravel/framework/issues/1058 --}}

{{-- This allows us to push some JS to the JS stack only once; it's a hack, but appears to work --}}
@if (empty($GLOBALS['push_krajee_js']))
    @push('js')
        <script src="{{ asset('assets/vendor/ctrl/vendor/bootstrap-fileinput/js/fileinput.js') }}"></script>
    @endpush
    <?php $GLOBALS['push_krajee_js'] = true; ?>
@endif

@push('js')
<script type="text/javascript">

  // Add a custom "download" button if we already have a file or image
  var btnCust = '';
  @if (!empty($field['value']))
    btnCust = '<a href="{{ $field['value'] }}" class="btn btn-default" id="fileinput-custom-download_{{ $field['id'] }}"><i class="fa fa-download"></i> Download</a>'; 
  @endif

  $('#{{ $field['id'] }}_krajee').fileinput({
  	/* Set the Ajax upload URL and pass in some extra details (file type, _token) */
	  	uploadUrl:'{{ route('ctrl::krajee_upload') }}',
	  	uploadExtraData: function() {
	        return {
	            _token: '{{ csrf_token() }}',
	            type: '{{ $field['type'] }}',
	            field_name: '{{ $field['name'] }}_krajee'
	        };
	    },
      // allowedPreviewTypes: ['image', 'video'], // Otherwise we preview huge CSV files when importing, which is slow and pointless      
      // ... or, can we limit the size of a file to be previewed? Yep:
      maxFilePreviewSize: 1000,      
	/* Remove the close button, and the upload button (we automatically upload) */
	  	showClose: false,
	  	showUpload: false,	  	
      @if (empty($field['allow-multiple']))      
	/* Limit this to only allow one file; from http://plugins.krajee.com/file-auto-replace-demo#1-file-limit-alt */	
    	maxFileCount: 1,
      @endif
    	autoReplace: true,
    	overwriteInitial: true,
    	showUploadedThumbs: false,
    @if ($field['type'] == 'image')
      /* Only allow images for now */
    		allowedFileTypes: ['image'],
    	/* Load the current image */
    @elseif (!empty($field['allowed-types']))      
        allowedFileTypes: ['{{ implode(',',$field['allowed-types']) }}'],
    @endif
    @if (!empty($field['value']))
      @if ($field['type'] == 'image')
  	  	initialPreview: [
  		    '<img src="{{ $field['value'] }}" class="file-preview-image">'
  		],
      /* I don't think we should preview files. The one it gives you after upload is really impressive, but I can't see how to include this when the form is first loaded. I suspect you can't (it'd be a security risk, surely?) */
      @endif
      initialCaption: '{{ basename($field['value']) }}',
    @endif      
			  
	/* Hide the small "delete" button on each thumbnail, I don't like it; we have a large Remove button instead */
	/* Also hide the footer under the thumbnail that shows the name, progress bar and status on upload; it's unnecessary IMHO */
		layoutTemplates: {
        actionDelete: '',
        footer: '',
        main1: '{preview}\n' +
          '<div class="kv-upload-progress hide"></div>\n' +
          '<div class="input-group {class}">\n' +
          '   {caption}\n' +
          '   <div class="input-group-btn">\n' +
          '       {remove}\n' +
          '       {cancel}\n' +
          '       {upload}\n' +
          btnCust +        
          '       {browse}\n' +
          '   </div>\n' +
          '</div>',
    },
	/* Don't allow files to be dragged, dropped into the upload widget */
  		dropZoneEnabled: false,
  	
  }).on("filebatchselected", function(event, files) {
	   // trigger upload method immediately after files are selected
	   $('#{{ $field['id'] }}_krajee').fileinput("upload");
  }).on('fileuploaded', function(event, data, previewId, index) {
    var response = data.response;
    // Set the hidden field value to that of the file just uploaded

    // Handle multiple uploads here
    if ($('#{{ $field['id'] }}').attr('name').indexOf('[]') > -1) { // If this is a multiple field
      new_field = $('#{{ $field['id'] }}').clone().removeAttr('id');  
      new_field.val(response.link);
      $('#{{ $field['id'] }}').after(new_field);      
    }
    else {
      $('#{{ $field['id'] }}').val(response.link);
    }
  
    // Does this need to change for multiple uploads too? 
    // Hide the progress bar (I'm not sure why this stays visible)
      // I think this has changed?
      // $('#{{ $field['id'] }}').prev('div.file-input').find('.kv-upload-progress').addClass('hide');
      $('#{{ $field['id'] }}').parent('div.form-group').find('.kv-upload-progress').addClass('hide');
    // Hide the "Download" option, no point having it there if we've just uploaded the file
    $('#fileinput-custom-download_{{ $field['id'] }}').hide();
  }).on('fileclear', function(event) {
    // There's an argument here to reinstate the current file, if present; not one for now though.
    $('#{{ $field['id'] }}').val('');    
  });
</script>
@endpush

@if (empty($GLOBALS['push_krajee_css']))
    @push('css')
    <link href="{{ asset('assets/vendor/ctrl/vendor/bootstrap-fileinput/css/fileinput.min.css') }}" rel="stylesheet" />    
    @endpush
    <?php $GLOBALS['push_krajee_css'] = true; ?>
@endif