<?php

namespace App\Imports;

use App\Models\Asset;
use App\Services\AssetOptionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class AssetsImport implements SkipsEmptyRows, ToCollection, WithCalculatedFormulas, WithHeadingRow
{
    use Importable;

    private int $createdCount = 0;

    private int $updatedCount = 0;

    private int $skippedCount = 0;

    /**
     * @var array<string, list<string>>
     */
    private array $assetOptions;

    public function __construct(
        private readonly AssetOptionService $assetOptionService,
    ) {
        $this->assetOptions = $this->assetOptionService->getOptions();
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $rowData = $row instanceof Collection ? $row : collect($row);

            $category = $this->readValue($rowData, ['kategori', 'category']);
            $brand = $this->readValue($rowData, ['merk', 'brand']);
            $model = $this->readValue($rowData, ['model_type_seri', 'model', 'type', 'seri']);
            $serialNumber = $this->readValue($rowData, ['serial_number', 'serial']);
            $barcodeCode = $this->readValue($rowData, ['kode_barcode', 'barcode_kode']);
            $barcodeBatang = $this->readValue($rowData, ['barcode_batang', 'barcode']);
            $statusRaw = $this->readValue($rowData, ['status']);
            $conditionRaw = $this->readValue($rowData, ['kondisi', 'condition']);

            if ($this->isEmptyRow([
                $category,
                $brand,
                $model,
                $serialNumber,
                $barcodeCode,
                $barcodeBatang,
                $statusRaw,
                $conditionRaw,
            ])) {
                $this->skippedCount++;
                continue;
            }

            if ($serialNumber === '' || $category === '' || $brand === '' || $model === '' || $statusRaw === '' || $conditionRaw === '') {
                $this->skippedCount++;
                continue;
            }

            $status = $this->normalizeStateValue($statusRaw);
            $condition = $this->normalizeStateValue($conditionRaw);

            if ($status === '' || $condition === '') {
                $this->skippedCount++;
                continue;
            }

            $barcodeValue = $barcodeBatang !== '' ? $barcodeBatang : $barcodeCode;
            if ($barcodeValue === '') {
                $barcodeValue = $serialNumber;
            }

            $this->appendOptionValue('categories', $category);
            $this->appendOptionValue('brands', $brand);
            $this->appendOptionValue('statuses', $status);
            $this->appendOptionValue('conditions', $condition);

            $payload = [
                'category' => $category,
                'brand' => $brand,
                'model' => $model,
                'serial_number' => $serialNumber,
                'barcode' => $barcodeValue,
                'status' => $status,
                'condition' => $condition,
                'qr_code_hash' => hash('sha256', $serialNumber),
            ];

            try {
                $existingAsset = Asset::query()->where('serial_number', $serialNumber)->first();

                if ($existingAsset !== null) {
                    if ($this->barcodeExists($payload['barcode'], $existingAsset->id)) {
                        $payload['barcode'] = (string) ($existingAsset->barcode ?: $existingAsset->serial_number);
                    }

                    $existingAsset->update($payload);
                    $this->updatedCount++;
                    continue;
                }

                if ($this->barcodeExists($payload['barcode'])) {
                    $payload['barcode'] = $serialNumber;
                }

                if ($this->barcodeExists($payload['barcode'])) {
                    $this->skippedCount++;
                    continue;
                }

                Asset::query()->create($payload);
                $this->createdCount++;
            } catch (Throwable) {
                $this->skippedCount++;
            }
        }

        $this->assetOptionService->saveOptions($this->assetOptions);
    }

    public function getCreatedCount(): int
    {
        return $this->createdCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    /**
     * @param Collection<int, mixed> $row
     * @param list<string> $keys
     */
    private function readValue(Collection $row, array $keys): string
    {
        foreach ($keys as $key) {
            $raw = $row->get($key);

            if ($raw === null) {
                continue;
            }

            $clean = trim((string) $raw);

            if ($clean === '') {
                continue;
            }

            return Str::limit($clean, 120, '');
        }

        return '';
    }

    /**
     * @param list<string> $values
     */
    private function isEmptyRow(array $values): bool
    {
        foreach ($values as $value) {
            if (trim($value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeStateValue(string $value): string
    {
        return Str::of($value)
            ->trim()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->limit(120, '')
            ->toString();
    }

    private function barcodeExists(string $barcode, ?int $ignoreAssetId = null): bool
    {
        $query = Asset::query()->where('barcode', $barcode);

        if ($ignoreAssetId !== null) {
            $query->where('id', '!=', $ignoreAssetId);
        }

        return $query->exists();
    }

    private function appendOptionValue(string $optionType, string $value): void
    {
        $cleanValue = trim($value);
        if ($cleanValue === '') {
            return;
        }

        $currentValues = $this->assetOptions[$optionType] ?? [];

        foreach ($currentValues as $currentValue) {
            if (Str::lower($currentValue) === Str::lower($cleanValue)) {
                return;
            }
        }

        $currentValues[] = Str::limit($cleanValue, 120, '');
        $this->assetOptions[$optionType] = $currentValues;
    }
}
