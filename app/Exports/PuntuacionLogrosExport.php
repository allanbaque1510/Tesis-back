<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;

class PuntuacionLogrosExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
// , WithEvents
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
            'Identificador',
            'Carrera',
            'Periodo',
            'Materia',
            'Nombre Docente',
            'Grupo',
            'Logro',
            'N° Pregunta',
            'Puntuacion',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 20,
            'C' => 17,
            'D' => 20,
            'E' => 25,
            'F' => 20,
            'G' => 10,
            'H' => 10,
            'I' => 10,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }

    // public function registerEvents(): array
    // {
    //     return [
    //         AfterSheet::class => function (AfterSheet $event) {
    //             $sheet = $event->sheet->getDelegate();

    //             // Proteger toda la hoja
    //             $sheet->getProtection()->setSheet(true);

    //             // Desproteger las celdas que quieres que sean editables (por ejemplo, B2 a H100)
    //             $editableRange = $sheet->getStyle('B2:H9999');
    //             $editableRange->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
    //         },
    //     ];
    // }
}
