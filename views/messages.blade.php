{{-- Draw messages, alerts, "infos" and errors on the page --}}
{{-- Could / should include the partials/message view here --}}
<div id="messages_success">
    @if(Session::has('messages'))    
    <div class="alert alert-success alert-dismissible" role="alert">
      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        @foreach (session('messages') as $message_success)
         {!! $message_success !!}<br/>
         @endforeach
    </div>
    @endif
</div>
<div id="messages_info">
    @if(Session::has('info'))    
    <div class="alert alert-info alert-dismissible" role="alert">
      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        @foreach (session('info') as $message_info)
         {!! $message_info !!}<br/>
         @endforeach
    </div>
    @endif
</div>
<div id="messages_warning">
    @if(Session::has('warnings'))    
    <div class="alert alert-warning alert-dismissible" role="alert">
      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        @foreach (session('messages') as $message_warning
         {!! $message_warning !!}<br/>
         @endforeach
    </div>
    @endif
</div>
<div id="messages_error">
    @if(Session::has('errors'))    
    <div class="alert alert-danger alert-dismissible" role="alert">
      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        @foreach (session('errors') as $message_error)
         {!! $message_error !!}<br/>
         @endforeach
    </div>
    @endif
</div>



