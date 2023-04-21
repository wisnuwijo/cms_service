<?php

Route::post('/v1/login', 'API\LoginController@login');

Route::group(['prefix' => 'v1/region'], function() {
    Route::get('/city/{provinceId}','AddressUtilityController@cityList');
});

Route::group(['prefix' => 'v1', 'middleware' => 'auth:api'], function() {
    Route::post('/change_password', 'API\LoginController@change_password');

    Route::group(['prefix' => 'product'], function() {
        Route::get('/', 'API\ProductController@index');
        Route::get('/detail', 'API\ProductController@detail');
        Route::post('/create', 'API\ProductController@create');
        Route::patch('/update', 'API\ProductController@update');
        Route::delete('/delete', 'API\ProductController@delete');
    });

    Route::group(['prefix' => 'customer'], function() {
        Route::get('/', 'API\CustomerController@index');
        Route::get('/detail', 'API\CustomerController@detail');
        Route::post('/create', 'API\CustomerController@create');
        Route::patch('/update', 'API\CustomerController@update');
        Route::delete('/delete', 'API\CustomerController@delete');
    });

    Route::group(['prefix' => 'transaction'], function() {
        Route::get('/', 'API\TransactionController@index');
        Route::get('/detail', 'API\TransactionController@detail');
    });

    Route::group(['prefix' => 'employee'], function() {
        Route::get('/', 'API\EmployeeController@index');
        Route::get('/role','API\EmployeeController@role_list');
        Route::get('/detail', 'API\EmployeeController@detail');
        Route::post('/create', 'API\EmployeeController@create');
        Route::patch('/update', 'API\EmployeeController@update');
        Route::delete('/delete', 'API\EmployeeController@delete');
    });

});
