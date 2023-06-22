<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => "package"], function () {
    Route::get('/', "PackageController@get");
});

Route::get('testEmail', "UserController@testEmail");

Route::group(['prefix' => "user"], function () {
    Route::post('auth', "UserController@auth");
    Route::post('login', "UserController@login");
    Route::post('register', "UserController@register");
    Route::post('home', "UserController@home");
    Route::post('test', "UserController@test");

    Route::group(['prefix' => "{username}"], function () {
        Route::get('/', "UserController@profile");
        Route::post('update', "UserController@update");
        Route::get('/products', "UserController@products");

        Route::group(['prefix' => "customer"], function () {
            Route::post('register', "CustomerController@register");
            Route::post('auth', "CustomerController@auth");

            Route::group(['prefix' => "cart"], function () {
                Route::post('/', "CustomerController@cart");
                Route::post('increase', "CartController@increase");
                Route::post('decrease', "CartController@decrease");
                Route::post('checkout', "CartController@checkout");
            });

            Route::group(['prefix' => "order/{code?}"], function () {
                Route::post('/', "CartController@detailOrder");
                Route::post('place', "CartController@placeOrder");
            });

            Route::post('{id}', "UserController@customerDetail");
            Route::post('/', "UserController@customer");
        });
    });
});

Route::group(['prefix' => "payment"], function () {
    Route::group(['prefix' => "callback"], function () {
        Route::post('ewallet', "CartController@ewalletCallback");
    });

    Route::get('va/{paymentID}', "PaymentController@getVA");
});

Route::group(['prefix' => "chat"], function () {
    Route::group(['prefix' => "customer"], function () {
        Route::post('/', "ChatController@customerGet");
        Route::post('send', "ChatController@customerSend");
    });

    Route::group(['prefix' => "question", 'middleware' => "User"], function () {
        Route::post('create', "QuestionController@create");
        Route::post('update', "QuestionController@update");
        Route::post('delete', "QuestionController@delete");
    });
});

Route::group(['prefix' => "product"], function () {
    Route::group(['middleware' => "User"], function () {
        Route::post('create', "ProductController@create");
        Route::post('delete', "ProductController@delete");
        Route::post('statistic', "ProductController@statistic");
        Route::post('mine', "ProductController@mine");
    });
    Route::group(['prefix' => "{id}"], function () {
        Route::post('statistic', "ProductController@statistic");
        Route::get('schedule', "ProductController@schedule");
        Route::post('/', "ProductController@get");
    });
});

Route::group(['prefix' => "otp"], function () {
    Route::post('create', "OtpController@create");
    Route::post('auth', "OtpController@auth");

    Route::group(['prefix' => "customer"], function () {
        Route::post('auth', "OtpController@customerAuth");
    });
});

Route::group(['prefix' => "affiliate"], function () {
    Route::post('login', "AffiliateController@login");

    Route::group(['prefix' => "coupon"], function () {
        Route::post('/', "AffiliateController@coupon");
        Route::post('create', "PackageController@createDiscount");
        Route::post('delete', "PackageController@deleteDiscount");
    });
});