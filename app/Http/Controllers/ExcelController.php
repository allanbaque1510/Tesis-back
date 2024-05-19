<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Readers\LaravelExcelReader;
use Illuminate\Support\Facades\Storage;

class ExcelController extends Controller
{
    public function registrarDatosExcel(Request $request){
        try {
            $uploadedFile = $request->file('file');

            $data = Excel::toArray([], $uploadedFile);
            Log::info($data);
    
            // Puedes devolver la ruta del archivo guardado si lo necesitas
            // Storage::put('excelxd.xlsx', $binaryData);

            // if (Auth::check()) {
            //     $usuario = Auth::user();
            //     return response()->json([
            //         'usuario' => $usuario,
            //     ]);
            // } else {
            //     return response()->json(['error' => 'No autenticado'], 401);
            // }
        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al verificar el token',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }
}