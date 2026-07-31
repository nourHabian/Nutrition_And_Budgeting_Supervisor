<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FamilySetupController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/ingredients/search', [SearchController::class, 'ingredients']);
Route::get('/meals/search', [SearchController::class, 'meals']);

Route::middleware('auth:sanctum')->group(function() {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/family/setup', [FamilySetupController::class, 'store']);
    Route::get('/family/profile', [FamilySetupController::class, 'show']);
    Route::put('/family/profile',[FamilySetupController::class,'update']);

});