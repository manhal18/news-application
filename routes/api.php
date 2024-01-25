<?php

use App\Http\Controllers\API\V1\ArticleController;
use App\Http\Controllers\API\V1\CategoryController;
use App\Http\Controllers\API\V1\TokenController;
use App\Http\Controllers\API\V1\UserController;
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
Route::apiResource('/categories',CategoryController::class, ['except' => ['show']]);

Route::apiResource('/articles',ArticleController::class, ['except' => ['update']]);

Route::get('/articles/byCategory/{category_id}',[ArticleController::class,'getByCategoryId']);

Route::post('/articles/{id}',[ArticleController::class,'update']);

Route::get('/tokens',[TokenController::class,'index']);

Route::post('/tokens',[TokenController::class,'store']);

Route::get('/tokens/checkToken/{token}',[TokenController::class,'checkTokenExist']);

Route::post('/auth/login',[UserController::class,'login']);

Route::post('/auth',[UserController::class,'store'])->middleware('auth:sanctum');

Route::post('/auth/changePassword',[UserController::class,'update'])->middleware('auth:sanctum');

