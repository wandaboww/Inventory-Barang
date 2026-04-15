<?php

namespace App\Exports;

use App\Models\Asset;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AssetsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    public function __construct(
        private readonly Collection $assets,
    ) {
    }

    public function collection(): Collection
    {
        return $this->assets;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'No',
            'Kategori',
            'Merk',
            'MODEL / TYPE / SERI',
            'Serial Number',
            'Kode Barcode',
            'Barcode Batang',
            'Status',
            'Kondisi',
        ];
    }

    /**
     * @param mixed $asset
     * @return list<string|int>
     */
    public function map($asset): array
    {
        if (!$asset instanceof Asset) {
            return [];
        }

        $this->rowNumber++;
        $barcodeValue = trim((string) ($asset->barcode ?: $asset->serial_number));

        return [
            $this->rowNumber,
            (string) $asset->category,
            (string) $asset->brand,
            (string) $asset->model,
            (string) $asset->serial_number,
            $barcodeValue,
            $barcodeValue,
            $this->formatOptionLabel((string) $asset->status),
            $this->formatOptionLabel((string) $asset->condition),
        ];
    }

    private function formatOptionLabel(string $value): string
    {
        return ucwords(str_replace(['_', '-'], ' ', trim($value)));
    }
}
