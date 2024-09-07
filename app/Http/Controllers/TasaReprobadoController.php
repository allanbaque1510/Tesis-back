<?php

namespace App\Http\Controllers;

use App\Exports\TasaReprobadosExport;
use App\Models\CarreraDocenteMateria;
use App\Models\EstudianteGrupoEstudiante;
use App\Models\EstudiantesReprobados;
use App\Models\Materia;
use App\Models\RegistroEstudiantil;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TasaReprobadoController extends Controller
{

    public function obtenerReprobadosMateria(Request $request){
        try {
            $query = EstudiantesReprobados::select(
                "materias.id_materia",
                "materias.descripcion as materia",
                DB::raw('COUNT(estudiantes_reprobados.id_estudiantes_reprobados) as cantidad'),
                DB::raw('SUM(CASE WHEN estudiantes_reprobados.reprobado_asistencia = 1 THEN 1 ELSE 0 END) as cantidadAsistencia'),
                DB::raw('SUM(CASE WHEN estudiantes_reprobados.reprobado_nota  = 1 AND estudiantes_reprobados.reprobado_asistencia = 0 THEN 1 ELSE 0 END) as cantidadNota'),
            )
            ->join('materias','materias.id_materia','estudiantes_reprobados.id_materia')
            ->where('id_carrera',$request->carrera)->where('id_periodo',$request->periodo)
            ->groupBy('materias.id_materia', 'materias.descripcion')
            ->orderBy('cantidad','desc')
            ->get();
            
            $materias = EstudiantesReprobados::select(
                "materias.id_materia as value",
                "materias.descripcion as label",
            )
            ->join('materias','materias.id_materia','estudiantes_reprobados.id_materia')
            ->where('id_carrera',$request->carrera)->where('id_periodo',$request->periodo)
            ->groupBy('materias.id_materia', 'materias.descripcion')
            ->orderBy('materias.descripcion','asc')
            ->get();

            $mayorReprobado = $this->reprobadoEspecificoPorMateria($query[0],$request->periodo,$request->carrera);
                
            return ["ok"=>true,'data'=>$query,"mas_reprobado"=>$mayorReprobado, "materias"=>$materias];  

        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error obtener el grafico TR',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }

    
    public function obtenerReprobadosMateriaPorcentaje(Request $request){
        try {
            $query = EstudiantesReprobados::select(
                "materias.id_materia",
                "materias.descripcion as materia",
                DB::raw('COUNT(estudiantes_reprobados.id_estudiantes_reprobados) as cantidad'),
                DB::raw('SUM(CASE WHEN estudiantes_reprobados.reprobado_asistencia = 1 THEN 1 ELSE 0 END) as cantidadAsistencia'),
                DB::raw('SUM(CASE WHEN estudiantes_reprobados.reprobado_nota  = 1 AND estudiantes_reprobados.reprobado_asistencia = 0 THEN 1 ELSE 0 END) as cantidadNota'),
           
            )
            ->join('materias','materias.id_materia','estudiantes_reprobados.id_materia')
            ->where('id_carrera',$request->carrera)->where('id_periodo',$request->periodo)
            ->groupBy('materias.id_materia', 'materias.descripcion')
            ->orderBy('cantidad','desc')
            ->get();

            $estudiantesTotales = EstudianteGrupoEstudiante::select(
                "id_materia"
            )
            ->where('id_carrera',$request->carrera)->where('id_periodo',$request->periodo)
            ->get();
            
            if(count($estudiantesTotales) === 0){
                throw new Exception("No existen registros de nomina estudiantil de este periodo");
            }
            $contadorGeneral = $estudiantesTotales->groupBy("id_materia")->map(function($map){
                return count($map);
            });

            $dataEnviar = $query->map(function($map) use($contadorGeneral){
                $map->porcentaje = round(($map->cantidad /$contadorGeneral[$map->id_materia] ) * 100,2);
                $map->totalEstudiantes = $contadorGeneral[$map->id_materia];
                return $map;
            });

            $coleccionMaxima = $dataEnviar->firstWhere('porcentaje', $dataEnviar->max('porcentaje'));
            
            $materias = EstudiantesReprobados::select(
                "materias.id_materia as value",
                "materias.descripcion as label",
            )
            ->join('materias','materias.id_materia','estudiantes_reprobados.id_materia')
            ->where('id_carrera',$request->carrera)->where('id_periodo',$request->periodo)
            ->groupBy('materias.id_materia', 'materias.descripcion')
            ->orderBy('materias.descripcion','asc')
            ->get();

            $mayorReprobado = $this->reprobadoEspecificoPorMateriaPorcentaje($coleccionMaxima,$request->periodo,$request->carrera);
                
            return ["ok"=>true,'data'=>$dataEnviar,"mas_reprobado"=>$mayorReprobado, "materias"=>$materias];  

        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error obtener el grafico TR',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }
    public function obtenerReprobadoMateriaDetalle(Request $request){
        try {
            $materia = EstudiantesReprobados::select(
                "materias.id_materia",
                "materias.descripcion as materia",
                DB::raw('COUNT(estudiantes_reprobados.id_estudiantes_reprobados) as cantidad'),
                DB::raw('SUM(CASE WHEN estudiantes_reprobados.reprobado_asistencia = 1 THEN 1 ELSE 0 END) as cantidadAsistencia'),
                DB::raw('SUM(CASE WHEN estudiantes_reprobados.reprobado_nota  = 1 AND estudiantes_reprobados.reprobado_asistencia = 0 THEN 1 ELSE 0 END) as cantidadNota'),
           
            )
            ->join('materias','materias.id_materia','estudiantes_reprobados.id_materia')
            ->where('id_carrera',$request->carrera)
            ->where('id_periodo',$request->periodo)
            ->where('materias.id_materia',$request->materia)
            ->groupBy('materias.id_materia', 'materias.descripcion')
            ->orderBy('cantidad','desc')
            ->first();
            
            $reprobado = $this->reprobadoEspecificoPorMateria($materia,$request->periodo,$request->carrera);
            return ["ok"=>true,"data"=>$reprobado];  
        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error obtener el grafico TR',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }
    public function obtenerReprobadoMateriaDetallePorcentaje(Request $request){
        try {
            $materia = EstudiantesReprobados::select(
                "materias.id_materia",
                "materias.descripcion as materia",
                DB::raw('COUNT(estudiantes_reprobados.id_estudiantes_reprobados) as cantidad'),
                DB::raw('SUM(CASE WHEN estudiantes_reprobados.reprobado_asistencia = 1 THEN 1 ELSE 0 END) as cantidadAsistencia'),
                DB::raw('SUM(CASE WHEN estudiantes_reprobados.reprobado_nota  = 1 AND estudiantes_reprobados.reprobado_asistencia = 0 THEN 1 ELSE 0 END) as cantidadNota'),
           
            )
            ->join('materias','materias.id_materia','estudiantes_reprobados.id_materia')
            ->where('id_carrera',$request->carrera)
            ->where('id_periodo',$request->periodo)
            ->where('materias.id_materia',$request->materia)
            ->groupBy('materias.id_materia', 'materias.descripcion')
            ->orderBy('cantidad','desc')
            ->first();
            
            $estudiantesTotales = EstudianteGrupoEstudiante::select(
                "id_materia"
            )
            ->where('id_materia',$request->materia)
            ->where('id_carrera',$request->carrera)->where('id_periodo',$request->periodo)
            ->count();

            $materia->porcentaje = round(($materia->cantidad /$estudiantesTotales ) * 100,2);
            $materia->totalEstudiantes = $estudiantesTotales;
            

            $reprobado = $this->reprobadoEspecificoPorMateriaPorcentaje($materia,$request->periodo,$request->carrera);
            return ["ok"=>true,"data"=>$reprobado];  
        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error obtener el grafico TR',
                "error"=>$e->getMessage()
            ],400);                 
        } 
    }

    public function reprobadoEspecificoPorMateriaPorcentaje($dataMateria,$periodo,$carrera){
        try {
            $estudiantesTotales = EstudianteGrupoEstudiante::select(
                "id_grupo"
            )
            ->where('id_carrera',$carrera)
            ->where('id_periodo',$periodo)
            ->where('id_materia',$dataMateria->id_materia)
            ->get();
            $cantidadPorGrupo =$estudiantesTotales->groupBy("id_grupo")->map(function($map){
                return count($map);
            });

            $dataGrupos = CarreraDocenteMateria::select(
            "docentes.nombre",
            "carrera_docente_materias.id_grupo",
           )
           ->join('docentes','docentes.id_docente','carrera_docente_materias.id_docente')
           ->where('id_materia',$dataMateria->id_materia)
           ->where('id_carrera',$carrera)
           ->where('id_periodo',$periodo)
           ->get();
           $dataReprobados = EstudiantesReprobados::select(
                "estudiantes_reprobados.id_grupo",
                "grupo_estudiantes.descripcion as grupo",
                DB::raw('COUNT(estudiantes_reprobados.id_estudiantes_reprobados) as cantidad'),
                DB::raw('SUM(CASE WHEN estudiantes_reprobados.reprobado_asistencia = 1 THEN 1 ELSE 0 END) as cantidadAsistencia'),
                DB::raw('SUM(CASE WHEN estudiantes_reprobados.reprobado_nota  = 1 AND estudiantes_reprobados.reprobado_asistencia = 0 THEN 1 ELSE 0 END) as cantidadNota'),
           
            )
            ->join("grupo_estudiantes","grupo_estudiantes.id_grupo","estudiantes_reprobados.id_grupo")
            ->where('id_materia',$dataMateria->id_materia)
           ->where('id_carrera',$carrera)
           ->where('id_periodo',$periodo)
           ->groupBy('estudiantes_reprobados.id_grupo','grupo_estudiantes.descripcion')
           ->get();
           if(count($dataGrupos) > 0){
            foreach ($dataReprobados as &$value) {
                if($cantidadPorGrupo->has($value->id_grupo)){
                    $value->cantidadTotal =$cantidadPorGrupo[$value->id_grupo];
                    $dataDocenteXd = $dataGrupos->where('id_grupo',$value->id_grupo)->first();
                    if($dataDocenteXd){
                        $value->docente =$dataDocenteXd->nombre; 
                    }
                }
            }
           }

            return ["ok"=>true, "data"=>$dataReprobados, "materia"=>$dataMateria];
        }catch (Exception $e) {
            Log::error($e);
            return [
                "ok"=>false,
                'message'=>'Error obtener el grafico TR',
                "error"=>$e->getMessage()
            ];                 
        }
    }

    
    public function reprobadoEspecificoPorMateria($dataMateria,$periodo,$carrera){
        try {
            $dataGrupos = CarreraDocenteMateria::select(
            "docentes.nombre",
            "carrera_docente_materias.id_grupo",
           )
           ->join('docentes','docentes.id_docente','carrera_docente_materias.id_docente')
           ->where('id_materia',$dataMateria->id_materia)
           ->where('id_carrera',$carrera)
           ->where('id_periodo',$periodo)
           ->get();
           $dataReprobados = EstudiantesReprobados::select(
                "estudiantes_reprobados.id_grupo",
                "grupo_estudiantes.descripcion as grupo",
                DB::raw('COUNT(estudiantes_reprobados.id_estudiantes_reprobados) as cantidad'),
                DB::raw('SUM(CASE WHEN estudiantes_reprobados.reprobado_asistencia = 1 THEN 1 ELSE 0 END) as cantidadAsistencia'),
                DB::raw('SUM(CASE WHEN estudiantes_reprobados.reprobado_nota  = 1 AND estudiantes_reprobados.reprobado_asistencia = 0 THEN 1 ELSE 0 END) as cantidadNota'),
           
            )
            ->join("grupo_estudiantes","grupo_estudiantes.id_grupo","estudiantes_reprobados.id_grupo")
            ->where('id_materia',$dataMateria->id_materia)
           ->where('id_carrera',$carrera)
           ->where('id_periodo',$periodo)
           ->groupBy('estudiantes_reprobados.id_grupo','grupo_estudiantes.descripcion')
           ->get();
           if(count($dataGrupos) > 0){
            foreach ($dataReprobados as &$value) {
                $dataDocenteXd = $dataGrupos->where('id_grupo',$value->id_grupo)->first();
                if($dataDocenteXd){
                    $value->docente =$dataDocenteXd->nombre; 
                }
            }
           }

            return ["ok"=>true, "data"=>$dataReprobados, "materia"=>$dataMateria];
        }catch (Exception $e) {
            Log::error($e);
            return [
                "ok"=>false,
                'message'=>'Error obtener el grafico TR',
                "error"=>$e->getMessage()
            ];                 
        }
    }

    public function descargarExcelTasaReprobados(Request $request){
        try {
            $query = EstudiantesReprobados::select(
                "carrera.carrera",
                "periodo.codigo",
                "materias.id_materia",
                "materias.descripcion as materia",
                DB::raw("NULL as totalEstudiantes"),
                DB::raw('COUNT(estudiantes_reprobados.id_estudiantes_reprobados) as cantidad'),
                DB::raw('SUM(CASE WHEN estudiantes_reprobados.reprobado_asistencia = 1 THEN 1 ELSE 0 END) as cantidadAsistencia'),
                DB::raw('SUM(CASE WHEN estudiantes_reprobados.reprobado_nota  = 1 AND estudiantes_reprobados.reprobado_asistencia = 0 THEN 1 ELSE 0 END) as cantidadNota'),
           
            )
            ->join('materias','materias.id_materia','estudiantes_reprobados.id_materia')
            ->where('estudiantes_reprobados.id_carrera',$request->carrera)->where('estudiantes_reprobados.id_periodo',$request->periodo)
            ->join('periodo','periodo.id','estudiantes_reprobados.id_periodo')
            ->join('carrera','carrera.id','estudiantes_reprobados.id_carrera')
            ->groupBy('materias.id_materia', 'materias.descripcion','carrera.carrera',"periodo.codigo")
            ->orderBy('cantidad','desc')
            ->get();

            $estudiantesTotales = EstudianteGrupoEstudiante::select(
                "id_materia"
            )
            ->where('id_carrera',$request->carrera)->where('id_periodo',$request->periodo)
            ->get();
            
            if(count($estudiantesTotales) === 0){
                throw new Exception("No existen registros de nomina estudiantil de este periodo");
            }
            $contadorGeneral = $estudiantesTotales->groupBy("id_materia")->map(function($map){
                return count($map);
            });

            $dataEnviar = $query->map(function($map) use($contadorGeneral){
                $map->totalEstudiantes = $contadorGeneral[$map->id_materia];
                $map->porcentaje = round(($map->cantidad /$contadorGeneral[$map->id_materia] ) * 100,2);
                unset($map->id_materia);
                return $map;
            });

            
            return Excel::download(new TasaReprobadosExport($dataEnviar), 'datos.xlsx');
            
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