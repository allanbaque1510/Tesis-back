<?php

namespace App\Http\Controllers;

use App\Exports\TasaTitulacionExport;
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
            
            $estudiantesEgresados = EstudiantesEgresados::where('id_carrera',$request->carrera)->where('id_periodo_relacionado',$filteredPeriodos[0]->id)->distinct('id_estudiante')->get();

            if(! count($estudiantesEgresados) > 0 ){
                throw new Exception("No existen estudiantes egresados en ese periodo");
            }

            $data = RegistroEstudiantil::select(
                "estudiantes.estudiante",
                "registro_estudiantil.id_periodo",
                "periodo_titulacion.codigo as periodo_titulacion",
                "periodo_ingreso.codigo as periodo_ingreso",
                "estudiantes_egresados.prom_materia",
                "estudiantes_egresados.prom_titulacion",
                "estudiantes_egresados.prom_general",
                'habilitado.descripcion as habilitado',
            )
            ->whereIn('registro_estudiantil.id_estudiante',$estudiantesEgresados->pluck('id_estudiante'))
            ->join('estudiantes','estudiantes.id','registro_estudiantil.id_estudiante')
            ->join('habilitado','registro_estudiantil.id_habilitado','habilitado.id')
            ->join('periodo as periodo_ingreso','periodo_ingreso.id','registro_estudiantil.id_periodo')
            ->join('estudiantes_egresados','estudiantes_egresados.id_estudiante','estudiantes.id')
            ->join('periodo as periodo_titulacion','periodo_titulacion.id','estudiantes_egresados.id_periodo')
            ->where('registro_estudiantil.id_carrera',$request->carrera)
            ->where('habilitado.descripcion','NIVELACION')
            ->orderBy('periodo_ingreso.codigo','desc')
            ->get();
            
            $hasta = $configuracionCarrera->total_periodos + $configuracionCarrera->periodos_gracia;
            $datosTitulacion = [];
            $sumatoriaValores = 0;
            for ($i=$configuracionCarrera->total_periodos- 1; $i < $hasta; $i++) { 
                if(isset($filteredPeriodos[$i])){
                        $contador = RegistroEstudiantil::select('estudiantes.estudiante')->where('registro_estudiantil.id_periodo',$filteredPeriodos[$i]->id)
                        ->whereIn('registro_estudiantil.id_estudiante',$estudiantesEgresados->pluck('id_estudiante'))
                        ->where('registro_estudiantil.id_carrera',$request->carrera)
                        ->join('estudiantes','estudiantes.id','registro_estudiantil.id_estudiante')
                        ->join('habilitado','registro_estudiantil.id_habilitado','habilitado.id')
                        ->where('habilitado.descripcion','NIVELACION');
                        $sumatoriaValores +=$contador->count();
                        $datosTitulacion [] =[
                            "value"=>$contador->count(),
                            "label"=>$filteredPeriodos[$i]->codigo
                        ];
                }
            }
            $datosTitulacion [] =[
                "value"=>count($estudiantesEgresados) - $sumatoriaValores,
                "label"=>"Otros casos"
            ];
            Log::info($datosTitulacion);
            return response()->json([
                'ok'=>true,
                "data_table"=>$data,
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

    
//---------------------Descargar Reporte Excel -----------------------
public function descargarExcelTasaTitulacion(Request $request){
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
        
        $estudiantesEgresados = EstudiantesEgresados::where('id_carrera',$request->carrera)->where('id_periodo_relacionado',$filteredPeriodos[0]->id)->distinct('id_estudiante')->get();

        $data = RegistroEstudiantil::select(
            "carrera.carrera",
            "estudiantes.estudiante",
            "periodo_ingreso.codigo as periodo_ingreso",
            "periodo_titulacion.codigo as periodo_titulacion",
            "estudiantes_egresados.prom_materia",
            "estudiantes_egresados.prom_titulacion",
            "estudiantes_egresados.prom_general",
        )
        ->whereIn('registro_estudiantil.id_estudiante',$estudiantesEgresados->pluck('id_estudiante'))
        ->join('carrera','carrera.id','registro_estudiantil.id_carrera')
        ->join('estudiantes','estudiantes.id','registro_estudiantil.id_estudiante')
        ->join('habilitado','registro_estudiantil.id_habilitado','habilitado.id')
        ->join('periodo as periodo_ingreso','periodo_ingreso.id','registro_estudiantil.id_periodo')
        ->join('estudiantes_egresados','estudiantes_egresados.id_estudiante','estudiantes.id')
        ->join('periodo as periodo_titulacion','periodo_titulacion.id','estudiantes_egresados.id_periodo')
        ->where('registro_estudiantil.id_carrera',$request->carrera)
        ->where('habilitado.descripcion','NIVELACION')
        ->orderBy('periodo_ingreso.codigo','desc')
        ->get();
        
        return Excel::download(new TasaTitulacionExport($data), 'datos.xlsx');
        
    }catch (Exception $e) {
        Log::error($e);
        return response([
            "ok"=>false,
            'message'=>'Error al descargar el formato excel',
            "error"=>$e->getMessage()
        ],400);                 
    }
}
    
}