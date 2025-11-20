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

class PeriodAnalysisExport implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $admissions;
    protected $period;

    public function __construct(array $admissions, string $period)
    {
        $this->admissions = $admissions;
        $this->period = $period;
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
                $admission['period'] ?? '',
                $admission['attendance_date'] ?? '',
                $admission['patient_name'] ?? '',
                $admission['patient_code'] ?? '',
                $admission['company'] ?? '',
                $admission['insurer_name'] ?? '',
                $admission['service_name'] ?? '',
                $admission['type'] ?? '',
                number_format($admission['amount'] ?? 0, 2),
                $admission['invoice_number'] ?? '',
                $admission['invoice_date'] ?? '',
                $admission['biller'] ?? '',
                $admission['status_biller'] ?? '',
                $admission['audit']['auditor'] ?? '',
                $admission['status_auditor'] ?? '',
                $admission['paid_invoice_number'] ?? '',
                $admission['verified_shipment_date'] ?? '',
                $admission['devolution_date'] ?? '',
                $admission['is_devolution'] ? 'Sí' : 'No',
                $admission['status'] ?? '',
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
            'Periodo',
            'Fecha de Atención',
            'Paciente',
            'Código de Paciente',
            'Empresa',
            'Aseguradora',
            'Servicio',
            'Tipo',
            'Monto',
            'Número de Factura',
            'Fecha de Factura',
            'Facturador',
            'Estado Facturador',
            'Auditor',
            'Estado Auditor',
            'Factura Pagada',
            'Fecha de Envío',
            'Fecha de Devolución',
            'Es Devolución',
            'Estado',
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Análisis Periodo ' . $this->period;
    }

    /**
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        // Estilo para encabezados
        $sheet->getStyle('A1:U1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E7D32'],
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
            $sheet->getStyle('A2:U' . $lastRow)->applyFromArray([
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
            $sheet->getStyle('J2:J' . $lastRow)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Formato condicional para estados
            // Estado Facturador (columna N)
            for ($row = 2; $row <= $lastRow; $row++) {
                $statusBiller = $sheet->getCell('N' . $row)->getValue();
                $this->applyStatusColor($sheet, 'N' . $row, $statusBiller);

                // Estado Auditor (columna P)
                $statusAuditor = $sheet->getCell('P' . $row)->getValue();
                $this->applyStatusColor($sheet, 'P' . $row, $statusAuditor);
            }
        }

        return [];
    }

    /**
     * Aplicar color según el estado
     */
    protected function applyStatusColor(Worksheet $sheet, string $cell, ?string $status)
    {
        $colors = [
            'PAGADO' => 'C8E6C9',      // Verde claro
            'ENVIADO' => 'BBDEFB',     // Azul claro
            'FACTURADO' => 'FFF9C4',   // Amarillo claro
            'AUDITADO' => 'FFE082',    // Amarillo
            'DEVOLUCION' => 'FFCDD2',  // Rojo claro
        ];

        if ($status && isset($colors[$status])) {
            $sheet->getStyle($cell)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $colors[$status]],
                ],
            ]);
        }
    }
}
