<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegistrationController;

Route::post('/login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logout');
        Route::get('/user', 'user');
    });

    Route::controller(RegistrationController::class)->group(function () {
        Route::get('/registration', 'getAllRegistrants');
        Route::get('/registration/{id}', 'getRegistrantById');
        Route::post('/registration', 'createRegistrant');
        Route::put('/registration/{id}', 'updateRegistrant');
        Route::delete('/registration/{id}', 'deleteRegistrant');
    });
});