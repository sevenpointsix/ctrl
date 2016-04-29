<?php

// Prefix all route names with ctrl::, all URLs with ctrl/, and enable the 'web' middlewhere (which automatically enables sessions, a global $errors variable, CSRF protection and probably some other stuff).

Route::group(['as' => 'ctrl::','prefix'=>'admin','middleware' => ['web']], function () {
	Route::get('/',[
		'as'=>'dashboard',
		'uses'=>'CtrlController@dashboard'
	]);

	Route::get('list/{ctrl_class_id}',[
		'as'=>'list_objects',
		'uses'=>'CtrlController@list_objects'
	]);

	Route::get('edit/{ctrl_class_id}/{object_id?}',[
		'as'=>'edit_object',
		'uses'=>'CtrlController@edit_object'
	]);

	Route::post('save/{ctrl_class_id}/{object_id?}',[
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

	Route::match(['get', 'post'],'data/{ctrl_class_id}',[
		'as'=>'get_data',
		'uses'=>'CtrlController@get_data'
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