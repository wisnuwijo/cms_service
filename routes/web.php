<?php

Route::get('/{token}', 'AddressUtilityController@index');
Route::post('/save-address', 'AddressUtilityController@saveAddress');
Route::get('/transaction-history/{token}', 'TransactionController@transactionHistory');
Route::get('/confirm-payment/feedback', 'TransactionController@feedback');
Route::get('/confirm-payment/{token}/{trxid}', 'TransactionController@confirmPaymentForm');
Route::get('/confirm-payment/{token}', 'TransactionController@transactionList');
Route::post('/confirm-payment', 'TransactionController@savePayment');

//Clear Cache facade value:
Route::get('/clear-cache', function() {
    $exitCode = Artisan::call('cache:clear');
    return '<h1>Cache facade value cleared</h1>';
});

//Reoptimized class loader:
Route::get('/optimize', function() {
    $exitCode = Artisan::call('optimize');
    return '<h1>Reoptimized class loader</h1>';
});

//Route cache:
Route::get('/route-cache', function() {
    $exitCode = Artisan::call('route:cache');
    return '<h1>Routes cached</h1>';
});

//Clear Route cache:
Route::get('/route-clear', function() {
    $exitCode = Artisan::call('route:clear');
    return '<h1>Route cache cleared</h1>';
});

//Clear View cache:
Route::get('/view-clear', function() {
    $exitCode = Artisan::call('view:clear');
    return '<h1>View cache cleared</h1>';
});

//Clear Config cache:
Route::get('/config-cache', function() {
    $exitCode = Artisan::call('config:cache');
    return '<h1>Clear Config cleared</h1>';
});

Route::get('/', function () {
    return response([
        "message" => "You are not allowed to access this resource",
        "code" => 401
    ], 401);
});
