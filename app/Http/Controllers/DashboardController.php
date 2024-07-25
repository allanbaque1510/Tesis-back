<?php

namespace App\Http\Controllers;

use App\Models\ArchivosSubidos;
use App\Models\ConfigIndicadoresCarrera;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\Periodo;
use App\Models\Carrera;
use App\Models\Estudiantes;
use App\Models\EstudiantesEgresados;
use App\Models\Habilitado;
use App\Models\RegistroEstudiantil;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $periodoRequest;
    protected $carreraRequest;

    public function __construct()
    {
    }

    public function index(Request $request){
        try {
            $this->carreraRequest = $request->carrera;
            $this->periodoRequest = $request->periodo;

            $TD = $this->obtenerGraficoTD();
            $TT = $this->obtenerGraficoTT();
            // if(!$TD['ok'])throw new Exception("Error al obtener la tasa de desercion");
            // if(!$TT['ok'])throw new Exception("Error al obtener la tasa de titulacion");

            return response()->json([
                'ok'=>true,
                'data' => [
                    "desercion"=>$TD['data']??null,
                    "titulacion"=>$TT['data']??null,
                    ]
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
    public function obtenerGraficoTD(){
        try {
            $configuracionCarrera = ConfigIndicadoresCarrera::where('id_carrera',$this->carreraRequest)->first();
            $row_periodo = $this->obtenerRowPeriodo()->row_num;
            $subQuery = Periodo::select(
                DB::raw('ROW_NUMBER() OVER (ORDER BY periodo.anio_inicio DESC, periodo.ciclo DESC) as row_num'),
                "periodo.id",
                "periodo.codigo",
                "periodo.anio_inicio",
                "periodo.anio_fin",
                "periodo.ciclo"
            )
            ->join('archivos_subidos','archivos_subidos.id_periodo','periodo.id')
            ->where('archivos_subidos.id_indicador',1);
            
            $filteredPeriodos = DB::table(DB::raw("({$subQuery->toSql()}) as sub"))
            ->mergeBindings($subQuery->getQuery()) // Para combinar las bindings de la subconsulta
            ->select('sub.*')
            ->where('sub.row_num', '>=', $row_periodo)
            ->orderBy('sub.anio_inicio', 'desc')
            ->orderBy('sub.ciclo', 'desc')
            ->limit($configuracionCarrera->periodos_desercion)
            ->get();
            
            $ultimoPeriodo = collect($filteredPeriodos)->last();
            Log::info(collect($ultimoPeriodo));
            $estudiantesIngresados = RegistroEstudiantil::select(
                "registro_estudiantil.id_estudiante",
                "estudiantes.estudiante",
                "periodo.codigo as periodo"
            )
            ->join("periodo",'periodo.id','registro_estudiantil.id_periodo')
            ->join('estudiantes','estudiantes.id','registro_estudiantil.id_estudiante')
            ->join('habilitado','habilitado.id','registro_estudiantil.id_habilitado')
            ->where('id_carrera',$this->carreraRequest)
            ->where('habilitado.descripcion','NIVELACION')
            ->where('registro_estudiantil.id_periodo',$ultimoPeriodo->id)
            ->get()->pluck('id_estudiante');
            
            $data2 = RegistroEstudiantil::
            where('id_periodo',$filteredPeriodos[0]->id)
            ->where('id_carrera',$this->carreraRequest)
            ->whereIn('id_estudiante',$estudiantesIngresados)
            ->count();

            return ["ok"=>true,'data'=>[
                "nivelacion"=>count($estudiantesIngresados),
                "actual"=>$data2
            ]];            
        }catch (Exception $e) {
            Log::error($e);
            return ["ok"=>false];
                           
        }
    }    

     

    public function obtenerGraficoTT(){
        try {
            $configuracionCarrera = ConfigIndicadoresCarrera::where('id_carrera',$this->carreraRequest)->first();
            $row_periodo = $this->obtenerRowPeriodo()->row_num;
            $subQuery = Periodo::select(
                DB::raw('ROW_NUMBER() OVER (ORDER BY periodo.anio_inicio DESC, periodo.ciclo DESC) as row_num'),
                "periodo.id",
                "periodo.codigo",
                "periodo.anio_inicio",
                "periodo.anio_fin",
                "periodo.ciclo"
            )
            ->join('archivos_subidos','archivos_subidos.id_periodo','periodo.id')
            ->where('archivos_subidos.id_indicador',1);
            $filteredPeriodos = DB::table(DB::raw("({$subQuery->toSql()}) as sub"))
                ->mergeBindings($subQuery->getQuery()) // Para combinar las bindings de la subconsulta
                ->select('sub.*')
                ->where('sub.row_num', '>=', $row_periodo)
                ->orderBy('sub.anio_inicio', 'desc')
                ->orderBy('sub.ciclo', 'desc')
                ->limit($configuracionCarrera->total_periodos + $configuracionCarrera->periodos_gracia)
                ->get();
                
            $estudiantesEgresados = EstudiantesEgresados::where('id_periodo_relacionado',$filteredPeriodos[0]->id)->get();

            if(!count($estudiantesEgresados) > 0 ){
                return false;
            }

            $hasta = $configuracionCarrera->total_periodos + $configuracionCarrera->periodos_gracia;
            $periodosTitulacion = [] ;
            for ($i=$configuracionCarrera->total_periodos- 1; $i < $hasta; $i++) { 
                if(isset($filteredPeriodos[$i])){
                        $periodosTitulacion [] =$filteredPeriodos[$i]->id;
                }
            }
            $NumeroEstudiantesTitulados = RegistroEstudiantil::whereIn('registro_estudiantil.id_periodo',$periodosTitulacion)
            ->whereIn('registro_estudiantil.id_estudiante',$estudiantesEgresados->pluck('id_estudiante'))
            ->distinct('registro_estudiantil.id_estudiante')
            ->count();
            
            $estudiantesEnNivelacion = RegistroEstudiantil::where('registro_estudiantil.id_periodo',$filteredPeriodos[$configuracionCarrera->total_periodos-1]->id)
            ->join('habilitado','registro_estudiantil.id_habilitado','habilitado.id')
            ->where('habilitado.descripcion','NIVELACION')
            ->count();

            return ["ok"=>true,'data'=>[
                "nivelacion"=>$estudiantesEnNivelacion,
                "titulados"=>$NumeroEstudiantesTitulados,
            ]];            
        }catch (Exception $e) {
            Log::error($e);
            return ["ok"=>false];
                           
        }
    }  


    public function obtenerRowPeriodo(){
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
            ->where('sub.id', $this->periodoRequest)
            ->first();
        return $periodoWithRowNum;
        }


}