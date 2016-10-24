<div id="errors">
    @if(Session::has('errors'))    
    <div class="alert alert-danger alert-dismissible" role="alert">
      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        @foreach (session('errors') as $error)
         {!! $error !!}<br/>
         @endforeach
    </div>
    @endif
</div>