<?php

// Prefix all route names with ctrl::, all URLs with ctrl/, and enable the 'web' middlewhere (which automatically enables sessions, a global $errors variable, CSRF protection and probably some other stuff).

Route::group(['as' => 'ctrl::','prefix'=>'admin','middleware' => ['web']], function () {
	Route::get('/',[
		'as'=>'dashboard',
		'uses'=>'CtrlController@dashboard'
	]);

	Route::get('list/{ctrl_class_id}/{filter_string?}',[
		'as'=>'list_objects',
		'uses'=>'CtrlController@list_objects'
	]);

	Route::get('edit/{ctrl_class_id}/{object_id?}/{default_properties?}',[
		'as'=>'edit_object',
		'uses'=>'CtrlController@edit_object'
	]);

	Route::post('delete/{ctrl_class_id}/{object_id}',[
		'as'=>'delete_object',
		'uses'=>'CtrlController@delete_object'
	]);

	Route::post('save/{ctrl_class_id}/{object_id?}/{filter_string?}',[
		'as'=>'save_object',
		'uses'=>'CtrlController@save_object'
	]);

	Route::post('froala',[
		'as'=>'froala_upload',
		'uses'=>'CtrlController@froala_upload'
	]);
	Route::post('krajee',[
		'as'=>'krajee_upload',
		'uses'=>'CtrlController@krajee_upload'
	]);

	Route::match(['get', 'post'],'data/{ctrl_class_id}/{filter_string?}',[
		'as'=>'get_data',
		'uses'=>'CtrlController@get_data'
	]);

	Route::post('reorder/{ctrl_class_id}',[
		'as'=>'reorder_objects',
		'uses'=>'CtrlController@reorder_objects'
	]);

	// Remote data sources for typeahead
	Route::get('typeahead/{search_term?}',[
		'as'=>'get_typeahead',
		'uses'=>'CtrlController@get_typeahead'
	]);

	// Remote data sources for select2
	Route::get('select2/{ctrl_class_name}',[
		'as'=>'get_select2',
		'uses'=>'CtrlController@get_select2'
	]);

	// Testing...
	Route::get('test',[
		'as'=>'test',
		'uses'=>'CtrlController@test'
	]);




	// AUTH:
	
	Route::get('login',[
		'as'=>'login',
		'uses'=>'CtrlController@login'
	]);
	
	Route::post('login',[
		'as'=>'post_login',
		'uses'=>'CtrlController@post_login'
	]);

	Route::get('logout',[
		'as'=>'logout',
		'uses'=>'CtrlController@logout'
	]);

});