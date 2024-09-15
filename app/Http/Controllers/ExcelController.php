<?php

namespace App\Http\Controllers;

use App\Exports\PuntuacionLogrosExport;
use App\Imports\PuntuacionLogroMasiva;
use App\Models\ArchivosSubidos;
use App\Models\ConfigIndicadoresCarrera;
use App\Models\EstudiantesEgresados;
use App\Models\EstudiantesReprobados;
use App\Models\Materia;
use App\Models\PuntuacionLogros;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\Periodo;
use App\Models\Carrera;
use App\Models\CarreraDocenteMateria;
use App\Models\Docentes;
use App\Models\EstudianteGrupoEstudiante;
use App\Models\Estudiantes;
use App\Models\GrupoEstudiante;
use App\Models\Habilitado;
use App\Models\RegistroEstudiantil;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class ExcelController extends Controller
{
    protected $indexPeriodo;
    protected $indexCodPeriodo;
    protected $indexCarrera;
    protected $indexCodCarrera;

    protected $indexMateria;
    protected $indexCodMateria;

    
    protected $indexGrupo;
    protected $indexCodGrupo;

    protected $indexCi;
    protected $indexNivelactual;
    protected $indexNombre;
    protected $indexApellido;
    protected $indexHabilitado;
    protected $indexMatriculado;
    protected $indexNivelAnterior;
    protected $indexRepetidor;
    protected $indexConvencional;
    protected $indexCelular;
    protected $indexCorreos;
    protected $promMateria;
    protected $promTitulacion;
    protected $promGeneral;
    protected $promAsistencia;

    public function registrarDatosExcel(Request $request){
        try {
            $startTime = microtime(true);

            $uploadedFile = $request->file('file');
            
            $fileContent = file_get_contents($uploadedFile->getRealPath());
            $fileHash = hash('sha256', $fileContent);
    
            if (ArchivosSubidos::where('file_hash', $fileHash)->exists()) {
                throw new Exception("El archivo ya ha sido subido anteriormente.");
            }
            
            $data = Excel::toArray([], $uploadedFile);
            $excel = $data[0];
            if (!empty($excel) && is_array($excel[0])) {
                foreach ($data[0][0] as $key => $value) {
                    if($value === 'PERIODO'){$this->indexPeriodo = $key;}
                    if($value === 'CARRERA'){$this->indexCarrera = $key;}
                    if($value === 'COD_CARRERA'){$this->indexCodCarrera = $key;}
                    if($value === 'COD_ESTUDIANTE'){$this->indexCi = $key;}
                    if($value === 'NIVEL_ACTUAL'){$this->indexNivelactual = $key;}
                    if($value === 'ESTUDIANTE'){$this->indexNombre = $key;}
                    if($value === 'HABILITADO POR'){$this->indexHabilitado = $key;}
                    if($value === 'MATRICULADO'){$this->indexMatriculado = $key;}
                    if($value === 'ES_REPETIDOR'){$this->indexRepetidor = $key;}
                    if($value === 'NIVEL_ULTIMO'){$this->indexNivelAnterior = $key;}
                    if($value === 'CONVENCIONAL'){$this->indexConvencional = $key;}
                    if($value === 'CELULAR'){$this->indexCelular = $key;}
                    if($value === 'CORREO_SIUG'){$this->indexCorreos = $key;}
                    if($value === 'COD_PLECTIVO'){$this->indexCodPeriodo = $key; }
                }
            }else{
                throw new Exception("Error no existen datos en el excel");
            }
            if(!isset($this->indexPeriodo)){throw new Exception("Error, verifique que exista la cabecera: 'PERIODO' ");}
            if(!isset($this->indexCodPeriodo)){throw new Exception("Error, verifique que exista la cabecera: 'COD_PLECTIVO' ");}
            if(!isset($this->indexCarrera)){throw new Exception("Error, verifique que exista la cabecera: 'CARRERA' ");}
            if(!isset($this->indexCodCarrera)){throw new Exception("Error, verifique que exista la cabecera: 'COD_CARRERA' ");}
            if(!isset($this->indexCi)){throw new Exception("Error, verifique que exista la cabecera: 'COD_ESTUDIANTE' ");}
            if(!isset($this->indexNivelactual)){throw new Exception("Error, verifique que exista la cabecera: 'NIVEL_ACTUAL' ");}
            if(!isset($this->indexNombre)){throw new Exception("Error, verifique que exista la cabecera: 'ESTUDIANTE' ");}
            if(!isset($this->indexHabilitado)){throw new Exception("Error, verifique que exista la cabecera: 'HABILITADO POR' ");}
            if(!isset($this->indexMatriculado)){throw new Exception("Error, verifique que exista la cabecera: 'MATRICULADO' ");}
            if(!isset($this->indexRepetidor)){throw new Exception("Error, verifique que exista la cabecera: 'ES_REPETIDOR' ");}
            if(!isset($this->indexNivelAnterior)){throw new Exception("Error, verifique que exista la cabecera: 'NIVEL_ULTIMO' ");}
            if(!isset($this->indexConvencional)){throw new Exception("Error, verifique que exista la cabecera: 'CONVENCIONAL' ");}
            if(!isset($this->indexCelular)){throw new Exception("Error, verifique que exista la cabecera: 'CELULAR' ");}
            if(!isset($this->indexCorreos)){throw new Exception("Error, verifique que exista la cabecera: 'CORREO_SIUG' ");}

            $periodo = $this->verificarPeriodo($excel[1][$this->indexPeriodo], $excel[1][$this->indexCodPeriodo] );
            $carrera = $this->verificarCarrera($excel[1]);
            array_shift($excel);
            if (ArchivosSubidos::where('id_periodo', $periodo->id)->where('id_carrera',$carrera->id)->where('id_indicador',1)->exists()) {
                throw new Exception("Ya existe un archivo con estos datos de carrera y periodo en este identificador.");
            }
            $habilitadoBase = $this->verificarHabilitado($excel);
            $estudiantesBase = $this->verificarEstudiantes($excel);

            $guardarEnBase = [];
            foreach ($excel as $valorExcel){
                if(trim($valorExcel[$this->indexMatriculado]) === 'SI'){
                    
                    $guardarEnBase[]=[
                        'id_estudiante'=>$estudiantesBase[$valorExcel[$this->indexCi]],
                        'id_periodo'=>$periodo->id,
                        'id_carrera'=>$carrera->id,
                        'id_habilitado'=>$habilitadoBase[$valorExcel[$this->indexHabilitado]],
                        'nivel_actual'=>$valorExcel[$this->indexNivelactual],
                        'nivel_anterior' => $valorExcel[$this->indexNivelAnterior],
                        'repetidor'=>$valorExcel[$this->indexRepetidor] === 'SI'?1:0,
                        'created_at'=> now(),
                        'updated_at'=>now(),
                    ];
                }
            }
            RegistroEstudiantil::insert($guardarEnBase);
            $path = $uploadedFile->store('uploads');
        // Guarda el registro en la base de datos
            ArchivosSubidos::create([
                'id_periodo'=>$periodo->id,
                'id_carrera'=>$carrera->id,
                'id_indicador'=>1,
                'file_name' => $uploadedFile->getClientOriginalName(),
                'file_hash' => $fileHash,
                'file_path'=>$path,
            ]);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            return response()->json(['ok'=>true,'message' => 'Archivo subido con éxito']);

        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                "error"=>$e->getMessage()
            ],400);                 
        }
    }

    public function registrarDatosExcelPeriodoTitulacion(Request $request){
        try {
            $uploadedFile = $request->file('file');
            
            $fileContent = file_get_contents($uploadedFile->getRealPath());
            $fileHash = hash('sha256', $fileContent);
    
            if (ArchivosSubidos::where('file_hash', $fileHash)->exists()) {
                throw new Exception("El archivo ya ha sido subido anteriormente.");
            } 
  
            
            $data = Excel::toArray([], $uploadedFile);
            $excel = $data[0];
            if (!empty($excel) && is_array($excel[0])) {
                foreach ($data[0][0] as $key => $value) {
                    if($value === 'PLECTIVO'){$this->indexPeriodo = $key;}
                    if($value === 'CARRERA'){$this->indexCarrera = $key;}
                    if($value === 'COD_CARRERA'){$this->indexCodCarrera = $key;}
                    if($value === 'IDENTIFICACION'){$this->indexCi = $key;}
                    if($value === 'NOMBRE'){$this->indexNombre = $key;}
                    if($value === 'APELLIDO'){$this->indexApellido = $key;}
                    if($value === 'CELULAR'){$this->indexCelular = $key;}
                    if($value === 'CORREO'){$this->indexCorreos = $key;}
                    if($value === 'COD_PLECTIVO'){$this->indexCodPeriodo = $key; }
                    if($value === 'PROM_MATERIA'){$this->promMateria = $key; }
                    if($value === 'PROM_TITULACION'){$this->promTitulacion = $key; }
                    if($value === 'PROM_GENERAL'){$this->promGeneral = $key; }
                }
            }else{
                throw new Exception("Error no existen datos en el excel");
            }
            if(!isset($this->indexPeriodo)){throw new Exception("Error, verifique que exista la cabecera: 'PLECTIVO' ");}
            if(!isset($this->indexCodPeriodo)){throw new Exception("Error, verifique que exista la cabecera: 'COD_PLECTIVO' ");}
            if(!isset($this->indexCarrera)){throw new Exception("Error, verifique que exista la cabecera: 'CARRERA' ");}
            if(!isset($this->indexCodCarrera)){throw new Exception("Error, verifique que exista la cabecera: 'COD_CARRERA' ");}
            if(!isset($this->indexCi)){throw new Exception("Error, verifique que exista la cabecera: 'IDENTIFICACION' ");}
            if(!isset($this->indexNombre)){throw new Exception("Error, verifique que exista la cabecera: 'NOMBRE' ");}
            if(!isset($this->indexApellido)){throw new Exception("Error, verifique que exista la cabecera: 'APELLIDO' ");}
            if(!isset($this->indexCelular)){throw new Exception("Error, verifique que exista la cabecera: 'CELULAR' ");}
            if(!isset($this->indexCorreos)){throw new Exception("Error, verifique que exista la cabecera: 'CORREO' ");}
            if(!isset($this->promMateria)){throw new Exception("Error, verifique que exista la cabecera: 'PROM_MATERIA' ");}
            if(!isset($this->promTitulacion)){throw new Exception("Error, verifique que exista la cabecera: 'PROM_TITULACION' ");}
            if(!isset($this->promGeneral)){throw new Exception("Error, verifique que exista la cabecera: 'PROM_GENERAL' ");}

            $periodo = $this->verificarPeriodo($excel[1][$this->indexPeriodo], $excel[1][$this->indexCodPeriodo] );
            $carrera = $this->verificarCarrera($excel[1]);
            array_shift($excel);
            if (ArchivosSubidos::where('id_periodo', $periodo->id)->where('id_carrera',$carrera->id)->where('id_indicador',2)->exists()) {
                throw new Exception("Ya existe un archivo con estos datos de carrera y periodo en este identificador.");
            }
            $periodoRelacionado = Periodo::where('anio_inicio',$periodo->anio_inicio)
            ->where('anio_fin',$periodo->anio_fin)
            ->where('ciclo',$periodo->ciclo)
            ->whereNot('id',$periodo->id)
            ->first();
            if(is_null($periodoRelacionado)){
                throw new Exception("No existe un periodo estudiantil al cual asociar el periodo de titulación");
            }
            $estudiantesBase = $this->verificarEstudiantes($excel);

            $guardarEnBase = [];
            foreach ($excel as $valorExcel){
                    $guardarEnBase[]=[
                        'id_estudiante'=>$estudiantesBase[$valorExcel[$this->indexCi]],
                        'id_periodo'=>$periodo->id,
                        'id_periodo_relacionado'=>$periodoRelacionado->id,
                        'id_carrera'=>$carrera->id,
                        'prom_materia'=>$valorExcel[$this->promMateria],
                        'prom_titulacion' => $valorExcel[$this->promTitulacion],
                        'prom_general' => $valorExcel[$this->promGeneral],
                        'estado'=>1,
                        'created_at'=> now(),
                        'updated_at'=>now(),
                    ];
            }
            EstudiantesEgresados::insert($guardarEnBase);
            $path = $uploadedFile->store('uploads');
        // Guarda el registro en la base de datos
            ArchivosSubidos::create([
                'id_periodo'=>$periodo->id,
                'id_carrera'=>$carrera->id,
                'id_indicador'=>2,
                'file_name' => $uploadedFile->getClientOriginalName(),
                'file_hash' => $fileHash,
                'file_path'=>$path,
            ]);

            return response()->json(['ok'=>true,'message' => 'Archivo subido con éxito']);

        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al verificar el token',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }

    public function registrarDatosExcelNominaEstudiantesPeriodo(Request $request){
        try {
            DB::beginTransaction();
            $uploadedFile = $request->file('file');
            $fileContent = file_get_contents($uploadedFile->getRealPath());
            $fileHash = hash('sha256', $fileContent);
            if (ArchivosSubidos::where('file_hash', $fileHash)->where('id_indicador',5)->exists()) {
                throw new Exception("El archivo ya ha sido subido anteriormente.");
            } 
            $data = Excel::toArray([], $uploadedFile);
            $excel = $data[0];
            if (!empty($excel) && is_array($excel[0])) {
                foreach ($data[0][0] as $key => $value) {
                    if($value === 'COD_PLECTIVO'){$this->indexCodPeriodo = $key; }
                    if($value === 'PERIODO'){$this->indexPeriodo = $key;}
                    if($value === 'CARRERA'){$this->indexCarrera = $key;}
                    if($value === 'CARRERA_ORIGEN'){$this->indexCodCarrera = $key;}
                    if($value === 'IDENTIFICACION'){$this->indexCi = $key;}
                    if($value === 'ESTUDIANTE'){$this->indexNombre = $key;}
                    if($value === 'NIVEL'){$this->indexNivelactual = $key;}

                    if($value === 'COD_MATERIA'){$this->indexCodMateria = $key;}
                    if($value === 'MATERIA'){$this->indexMateria = $key;}
                    if($value === 'COD_GRUPO'){$this->indexCodGrupo = $key;}
                    if($value === 'GRUPO/PARALELO'){$this->indexGrupo = $key;}
                    
                    if($value === 'CONVENCIONAL'){$this->indexConvencional = $key;}
                    if($value === 'CELULAR'){$this->indexCelular = $key;}
                    if($value === 'CORREO_PERSONAL'){$this->indexCorreos = $key;}
                }
            }else{
                throw new Exception("Error no existen datos en el excel");
            }
            if(!isset($this->indexNivelactual)){throw new Exception("Error, verifique que exista la cabecera: 'NIVEL' ");}
            if(!isset($this->indexPeriodo)){throw new Exception("Error, verifique que exista la cabecera: 'PERIODO' ");}
            if(!isset($this->indexCodPeriodo)){throw new Exception("Error, verifique que exista la cabecera: 'COD_PLECTIVO' ");}
            if(!isset($this->indexCarrera)){throw new Exception("Error, verifique que exista la cabecera: 'CARRERA' ");}
            if(!isset($this->indexCodCarrera)){throw new Exception("Error, verifique que exista la cabecera: 'CARRERA_ORIGEN' ");}
            if(!isset($this->indexCi)){throw new Exception("Error, verifique que exista la cabecera: 'IDENTIFICACION' ");}
            if(!isset($this->indexNombre)){throw new Exception("Error, verifique que exista la cabecera: 'ESTUDIANTE' ");}
            if(!isset($this->indexCodMateria)){throw new Exception("Error, verifique que exista la cabecera: 'COD_MATERIA' ");}
            if(!isset($this->indexMateria)){throw new Exception("Error, verifique que exista la cabecera: 'MATERIA' ");}
            if(!isset($this->indexCodGrupo)){throw new Exception("Error, verifique que exista la cabecera: 'COD_GRUPO' ");}
            if(!isset($this->indexGrupo)){throw new Exception("Error, verifique que exista la cabecera: 'GRUPO/PARALELO' ");}
            if(!isset($this->indexConvencional)){throw new Exception("Error, verifique que exista la cabecera: 'CONVENCIONAL' ");}
            if(!isset($this->indexCelular)){throw new Exception("Error, verifique que exista la cabecera: 'CELULAR' ");}
            if(!isset($this->indexCorreos)){throw new Exception("Error, verifique que exista la cabecera: 'CORREO' ");}
            array_shift($excel);

            for ($i=0; $i < 1000 ; $i++) { 
                if(!isset($excel[$i][$this->indexCodCarrera])){
                    $periodo = Periodo::where('codigo',$excel[$i][$this->indexPeriodo])->first();
                    $carrera = Carrera::where('carrera',$excel[$i][$this->indexCarrera])->first();
                    $i = 1000;
                }
            }
            
            if(!$periodo){
                throw new Exception("El periodo del documento no existe dentro de la base, debe primero ingresar una Nomina Periodo Carrera Docente");
            }

            $materias =  $this->verificarMaterias($excel);
            $estudiantes =  $this->verificarEstudiantesNomina($excel);
            $carreras = $this->verificarCarreraSinCodigo($excel);
            $grupos = $this->verificarGruposEstudiantes($excel);
            
            $path = $uploadedFile->store('uploads');

            $archivo = ArchivosSubidos::create([
                'id_periodo'=>$periodo->id,
                'id_carrera'=>$carrera->id,
                'id_indicador'=>5,
                'file_name' => $uploadedFile->getClientOriginalName(),
                'file_hash' => $fileHash,
                'file_path'=>$path,
            ]);

            $dataExcel = [];
            foreach ($excel as $key => $valorExcel) {
                if(isset($valorExcel[$this->indexCodMateria]) && strlen($valorExcel[$this->indexCodGrupo]) > 0){
                    $dataExcel[] =[
                        'id_periodo'=>$periodo->id,
                        'id_carrera'=>$carreras[$valorExcel[$this->indexCarrera]],
                        'id_materia'=>$materias[trim($valorExcel[$this->indexCodMateria])],
                        'id_grupo'=>$grupos[trim($valorExcel[$this->indexCodGrupo])],
                        'id_estudiante'=>$estudiantes[trim($valorExcel[$this->indexCi])],
                        'id_archivo'=>$archivo->id,
                        'created_at'=>now(),
                    ];
                }
            }

            EstudianteGrupoEstudiante::insert($dataExcel);
   
            DB::commit();
            return response()->json(['ok'=>true,'message' => 'Archivo subido con éxito']);

        }catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'error al registrar los datos',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }




    
    public function registrarDatosExcelReprobados(Request $request){
        try {
            DB::beginTransaction();
            $uploadedFile = $request->file('file');
            $fileContent = file_get_contents($uploadedFile->getRealPath());
            $fileHash = hash('sha256', $fileContent);
            if (ArchivosSubidos::where('file_hash', $fileHash)->where('id_indicador',6)->exists()) {
                throw new Exception("El archivo ya ha sido subido anteriormente.");
            } 
            $data = Excel::toArray([], $uploadedFile);
            $excel = $data[0];
            if (!empty($excel) && is_array($excel[0])) {
                foreach ($data[0][0] as $key => $value) {
                    if($value === 'COD_PLECTIVO'){$this->indexCodPeriodo = $key; }
                    if($value === 'PERIODO'){$this->indexPeriodo = $key;}
                    if($value === 'CARRERA'){$this->indexCarrera = $key;}
                    if($value === 'IDENTIFICACION'){$this->indexCi = $key;}
                    if($value === 'ESTUDIANTE'){$this->indexNombre = $key;}

                    if($value === 'COD_MATERIA'){$this->indexCodMateria = $key;}
                    if($value === 'MATERIA'){$this->indexMateria = $key;}
                    if($value === 'COD_GRUPO'){$this->indexCodGrupo = $key;}
                    if($value === 'GRUPO/PARALELO'){$this->indexGrupo = $key;}
                    
                    if($value === 'CONVENCIONAL'){$this->indexConvencional = $key;}
                    if($value === 'CELULAR'){$this->indexCelular = $key;}
                    if($value === 'CORREO_PERSONAL'){$this->indexCorreos = $key;}
                    
                    if($value === 'PROMEDIO'){$this->promGeneral = $key;}
                    if($value === 'ASISTENCIA'){$this->promAsistencia = $key;} 

                    if($value === 'ESTADO'){$this->indexNivelactual = $key;} 
                                    
                }
            }else{
                throw new Exception("Error no existen datos en el excel");
            }
            if(!isset($this->indexPeriodo)){throw new Exception("Error, verifique que exista la cabecera: 'PERIODO' ");}
            if(!isset($this->indexCodPeriodo)){throw new Exception("Error, verifique que exista la cabecera: 'COD_PLECTIVO' ");}
            if(!isset($this->indexCarrera)){throw new Exception("Error, verifique que exista la cabecera: 'CARRERA' ");}
            if(!isset($this->indexCi)){throw new Exception("Error, verifique que exista la cabecera: 'IDENTIFICACION' ");}
            if(!isset($this->indexNombre)){throw new Exception("Error, verifique que exista la cabecera: 'ESTUDIANTE' ");}
            if(!isset($this->indexCodMateria)){throw new Exception("Error, verifique que exista la cabecera: 'COD_MATERIA' ");}
            if(!isset($this->indexMateria)){throw new Exception("Error, verifique que exista la cabecera: 'MATERIA' ");}
            if(!isset($this->indexCodGrupo)){throw new Exception("Error, verifique que exista la cabecera: 'COD_GRUPO' ");}
            if(!isset($this->indexGrupo)){throw new Exception("Error, verifique que exista la cabecera: 'GRUPO/PARALELO' ");}
            if(!isset($this->indexConvencional)){throw new Exception("Error, verifique que exista la cabecera: 'CONVENCIONAL' ");}
            if(!isset($this->indexCelular)){throw new Exception("Error, verifique que exista la cabecera: 'CELULAR' ");}
            if(!isset($this->indexCorreos)){throw new Exception("Error, verifique que exista la cabecera: 'CORREO_PERSONAL' ");}
            
            if(!isset($this->promAsistencia)){throw new Exception("Error, verifique que exista la cabecera: 'ASISTENCIA' ");}
            if(!isset($this->promGeneral)){throw new Exception("Error, verifique que exista la cabecera: 'PROMEDIO' ");}

            if(!isset($this->indexNivelactual)){throw new Exception("Error, verifique que exista la cabecera: 'ESTADO' ");}
            array_shift($excel);
            
            $periodo = $this->verificarPeriodo($excel[1][$this->indexPeriodo], $excel[1][$this->indexCodPeriodo] );
            $carrera = Carrera::where('carrera',$excel[4][$this->indexCarrera])->first();

            $configuracion = ConfigIndicadoresCarrera::where('id_carrera',$carrera->id)->where('estado',1)->first();
            
            if(!$periodo){
                throw new Exception("El periodo del documento no existe dentro de la base, debe primero ingresar una Nomina Periodo Carrera Docente");
            }

            $materias =  $this->verificarMaterias($excel);
            $estudiantes =  $this->verificarEstudiantesNomina($excel);
            $grupos = $this->verificarGruposEstudiantes($excel);
            
            $path = $uploadedFile->store('uploads');

            $archivo = ArchivosSubidos::create([
                'id_periodo'=>$periodo->id,
                'id_carrera'=>$carrera->id,
                'id_indicador'=>6,
                'file_name' => $uploadedFile->getClientOriginalName(),
                'file_hash' => $fileHash,
                'file_path'=>$path,
            ]);
            $dataExcel = [];
            foreach ($excel as $key => $valorExcel) {
                if(isset($valorExcel[$this->indexCodGrupo]) && $valorExcel[$this->indexNivelactual]==="REPROBADA"){
                    $dataExcel[] =[
                        'id_periodo'=>$periodo->id,
                        'id_carrera'=>$carrera->id,
                        'id_materia'=>$materias[trim($valorExcel[$this->indexCodMateria])],
                        'id_grupo'=>$grupos[trim($valorExcel[$this->indexCodGrupo])],
                        'id_estudiante'=>$estudiantes[trim($valorExcel[$this->indexCi])],
                        'asistencia'=>floatval($valorExcel[$this->promAsistencia]),
                        'promedio'=>floatval($valorExcel[$this->promGeneral]),
                        'reprobado_asistencia'=>floatval($valorExcel[$this->promAsistencia]) < $configuracion->prom_min_asistencia ? 1:0,
                        'reprobado_nota'=> floatval($valorExcel[$this->promGeneral]) <  $configuracion->prom_min_notas?1:0,
                        'id_archivo'=>$archivo->id,
                        'created_at'=>now(),
                    ];
                }
            }
            
            EstudiantesReprobados::insert($dataExcel);
            DB::commit();

            return response()->json(['ok'=>true,'message' => 'Archivo subido con éxito']);

        }catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'error al registrar los datos',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }






    public function registrarDatosExcelNominaMateriaDocent(Request $request){
        try {
            $uploadedFile = $request->file('file');
            
            $fileContent = file_get_contents($uploadedFile->getRealPath());
            $fileHash = hash('sha256', $fileContent);
    
            if (ArchivosSubidos::where('file_hash', $fileHash)->exists()) {
                throw new Exception("El archivo ya ha sido subido anteriormente.");
            } 
  
            $data = Excel::toArray([], $uploadedFile);
            $excel = $data[0];
            if (!empty($excel) && is_array($excel[0])) {
                foreach ($data[0][0] as $key => $value) {
                    if($value === 'COD_PLECTIVO'){$this->indexCodPeriodo = $key; }
                    if($value === 'PERIODO'){$this->indexPeriodo = $key;}
                    if($value === 'COD_CARRERA'){$this->indexCodCarrera = $key;}
                    if($value === 'CARRERA'){$this->indexCarrera = $key;}
                    if($value === 'MATERIA'){$this->indexMateria = $key;}
                    if($value === 'COD_MATERIA'){$this->indexCodMateria = $key;}
                    if($value === 'NIVEL'){$this->indexNivelactual = $key;}
                    if($value === 'COD_GRUPO'){$this->indexCodGrupo = $key;}
                    if($value === 'GRUPO'){$this->indexGrupo = $key;}
                    
                    if($value === 'IDENTIFICACION'){$this->indexCi = $key;}
                    if($value === 'DOCENTE'){$this->indexNombre = $key;}
                }
            }else{
                throw new Exception("Error no existen datos en el excel");
            }
            if(!isset($this->indexPeriodo)){throw new Exception("Error, verifique que exista la cabecera: 'PERIODO' ");}
            if(!isset($this->indexCodPeriodo)){throw new Exception("Error, verifique que exista la cabecera: 'COD_PLECTIVO' ");}
            if(!isset($this->indexCarrera)){throw new Exception("Error, verifique que exista la cabecera: 'CARRERA' ");}
            if(!isset($this->indexCodCarrera)){throw new Exception("Error, verifique que exista la cabecera: 'COD_CARRERA' ");}
            if(!isset($this->indexCi)){throw new Exception("Error, verifique que exista la cabecera: 'IDENTIFICACION' ");}
            if(!isset($this->indexNombre)){throw new Exception("Error, verifique que exista la cabecera: 'DOCENTE' ");}
            if(!isset($this->indexMateria)){throw new Exception("Error, verifique que exista la cabecera: 'MATERIA' ");}
            if(!isset($this->indexCodMateria)){throw new Exception("Error, verifique que exista la cabecera: 'COD_MATERIA' ");}
            if(!isset($this->indexNivelactual)){throw new Exception("Error, verifique que exista la cabecera: 'NIVEL' ");}
            if(!isset($this->indexCodGrupo)){throw new Exception("Error, verifique que exista la cabecera: 'COD_GRUPO' ");}
            if(!isset($this->indexGrupo)){throw new Exception("Error, verifique que exista la cabecera: 'GRUPO' ");}
            array_shift($excel);
            DB::beginTransaction();
            $periodo = $this->verificarPeriodo($excel[1][$this->indexPeriodo], $excel[1][$this->indexCodPeriodo] );
            $carrera = $this->verificarCarrera($excel[1]);
            
            if (ArchivosSubidos::where('id_periodo', $periodo->id)->where('id_carrera',$carrera->id)->where('id_indicador',3)->exists()) {
                throw new Exception("Ya existe un archivo con estos datos de carrera y periodo en este identificador.");
            }

            $docentes = $this->verificarDocentes($excel);
            $materias = $this->verificarMaterias($excel);
            $grupoEstudiantes = $this->verificarGruposEstudiantes($excel);

            $guardarEnBase = [];
            foreach ($excel as $valorExcel){
                    $guardarEnBase[]=[
                        'id_periodo'=>$periodo->id,
                        'id_carrera'=>$carrera->id,

                        'id_materia'=>$materias[trim($valorExcel[$this->indexCodMateria])],
                        'id_grupo'=>$grupoEstudiantes[trim( $valorExcel[$this->indexCodGrupo])],
                        'id_docente'=>$docentes[trim($valorExcel[$this->indexCi])],

                        'created_at'=> now(),
                        'updated_at'=>now(),
                    ];
            }
            
            CarreraDocenteMateria::insert($guardarEnBase);
            $path = $uploadedFile->store('uploads');
        // Guarda el registro en la base de datos
            ArchivosSubidos::create([
                'id_periodo'=>$periodo->id,
                'id_carrera'=>$carrera->id,
                'id_indicador'=>3,
                'file_name' => $uploadedFile->getClientOriginalName(),
                'file_hash' => $fileHash,
                'file_path'=>$path,
            ]);

            DB::commit();

            return response()->json(['ok'=>true,'message' => 'Archivo subido con éxito']);

        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al verificar el token',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }

//----------------------SUBIR CARGA MASIVA GENERADA POR EL SISTEMA-----------------------------
public function subirAsignacionPuntosMasiva(Request $request){
    try {
        DB::beginTransaction();
        $uploadedFile = $request->file('file');
        $fileContent = file_get_contents($uploadedFile->getRealPath());
        $fileHash = hash('sha256', $fileContent);
        if (ArchivosSubidos::where('file_hash', $fileHash)->where('id_indicador',4)->exists() ) {
            throw new Exception("El archivo ya ha sido subido anteriormente.");
        }

        $data = Excel::toArray([], $uploadedFile);
        $excel = $data[0];
        if (!empty($excel) && is_array($excel[0])) {
            foreach ($data[0][0] as $key => $value) {
                if($value === 'Grupo'){$this->indexCodGrupo = $key; }
                if($value === 'Id_logro'){$this->indexGrupo = $key;}
                if($value === 'Id_Estudiante'){$this->indexCi = $key;}
                if($value === 'Pregunta'){$this->indexConvencional = $key;}
                if($value === 'Puntuacion'){$this->indexNivelactual = $key;}

                if($value === 'Periodo'){$this->indexPeriodo = $key;}
                if($value === 'Carrera'){$this->indexCarrera = $key;}
            }
        }else{
            throw new Exception("Error no existen datos en el excel");
        }

        if(!isset($this->indexCodGrupo)){throw new Exception("Error, verifique que exista la cabecera: 'Grupo' ");}
        if(!isset($this->indexGrupo)){throw new Exception("Error, verifique que exista la cabecera: 'Id_logro' ");}
        if(!isset($this->indexCi)){throw new Exception("Error, verifique que exista la cabecera: 'Id_Estudiante' ");}
        if(!isset($this->indexConvencional)){throw new Exception("Error, verifique que exista la cabecera: 'Pregunta' ");}
        if(!isset($this->indexNivelactual)){throw new Exception("Error, verifique que exista la cabecera: 'Puntuacion' ");}
        if(!isset($this->indexPeriodo)){throw new Exception("Error, verifique que exista la cabecera: 'Periodo' ");}
        if(!isset($this->indexCarrera)){throw new Exception("Error, verifique que exista la cabecera: 'Carrera' ");}

        $periodo = Periodo::where('codigo',$excel[3][$this->indexPeriodo])->first();
        $carrera = Carrera::where('carrera',$excel[3][$this->indexCarrera])->first();
        $path = $uploadedFile->store('uploads');
        $archivo = ArchivosSubidos::create([
            'id_periodo'=>$periodo->id,
            'id_carrera'=>$carrera->id,
            'id_indicador'=>4,
            'file_name' => $uploadedFile->getClientOriginalName(),
            'file_hash' => $fileHash,
            'file_path'=>$path,
        ]);

        Excel::import(new PuntuacionLogroMasiva($archivo->id), $uploadedFile);

        DB::commit();
        return response()->json(['ok'=>true,'message' => 'Archivo subido con éxito']);
    
    }catch (Exception $e) {
        DB::rollBack();
        Log::error($e);
        return response([
            "ok"=>false,
            'message'=>'Error al asignar una puntuación masiva',
            "error"=>$e->getMessage()
        ],400);                 
    }
}
//---------------------Descargar Formatos Excel -----------------------
    public function descargarFormatoPuntuacion(Request $request){
        try {
            DB::beginTransaction();
            $logrosCarreraDocente = CarreraDocenteMateria::select(
                "logros_mat_carr_per_doc.id_logros_mat_carr_per_doc",
                "logros_mat_carr_per_doc.id_logros",
            )
            ->join('logros_mat_carr_per_doc',function($join){
                $join->on('logros_mat_carr_per_doc.id_carrera_docente_materia','carrera_docente_materias.id_carrera_docente_materia')
                ->where("logros_mat_carr_per_doc.estado",1);
            })
            ->where('id_materia',$request->datos["materia"])
            ->where('id_carrera',$request->datos["carrera"])
            ->where('id_periodo',$request->datos["periodos"])
            ->where('id_docente',$request->datos["docente"])
            ->where('id_grupo',$request->datos["grupo"])
            ->get();
            $data = $request["puntuacion"];
            foreach ($data as  &$value) {
                $idLogro = collect($logrosCarreraDocente)->where("id_logros",$value["logro"])->first();
                $value["id_logros_mat_carr_per_doc"] = $idLogro->id_logros_mat_carr_per_doc;
                $value["created_at"] = now();
                unset($value["logro"]);
            }
            PuntuacionLogros::insert($data);
            $datos = CarreraDocenteMateria::select(
                "carrera.carrera",
                "periodo.codigo as periodo",
                "materias.descripcion as materia",
                "docentes.nombre as docente",
                "grupo_estudiantes.descripcion as grupo",
                "logros_mat_carr_per_doc.id_logros_mat_carr_per_doc as identificador",
                "logros_aprendizaje.codigo as logro",
                "estudiantes.id",
                "estudiantes.estudiante",
                "puntuacion_logros.pregunta",
                "puntuacion_logros.puntuacion"
            )
            ->join('logros_mat_carr_per_doc',function($join){
                $join->on('logros_mat_carr_per_doc.id_carrera_docente_materia','carrera_docente_materias.id_carrera_docente_materia')
                ->where("logros_mat_carr_per_doc.estado",1);
            })
            ->join('logros_aprendizaje','logros_mat_carr_per_doc.id_logros','logros_aprendizaje.id_logros')
            ->join("puntuacion_logros","puntuacion_logros.id_logros_mat_carr_per_doc","logros_mat_carr_per_doc.id_logros_mat_carr_per_doc")
            ->join('docentes','carrera_docente_materias.id_docente','docentes.id_docente')
            ->join('estudiante_grupo_estudiante',function($join) use($request){
                $join->on("estudiante_grupo_estudiante.id_grupo",'carrera_docente_materias.id_grupo')
                ->where('estudiante_grupo_estudiante.id_materia',$request->datos["materia"])
                ->where('estudiante_grupo_estudiante.id_carrera',$request->datos["carrera"])
                ->where('estudiante_grupo_estudiante.id_periodo',$request->datos["periodos"]);
            })
            ->join("estudiantes","estudiante_grupo_estudiante.id_estudiante","estudiantes.id")
            ->join('grupo_estudiantes','carrera_docente_materias.id_grupo','grupo_estudiantes.id_grupo')
            ->join('materias','carrera_docente_materias.id_materia','materias.id_materia')
            ->join('carrera','carrera_docente_materias.id_carrera','carrera.id')
            ->join('periodo','carrera_docente_materias.id_periodo','periodo.id')

            ->where('materias.id_materia',$request->datos["materia"])
            ->where('carrera.id',$request->datos["carrera"])
            ->where('periodo.id',$request->datos["periodos"])
            ->where('docentes.id_docente',$request->datos["docente"])
            ->orderBy('estudiantes.id')
            ->orderBy('logros_aprendizaje.id_logros')
            ->get();
            DB::commit();
            return Excel::download(new PuntuacionLogrosExport($datos), 'datos.xlsx');
            
        }catch (Exception $e) {
            Log::error($e);
            DB::rollBack();
            return response([
                "ok"=>false,
                'message'=>'Error al descargar el formato excel',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }


//--------------Validaciones----------------------------
    public function verificarEstudiantes($excel){
        $ci_estudiantes = array_map(function($estudiantes) {
            return $estudiantes[$this->indexCi];
        }, $excel);
        $estudiantes = Estudiantes::select('id','ci','estudiante')->whereIn('ci',$ci_estudiantes)->get();
        $arrayEstudiantes = [];
        foreach ($estudiantes as $estudiante) {
            $arrayEstudiantes[$estudiante->ci] = $estudiante->id;
        } 

        $cedulasBase = $estudiantes->pluck('ci');
        foreach ($excel as $value) {
            if (!$cedulasBase->contains($value[$this->indexCi])) {
                $correos = explode(';', $value[$this->indexCorreos]);

                $estudiantesAEnviar = Estudiantes::create([
                        'ci'=>$value[$this->indexCi], 
                        'estudiante'=>$value[$this->indexNombre],
                        'telefono' => $value[$this->indexConvencional],
                        'celular' => $value[$this->indexCelular],
                        'correo_personal' => $correos[0],
                        'correo_institucional' => $correos[1]??null,
                        ] );
                $arrayEstudiantes[ $estudiantesAEnviar->ci] = $estudiantesAEnviar->id;
            }
        }
        return $arrayEstudiantes;
    } 

    public function verificarEstudiantesNomina($excel){
        $datosEstudiantesExcel = array_map(function($grupos) {
            return [
                "telefono"=>$grupos[$this->indexConvencional],
                "celular"=>$grupos[$this->indexCelular],
                "correo_personal"=>$grupos[$this->indexCorreos],
                "estudiante"=>$grupos[$this->indexNombre], 
                "ci"=>trim($grupos[$this->indexCi]),
            ];
        }, $excel);
        $GruposUnicos = collect($datosEstudiantesExcel)->unique("ci")->values();

        $Grupos = Estudiantes::select('ci')->whereIn('ci',$GruposUnicos->pluck('ci'))->get()->pluck('ci');
        $GruposFiltrados = $GruposUnicos->filter(function ($x) use ($Grupos) {
            return !$Grupos->contains($x['ci']);
        });

        if($GruposFiltrados->count() > 0){Estudiantes::insert($GruposFiltrados->toArray());}

        $todosLosgrupos = Estudiantes::whereIn("ci",$GruposUnicos->pluck('ci'))->get();
        
        $arrayAsociativoMaterias = [] ;
        foreach ($todosLosgrupos as $value) {
            $arrayAsociativoMaterias[trim($value->ci)] =  $value->id;
        }
        return $arrayAsociativoMaterias;
    }

    public function verificarCarreraSinCodigo($excel){
        $datosEstudiantesExcel = array_map(function($grupos) {
            return [
                "carrera"=>$grupos[$this->indexCarrera],
                "estado"=>1,
                "created_at"=>now(),
            ];
        }, $excel);

        $GruposUnicos = collect($datosEstudiantesExcel)->unique("carrera")->values();

        $Grupos = Carrera::select('carrera')->whereIn('carrera',$GruposUnicos->pluck('carrera'))->get()->pluck('carrera');
        $GruposFiltrados = $GruposUnicos->filter(function ($x) use ($Grupos) {
            return !$Grupos->contains($x['carrera']);
        });

        if($GruposFiltrados->count() > 0){Carrera::insert($GruposFiltrados->toArray());}

        $todosLosgrupos = Carrera::whereIn("carrera",$GruposUnicos->pluck('carrera'))->get();
        
        $arrayAsociativoMaterias = [] ;
        foreach ($todosLosgrupos as $value) {
            $arrayAsociativoMaterias[$value->carrera] =  $value->id;
        }
        return $arrayAsociativoMaterias;
    }


    public function verificarHabilitado($excel){
        $collecionHabilitado = collect($excel)->map(function($map){
            return $map[$this->indexHabilitado];
        })->unique()->values();
        $array = [];
        foreach ($collecionHabilitado as $habilitado) {
            $habilitadoModel = Habilitado::firstOrCreate(['descripcion' => $habilitado]);
            $array[$habilitadoModel->descripcion] = $habilitadoModel->id;
        }
        return $array;
    }
    
    public function verificarCarrera($array){
        $codigo = trim($array[$this->indexCodCarrera]);
        $carrera = trim($array[$this->indexCarrera]);

        $data = Carrera::firstOrCreate(['carrera'=>$carrera],['codigo'=>$codigo]);
        if(is_null($data->codigo)){
            Carrera::where('id',$data->id)->update(['codigo'=>$codigo]);
        }

        return $data; 
        // return Carrera::firstOrCreate(['codigo'=>$codigo],['carrera'=>$carrera]);
    }

    public function verificarMateria($array){
        $codigo = trim($array[$this->indexCodMateria]);
        $carrera = trim($array[$this->indexMateria]);
        $nivel = trim($array[$this->indexNivelactual]);
        return Materia::firstOrCreate(['codigo'=>$codigo],['descripcion'=>$carrera,'nivel'=>$nivel]);
    }

    public function verificarMaterias($excel){
        $datosMateriasExcel = array_map(function($materia) {
            return [
                "descripcion"=>$materia[$this->indexMateria], 
                "codigo"=>trim($materia[$this->indexCodMateria]),
                "nivel"=>$materia[$this->indexNivelactual],
            ];
        }, $excel);
        
        $MateriasUnicas = collect($datosMateriasExcel)->unique("codigo")->where("codigo","!=","")->values();
        
        
        $Materias = Materia::select('codigo')->whereIn('codigo',$MateriasUnicas->pluck('codigo'))->get()->pluck('codigo');
        $MateriasFiltrados = $MateriasUnicas->filter(function ($materia) use ($Materias) {
            return !$Materias->contains($materia['codigo']);
        });
        
        if($MateriasFiltrados->count() > 0){Materia::insert($MateriasFiltrados->toArray());}

        $todasLasMaterias = Materia::whereIn("codigo",$MateriasUnicas->pluck('codigo'))->get();
        
        $arrayAsociativoMaterias = [] ;
        foreach ($todasLasMaterias as $value) {
            $arrayAsociativoMaterias[$value->codigo] =  $value->id_materia;
        }
        return $arrayAsociativoMaterias;
    }
    



    public function verificarGruposEstudiantes($excel){
        $datosGruposExcel = array_map(function($grupos) {
            $descripcion = $grupos[$this->indexGrupo];
            $codigo = trim($grupos[$this->indexCodGrupo]);
            if ($codigo !== "") {
                return [
                    "descripcion" => $descripcion,
                    "codigo" => $codigo,
                ];
            } else {
                return null; // O cualquier otro valor que desees cuando el código esté vacío
            }
        }, $excel);
        $datosGruposExcel = array_filter($datosGruposExcel);
        $GruposUnicos = collect($datosGruposExcel)->unique("codigo")->values();

        $Grupos = GrupoEstudiante::select('codigo')->whereIn('codigo',$GruposUnicos->pluck('codigo'))->get()->pluck('codigo');

        $GruposFiltrados = $GruposUnicos->filter(function ($x) use ($Grupos) {
            return !$Grupos->contains($x['codigo']);
        });

        if($GruposFiltrados->count() > 0){GrupoEstudiante::insert($GruposFiltrados->toArray());}
        
        $todosLosgrupos = GrupoEstudiante::whereIn("codigo",$GruposUnicos->pluck('codigo'))->get();
        
        $arrayAsociativoMaterias = [] ;
        foreach ($todosLosgrupos as $value) {
            $arrayAsociativoMaterias[$value->codigo] =  $value->id_grupo;
        }
        return $arrayAsociativoMaterias;
    }

    public function verificarDocentes($excel){
        $datosDocente = array_map(function($docentes_id) {
            return [
                "cedula"=>$docentes_id[$this->indexCi], 
                "nombre"=>$docentes_id[$this->indexNombre]
            ];
        }, $excel);
        $docentesUnicos = collect($datosDocente)->unique("cedula")->values();
        $Docentes = Docentes::select('cedula')->whereIn('cedula',$docentesUnicos->pluck('cedula'))->get()->pluck('cedula');
        $docentesFiltrados = $docentesUnicos->filter(function ($docente) use ($Docentes) {
            return !$Docentes->contains($docente['cedula']);
        });
        if($docentesFiltrados->count() > 0){
            Docentes::insert($docentesFiltrados->toArray());
        }
        $todosLosDocentes = Docentes::whereIn("cedula",$docentesUnicos->pluck('cedula'))->get();
        $arrayAsociativoDocente = [] ;
        foreach ($todosLosDocentes as $value) {
            $arrayAsociativoDocente[$value->cedula] =  $value->id_docente;
        }
        return $arrayAsociativoDocente;
    } 



       
    public function verificarDocente($nombre,$cedula){
        $nombreDocente = trim($nombre);
        $cedulaDocente = trim($cedula);
        
        return Docentes::firstOrCreate(['cedula'=>$cedulaDocente],['nombre'=>$nombreDocente]);
    } 
    public function verificarGrupoEstudiante($codigo,$descripcion){
        $codGrupo = trim($codigo);
        $grupo = trim($descripcion);
        return GrupoEstudiante::firstOrCreate(['codigo'=>$codGrupo],['descripcion'=>$grupo]);
    }


    public function verificarPeriodo($periodo,$codPeriodo){
        $words = explode(' ', $periodo);
        $lastCharacter = substr($words[3], -1);
        if(!is_numeric($lastCharacter)){
            if(strlen($words[3]) > 2){
                $ciclo = 2;
            }else if(strlen($words[3]) === 2){
                $ciclo = 1;
            }else{
                throw new Exception("Error con el ciclo academico".$words[3]);
            }
        }else{
            $ciclo = $lastCharacter;
        }
        return Periodo::firstOrCreate(
            ['id_codigo'=>$codPeriodo],
            ['codigo'=>$periodo,'anio_inicio'=>$words[0],'anio_fin'=>$words[2],'ciclo'=>$ciclo]
        );


    }

}