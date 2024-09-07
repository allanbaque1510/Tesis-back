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
            'Carrera',
            'Periodo',
            'Materia',
            'Nombre Docente',
            'Grupo',
            'Id_logro',
            'Logro',
            "Id_Estudiante",
            "Estudiante",
            "Pregunta",
            "Punto Referencia",
            "Puntuacion"

        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 17,
            'C' => 20,
            'D' => 25,
            'E' => 20,
            'F' => 10,
            'G' => 10,
            'H' => 10,
            'I' => 25,
            'K' => 15,
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
