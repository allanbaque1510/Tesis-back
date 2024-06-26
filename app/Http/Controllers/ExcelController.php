<?php

namespace App\Http\Controllers;

use App\Models\ArchivosSubidos;
use App\Models\EstudiantesEgresados;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\Periodo;
use App\Models\Carrera;
use App\Models\Estudiantes;
use App\Models\Habilitado;
use App\Models\RegistroEstudiantil;
use Illuminate\Support\Facades\Date;
class ExcelController extends Controller
{
    protected $indexPeriodo;
    protected $indexCodPeriodo;
    protected $indexCarrera;
    protected $indexCodCarrera;
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

    public function registrarDatosExcel(Request $request){
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

            return response()->json(['ok'=>true,'message' => 'Archivo subido con éxito']);

        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                "error"=>$e->getMessage()
            ],400);                 
        }
    }
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
        return Carrera::firstOrCreate(['codigo'=>$codigo],['carrera'=>$carrera]);
    }
    public function obtenerHistorialPeriodoTasaDesercion(Request $request){
        try {
            $periodos = Periodo::select(
                'periodo.id',
                'periodo.id_codigo',
                'periodo.codigo',
                'periodo.anio_inicio',
                'periodo.anio_fin',
                'periodo.ciclo',
                'periodo.created_at',
            )
            ->join('archivos_subidos','archivos_subidos.id_periodo','periodo.id')
            ->orderBy('periodo.anio_inicio','asc')
            ->orderBy('periodo.ciclo','asc')
            ->where('archivos_subidos.id_indicador',1)
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

    public function obtenerHistorialPeriodoTasaTitulacion(Request $request){
        try {
            $periodos = Periodo::select(
                'periodo.id',
                'periodo.id_codigo',
                'periodo.codigo',
                'periodo.anio_inicio',
                'periodo.anio_fin',
                'periodo.ciclo',
                'periodo.created_at',
            )
            ->join('archivos_subidos','archivos_subidos.id_periodo','periodo.id')
            ->orderBy('periodo.anio_inicio','asc')
            ->orderBy('periodo.ciclo','asc')
            ->where('archivos_subidos.id_indicador',2)
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

}