<?php

Route::get('/{token}', 'AddressUtilityController@index');
Route::post('/save-address', 'AddressUtilityController@saveAddress');
Route::get('/transaction-history/{token}', 'TransactionController@transactionHistory');
Route::get('/confirm-payment/feedback', 'TransactionController@feedback');
Route::get('/confirm-payment/{token}/{trxid}', 'TransactionController@confirmPaymentForm');
Route::get('/confirm-payment/{token}', 'TransactionController@transactionList');
Route::post('/confirm-payment', 'TransactionController@savePayment');

Route::get('/', function () {
    return response([
        "message" => "You are not allowed to access this resource",
        "code" => 401
    ], 401);
});
