<?php

namespace App\Jobs;

use App\Models\Indicadores;
use App\Models\TipoGrafico;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTesis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {

    }
    public function agregandoDatosYvariables(){
        try {
            Log::info("Ejecutandose Job");
            $variables = ['LINEA DE TIEMPO','GRAFICO DE BARRAS','GRAFICO DE PASTEL'];
            $indicadores = ['TASA DE DESERCION','TASA DE TITULACION'];
            $datos = [] ;
            foreach ($variables as $value) {
                $datos [] =[
                    'descripcion'=>$value,
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ];
            }
            TipoGrafico::insert($datos);

            $datosIndicadores = [] ;
            foreach ($indicadores as $value2) {
                $datosIndicadores [] =[
                    'descripcion'=>$value2
                ];
            }
            Indicadores::insert($datosIndicadores);

        }catch (Exception $e) {
            Log::error($e);           
        }



    }
    public function handle(): void
    {
        Log::info("Estas en el handle");
        $this->agregandoDatosYvariables();
    }
}
