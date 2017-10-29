<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/','ChatController@enter');
Route::post('/bind','ChatController@bind');
Route::post('/send_message','ChatController@sendMessage');
Route::post('/upload_img','ChatController@uploadImg');
Route::post('/test',function(){
    return 'test';
});