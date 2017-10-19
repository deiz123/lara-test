<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::group(['middleware' => ['web']], function () {

    Route::get('/', function () {
        return view('welcome');
    })->middleware('guest');

    Route::auth();

    Route::get('files',['as' => 'file_get_all', 'uses' => 'FileController@getFiles']);
	Route::post('file/upload',['as' => 'file_upload','uses' => 'FileController@upload']);
	Route::delete('file/delete/{filename}',['as' => 'file_delete','uses' => 'FileController@delete']);

	Route::post('xlsx/get-data/{filename}',['as' => 'xlsx_get_data', 'uses' => 'XlsxParserController@getParseData']);

});
