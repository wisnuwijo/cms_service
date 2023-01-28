<?php

use Illuminate\Http\Request;

Route::post('v1/login', 'API\LoginController@login');

Route::group(['prefix' => 'v1', 'middleware' => 'auth:api'], function() {
    Route::post('/change_password', 'API\LoginController@change_password');

    Route::group(['prefix' => 'product'], function() {
        Route::get('/', 'API\ProductController@index');
        Route::get('/detail', 'API\ProductController@detail');
        Route::post('/create', 'API\ProductController@create');
        Route::patch('/update', 'API\ProductController@update');
        Route::delete('/delete', 'API\ProductController@delete');
    });
    
});


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
