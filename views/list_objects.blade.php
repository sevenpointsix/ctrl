@extends('ctrl::template')


@section('css')
<!-- DataTables --> 
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/t/bs-3.3.6/dt-1.10.11,b-1.1.2,r-2.0.2/datatables.min.css"/>
<style type="text/css">
	.btn-group.flex {
	  display: flex; /* Prevent button dropdowns from wrapping in table, see https://github.com/twbs/bootstrap/issues/9939 */
	}
	/* Not needed
	div.dataTables_wrapper div.dataTables_filter { /* Put the search box on the left * /
		text-align: left;
	}
	div.dataTables_wrapper div.dataTables_filter input {		
		margin-left: 0; /* Don't need the indent now that we're removing the label  * /
	}
	div.dataTables_wrapper div.dataTables_custom_buttons { /* Put the butons on the right * /
		float: right;
	}	
	*/
	
	
	table.dataTable.table-bordered>thead>tr>th.final_header {
		border-right: none;
	}	
	table.dataTable.table-bordered>thead>tr>th.empty_header {
		border-left: none;
	}
	table.dataTable>tbody>tr>td {
		vertical-align: middle;
	}
	/* WIP */
	.row.table-header {
		margin-left: 0;
		margin-right: 2px;
		padding-top: 10px;
		padding-bottom: 10px;
		color: #333;
	    background-color: #f5f5f5;
	    border: 1px solid #ddd;
	    border-bottom: 0px;
    	border-top-left-radius: 3px;
    	border-top-right-radius: 3px;
	}
	table.dataTable {
		margin-top: 0px !important;
	}
</style>

@stop

@section('js')
<!-- DataTables --> 
<script type="text/javascript" src="https://cdn.datatables.net/t/bs-3.3.6/dt-1.10.11,b-1.1.2,r-2.0.2/datatables.min.js"></script>

<script>
$(function() {

	$.extend($.fn.dataTableExt.oStdClasses, {
		"sFilterInput": "form-control", // Remove the default input-sm
		"sFilter": "input-group" // Add input-group class to the parent div
			// This won't work unless we can remove the parent <label>, which we now do (below)
	});

    var table = $('#data-table').DataTable({
    	dom: "<'row table-header'<'col-sm-6'f><'col-sm-6'<'dataTables_custom_buttons pull-right'>>>" +
			 "<'row'<'col-sm-12'tr>>" +
			 "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        processing: true,
        serverSide: true,
        ajax: '{!! route('ctrl::get_data',array($ctrl_class->id)) !!}',
        columns: [            
            { data: 'title', name: 'title' },
            { data: 'action', name: 'action' },            
        ],
        "drawCallback": function( settings ) {
        	$('.dropdown-toggle').dropdown(); // Refresh Bootstrap dropdowns
    	},
    	language: { 'searchPlaceholder': 'Search...','sSearch':'' } // Remove the "Search:" label, and add a placeholder
    });

    // Add custom buttons
     
    // $('div.dataTables_custom_buttons').html('<a href="#" class="btn btn-success"><i class="fa fa-plus"></i> Add</a>');

    $('div.dataTables_custom_buttons').html('<!-- Split button --><div class="btn-group"><a href="{{ route('ctrl::edit_object',$ctrl_class->id) }}" class="btn btn-success"><i class="fa fa-plus"></i> Add a {{ $ctrl_class->name }}</a></a><button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button><ul class="dropdown-menu dropdown-menu-right"><li><a href="#">Action</a></li></ul></div>');    
    
    // This removes the parent "label" from the search filter, from http://stackoverflow.com/questions/170004/how-to-remove-only-the-parent-element-and-not-its-child-elements-in-javascript
   	var label = $('#data-table_filter label');
   	var contents = label.contents();
	label.replaceWith(contents);
	$('#data-table_filter').prepend('<span class="input-group-addon"><i class="fa fa-search"></i></span>');

});
</script>

@stop

@section('content')
	
	{{-- Is there any value in having a dashboard at all? Can't we just have a "Back to list" button when editing an item, I think that's all we'd use a breadcrumb for anyway... --}}
	{{-- Yes, agreed. We need a "Back to list" option when editing items OR viewing a filtered list. That's it. 
	<ol class="breadcrumb">
	  <li><a href="{{ route('ctrl::dashboard') }}">Dashboard</a></li>	  
	  <li class="active">{{ $ctrl_class->name }}</li>
	</ol>
	--}}

	<div class="page-header">
		<h1>@if ($ctrl_class->icon)<i class="fa fa-cog"></i>@endif
		{{ $ctrl_class->name }} <small>Description (filter?) if necessary</small></h1>
	</div>
	
	<table class="table table-bordered table-striped" id="data-table">
        <thead>
            <tr>
                <th class="final_header">Title</th>
                <th class="empty_header" width="1"  data-orderable="false"  data-searchable="false"></th>
            </tr>
        </thead>
    </table>
    <hr />

@stop

