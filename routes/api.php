<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductVariationController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

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
//User Routes
Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::get('profile', 'me')->middleware('jwt.verify');
});

// Customer Routes
Route::group(['prefix' => 'customer'], function () {
    Route::post('/create', [CustomerController::class, 'create']);
    Route::patch('/update', [CustomerController::class, 'update']);
    Route::delete('/delete', [CustomerController::class, 'destroy']);
    Route::get('/', [CustomerController::class, 'show']);
});

// Role Routes
Route::group(['prefix' => 'role'], function () {
    Route::post('/create', [RoleController::class, 'create'])->middleware('jwt.verify', 'permission:CREATE_ROLE');
    Route::patch('/update', [RoleController::class, 'update'])->middleware('jwt.verify', 'permission:EDIT_ROLE');
    Route::delete('/delete', [RoleController::class, 'destroy']);
//    Route::post('/assign', [RoleController::class,'assign']);
});

// Brand Routes
Route::group(['prefix' => 'brand'], function () {
    Route::post('/create', [BrandController::class, 'store'])->middleware('jwt.verify', 'permission:CREATE_BRAND');
    Route::post('/update', [BrandController::class, 'update'])->middleware('jwt.verify', 'permission:EDIT_BRAND');
    Route::delete('/delete', [BrandController::class, 'destroy'])->middleware('jwt.verify', 'permission:DELETE_BRAND');
    Route::get('/', [BrandController::class, 'show']);
    Route::get('/list', [BrandController::class, 'all']);

});

// Categories Routes
Route::group(['prefix' => 'category'], function () {
    Route::post('/create', [CategoryController::class, 'store'])->middleware('jwt.verify', 'permission:CREATE_CATEGORY');
    Route::patch('/update', [CategoryController::class, 'update'])->middleware('jwt.verify', 'permission:EDIT_CATEGORY');
    Route::delete('/delete', [CategoryController::class, 'destroy'])->middleware('jwt.verify', 'permission:DELETE_CATEGORY');
    Route::get('/', [CategoryController::class, 'show']);
    Route::get('/list', [CategoryController::class, 'all']);
});

//Products Routes
Route::group(['prefix' => 'product'], function () {
    Route::post('/create', [ProductController::class, 'store'])->middleware('jwt.verify', 'permission:CREATE_PRODUCT');
    Route::post('/update', [ProductController::class, 'update'])->middleware('jwt.verify', 'permission:EDIT_PRODUCT');
    Route::delete('/delete', [ProductController::class, 'destroy'])->middleware('jwt.verify', 'permission:DELETE_PRODUCT');
    Route::get('/', [ProductController::class, 'show']);
    Route::get('/list', [ProductController::class, 'all']);

    Route::delete('/image/delete', [ImageController::class, 'deleteProductImage']);
    Route::patch('/image/primary', [ImageController::class, 'makePrimaryImage']);
});

// Product Variant Routes
Route::group(['prefix' => 'product-variant'], function () {
    Route::post('/create', [ProductVariationController::class, 'store'])->middleware('jwt.verify', 'permission:CREATE_PRODUCT_VARIANT');
    Route::post('/update', [ProductVariationController::class, 'update'])->middleware('jwt.verify', 'permission:EDIT_PRODUCT_VARIANT');
    Route::delete('/delete', [ProductVariationController::class, 'destroy'])->middleware('jwt.verify', 'permission:DELETE_PRODUCT_VARIANT');
    Route::get('/', [ProductVariationController::class, 'show']);
    Route::get('/list', [ProductVariationController::class, 'all']);
});

// Offer Routes
Route::group(['prefix' => 'offer'], function () {
    Route::post('/create', [OfferController::class, 'store'])->middleware('jwt.verify', 'permission:CREATE_OFFER');
    Route::patch('/update', [OfferController::class, 'update'])->middleware('jwt.verify', 'permission:EDIT_OFFER');
    Route::delete('/delete', [OfferController::class, 'destroy'])->middleware('jwt.verify', 'permission:DELETE_OFFER');
    Route::get('/', [OfferController::class, 'show']);
    Route::get('/list/private', [OfferController::class, 'all'])->middleware('jwt.verify', 'permission:VIEW_ALL_OFFERS');
    Route::get('/list', [OfferController::class, 'allActive']);
    // Active Offer
    Route::patch('/activateOrDeactivate', [OfferController::class, 'activateOrDeactivate'])->middleware('jwt.verify', 'permission:EDIT_OFFER');
    Route::post('/assign-offer', [ProductController::class, 'assignOffer'])->middleware('jwt.verify', 'permission:ASSIGN_OFFER');
});

// Cart Routes
Route::group(['prefix' => 'cart'], function () {
    Route::get('/my-cart', [CartController::class, 'show'])->middleware('jwt.verify');
    Route::post('/create', [CartController::class, 'store'])->middleware('jwt.verify');
    Route::patch('/update', [CartController::class, 'update'])->middleware('jwt.verify');
    Route::delete('/delete', [CartController::class, 'destroy'])->middleware('jwt.verify');
    Route::delete('/remove-item', [CartController::class, 'remove'])->middleware('jwt.verify');
});

// Test Route
Route::get('/test', function () {
    return response()->json(['Hello Raihan']);
});

// Default Route
Route::fallback(function () {
    return response()->json(['message' => 'Route not found'], 404);
});


