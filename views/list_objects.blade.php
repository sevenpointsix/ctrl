@extends('ctrl::master')


@section('css')
<!-- DataTables --> 
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/t/bs-3.3.6/dt-1.10.11,b-1.1.2,r-2.0.2/datatables.min.css"/>
<!-- Row reorder -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/rowreorder/1.1.1/css/rowReorder.dataTables.min.css" />
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/rowreorder/1.1.1/css/rowReorder.bootstrap.min.css" />

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
	
	/* Is this actually necessary? It looks fine with the border:;
	table.dataTable.table-bordered>thead>tr>th.final_header {
		border-right: none;
	}	
	
	table.dataTable.table-bordered>thead>tr>th.empty_header {
		border-left: none;
	}
	*/
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
	/* Move the footer (with search tools) to the top of the table, from https://www.datatables.net/forums/discussion/20272/how-to-put-individual-column-filter-inputs-at-top-of-columns */
	/* Not needed either, we've moved the filters into the searchable headers
	table.dataTable tfoot {
		display: table-header-group;
	}
	table.dataTable tfoot th {
		font-weight: normal; /* Text inputs look weird in bold * /
	}
	*/
	/* Remove bold text from filter inputs when rendered in the table header */
	table.dataTable thead th input, 
		table.dataTable thead th select {
		font-weight: normal;
	}
	/* A very minor one; this aligns the sort buttons vertically, now that the table header contains filter inputs and is therefore deeper */
	table.dataTable thead .sorting:after, table.dataTable thead .sorting_asc:after, table.dataTable thead .sorting_desc:after, table.dataTable thead .sorting_asc_disabled:after, table.dataTable thead .sorting_desc_disabled:after {
		bottom: 14px;
		right: 14px;
	}

	/* I believe that the "reorder" column always has this class */
	/* No, this is the column we're currently sorting on. How to identify the "order" column?
	We can't do this in JS very easily because we have to wait for the table to be drawn first...
	td.sorting_1 {
		text-align: center;
		cursor: row-resize;
	}
	*/
	/* Add a "Clear search" button, from http://stackoverflow.com/questions/20062218/how-do-i-clear-a-search-box-with-an-x-in-bootstrap-3 */
	span.input-group-addon.clear-search {
		background-color: transparent;
		z-index: 100;
		border: none;
		position:absolute;
	    right:0px;
	    top:4px;
	    bottom:0;
	    height:14px;	    
	    font-size:14px;
	    cursor:pointer;
	    color:#aaa;
	}
	/* this needs code adding to actually clear the field */

</style>

@stop

@section('js')
<!-- DataTables --> 
<script type="text/javascript" src="https://cdn.datatables.net/t/bs-3.3.6/dt-1.10.11,b-1.1.2,r-2.0.2/datatables.min.js"></script>
<!-- Row reorder -->
<script type="text/javascript" src="https://cdn.datatables.net/rowreorder/1.1.1/js/dataTables.rowReorder.min.js"></script>

<script>


// Attempting to allow filters in column headers, which don't then sort the column when clicked
// See http://jsfiddle.net/s8F9V/1/ and https://www.datatables.net/forums/discussion/20272/how-to-put-individual-column-filter-inputs-at-top-of-columns
	function stopPropagation(evt) {
	if (evt.stopPropagation !== undefined) {
		evt.stopPropagation();
	} else {
		evt.cancelBubble = true;
	}
}

$(function() {

	/* No longer necessary, we've removed the main search input
	$.extend($.fn.dataTableExt.oStdClasses, {
		"sFilterInput": "form-control", // Remove the default input-sm
		"sFilter": "input-group" // Add input-group class to the parent div
			// This won't work unless we can remove the parent <label>, which we now do (below)
	});
	*/


	// Add text search inputs to each column, from https://datatables.net/examples/api/multi_filter.html
    $('#data-table thead th').each( function () {
    	var column_searchable = $(this).attr('data-search-text');                    	
        var column_title = $(this).text();
        if (column_searchable === 'true') {
        	$(this).html('<div class="input-group"><span class="input-group-addon"><i class="fa fa-search"></i></span><input type="text" class="form-control" placeholder="'+column_title+'" onclick="stopPropagation(event);" /><span class="input-group-addon clear-search"><i class="fa fa-times-circle-o"></i></span></div>');
        }
    } );

    var table = $('#data-table').DataTable({
    	/* Remve the header altogether, we'll search on individual columns instead
    	dom: "<'row table-header'<'col-sm-6'f><'col-sm-6'<'dataTables_custom_buttons pull-right'>>>" +
			 "<'row'<'col-sm-12'tr>>" +
			 "<'row'<'col-sm-5'i><'col-sm-7'p>>",
			 */
		"orderCellsTop": true, // Is this required? It's designed to prevent the click on a search box propagating to the reorder button, but I think we handle this using stopPropagation above
		dom: "<'row'<'col-sm-12'tr>>" +
			 "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        processing: true,
        serverSide: true,
        ajax: '{!! route('ctrl::get_data',array($ctrl_class->id)) !!}',
        columns: {!! $js_columns !!},
        rowReorder: { update: false }, // Prevents the data from being redrawn after we've reordered; is this what we want? Depends if we get the Ajax saving sorted
        drawCallback: function( settings ) {
        	$('.dropdown-toggle').dropdown(); // Refresh Bootstrap dropdowns
    	},
    	/* No longer necessary, we've removed the main search input
    	language: { 'searchPlaceholder': 'Search...','sSearch':'' }, // Remove the "Search:" label, and add a placeholder
    	*/    	
    	initComplete: function () { // Add column filters; see https://datatables.net/examples/api/multi_filter_select.html
    		var total_columns = this.api().columns().indexes().length;
            this.api().columns().every( function () {
                var column = this;         
                // Only draw a dropdown for fields marked as such (usually, relationship fields, possibly ENUM?)
                var column_searchable = $(column.header()).attr('data-search-dropdown');                
                var column_title = $(column.header()).text();
                if (column_searchable !== 'true') return false;                        		
                var select = $('<select class="form-control" style="width: 90%;" onclick="stopPropagation(event);"><option value="">'+column_title+'</option></select>')
                    .appendTo( $(column.header()).empty() )
                    .on( 'change', function () {
                        var val = $.fn.dataTable.util.escapeRegex(
                            $(this).val()
                        );                        
                        column
                            .search( val ? '^'+val+'$' : '', true, false )
                            .draw();
                    } );
                column.data().unique().sort().each( function ( d, j ) {
                	if (!d) return false;
                	/* OMIT empty values as we can't yet search for "missing" relationships; see notes in CtrlController */
                    select.append( '<option value="'+d+'">'+d+'</option>' )
                } );
            } );
        }
        
    });

    // Apply the search (again, see https://datatables.net/examples/api/multi_filter.html)
    table.columns().every( function () {
        var that = this;

        $( 'input', this.header() ).on( 'keyup change', function () {
            if ( that.search() !== this.value ) {
                that
                    .search( this.value )
                    .draw();
            }
        } );

    } );

    // Re-order rows   
   table.on('row-reorder', function (e, diff, edit) { // See https://datatables.net/reference/event/row-reorder   		
	   	var new_order = [];
        for ( var i=0, ien=diff.length ; i<ien ; i++ ) {
	        var row = $(diff[i].node);
	        var row_id = row.attr('id');       
   			// var row_old_order = diff[i].oldPosition + 1; // Not useful
   			var row_new_order = diff[i].newPosition + 1;   
	        new_order.push({ 
		        "id" : row_id,		        
		        "order" : row_new_order
		    }); 
	    }
	    // Now, send this result to a script that updates object orders, and it should all just work. Do we call draw() afterwards?
	    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
		$.ajax({
		    url: '{!! route('ctrl::reorder_objects',array($ctrl_class->id)) !!}',
		    type: 'POST',
		    data: {_token: CSRF_TOKEN, new_order: new_order },
		    dataType: 'JSON',
		    success: function (data) {
		       $.notify({
					icon: 'fa fa-check-square-o fa-fw',				
					message: 'Items reordered',					
				},{
					type: "success",
					newest_on_top: true,
					delay: 2500,					
				});		       
		    }
		});
    }).draw(); // Is the redraw actually necessary?

    // Add custom buttons
     
    // $('div.dataTables_custom_buttons').html('<a href="#" class="btn btn-success"><i class="fa fa-plus"></i> Add</a>');

    /* Can we add this in the HTML instead? See below:
    $('div.dataTables_custom_buttons').html('<!-- Split button --><div class="btn-group"><a href="{{ route('ctrl::edit_object',$ctrl_class->id) }}" class="btn btn-success"><i class="fa fa-plus"></i> Add a {{ $ctrl_class->name }}</a></a><button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button><ul class="dropdown-menu dropdown-menu-right"><li><a href="#">Action</a></li></ul></div>');    
    */
   
    // This removes the parent "label" from the search filter, from http://stackoverflow.com/questions/170004/how-to-remove-only-the-parent-element-and-not-its-child-elements-in-javascript
    /* No longer necessary, we've removed the main search input
   	var label = $('#data-table_filter label');
   	var contents = label.contents();
	label.replaceWith(contents);
	$('#data-table_filter').prepend('<span class="input-group-addon"><i class="fa fa-search"></i></span>');
	*/

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
		{{ $ctrl_class->name }} <small>Description goes here if necessary</small></h1>
	</div>
	
	<table class="table table-bordered table-striped" id="data-table">
        <thead>
            <tr>
            	{!! $th_columns !!}
                <th class="_empty_header" width="1"  data-orderable="false"  data-searchable="false"><!-- Split button --><div class="btn-group flex"><a href="{{ route('ctrl::edit_object',$ctrl_class->id) }}" class="btn btn-success"><i class="fa fa-plus"></i> Add</a></a><button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button><ul class="dropdown-menu dropdown-menu-right"><li><a href="#">Action</a></li></ul></div></th>
            </tr>
        </thead>        
    </table>
    <hr />

@stop

