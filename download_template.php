<?php
// download_template.php
require_once __DIR__ . '/config/db.php';
check_auth();

$module = $_GET['module'] ?? '';

$valid_modules = [
    'patients', 'ipop', 'activities', 'cmchis', 'asthma', 'copd', 'occupational',
    'op_census', 'ward_census', 'elective_ot_census', 'minor_procedure_census',
    'speech_therapy_census', 'pta_census', 'bera_census', 'oae_census'
];

if (!in_array($module, $valid_modules)) {
    die("Invalid module specified.");
}

$hasVendor = file_exists(__DIR__ . '/vendor/autoload.php');

if ($hasVendor) {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        // PhpSpreadsheet XLSX export
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $yr_suffix = date('y');
        $months_with_yr = [
            'Jan-' . $yr_suffix, 'Feb-' . $yr_suffix, 'Mar-' . $yr_suffix, 'Apr-' . $yr_suffix,
            'May-' . $yr_suffix, 'Jun-' . $yr_suffix, 'Jul-' . $yr_suffix, 'Aug-' . $yr_suffix,
            'Sep-' . $yr_suffix, 'Oct-' . $yr_suffix, 'Nov-' . $yr_suffix, 'Dec-' . $yr_suffix
        ];

        $styleHeader = function($s, $highestCol) {
            $s->getStyle("A1:{$highestCol}1")->getFont()->setBold(true);
            $s->getStyle("A1:{$highestCol}1")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF0D6EFD');
            $s->getStyle("A1:{$highestCol}1")->getFont()->getColor()->setARGB('FFFFFFFF');
        };

        if ($module === 'op_census') {
            $sheet->setTitle('1. OP CENSUS');
            $headers = ['MONTH', 'NEW MALE', 'NEW FEMALE', 'NEW M.CH', 'NEW F.CH', 'NEW TOTAL', 'OLD MALE', 'OLD FEMALE', 'OLD M.CH', 'OLD F.CH', 'OLD TOTAL', 'GRAND TOTAL'];
            foreach ($headers as $colIndex => $header) {
                $sheet->setCellValue([$colIndex + 1, 1], $header);
            }
            $styleHeader($sheet, 'L');
            foreach ($months_with_yr as $rowIndex => $m) {
                $rowNum = $rowIndex + 2;
                $sheet->setCellValue([1, $rowNum], $m);
                for ($col = 2; $col <= count($headers); $col++) {
                    $sheet->setCellValue([$col, $rowNum], 0);
                }
            }
        } elseif ($module === 'ward_census') {
            $sheet->setTitle('2. WARD CENSUS');
            $headers = ['MONTH', 'ADMISSION MALE', 'ADMISSION FEMALE', 'ADMISSION MCH', 'ADMISSION FCH', 'ADMISSION TOTAL', 'DISCHARGE MALE', 'DISCHARGE FEMALE', 'DISCHARGE MCH', 'DISCHARGE FCH', 'DISCHARGE TOTAL', 'AMA', 'DEATH'];
            foreach ($headers as $colIndex => $header) {
                $sheet->setCellValue([$colIndex + 1, 1], $header);
            }
            $styleHeader($sheet, 'M');
            foreach ($months_with_yr as $rowIndex => $m) {
                $rowNum = $rowIndex + 2;
                $sheet->setCellValue([1, $rowNum], $m);
                for ($col = 2; $col <= count($headers); $col++) {
                    $sheet->setCellValue([$col, $rowNum], 0);
                }
            }
        } elseif ($module === 'elective_ot_census') {
            $sheet->setTitle('3. ELECTIVE OT CENSUS');
            $headers = ['MONTH', 'MAJOR MALE', 'MAJOR FEMALE', 'MAJOR MCH', 'MAJOR FCH', 'MAJOR TOTAL', 'MINOR MALE', 'MINOR FEMALE', 'MINOR MCH', 'MINOR FCH', 'MINOR TOTAL', 'OVER ALL TOTAL', 'UNIT'];
            foreach ($headers as $colIndex => $header) {
                $sheet->setCellValue([$colIndex + 1, 1], $header);
            }
            $styleHeader($sheet, 'M');
            foreach ($months_with_yr as $rowIndex => $m) {
                $rowNum = $rowIndex + 2;
                $sheet->setCellValue([1, $rowNum], $m);
                for ($col = 2; $col <= 12; $col++) {
                    $sheet->setCellValue([$col, $rowNum], 0);
                }
                $sheet->setCellValue([13, $rowNum], '');
            }
        } elseif ($module === 'minor_procedure_census') {
            $sheet->setTitle('4. MINOR PROCEDURE CENSUS');
            $headers = ['DATE', 'MALE', 'FEMALE', 'MCH', 'FCH', 'TOTAL'];
            foreach ($headers as $colIndex => $header) {
                $sheet->setCellValue([$colIndex + 1, 1], $header);
            }
            $styleHeader($sheet, 'F');
            foreach ($months_with_yr as $rowIndex => $m) {
                $rowNum = $rowIndex + 2;
                $sheet->setCellValue([1, $rowNum], $m);
                for ($col = 2; $col <= count($headers); $col++) {
                    $sheet->setCellValue([$col, $rowNum], 0);
                }
            }
        } elseif ($module === 'speech_therapy_census') {
            $sheet->setTitle('5. SPEECH THERAPY CENSUS');
            $headers = ['MONTH', 'NEW MALE', 'NEW FEMALE', 'NEW M.CH', 'NEW F.CH', 'NEW TOTAL', 'OLD MALE', 'OLD FEMALE', 'OLD M.CH', 'OLD F.CH', 'OLD TOTAL', 'GRAND TOTAL'];
            foreach ($headers as $colIndex => $header) {
                $sheet->setCellValue([$colIndex + 1, 1], $header);
            }
            $styleHeader($sheet, 'L');
            foreach ($months_with_yr as $rowIndex => $m) {
                $rowNum = $rowIndex + 2;
                $sheet->setCellValue([1, $rowNum], $m);
                for ($col = 2; $col <= count($headers); $col++) {
                    $sheet->setCellValue([$col, $rowNum], 0);
                }
            }
        } elseif (in_array($module, ['pta_census', 'bera_census', 'oae_census'])) {
            $labels = [
                'pta_census' => '6. PTA CENSUS',
                'bera_census' => '7. BERA CENSUS',
                'oae_census' => '8. OAE CENSUS'
            ];
            $sheet->setTitle($labels[$module]);
            $headers = ['MONTH', 'MALE', 'FEMALE', 'MCH', 'FCH', 'TOTAL'];
            foreach ($headers as $colIndex => $header) {
                $sheet->setCellValue([$colIndex + 1, 1], $header);
            }
            $styleHeader($sheet, 'F');
            foreach ($months_with_yr as $rowIndex => $m) {
                $rowNum = $rowIndex + 2;
                $sheet->setCellValue([1, $rowNum], $m);
                for ($col = 2; $col <= count($headers); $col++) {
                    $sheet->setCellValue([$col, $rowNum], 0);
                }
            }
        }

        foreach ($spreadsheet->getAllSheets() as $wSheet) {
            foreach ($wSheet->getColumnIterator() as $column) {
                $wSheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            }
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Template_' . ucfirst($module) . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}

// Fallback: Pure CSV generation with UTF-8 BOM (Always works, 0 dependencies, opens natively in Excel)
$yr_suffix = date('y');
$months_with_yr = [
    'Jan-' . $yr_suffix, 'Feb-' . $yr_suffix, 'Mar-' . $yr_suffix, 'Apr-' . $yr_suffix,
    'May-' . $yr_suffix, 'Jun-' . $yr_suffix, 'Jul-' . $yr_suffix, 'Aug-' . $yr_suffix,
    'Sep-' . $yr_suffix, 'Oct-' . $yr_suffix, 'Nov-' . $yr_suffix, 'Dec-' . $yr_suffix
];

$filename = 'Template_' . ucfirst($module) . '.csv';
$csvRows = [];

if ($module === 'op_census') {
    $filename = '1. OP CENSUS - Template.csv';
    $csvRows[] = ['MONTH', 'NEW MALE', 'NEW FEMALE', 'NEW M.CH', 'NEW F.CH', 'NEW TOTAL', 'OLD MALE', 'OLD FEMALE', 'OLD M.CH', 'OLD F.CH', 'OLD TOTAL', 'GRAND TOTAL'];
    foreach ($months_with_yr as $m) {
        $csvRows[] = [$m, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    }
} elseif ($module === 'ward_census') {
    $filename = '2. WARD CENSUS - Template.csv';
    $csvRows[] = ['MONTH', 'ADMISSION MALE', 'ADMISSION FEMALE', 'ADMISSION MCH', 'ADMISSION FCH', 'ADMISSION TOTAL', 'DISCHARGE MALE', 'DISCHARGE FEMALE', 'DISCHARGE MCH', 'DISCHARGE FCH', 'DISCHARGE TOTAL', 'AMA', 'DEATH'];
    foreach ($months_with_yr as $m) {
        $csvRows[] = [$m, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    }
} elseif ($module === 'elective_ot_census') {
    $filename = '3. ELECTIVE OT CENSUS - Template.csv';
    $csvRows[] = ['MONTH', 'MAJOR MALE', 'MAJOR FEMALE', 'MAJOR MCH', 'MAJOR FCH', 'MAJOR TOTAL', 'MINOR MALE', 'MINOR FEMALE', 'MINOR MCH', 'MINOR FCH', 'MINOR TOTAL', 'OVER ALL TOTAL', 'UNIT'];
    foreach ($months_with_yr as $m) {
        $csvRows[] = [$m, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, ''];
    }
} elseif ($module === 'minor_procedure_census') {
    $filename = '4. MINOR PROCEDURE CENSUS - Template.csv';
    $csvRows[] = ['DATE', 'MALE', 'FEMALE', 'MCH', 'FCH', 'TOTAL'];
    foreach ($months_with_yr as $m) {
        $csvRows[] = [$m, 0, 0, 0, 0, 0];
    }
} elseif ($module === 'speech_therapy_census') {
    $filename = '5. SPEECH THERAPY CENSUS - Template.csv';
    $csvRows[] = ['MONTH', 'NEW MALE', 'NEW FEMALE', 'NEW M.CH', 'NEW F.CH', 'NEW TOTAL', 'OLD MALE', 'OLD FEMALE', 'OLD M.CH', 'OLD F.CH', 'OLD TOTAL', 'GRAND TOTAL'];
    foreach ($months_with_yr as $m) {
        $csvRows[] = [$m, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    }
} elseif ($module === 'pta_census') {
    $filename = '6. PTA CENSUS - Template.csv';
    $csvRows[] = ['MONTH', 'MALE', 'FEMALE', 'MCH', 'FCH', 'TOTAL'];
    foreach ($months_with_yr as $m) {
        $csvRows[] = [$m, 0, 0, 0, 0, 0];
    }
} elseif ($module === 'bera_census') {
    $filename = '7. BERA CENSUS - Template.csv';
    $csvRows[] = ['MONTH', 'MALE', 'FEMALE', 'MCH', 'FCH', 'TOTAL'];
    foreach ($months_with_yr as $m) {
        $csvRows[] = [$m, 0, 0, 0, 0, 0];
    }
} elseif ($module === 'oae_census') {
    $filename = '8. OAE CENSUS - Template.csv';
    $csvRows[] = ['MONTH', 'MALE', 'FEMALE', 'MCH', 'FCH', 'TOTAL'];
    foreach ($months_with_yr as $m) {
        $csvRows[] = [$m, 0, 0, 0, 0, 0];
    }
} else {
    $filename = 'Template_' . ucfirst($module) . '.csv';
    $csvRows[] = ['MONTH', 'VALUE 1', 'VALUE 2', 'TOTAL'];
    foreach ($months_with_yr as $m) {
        $csvRows[] = [$m, 0, 0, 0];
    }
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Output UTF-8 BOM so Excel opens UTF-8 characters correctly
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
foreach ($csvRows as $row) {
    fputcsv($out, $row);
}
fclose($out);
exit;
