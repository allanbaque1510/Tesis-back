<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExcelController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/register', [AuthController::class,'register']);
Route::post('/login', [AuthController::class,'login']);
Route::get('/verifyToken', [AuthController::class,'verificarToken'])->middleware(['auth:sanctum', 'abilities:check-status,place-orders']);
Route::post('/registrarDatosExcel', [ExcelController::class,'registrarDatosExcel'])->middleware(['auth:sanctum', 'abilities:check-status,place-orders']);