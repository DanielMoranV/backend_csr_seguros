<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DateRangeAnalysisExport implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $admissions;
    protected $startDate;
    protected $endDate;

    public function __construct(array $admissions, string $startDate, string $endDate)
    {
        $this->admissions = $admissions;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return array
     */
    public function array(): array
    {
        $data = [];

        foreach ($this->admissions as $admission) {
            $data[] = [
                $admission['number'] ?? '',
                $admission['attendance_date'] ?? '',
                $admission['patient'] ?? '',
                $admission['patient_code'] ?? '',
                $admission['company'] ?? '',
                $admission['insurer_name'] ?? '',
                $admission['type'] ?? '',
                number_format($admission['amount'] ?? 0, 2),
                $admission['invoice_number'] ?? '',
                $admission['invoice_date'] ?? '',
                $admission['biller'] ?? '',
                $admission['paid_invoice_number'] ?? '',
                $admission['verified_shipment_date'] ?? '',
                $admission['devolution_date'] ?? '',
                $admission['devolution_invoice_number'] ?? '',
                $admission['status'] ?? '',
                $admission['doctor'] ?? '',
                $admission['medical_record_number'] ?? '',
                $admission['days_passed'] ?? '',
            ];
        }

        return $data;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Número de Admisión',
            'Fecha de Atención',
            'Paciente',
            'Código de Paciente',
            'Empresa',
            'Aseguradora',
            'Tipo',
            'Monto',
            'Número de Factura',
            'Fecha de Factura',
            'Facturador',
            'Factura Pagada',
            'Fecha de Envío',
            'Fecha de Devolución',
            'Factura Devolución',
            'Estado',
            'Doctor',
            'Historia Clínica',
            'Días Transcurridos',
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Análisis ' . $this->startDate . ' - ' . $this->endDate;
    }

    /**
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        // Estilo para encabezados
        $sheet->getStyle('A1:S1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Altura de la fila de encabezados
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Estilo para todas las celdas con datos
        $lastRow = count($this->admissions) + 1;
        if ($lastRow > 1) {
            $sheet->getStyle('A2:S' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Alineación específica para montos
            $sheet->getStyle('H2:H' . $lastRow)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        return [];
    }
}
