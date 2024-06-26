<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExcelController;
use App\Http\Controllers\TasaDesercionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServiciosController;
use App\Http\Controllers\TasaTitulacionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/register', [AuthController::class,'register']);
Route::post('/login', [AuthController::class,'login']);
Route::get('/verifyToken', [AuthController::class,'verificarToken'])->middleware(['auth:sanctum', 'abilities:check-status']);

Route::post('/registrarDatosExcel', [ExcelController::class,'registrarDatosExcel'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/registrarDatosExcelPeriodoTitulacion', [ExcelController::class,'registrarDatosExcelPeriodoTitulacion'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/obtenerHistorialPeriodoTasaDesercion', [ExcelController::class,'obtenerHistorialPeriodoTasaDesercion'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/obtenerHistorialPeriodoTasaTitulacion', [ExcelController::class,'obtenerHistorialPeriodoTasaTitulacion'])->middleware(['auth:sanctum', 'abilities:check-status']);

Route::post('/obtenerDataPeriodo', [TasaDesercionController::class,'obtenerDataPeriodo'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/obtenerDataPeriodoTitulacion', [TasaTitulacionController::class,'obtenerDataPeriodoTitulacion'])->middleware(['auth:sanctum', 'abilities:check-status']);


Route::post('/obtenerDashboard', [DashboardController::class,'index'])->middleware(['auth:sanctum', 'abilities:check-status']);

Route::post('/obtenerComboPeriodo', [ServiciosController::class,'obtenerComboPeriodo'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::get('/comboCarreras', [ServiciosController::class,'comboCarreras'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::get('/getConfiguracion/{id}', [ServiciosController::class,'getConfiguracion'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/saveConfiguration', [ServiciosController::class,'saveConfiguration'])->middleware(['auth:sanctum', 'abilities:check-status']);


