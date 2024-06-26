<?php

namespace App\Http\Controllers;

use App\Models\ArchivosSubidos;
use App\Models\ConfigIndicadoresCarrera;
use App\Models\TipoGrafico;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\Periodo;
use App\Models\Carrera;
use App\Models\Estudiantes;
use App\Models\Habilitado;
use App\Models\RegistroEstudiantil;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ServiciosController extends Controller
{
    public function comboCarreras(){
        try {
            
            $data = Carrera::select(
                "id as value",
                "carrera as label"
            )->get();
            return response(["ok"=>true,"data"=>$data],200);
        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al verificar el token',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }

    public function getConfiguracion($id){
        try {
            $data = ConfigIndicadoresCarrera::where('id_carrera',$id)->first();
            return ['ok'=>true,'data'=>$data];
            
        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al verificar el token',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }
    public function obtenerComboPeriodo(Request $request){
        try {

            if(!isset($request->id_carrera)){
                $periodos = Periodo::select(
                    "id as value",
                    "codigo as label"
                )->orderBy('codigo','desc')->get();
            }else{
                $periodos = RegistroEstudiantil::select(
                    "periodo.id as value",
                    "periodo.codigo as label"
                )
                ->where('registro_estudiantil.id_carrera',$request->id_carrera)
                ->join('periodo','registro_estudiantil.id_periodo','periodo.id')
                ->distinct('periodo.id')
                ->orderBy('periodo.codigo','desc')
                ->get();
            }
            $configuracion = ConfigIndicadoresCarrera::where('id_carrera',$request->id_carrera)->first();


            $tipoGrafico = TipoGrafico::select(
                "id_tipo as value",
                "descripcion as label"
            )->get();

            return response()->json([
                'ok'=>true,
                'data' => ["periodo"=>$periodos,'tipo_grafico'=>$tipoGrafico,'configuracion'=>$configuracion]
            ], 200);
        
        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al verificar el token',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }

    public function saveConfiguration(Request $request){
        try {
            if(isset($request->id_configuracion)){
                $data = ConfigIndicadoresCarrera::where('id_configuracion',$request->id_configuracion)
                ->update([
                    'periodos_desercion'=>$request->cantidad_periodos,
                    'total_periodos'=>$request->cantidad_total_periodos,
                    'periodos_gracia'=>$request->cantidad_periodos_gracia,
                ]);               
            }else{
                $data = ConfigIndicadoresCarrera::create([
                     'id_carrera'=>$request->carrera,
                     'periodos_desercion'=>$request->cantidad_periodos,
                     'total_periodos'=>$request->cantidad_total_periodos,
                     'periodos_gracia'=>$request->cantidad_periodos_gracia,
                 ]);
            }

            return ['ok'=>true,'data'=>$data];

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