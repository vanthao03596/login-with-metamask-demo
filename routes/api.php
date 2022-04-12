<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AutController;
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

Route::middleware('auth:api')
    ->group(function() {
        Route::get('/account', [AccountController::class, 'index']);
    });

Route::post('/user/nonce', [AutController::class, 'getNonce']);

Route::post('/user/nonce', [AutController::class, 'getNonce']);

Route::post('/auth/web3', [AutController::class, 'authWeb3']);
