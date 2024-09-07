<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class TasaReprobadosExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
{
    protected $datos;

    public function __construct(Collection $datos)
    {
        $this->datos = $datos;
    }
    public function collection()
    {
        return $this->datos;
    }
    
    public function headings(): array
    {
        return [
            'Carrera',
            'Periodo',
            "Materia",
            'Cantidad Estudiantes',
            'Total Reprobados',
            'Reprobados Asistencia',
            'Reprobados Promedio',
            'Porcentaje',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 40,
            'D' => 20,
            'E' => 20,
            'F' => 15,
            'G' => 15,
            'H' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }
}
