<?php

Route::get('/{token}', 'AddressUtilityController@index');
Route::post('/save-address', 'AddressUtilityController@saveAddress');

Route::get('/', function () {
    return view('welcome');
});
