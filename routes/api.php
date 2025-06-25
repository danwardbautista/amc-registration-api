<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegistrationController;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:20,1');

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logout')
            ->middleware('throttle:10,1');
        Route::get('/user', 'user')
            ->middleware('throttle:60,1');
    });

    Route::controller(RegistrationController::class)
        ->middleware('throttle:30,0.5')
        ->group(function () {
            Route::get('/registration', 'getAllRegistrants');
            Route::get('/registration/{id}', 'getRegistrantById');
            Route::post('/registration', 'createRegistrant');
            Route::put('/registration/{id}', 'updateRegistrant');
            Route::delete('/registration/{id}', 'deleteRegistrant');
        });
});
