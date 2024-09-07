<?php

namespace App\Http\Controllers;

use App\Models\ArchivosSubidos;
use App\Models\ConfigIndicadoresCarrera;
use App\Models\EstudiantesReprobados;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\Periodo;
use App\Models\Carrera;
use App\Models\CarreraDocenteMateria;
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
            $nivel=NULL;
            $TD = $this->obtenerGraficoTD();
            $TT = $this->obtenerGraficoTT();
            $TR =$this->obtenerGraficoTR();
            $niveles = $this->obtenerNivelesLA();
            $datos = [
                "desercion"=>$TD['data']??null,
                "titulacion"=>$TT['data']??null,
                "reprobados"=>$TR['data']??null,
                "niveles"=>$niveles,
            ];
            
            if(count($niveles) > 0){
                $nivel = $niveles[0]->value;
                $datos['nivel']=$nivel;
            }
            $LA =$this->obtenerGraficoLA($nivel);
            $datos['logros']=$LA['data']??null;
            
            return response()->json([
                'ok'=>true,
                'data' =>$datos, 
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

    public function obtenerDashboardLogros(Request $request){
        try {
            $this->carreraRequest = $request->carrera;
            $this->periodoRequest = $request->periodo;
            $nivel=$request->nivel;
            
            $niveles = $this->obtenerGraficoLA($nivel);
            return response()->json([
                'ok'=>true,
                'data' =>$niveles, 
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

    public function obtenerGraficoTR(){
        try {
            $carrera = $this->carreraRequest;
            $periodo = $this->periodoRequest;
            
            $query = EstudiantesReprobados::select(
                "materias.id_materia",
                "materias.descripcion as materia",
                DB::raw('COUNT(estudiantes_reprobados.id_estudiantes_reprobados) as cantidad'),
                DB::raw('SUM(CASE WHEN estudiantes_reprobados.reprobado_asistencia = 1 THEN 1 ELSE 0 END) as cantidadAsistencia'),
                DB::raw('SUM(CASE WHEN estudiantes_reprobados.reprobado_nota  = 1 THEN 1 ELSE 0 END) as cantidadNota'),
            )
            ->join('materias','materias.id_materia','estudiantes_reprobados.id_materia')
            ->where('id_carrera',$carrera)->where('id_periodo',$periodo)
            ->groupBy('materias.id_materia', 'materias.descripcion')
            ->orderBy('cantidad','desc')
            ->limit(10)
            ->get();

            return ["ok"=>true,'data'=>$query];  

        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error obtener el grafico TR',
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


    public function obtenerNivelesLA(){
        try {
            $carrera = $this->carreraRequest;
            $periodo = $this->periodoRequest;

            $data = CarreraDocenteMateria::select(
                "materias.nivel as value",
                DB::raw("CONCAT('NIVEL ',materias.nivel) as label"),
            )
            ->where('carrera_docente_materias.id_periodo',$periodo)
            ->where('carrera_docente_materias.id_carrera',$carrera)
            ->join('materias','materias.id_materia','carrera_docente_materias.id_materia')
            ->join("logros_mat_carr_per_doc","logros_mat_carr_per_doc.id_carrera_docente_materia",'carrera_docente_materias.id_carrera_docente_materia')
            ->join("puntuacion_logro_grupo_estudiante","puntuacion_logro_grupo_estudiante.id_logros_mat_carr","logros_mat_carr_per_doc.id_logros_mat_carr_per_doc")
            ->distinct("materia.nivel")
            ->get();
            return $data;
        }catch (Exception $e) {
            Log::error($e);
            return ["ok"=>false];
        }
        
    }
    public function obtenerGraficoLA($nivel){
        try {
            $carrera = $this->carreraRequest;
            $periodo = $this->periodoRequest;

            $query = CarreraDocenteMateria::select(
                DB::raw("CONCAT(grupo_estudiantes.descripcion,' - ',docentes.nombre) as docente"),
                "logros_aprendizaje.codigo as logro",
                'materias.descripcion as materia',
                "grupo_estudiantes.descripcion as grupo",
                DB::raw("ROUND((puntuacion_logro_grupo_estudiante.puntuacion / puntuacion_logros.puntuacion) * 100, 2) AS porcentaje"),
                "estudiante_grupo_estudiante.id_estudiante",
                "puntuacion_logro_grupo_estudiante.puntuacion",
                "puntuacion_logros.puntuacion as puntuacion_total"
            )
            // ->where('carrera_docente_materias.id_materia',$request->materia)
            ->where('carrera_docente_materias.id_periodo',$periodo)
            ->where('carrera_docente_materias.id_carrera',$carrera)
            ->join('materias','materias.id_materia','carrera_docente_materias.id_materia')
            ->join('docentes','carrera_docente_materias.id_docente','docentes.id_docente' )
            ->join("logros_mat_carr_per_doc","logros_mat_carr_per_doc.id_carrera_docente_materia",'carrera_docente_materias.id_carrera_docente_materia')
            ->join("logros_aprendizaje","logros_aprendizaje.id_logros",'logros_mat_carr_per_doc.id_logros')
            ->join("grupo_estudiantes","grupo_estudiantes.id_grupo","carrera_docente_materias.id_grupo")
            ->join("puntuacion_logro_grupo_estudiante","puntuacion_logro_grupo_estudiante.id_logros_mat_carr","logros_mat_carr_per_doc.id_logros_mat_carr_per_doc")
            ->join("estudiante_grupo_estudiante","estudiante_grupo_estudiante.id_estudiante_grupo","puntuacion_logro_grupo_estudiante.id_estudiante_grupo")
            ->join("puntuacion_logros","puntuacion_logros.id_logros_mat_carr_per_doc","puntuacion_logro_grupo_estudiante.id_logros_mat_carr");
            
            if(is_null($nivel)){
                $data = $query->get();
            }else{
                $data = $query->where("materias.nivel",$nivel)->get();
            }

            if(count($data) > 0){
                $agrupaciones = $data->groupBy("materia")->map(function($map,$materia){
                    $datoMap=  $map->groupBy("grupo")->map(function($mapGrupo,$grupo) {
                        return $mapGrupo->groupBy("logro")->map(function($mapLogro,$indice) {
                            return[
                                "docente"=>$mapLogro[0]->docente,
                                "logro"=>$indice,
                                "promedio"=>round($mapLogro->sum("porcentaje") / count($mapLogro),2),
                            ];
                        })->values();
                        
                    });
                    
                    return[
                        "materia"=>$materia,
                        "data"=>$datoMap->values()->flatten(1),
                    ];
                })->values();
                
            }else{
                $agrupaciones = $data ;
            }

            return [
                "ok"=>true,
                "data"=>$agrupaciones
            ];
        
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