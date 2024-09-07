<?php

namespace App\Imports;
use App\Models\GrupoEstudiante;
use App\Models\PuntuacionGrupoEstudiante;
use App\Models\PuntuacionLogros;
use Exception;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PuntuacionLogroMasiva implements ToCollection, WithCalculatedFormulas
{
    protected $grupo;
    protected $periodo;
    protected $materia;
    protected $carrera;
    protected $id_logro_carrera;
    protected $id_estudiante;
    protected $pregunta;
    protected $estudiante;
    
    protected $puntuacion;
    protected $id_archivo;

    public function __construct($additionalParam1)
    {
        $this->id_archivo = $additionalParam1;
    }
     public function collection(Collection $collection)
    {
        $arrayExcel = $collection->toArray();
        foreach ($arrayExcel[0] as $key => $value) {
            if($value === 'Grupo'){$this->grupo = $key; }
            if($value === 'Id_logro'){$this->id_logro_carrera = $key;}
            if($value === 'Id_Estudiante'){$this->id_estudiante = $key;}
            if($value === 'Estudiante'){$this->estudiante = $key;}
            if($value === 'Pregunta'){$this->pregunta = $key;}
            if($value === 'Puntuacion'){$this->puntuacion = $key;}
            if($value === 'Periodo'){$this->periodo = $key;}
            if($value === 'Carrera'){$this->carrera = $key;}
            if($value === 'Materia'){$this->materia = $key;}
        }
        array_shift($arrayExcel);
        $periodo = $arrayExcel[0][$this->periodo];
        $carrera = $arrayExcel[0][$this->carrera];
        $materia = $arrayExcel[0][$this->materia];

        $grupoEstudiantes = GrupoEstudiante::
        select(
            "estudiante_grupo_estudiante.id_estudiante_grupo",
            'estudiante_grupo_estudiante.id_estudiante'
        )
        ->where('grupo_estudiantes.descripcion',$arrayExcel[0][$this->grupo])
        ->join('estudiante_grupo_estudiante','estudiante_grupo_estudiante.id_grupo','grupo_estudiantes.id_grupo')
        ->join('periodo',function($join) use($periodo){
            $join->on('periodo.id','estudiante_grupo_estudiante.id_periodo')
            ->where('periodo.codigo',$periodo);
        })
        ->join('carrera',function($join) use($carrera){
            $join->on('carrera.id','estudiante_grupo_estudiante.id_carrera')
            ->where('carrera.carrera',$carrera);
        })
        ->join('materias',function($join) use($materia){
            $join->on('materias.id_materia','estudiante_grupo_estudiante.id_materia')
            ->where('materias.descripcion',$materia);
        })
        ->get();
        $data=[];
        $logros = [];
        foreach ($arrayExcel as $key => $value) {
            if(is_null($value[$this->puntuacion])){
                throw new Exception("El estudiante ".$value[$this->estudiante]." no tiene puntuacion asignada en la fila ".$key+2);
            }
            if(!is_numeric($value[$this->puntuacion])){
                throw new Exception("La siguiente puntuacion '".$value[$this->puntuacion]."' del estudiante ".$value[$this->estudiante]." no es valida => fila ".$key+2);
            }
            if(!in_array($value[$this->id_logro_carrera], $logros)){
                $logros[]=$value[$this->id_logro_carrera];
            }
            $data [] = [
                "id_logros_mat_carr"=>$value[$this->id_logro_carrera],
                "pregunta"=>$value[$this->pregunta],
                "puntuacion"=>$value[$this->puntuacion],
                "id_estudiante_grupo"=>$grupoEstudiantes->where('id_estudiante',$value[$this->id_estudiante])->first()->id_estudiante_grupo,
                "id_archivo"=>$this->id_archivo, 
                "created_at"=>now(),
            ];
        }
        PuntuacionLogros::whereIn('id_logros_mat_carr_per_doc',$logros)->update(["id_archivo"=>$this->id_archivo]);
        PuntuacionGrupoEstudiante::insert($data);


    }
}
