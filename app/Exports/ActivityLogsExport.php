<?php

namespace App\Exports;

use App\Models\ActivityLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ActivityLogsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    public function __construct(
        private readonly Collection $activityLogs,
    ) {
    }

    public function collection(): Collection
    {
        return $this->activityLogs;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'No',
            'Waktu',
            'Aksi',
            'Tabel',
            'Data',
            'Detail',
            'User Agent',
        ];
    }

    /**
     * @param mixed $activityLog
     * @return list<string|int>
     */
    public function map($activityLog): array
    {
        if (!$activityLog instanceof ActivityLog) {
            return [];
        }

        $this->rowNumber++;

        return [
            $this->rowNumber,
            (string) optional($activityLog->timestamp)->format('Y-m-d H:i:s'),
            (string) $activityLog->action,
            (string) $activityLog->table_name,
            (string) $activityLog->data,
            (string) ($activityLog->details ?? ''),
            (string) ($activityLog->user_agent ?? ''),
        ];
    }
}
