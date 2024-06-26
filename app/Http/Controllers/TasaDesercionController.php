<?php

namespace App\Http\Controllers;

use App\Models\ArchivosSubidos;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\Periodo;
use App\Models\Carrera;
use App\Models\ConfigIndicadoresCarrera;
use App\Models\Estudiantes;
use App\Models\Habilitado;
use App\Models\RegistroEstudiantil;
use App\Models\TipoGrafico;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TasaDesercionController extends Controller
{
 
public function obtenerRowPeriodo($id_periodo){
    $rawPeriodos = Periodo::select(
        DB::raw('ROW_NUMBER() OVER (ORDER BY periodo.anio_inicio DESC, periodo.ciclo DESC) as row_num'),
        "periodo.id",
        "periodo.codigo"
    )
    ->join('archivos_subidos','archivos_subidos.id_periodo','periodo.id')
    ->where('archivos_subidos.id_indicador',1)
    ->orderBy('periodo.anio_inicio','desc')
    ->orderBy('periodo.ciclo','desc');
    
    $periodoWithRowNum = DB::table(DB::raw("({$rawPeriodos->toSql()}) as sub"))
        ->mergeBindings($rawPeriodos->getQuery()) // Para combinar las bindings de la subconsulta
        ->select('sub.*')
        ->where('sub.id', $id_periodo)
        ->first();
    return $periodoWithRowNum;
    }

    public function obtenerDataPeriodo(Request $request){
        try {
            $configuracionCarrera = ConfigIndicadoresCarrera::where('id_carrera',$request->carrera)->first();
            $row_periodo = $this->obtenerRowPeriodo($request->periodo)->row_num;
            $subQuery = Periodo::select(
                DB::raw('ROW_NUMBER() OVER (ORDER BY periodo.anio_inicio DESC, periodo.ciclo DESC) as row_num'),
                "periodo.id",
                "periodo.codigo",
                "periodo.anio_inicio",
                "periodo.anio_fin",
                "periodo.ciclo"
            )
            ->join('archivos_subidos','archivos_subidos.id_periodo','periodo.id')
            ->where('archivos_subidos.id_indicador',1)
            ;
            $filteredPeriodos = DB::table(DB::raw("({$subQuery->toSql()}) as sub"))
                ->mergeBindings($subQuery->getQuery()) // Para combinar las bindings de la subconsulta
                ->select('sub.*')
                ->where('sub.row_num', '>=', $row_periodo)
                ->orderBy('sub.anio_inicio', 'desc')
                ->orderBy('sub.ciclo', 'desc')
                ->limit($configuracionCarrera->periodos_desercion)
                ->get();

                $ultimoPeriodo = collect($filteredPeriodos)->last();
         
            
            $estudiantesIngresados = RegistroEstudiantil::select(
                "registro_estudiantil.id_estudiante",
                "estudiantes.estudiante",
                "periodo.codigo as periodo"
            )
            ->join("periodo",'periodo.id','registro_estudiantil.id_periodo')
            ->join('estudiantes','estudiantes.id','registro_estudiantil.id_estudiante')
            ->join('habilitado','habilitado.id','registro_estudiantil.id_habilitado')
            ->where('habilitado.descripcion','NIVELACION')
            ->where('registro_estudiantil.id_carrera',$request->carrera)
            ->where('registro_estudiantil.id_periodo',$ultimoPeriodo->id)
            ->get();
            $data = [];

            $arrayEstudiantes = $estudiantesIngresados->pluck('id_estudiante');
            
            foreach ($filteredPeriodos as $key => $valueDatos) {
                    $total_estudiantes = RegistroEstudiantil::
                    join("periodo",'periodo.id','registro_estudiantil.id_periodo')
                    ->join('estudiantes','estudiantes.id','registro_estudiantil.id_estudiante')
                    ->join('habilitado','habilitado.id','registro_estudiantil.id_habilitado')
                    ->whereIn('registro_estudiantil.id_estudiante',$arrayEstudiantes)
                    ->where('registro_estudiantil.id_carrera',$request->carrera)
                    ->where('registro_estudiantil.id_periodo',$valueDatos->id)
                    ->count();
                    $data[]=[
                        "row"=>$key,
                        "total_estudiantes"=>$total_estudiantes,
                        "id"=>$valueDatos->id,
                        "codigo"=>$valueDatos->codigo,
                        "anio_inicio"=>$valueDatos->anio_inicio,
                        "anio_fin"=>$valueDatos->anio_fin,
                        "ciclo"=>$valueDatos->ciclo,
                    ];
            }

          
            return response()->json([
                'ok'=>true,
                'data' => $data,
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

    
}