<?php

Route::group(['namespace' => 'Apampolino\Webshark\Controllers', 'prefix' => 'webshark', 'middleware' => ['web', 'auth']], function() {
    Route::get('/', ['uses' => 'WebsharkController@index', 'as' => 'webshark.index']);
    Route::get('/upload', ['uses' => 'WebsharkController@showUpload', 'as' => 'webshark.upload.show']);
    Route::get('/json', ['uses' => 'WebsharkController@json', 'as' => 'webshark.json']);
    Route::get('/config', ['uses' => 'WebsharkController@showConfig', 'as' => 'webshark.config.show']);
    Route::post('/upload', ['uses' => 'WebsharkController@upload', 'as' => 'webshark.upload']);
});