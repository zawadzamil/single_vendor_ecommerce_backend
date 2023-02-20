<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\RoleController;
use Illuminate\Http\Request;
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
    Route::get('/', [BrandController::class,'show']);
    Route::get('/all', [BrandController::class, 'all']);

});

// Categories Routes
Route::group(['prefix' => 'category'], function () {
    Route::post('/create', [CategoryController::class,'store'])->middleware('jwt.verify', 'permission:CREATE_CATEGORY');
    Route::post('/update', [CategoryController::class, 'update'])->middleware('jwt.verify', 'permission:EDIT_CATEGORY');
    Route::delete('/delete', [CategoryController::class, 'destroy'])->middleware('jwt.verify', 'permission:DELETE_CATEGORY');
    Route::get('/', [CategoryController::class,'show']);
    Route::get('/all', [CategoryController::class, 'all']);
});



