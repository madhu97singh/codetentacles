<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\SellerAuthController;
use App\Http\Controllers\Api\ProductController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['namespace' => 'Api\admin',"prefix"=>"admin"], function () {
    Route::post('admin-register',[AdminAuthController::class,'register']);
    Route::post('admin-login',[AdminAuthController::class,'login']);
    Route::post('seller-login',[SellerAuthController::class,'sellerLogin']);
    Route::get('sellers-list', [ProductController::class,'productsList']);
});
Route::group(['namespace' => 'Api\admin',"prefix"=>"admin"], function () {
    Route::group(['middleware' => ['jwt']], function () {
        Route::post('seller-register',[SellerAuthController::class,'sellerRegister']);
        Route::get('sellers-list', [SellerAuthController::class,'sellersList']);
        // admin routes
        Route::group(['middleware' => 'jwt:admin'], function () {
            
        });
        // seller routes
        Route::group(['middleware' => 'jwt:seller'], function () {
            Route::post('add-product', [ProductController::class,'addProduct']);
            Route::get('products-list', [ProductController::class,'productsList']);
            Route::delete('delete-product', [ProductController::class,'deleteProduct']);
        });
    });
});
