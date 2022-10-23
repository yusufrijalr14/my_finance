<?php

use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
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

Route::group(['prefix' => 'user'], function() {
    Route::post('/register', [UserController::class, 'store']);
    Route::post('/login', [UserController::class, 'login']);
});

Route::group(['middleware' => 'App\Http\Middleware\AuthCheck'], function () {
    Route::group(['prefix' => 'user'], function() {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/force_deletes', [UserController::class, 'forceDeletes']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
        Route::post('/logout', [UserController::class, 'logout']);
        Route::get('/me', [UserController::class, 'me']);
        Route::get('/trash', [UserController::class, 'trash']);
        Route::post('/restore/{id}', [UserController::class, 'restore']);
        Route::post('/restores', [UserController::class, 'restores']);
        Route::delete('/force_delete/{id}', [UserController::class, 'forceDelete']);
    });

    Route::group(['prefix' => 'transaction'], function() {
        Route::get('/', [TransactionController::class, 'index']);
        Route::post('/', [TransactionController::class, 'store']);
        Route::put('/{id}', [TransactionController::class, 'update']);
        Route::delete('/{id}', [TransactionController::class, 'destroy']);
        Route::get('/income_summary', [TransactionController::class, 'incomeSummary']);
        Route::get('/expense_summary', [TransactionController::class, 'expenseSummary']);
        Route::get('/highest', [TransactionController::class, 'highest']);
        Route::get('/left', [TransactionController::class, 'left']);
        Route::get('/trash', [TransactionController::class, 'trash']);
        Route::post('/restore/{id}', [TransactionController::class, 'restore']);
        Route::post('/restores/{type}', [TransactionController::class, 'restores']);
        Route::delete('/force_delete/{id}', [TransactionController::class, 'forceDelete']);
        Route::delete('/force_deletes/{type}', [TransactionController::class, 'forceDeletes']);
    });
});
