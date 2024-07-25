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
use App\Models\EstudiantesEgresados;
use App\Models\Habilitado;
use App\Models\RegistroEstudiantil;
use App\Models\TipoGrafico;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TasaTitulacionController extends Controller
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
    public function eliminarDatosTasaTitulacion(Request $request){
        try {
            ArchivosSubidos::where('id_periodo',$request->id)->where('id_carrera',$request->id_carrera)->delete();
            EstudiantesEgresados::where('id_periodo',$request->id)->where('id_carrera',$request->id_carrera)->delete();
            return response()->json([
                'ok'=>true,
            ], 200);
        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al eliminar los datos',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }


    public function obtenerDataPeriodoTitulacion(Request $request){
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
                ->mergeBindings($subQuery->getQuery()) 
                ->select('sub.*')
                ->where('sub.row_num', '>=', $row_periodo)
                ->orderBy('sub.anio_inicio', 'desc')
                ->orderBy('sub.ciclo', 'desc')
                ->limit($configuracionCarrera->total_periodos + $configuracionCarrera->periodos_gracia)
                ->get();
            
            $estudiantesEgresados = EstudiantesEgresados::where('id_periodo_relacionado',$filteredPeriodos[0]->id)->get();
            if(! count($estudiantesEgresados) > 0 ){
                throw new Exception("No existen estudiantes egresados en ese periodo");
            }
            
            $hasta = $configuracionCarrera->total_periodos + $configuracionCarrera->periodos_gracia;
            $datosTitulacion = [] ;
            for ($i=$configuracionCarrera->total_periodos- 1; $i < $hasta; $i++) { 
                if(isset($filteredPeriodos[$i])){
                        $contador = RegistroEstudiantil::where('registro_estudiantil.id_periodo',$filteredPeriodos[$i]->id)
                        ->whereIn('registro_estudiantil.id_estudiante',$estudiantesEgresados->pluck('id_estudiante'))
                        ->join('habilitado','registro_estudiantil.id_habilitado','habilitado.id')
                        ->where('habilitado.descripcion','NIVELACION')
                        ->count();
                        $datosTitulacion [] =[
                            "value"=>$contador,
                            "label"=>$filteredPeriodos[$i]->codigo
                        ];
                }
            }
          
            return response()->json([
                'ok'=>true,
                'data' => ["titulados_nivelacion"=>$datosTitulacion,"total_titulados"=>["label"=>$filteredPeriodos[0]->codigo,"value"=>count($estudiantesEgresados)]],
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


    public function obtenerHistorialPeriodoTasaTitulacion(Request $request){
        try {
            $periodos = Periodo::select(
                'periodo.id',
                'archivos_subidos.id_carrera',
                'periodo.codigo as periodo',
                'archivos_subidos.created_at',
                "carrera.carrera",
            )
            ->join('archivos_subidos','archivos_subidos.id_periodo','periodo.id')
            ->join('carrera','carrera.id','archivos_subidos.id_carrera')
            ->orderBy('periodo.codigo','asc')
            ->where('archivos_subidos.id_indicador',2)
            ->where('archivos_subidos.estado',1)
            ->get();
            return response()->json(['ok'=>true,'periodos' =>$periodos]);

        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al obtener el registro de periodos',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }

    

    
}