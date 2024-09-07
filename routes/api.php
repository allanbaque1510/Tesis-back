<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExcelController;
use App\Http\Controllers\TasaDesercionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogroAprendizajeController;
use App\Http\Controllers\ServiciosController;
use App\Http\Controllers\TasaReprobadoController;
use App\Http\Controllers\TasaTitulacionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//autenticacion
Route::post('/register', [AuthController::class,'register']);
Route::post('/login', [AuthController::class,'login']);
Route::get('/verifyToken', [AuthController::class,'verificarToken'])->middleware(['auth:sanctum', 'abilities:check-status']);


//excel
Route::post('/registrarDatosExcel', [ExcelController::class,'registrarDatosExcel'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/registrarDatosExcelPeriodoTitulacion', [ExcelController::class,'registrarDatosExcelPeriodoTitulacion'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/registrarDatosExcelNominaMateriaDocent', [ExcelController::class,'registrarDatosExcelNominaMateriaDocent'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/descargarFormatoPuntuacion', [ExcelController::class,'descargarFormatoPuntuacion'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/subirAsignacionPuntosMasiva', [ExcelController::class,'subirAsignacionPuntosMasiva'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/registrarDatosExcelNominaEstudiantesPeriodo', [ExcelController::class,'registrarDatosExcelNominaEstudiantesPeriodo'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/registrarDatosExcelReprobados', [ExcelController::class,'registrarDatosExcelReprobados'])->middleware(['auth:sanctum', 'abilities:check-status']);


//tasa desercion
Route::post('/obtenerHistorialPeriodoTasaDesercion', [TasaDesercionController::class,'obtenerHistorialPeriodoTasaDesercion'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/obtenerDataPeriodo', [TasaDesercionController::class,'obtenerDataPeriodo'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/eliminarTasaDesercion', [TasaDesercionController::class,'eliminarTasaDesercion'])->middleware(['auth:sanctum', 'abilities:check-status']);


//tasa titulacion
Route::post('/obtenerHistorialPeriodoTasaTitulacion', [TasaTitulacionController::class,'obtenerHistorialPeriodoTasaTitulacion'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/obtenerDataPeriodoTitulacion', [TasaTitulacionController::class,'obtenerDataPeriodoTitulacion'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/eliminarDatosTasaTitulacion', [TasaTitulacionController::class,'eliminarDatosTasaTitulacion'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/descargarExcelTasaTitulacion', [TasaTitulacionController::class,'descargarExcelTasaTitulacion'])->middleware(['auth:sanctum', 'abilities:check-status']);


//Tasa de reprobados
Route::post('/obtenerReprobadosMateria', [TasaReprobadoController::class,'obtenerReprobadosMateria'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/obtenerReprobadoMateriaDetalle', [TasaReprobadoController::class,'obtenerReprobadoMateriaDetalle'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/obtenerReprobadosMateriaPorcentaje', [TasaReprobadoController::class,'obtenerReprobadosMateriaPorcentaje'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/obtenerReprobadoMateriaDetallePorcentaje', [TasaReprobadoController::class,'obtenerReprobadoMateriaDetallePorcentaje'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/descargarExcelTasaReprobados', [TasaReprobadoController::class,'descargarExcelTasaReprobados'])->middleware(['auth:sanctum', 'abilities:check-status']);


//dashboard
Route::post('/obtenerDashboard', [DashboardController::class,'index'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/obtenerDashboardLogros', [DashboardController::class,'obtenerDashboardLogros'])->middleware(['auth:sanctum', 'abilities:check-status']);


//servicios
Route::post('/obtenerComboPeriodo', [ServiciosController::class,'obtenerComboPeriodo'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/obtenerComboPeriodoTitulacion', [ServiciosController::class,'obtenerComboPeriodoTitulacion'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/saveConfiguration', [ServiciosController::class,'saveConfiguration'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::get('/comboCarreras', [ServiciosController::class,'comboCarreras'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::get('/getConfiguracion/{id}', [ServiciosController::class,'getConfiguracion'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::get('/obtenerMaterias/{id_carrera}/{id_periodo}', [ServiciosController::class,'obtenerMaterias'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::get('/obtenerPeriodoNominaCarreraDocenteMateria/{id_carrera}', [ServiciosController::class,'obtenerPeriodoNominaCarreraDocenteMateria'])->middleware(['auth:sanctum', 'abilities:check-status']);

Route::get('/obtenerPeriodoNominaCarreraDocenteMateriaConEstudiantes/{id_carrera}', [ServiciosController::class,'obtenerPeriodoNominaCarreraDocenteMateriaConEstudiantes'])->middleware(['auth:sanctum', 'abilities:check-status']);

Route::get('/obtenerMateriasConLogros/{id_carrera}/{id_periodo}', [ServiciosController::class,'obtenerMateriasConLogros'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/obtenerDocentesPeriodoCarrera', [ServiciosController::class,'obtenerDocentesPeriodoCarrera'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/obtenerLogrosAprendizajeDocente', [ServiciosController::class,'obtenerLogrosAprendizajeDocente'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/obtenerGruposDocenteMateria', [ServiciosController::class,'obtenerGruposDocenteMateria'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/historialReporteNominaGrupoEstudiantes', [ServiciosController::class,'historialReporteNominaGrupoEstudiantes'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/historialReporteReprobados', [ServiciosController::class,'historialReporteReprobados'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/eliminarDatosArchivo', [ServiciosController::class,'eliminarDatosArchivo'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/obtenerComboPeriodoReprobados', [ServiciosController::class,'obtenerComboPeriodoReprobados'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/obtenerComboPeriodoLogrosAprendizaje', [ServiciosController::class,'obtenerComboPeriodoLogrosAprendizaje'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/obtenerComboMateriasLogrosAprendizaje', [ServiciosController::class,'obtenerComboMateriasLogrosAprendizaje'])->middleware(['auth:sanctum', 'abilities:check-status']);



//logros de aprendizaje
Route::post('/saveLogroAprendizaje', [LogroAprendizajeController::class,'store'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::get('/obtenerLogrosAprendizaje', [LogroAprendizajeController::class,'index'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/modificarLogroAprendizaje', [LogroAprendizajeController::class,'update'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/historialReporteNominaCarreraDocenteMateria', [LogroAprendizajeController::class,'historialReporteNominaCarreraDocenteMateria'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/asignarLogrosAprendizajeMasivo', [LogroAprendizajeController::class,'asignarLogrosAprendizajeMasivo'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/obtenerLogrosPeriodo', [LogroAprendizajeController::class,'obtenerLogrosPeriodo'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/clonarLogrosPorPeriodo', [LogroAprendizajeController::class,'clonarLogrosPorPeriodo'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/asignarLogrosPorMateria', [LogroAprendizajeController::class,'asignarLogrosPorMateria'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/obtenerMateriasLogrosPeriodo', [LogroAprendizajeController::class,'obtenerMateriasLogrosPeriodo'])->middleware(['auth:sanctum', 'abilities:check-status']);
Route::post('/getLogrosPorMateria', [LogroAprendizajeController::class,'getLogrosPorMateria'])->middleware(['auth:sanctum', 'abilities:check-status']);




