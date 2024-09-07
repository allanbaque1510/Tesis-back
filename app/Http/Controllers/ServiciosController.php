<?php

namespace App\Http\Controllers;

use App\Models\ArchivosSubidos;
use App\Models\ConfigIndicadoresCarrera;
use App\Models\EstudiantesReprobados;
use App\Models\Materia;
use App\Models\PuntuacionGrupoEstudiante;
use App\Models\TipoGrafico;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\Periodo;
use App\Models\Carrera;
use App\Models\CarreraDocenteMateria;
use App\Models\EstudianteGrupoEstudiante;
use App\Models\Estudiantes;
use App\Models\EstudiantesEgresados;
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
                'message'=>'Error al obtener las carreras',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }
    public function obtenerDocentesPeriodoCarrera(Request $request){
        try {
            $docentes = CarreraDocenteMateria::select(
                "docentes.id_docente as value",
                "docentes.nombre as label",
            )
            ->join('docentes','docentes.id_docente','carrera_docente_materias.id_docente')
            ->where('id_carrera',$request->carrera)
            ->where('id_periodo',$request->periodos)
            ->where('id_materia',$request->materia)
            ->distinct('docentes.id_docente')
            ->get();
            return ["ok"=>true,"data"=>$docentes];

        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al obtener los docentes por periodo y carrera',
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
                'message'=>'Error al obtener la configuracion',
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
                'message'=>'Error al obtener el combo periodo ordinario',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }
    public function obtenerComboPeriodoReprobados(Request $request){
        try {
                $periodos = EstudiantesReprobados::select(
                    "periodo.id as value",
                    "periodo.codigo as label"
                )
                ->where('estudiantes_reprobados.id_carrera',$request->id_carrera)
                ->join('periodo','estudiantes_reprobados.id_periodo','periodo.id')
                ->distinct('periodo.id')
                ->orderBy('periodo.codigo','desc')
                ->get();

                return response()->json([
                    'ok'=>true,
                    'data' => $periodos,
                ], 200);

            }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al obtener el combo periodo reprobados',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }
    public function obtenerComboPeriodoTitulacion(Request $request){
        try {
            if(!isset($request->id_carrera)){
                $periodos = Periodo::select(
                    "id as value",
                    "codigo as label"
                )->orderBy('codigo','desc')->get();
            }else{
                $periodos = EstudiantesEgresados::select(
                    "periodo.id as value",
                    "periodo.codigo as label"
                )
                ->where('estudiantes_egresados.id_carrera',$request->id_carrera)
                ->join('periodo','estudiantes_egresados.id_periodo_relacionado','periodo.id')
                ->distinct('periodo.id')
                ->orderBy('periodo.codigo','desc')
                ->get();
            }
            $configuracion = ConfigIndicadoresCarrera::where('id_carrera',$request->id_carrera)->first();

          
            return response()->json([
                'ok'=>true,
                'data' => ["periodo"=>$periodos,'configuracion'=>$configuracion]
            ], 200);
        
        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al obtener el combo de datos del periodo de titulacion',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }
    
    public function obtenerPeriodoNominaCarreraDocenteMateriaConEstudiantes($id_carrera){
        try {
            $periodos = EstudianteGrupoEstudiante::select(
                "periodo.id as value",
                "periodo.codigo as label"
            )
            ->where('estudiante_grupo_estudiante.id_carrera',$id_carrera)
            ->join('periodo','periodo.id','estudiante_grupo_estudiante.id_periodo')
            ->distinct('periodo.id')
            ->get();
            return ["ok"=>true,"data"=>$periodos];
        }catch (Exception $e) {
            return response([
                "ok"=>false,
                'message'=>'Error al obtener los periodos',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }


    public function obtenerPeriodoNominaCarreraDocenteMateria($id_carrera){
        try {
            $periodos = CarreraDocenteMateria::select(
                "periodo.id as value",
                "periodo.codigo as label"
            )
            ->where('carrera_docente_materias.id_carrera',$id_carrera)
            ->join('periodo','periodo.id','carrera_docente_materias.id_periodo')
            ->distinct('periodo.id')
            ->get();
            return ["ok"=>true,"data"=>$periodos];
        }catch (Exception $e) {
            return response([
                "ok"=>false,
                'message'=>'Error al obtener los periodos',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }
    
    public function obtenerMateriasConLogros($id_carrera,$id_periodo){
        try {
            $carreras = CarreraDocenteMateria::select(
                'materias.id_materia as value',
                'materias.descripcion as label',
            )
            ->where('carrera_docente_materias.id_carrera',$id_carrera)
            ->where('carrera_docente_materias.id_periodo',$id_periodo)
            ->join('logros_mat_carr_per_doc',function($join){
                $join->on('logros_mat_carr_per_doc.id_carrera_docente_materia','carrera_docente_materias.id_carrera_docente_materia')
                ->where("logros_mat_carr_per_doc.estado",1);
            })
            ->join('materias','materias.id_materia','carrera_docente_materias.id_materia')
            ->distinct('materias.id_materia')
            ->get();
            return response()->json([
                'ok'=>true,
                'data'=>$carreras
            ]);
        }catch (Exception $e) {
            Log::error($e->getMessage());
            return response([
                "ok"=>false,
                'message'=>'Error al obtener las materias',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }
    public function obtenerGruposDocenteMateria(Request $request){
        try {
            $data= CarreraDocenteMateria::select(
                "grupo_estudiantes.id_grupo as value",
                "grupo_estudiantes.descripcion as label",
            )
            ->join('grupo_estudiantes','grupo_estudiantes.id_grupo','carrera_docente_materias.id_grupo')
            ->where('carrera_docente_materias.id_carrera',$request->carrera)
            ->where('carrera_docente_materias.id_periodo',$request->periodos)
            ->where('carrera_docente_materias.id_docente',$request->docente)
            ->where('carrera_docente_materias.id_materia',$request->materia)
            ->get();

            return response()->json([
                'ok'=>true,
                'data'=>$data
            ]);
        }catch (Exception $e) {
            Log::error($e->getMessage());
            return response([
                "ok"=>false,
                'message'=>'Error al obtener los grupos',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }
    public function obtenerLogrosAprendizajeDocente(Request $request){
        try {
            $configuracion = ConfigIndicadoresCarrera::where('id_carrera',$request->carrera)->first();
            
            $datos = CarreraDocenteMateria::select(
                "logros_aprendizaje.codigo as label",
                "logros_aprendizaje.id_logros as value",
                "logros_aprendizaje.descripcion as logro",
            )
            ->join('logros_mat_carr_per_doc',function($join){
                $join->on('logros_mat_carr_per_doc.id_carrera_docente_materia','carrera_docente_materias.id_carrera_docente_materia')
                ->where("logros_mat_carr_per_doc.estado",1);
            })
            ->join('logros_aprendizaje','logros_mat_carr_per_doc.id_logros','logros_aprendizaje.id_logros')
            ->where('carrera_docente_materias.id_carrera',$request->carrera)
            ->where('carrera_docente_materias.id_periodo',$request->periodos)
            ->where('carrera_docente_materias.id_docente',$request->docente)
            ->where('carrera_docente_materias.id_materia',$request->materia)
            ->where('carrera_docente_materias.id_grupo',$request->grupo)
            ->get();

            return response()->json([
                "ok"=>true,
                "data"=>$datos,
                "configuracion"=>$configuracion,
            ]);
            
        }catch (Exception $e) {
            Log::error($e->getMessage());
            return response([
                "ok"=>false,
                'message'=>'Error al obtener los logros',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }
    public function obtenerMaterias($id_carrera,$id_periodo){
        try {
            $carreras = CarreraDocenteMateria::select(
                'materias.id_materia as value',
                'materias.descripcion as label',
            )
            ->where('carrera_docente_materias.id_carrera',$id_carrera)
            ->where('carrera_docente_materias.id_periodo',$id_periodo)
            ->join('materias','materias.id_materia','carrera_docente_materias.id_materia')
            ->distinct('materias.id_materia')
            ->get();
            return response()->json([
                'ok'=>true,
                'data'=>$carreras
            ]);
        }catch (Exception $e) {
            return response([
                "ok"=>false,
                'message'=>'Error al obtener las materias',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }
    public function obtenerComboPeriodoLogrosAprendizaje(Request $request){
        try {
            $periodos = PuntuacionGrupoEstudiante::select(
                "periodo.id as value",
                "periodo.codigo as label"
            )
            ->where('carrera_docente_materias.id_carrera',$request->id_carrera)
            ->join('logros_mat_carr_per_doc','puntuacion_logro_grupo_estudiante.id_logros_mat_carr','logros_mat_carr_per_doc.id_logros_mat_carr_per_doc')
            ->join('carrera_docente_materias','carrera_docente_materias.id_carrera_docente_materia','logros_mat_carr_per_doc.id_carrera_docente_materia')
            ->join('periodo','carrera_docente_materias.id_periodo','periodo.id')
            ->distinct('periodo.id')
            ->orderBy('periodo.codigo','desc')
            ->get();

            return response()->json([
                'ok'=>true,
                'data' => $periodos,
            ], 200);
        }catch (Exception $e) {
            return response([
                "ok"=>false,
                'message'=>'Error al obtener los periodos',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }

    public function obtenerComboMateriasLogrosAprendizaje(Request $request){
        try {

            $materias = PuntuacionGrupoEstudiante::select(
                "materias.id_materia as value",
                "materias.descripcion as label"
            )
            ->where('carrera_docente_materias.id_carrera',$request->carrera)
            ->where('carrera_docente_materias.id_periodo',$request->periodo)
            ->join('logros_mat_carr_per_doc','puntuacion_logro_grupo_estudiante.id_logros_mat_carr','logros_mat_carr_per_doc.id_logros_mat_carr_per_doc')
            ->join('carrera_docente_materias','carrera_docente_materias.id_carrera_docente_materia','logros_mat_carr_per_doc.id_carrera_docente_materia')
            ->join('materias','carrera_docente_materias.id_materia','materias.id_materia')
            ->distinct('carrera_docente_materias.id_materia')
            ->orderBy('materias.descripcion','desc')
            ->get();

            return response()->json([
                'ok'=>true,
                'data' => $materias,
            ], 200);
        }catch (Exception $e) {
            return response([
                "ok"=>false,
                'message'=>'Error al obtener los periodos',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }


    public function historialReporteNominaGrupoEstudiantes(Request $request){
        try {
            $datos = ArchivosSubidos::select(
                "periodo.codigo as periodo",
                "carrera.carrera",
                "archivos_subidos.created_at as fecha"
            )
            ->join('periodo','periodo.id','archivos_subidos.id_periodo')
            ->join('carrera','carrera.id','archivos_subidos.id_carrera')
            ->where('archivos_subidos.id_indicador',5)
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

    public function eliminarDatosArchivo(Request $request){
        try {
            ArchivosSubidos::where('id',$request->id)->delete();
            
        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al borrar el archivo del historial',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }

    public function historialReporteReprobados(Request $request){
        try {
            $datos = ArchivosSubidos::select(
                "archivos_subidos.id",
                "periodo.codigo as periodo",
                "carrera.carrera",
                "archivos_subidos.created_at as fecha"
            )
            ->join('periodo','periodo.id','archivos_subidos.id_periodo')
            ->join('carrera','carrera.id','archivos_subidos.id_carrera')
            ->where('archivos_subidos.id_indicador',6)
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

    public function saveConfiguration(Request $request){
        try {
            if(isset($request->id_configuracion)){
                $data = ConfigIndicadoresCarrera::where('id_configuracion',$request->id_configuracion)
                ->update([
                    'periodos_desercion'=>$request->cantidad_periodos,
                    'total_periodos'=>$request->cantidad_total_periodos,
                    'periodos_gracia'=>$request->cantidad_periodos_gracia,
                    "puntuacion"=>$request->puntuacion,
                ]);               
            }else{
                $data = ConfigIndicadoresCarrera::create([
                     'id_carrera'=>$request->carrera,
                     'periodos_desercion'=>$request->cantidad_periodos,
                     'total_periodos'=>$request->cantidad_total_periodos,
                     'periodos_gracia'=>$request->cantidad_periodos_gracia,
                     "puntuacion"=>$request->puntuacion,
                 ]);
            }

            return ['ok'=>true,'data'=>$data];

        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al guardar la configuracion',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }
}