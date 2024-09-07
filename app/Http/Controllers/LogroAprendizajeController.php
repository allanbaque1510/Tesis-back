<?php

namespace App\Http\Controllers;

use App\Models\ArchivosSubidos;
use App\Models\CarreraDocenteMateria;
use App\Models\ConfigIndicadoresCarrera;
use App\Models\LogrosAprendizaje;
use App\Models\LogrosMateriaPeriodoDocente;
use App\Models\Materia;
use App\Models\PuntuacionGrupoEstudiante;
use App\Models\TipoGrafico;
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

class LogroAprendizajeController extends Controller
{
    public function index(){
        try {
            $data = LogrosAprendizaje::get();
            return ['ok'=>true,'data'=>$data];
        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al obtener logros de aprendizaje',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }

    
    public function update(Request $request){
        try {
            LogrosAprendizaje::where('id_logros',$request['id_logros'])->update(['descripcion'=>$request['descripcion']]); 

            return ['ok'=>true];
        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al guardar datos',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }


    public function store(Request $request){
        try {
            DB::beginTransaction();
            
            $logro = new LogrosAprendizaje();
            
            $logro->descripcion = $request->input('descripcion');
            $logro->save();
            $logro->codigo = 'LOGRO_' . $logro->id_logros;
            $logro->save();

            $carreraDocenteMateria = CarreraDocenteMateria::select('id_carrera_docente_materia')
            ->where('id_periodo',$request->periodos)
            ->where('id_carrera',$request->carrera)
            ->whereIn('id_materia',$request->materias)
            ->get()->pluck('id_carrera_docente_materia');
            
            $tablaInsert = [];
            foreach ($carreraDocenteMateria as $value) {
                $tablaInsert[] = [
                    'id_carrera_docente_materia'=>$value,
                    'id_logros' => $logro->id_logros,
                    'created_at'=>now(),
                ];
            }
            LogrosMateriaPeriodoDocente::insert($tablaInsert);
            DB::commit();
            return ['ok'=>true, 'data'=>$logro];
        }catch (Exception $e) {
            Log::error($e);
            DB::rollBack();
            return response([
                "ok"=>false,
                'message'=>'Error al guardar datos',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }

    public function obtenerLogrosPeriodo(Request $request){
        try {
            $datos = CarreraDocenteMateria::select(
                'logros_aprendizaje.codigo as codigo_logro',
                'logros_aprendizaje.descripcion as logro',
                'carrera_docente_materias.id_materia',
                'materias.descripcion as materia',
            )
            ->join('logros_mat_carr_per_doc',function($join){
                $join->on('logros_mat_carr_per_doc.id_carrera_docente_materia','carrera_docente_materias.id_carrera_docente_materia')
                ->where("logros_mat_carr_per_doc.estado",1);
            })
            ->join('logros_aprendizaje','logros_mat_carr_per_doc.id_logros','logros_aprendizaje.id_logros')
            ->join('materias','carrera_docente_materias.id_materia','materias.id_materia')
            ->where('carrera_docente_materias.id_carrera',$request->carrera)
            ->where('carrera_docente_materias.id_periodo',$request->periodos)
            ->distinct('carrera_docente_materias.id_materia')
            ->get();


            $dataEnviar = $datos->groupBy('id_materia')->map(function($group,$key){
                return [
                    "id_materia"=>$key,
                    "materia"=>$group[0]->materia,
                    "logros"=>$group,
                ];
            })->values();

            return ['ok'=>true, 'data'=>$dataEnviar];

        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al obtener los logros por periodo',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }
    public function getLogrosPorMateria(Request $request){
        try {
            $data = CarreraDocenteMateria::select(
                "docentes.nombre as docente",
                "logros_aprendizaje.codigo as logro",
                "grupo_estudiantes.descripcion as grupo",
                DB::raw("ROUND((puntuacion_logro_grupo_estudiante.puntuacion / puntuacion_logros.puntuacion) * 100, 2) AS porcentaje"),
                "estudiante_grupo_estudiante.id_estudiante",
                "puntuacion_logro_grupo_estudiante.puntuacion",
                "puntuacion_logros.puntuacion as puntuacion_total"
            )
            ->where('carrera_docente_materias.id_materia',$request->materia)
            ->where('carrera_docente_materias.id_periodo',$request->periodo)
            ->where('carrera_docente_materias.id_carrera',$request->carrera)
            ->join('docentes','carrera_docente_materias.id_docente','docentes.id_docente' )
            ->join('logros_mat_carr_per_doc','logros_mat_carr_per_doc.id_carrera_docente_materia','carrera_docente_materias.id_carrera_docente_materia')
            ->join("logros_aprendizaje","logros_aprendizaje.id_logros",'logros_mat_carr_per_doc.id_logros')
            ->join("grupo_estudiantes","grupo_estudiantes.id_grupo","carrera_docente_materias.id_grupo")
            ->join("puntuacion_logro_grupo_estudiante","puntuacion_logro_grupo_estudiante.id_logros_mat_carr","logros_mat_carr_per_doc.id_logros_mat_carr_per_doc")
            ->join("estudiante_grupo_estudiante","estudiante_grupo_estudiante.id_estudiante_grupo","puntuacion_logro_grupo_estudiante.id_estudiante_grupo")
            ->join("puntuacion_logros","puntuacion_logros.id_logros_mat_carr_per_doc","puntuacion_logro_grupo_estudiante.id_logros_mat_carr")
            ->get();
            $agrupaciones = $data->groupBy("grupo")->map(function($map,$grupo){
                $datoMap=  $map->groupBy("logro")->map(function($mapLogro,$indice) use($grupo){
                    return[
                        "grupo"=>$grupo,
                        "logro"=>$indice,
                        "cantidad_estudiantes"=>count($mapLogro),
                        "porcentaje"=>round($mapLogro->sum("porcentaje") / count($mapLogro),2),
                    ];
                });
                $cantidadEstudiantes = count($map->groupBy('id_estudiante'));
                return[
                    "docente"=>$map[0]->docente,
                    "cantidad_estudiantes"=>$cantidadEstudiantes,
                    "grupo"=>$grupo,
                    "datos"=>$datoMap->values(),
                ];
            })->values();
            return response()->json([
                "ok"=>true,
                "data"=>$agrupaciones
            ],200);
        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al obtener los logros por materia',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }

    public function historialReporteNominaCarreraDocenteMateria(Request $request){
        try {
            $datos = ArchivosSubidos::select(
                "periodo.codigo as periodo",
                "carrera.carrera",
                "archivos_subidos.created_at as fecha"
            )
            ->join('periodo','periodo.id','archivos_subidos.id_periodo')
            ->join('carrera','carrera.id','archivos_subidos.id_carrera')
            ->where('archivos_subidos.id_indicador',3)
            ->where('archivos_subidos.estado',1)
            ->get();
            return ["ok"=>true, "data"=>$datos];
        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al obtener el historial',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }
    public function clonarLogrosPorPeriodo(Request $request){
        try {
            $datosReferencia = CarreraDocenteMateria::select(
                "carrera_docente_materias.id_materia",
                'logros_mat_carr_per_doc.id_logros'
            )
            ->join('logros_mat_carr_per_doc',function($join){
                $join->on('logros_mat_carr_per_doc.id_carrera_docente_materia','carrera_docente_materias.id_carrera_docente_materia')
                ->where("logros_mat_carr_per_doc.estado",1);
            })
            ->where('carrera_docente_materias.id_carrera',$request->carrera)
            ->where('carrera_docente_materias.id_periodo',$request->periodo_referencio)
            ->whereIn('carrera_docente_materias.id_materia',$request->materias)
            ->distinct('carrera_docente_materias.id_materia')
            ->get();

            $periodoAsignado = CarreraDocenteMateria::select(
                'id_materia',
                'id_carrera_docente_materia',
            )
            ->where('carrera_docente_materias.id_carrera',$request->carrera)
            ->where('carrera_docente_materias.id_periodo',$request->periodo)
            ->whereIn('carrera_docente_materias.id_materia',$request->materias)
            ->get();

            LogrosMateriaPeriodoDocente::whereIn('id_carrera_docente_materia',$periodoAsignado->pluck('id_carrera_docente_materia'))->update(['estado'=>0]);

            $dataFinal = [];
            foreach ($periodoAsignado as  $periodo) {
                foreach ($datosReferencia as $key => $referencia) {
                    if($periodo->id_materia === $referencia->id_materia){
                        $dataFinal[]=[
                            "id_carrera_docente_materia"=>$periodo->id_carrera_docente_materia,
                            "id_logros"=>$referencia->id_logros,
                            "created_at"=>now()
                        ];
                    }
                }
            }

            LogrosMateriaPeriodoDocente::insert($dataFinal);
            return ["ok"=>true];
        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al clonar los logros',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }
    public function asignarLogrosPorMateria(Request $request){
        try {
            foreach ($request->data as $key => $value) {
                
                $id_carrera_docente_materia = CarreraDocenteMateria::
                select('carrera_docente_materias.id_carrera_docente_materia')
                ->where('id_carrera',$request->carrera)
                ->where('id_periodo',$request->periodo)
                ->where('id_materia',$key)
                ->get()
                ->pluck('id_carrera_docente_materia');
          
                LogrosMateriaPeriodoDocente::whereIn('id_carrera_docente_materia',$id_carrera_docente_materia)->update(['estado'=>0]);
                
                $datosAGuardar = [];
                foreach ($id_carrera_docente_materia as $id_materia_docente) {
                    foreach ($value as $logros){
                        $datosAGuardar [] = [
                            "id_logros"=>$logros,
                            "id_carrera_docente_materia"=>$id_materia_docente,
                            "created_at"=>now()
                        ];
                    }
                }
                LogrosMateriaPeriodoDocente::insert($datosAGuardar);
            }
            return ['ok'=>true];
        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al asignar los logros',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }

    public function obtenerMateriasLogrosPeriodo(Request $request){
        try {
            $todasLasMaterias = CarreraDocenteMateria::
            select(
                'carrera_docente_materias.id_carrera_docente_materia',
                'carrera_docente_materias.id_materia',
                'logros_mat_carr_per_doc.id_logros',
            )
            ->join('logros_mat_carr_per_doc',function($join){
                $join->on('logros_mat_carr_per_doc.id_carrera_docente_materia','carrera_docente_materias.id_carrera_docente_materia')
                ->where("logros_mat_carr_per_doc.estado",1);
            }) 
            ->where('carrera_docente_materias.id_carrera',$request->carrera)
            ->where('carrera_docente_materias.id_periodo',$request->periodos)
            ->get();

             $dataFinal = $todasLasMaterias->groupBy('id_materia')->map(function ($materia, $key){
               
                return $materia->groupBy('id_logros')->keys();
            });
            
            return ['ok'=>true,'data'=>$dataFinal];
            
        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al obtener los logros por materia',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }

    public function asignarLogrosAprendizajeMasivo(Request $request){
        try {
            $id_carrera_docente_materia = CarreraDocenteMateria::
            select('carrera_docente_materias.id_carrera_docente_materia')
            ->where('id_carrera',$request->carrera)
            ->where('id_periodo',$request->periodos)
            ->whereIn('id_materia',$request->materias)
            ->get()->pluck('id_carrera_docente_materia');
            
            LogrosMateriaPeriodoDocente::whereIn('id_carrera_docente_materia',$id_carrera_docente_materia)->update(['estado',0]);

            $datosAGuardar = [];
            foreach ($id_carrera_docente_materia as $id_materia_docente) {
                foreach ($request->logros as $logros){
                    $datosAGuardar [] = [
                        "id_logros"=>$logros,
                        "id_carrera_docente_materia"=>$id_materia_docente,
                        "created_at"=>now()
                    ];
                }
            }
            LogrosMateriaPeriodoDocente::insert($datosAGuardar);

            return["ok"=>true];

            
        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al guardar masivamente',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }

    
}