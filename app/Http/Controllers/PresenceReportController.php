<?php

namespace App\Http\Controllers;

use App\Jobs\GeneratePresenceReport;
use App\Models\PresenceReport;
use App\Models\Stream;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PresenceReportController extends Controller
{
    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'entry_camera' => ['required', 'string'],
            'exit_camera' => ['required', 'string'],
            'type' => ['required', 'in:day,week,month,year'],
            'break_minutes' => ['required', 'integer', 'min:1'],
            'person_id' => ['nullable', 'array'],
            'person_id.*' => ['integer'],
            'tag_id' => ['nullable', 'array'],
            'tag_id.*' => ['integer'],
        ]);

        $cameraNames = Stream::query()
            ->whereIn('uid', [$data['entry_camera'], $data['exit_camera']])
            ->pluck('name', 'uid');

        $data['entry_camera_name'] = $cameraNames[$data['entry_camera']] ?? $data['entry_camera'];
        $data['exit_camera_name'] = $cameraNames[$data['exit_camera']] ?? $data['exit_camera'];

        $report = PresenceReport::create([
            'user_id' => auth()->id(),
            'params' => $data,
            'status' => PresenceReport::STATUS_PENDING,
        ]);

        if (config('queue.default') === 'sync') {
            GeneratePresenceReport::dispatchAfterResponse($report->id);
        } else {
            GeneratePresenceReport::dispatch($report->id);
        }

        return response()->json(['report_id' => $report->id]);
    }

    public function status(PresenceReport $report): JsonResponse
    {
        $payload = ['status' => $report->status];

        if ($report->status === PresenceReport::STATUS_DONE) {
            $payload['html'] = view('components.presence-report', [
                'fragment' => 'table',
                'result' => $report->result,
            ])->render();
        } elseif ($report->status === PresenceReport::STATUS_FAILED) {
            $payload['error'] = $report->error ?: 'Не удалось сформировать отчёт';
        }

        return response()->json($payload);
    }

    public function export(Request $request, PresenceReport $report)
    {
        abort_unless($report->status === PresenceReport::STATUS_DONE && $report->result, 404);

        $model = $report;
        $result = $model->result;
        $spreadsheet = new Spreadsheet();

        if ($request->get('format') === 'csv') {
            $this->buildDataSheet($spreadsheet->getActiveSheet(), $result);
            $fileName = 'presence_report_' . $model->id . '.csv';
            $tmp = tempnam(sys_get_temp_dir(), 'presence');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
            $writer->setUseBOM(true);
            $writer->save($tmp);

            return response()->download($tmp, $fileName)->deleteFileAfterSend(true);
        }

        $this->buildResultSheet($spreadsheet->getActiveSheet(), $result);
        $this->buildDataSheet($spreadsheet->createSheet(), $result);
        $spreadsheet->setActiveSheetIndex(0);

        $fileName = 'presence_report_' . $model->id . '.xlsx';
        $tmp = tempnam(sys_get_temp_dir(), 'presence');
        (new Xlsx($spreadsheet))->save($tmp);

        return response()->download($tmp, $fileName)->deleteFileAfterSend(true);
    }

    protected function buildResultSheet($sheet, array $result): void
    {
        $sheet->setTitle('Результат');
        $periods = $result['periods'];
        $isWeek = ($result['type'] ?? 'day') === 'week';
        $topRow = 1;
        $subRow = $isWeek ? 3 : 2;
        $sheet->setCellValue('A' . $topRow, 'Фамилия');
        $sheet->mergeCells('A' . $topRow . ':A' . $subRow);

        $col = 2; // B
        foreach ($periods as $p) {
            $c1 = $this->colLetter($col);
            $c2 = $this->colLetter($col + 1);

            $sheet->setCellValue($c1 . $topRow, $p['label']);
            $sheet->mergeCells($c1 . $topRow . ':' . $c2 . $topRow);
            if ($isWeek) {
                $sheet->setCellValue($c1 . 2, $p['sublabel']);
                $sheet->mergeCells($c1 . 2 . ':' . $c2 . 2);
            }
            $sheet->setCellValue($c1 . $subRow, 'Время');
            $sheet->setCellValue($c2 . $subRow, 'кол-во');
            $col += 2;
        }
        $totalTimeCol = $this->colLetter($col);
        $totalCountCol = $this->colLetter($col + 1);
        $sheet->setCellValue($totalTimeCol . $topRow, 'Всего время');
        $sheet->mergeCells($totalTimeCol . $topRow . ':' . $totalCountCol . ($isWeek ? 2 : $topRow));
        $sheet->setCellValue($totalTimeCol . $subRow, 'Время');
        $sheet->setCellValue($totalCountCol . $subRow, 'сессий');

        $row = $subRow + 1;
        foreach ($result['persons'] as $person) {
            $sheet->setCellValue('A' . $row, $person['name']);
            $col = 2;
            foreach ($periods as $p) {
                $cell = $person['cells'][$p['key']] ?? null;
                $c1 = $this->colLetter($col);
                $c2 = $this->colLetter($col + 1);
                $sheet->setCellValue($c1 . $row, $cell && $cell['count'] > 0 ? $this->fmtTime($cell['time']) : '-');
                $sheet->setCellValue($c2 . $row, $cell && $cell['count'] > 0 ? $cell['count'] : '-');
                $col += 2;
            }
            $sheet->setCellValue($this->colLetter($col) . $row, $this->fmtTime($person['total_time']));
            $sheet->setCellValue($this->colLetter($col + 1) . $row, $person['total_count']);
            $row++;
        }

        $sheet->getStyle('A1:' . $totalCountCol . $subRow)
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getColumnDimension('A')->setWidth(32);
    }

    protected function buildDataSheet($sheet, array $result): void
    {
        $sheet->setTitle('Data');
        $periods = $result['periods'];

        $header = ['Фамилия'];
        foreach ($periods as $p) {
            $header[] = trim($p['label'] . ' ' . $p['sublabel']);
            $header[] = 'кол-во';
        }
        $header[] = 'Всего время';
        $header[] = 'сессий';
        $sheet->fromArray($header, null, 'A1');

        $row = 2;
        foreach ($result['persons'] as $person) {
            $line = [$person['name']];
            foreach ($periods as $p) {
                $cell = $person['cells'][$p['key']] ?? null;
                $line[] = $cell && $cell['count'] > 0 ? $this->fmtTime($cell['time']) : '-';
                $line[] = $cell && $cell['count'] > 0 ? $cell['count'] : '-';
            }
            $line[] = $this->fmtTime($person['total_time']);
            $line[] = $person['total_count'];
            $sheet->fromArray($line, null, 'A' . $row);
            $row++;
        }
    }

    protected function fmtTime(int $seconds): string
    {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;
        return sprintf('%d:%02d:%02d', $h, $m, $s);
    }

    protected function colLetter(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
    }
}
