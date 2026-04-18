@extends('layouts.app')

@section('content')
    @php
        $printFormat = strtolower((string) request('format', 'a4'));
        $printFormat = in_array($printFormat, ['a4', 'label107', 'label103'], true) ? $printFormat : 'a4';

        $a4GridVariants = [
            'ringkas' => [
                'label' => 'Ringkas',
                'description' => '2 x 3 Grid (6 kartu)',
                'columns' => 2,
                'per_page' => 6,
                'barcode_width' => 1.25,
                'barcode_height' => 44,
                'card_min_height' => '79mm',
                'barcode_min_height' => '20mm',
                'capture_scale' => 2.15,
            ],
            'standar' => [
                'label' => 'Standar',
                'description' => '2 x 4 Grid (8 kartu)',
                'columns' => 2,
                'per_page' => 8,
                'barcode_width' => 1.18,
                'barcode_height' => 40,
                'card_min_height' => '64mm',
                'barcode_min_height' => '19mm',
                'capture_scale' => 2.2,
            ],
            'rapat' => [
                'label' => 'Rapat',
                'description' => '3 x 4 Grid (12 kartu)',
                'columns' => 3,
                'per_page' => 12,
                'barcode_width' => 1.05,
                'barcode_height' => 36,
                'card_min_height' => '57mm',
                'barcode_min_height' => '17mm',
                'capture_scale' => 2.2,
            ],
        ];

        $labelVariants = [
            'label107' => [
                'button_label' => 'Label 107',
                'label' => 'Label T&J 107',
                'paper_brand' => 'Tom & Jerry (T&J)',
                'paper_series' => 'No. 107',
                'dimensions' => '64 x 32 mm',
                'paper_label' => '21 x 16.5 cm',
                'paper_width' => 210,
                'paper_height' => 165,
                'orientation' => 'Portrait',
                'sheet_width' => '210mm',
                'sheet_height' => '165mm',
                'sheet_padding_top' => '11mm',
                'sheet_padding_right' => '4mm',
                'sheet_padding_bottom' => '11mm',
                'sheet_padding_left' => '4mm',
                'grid_columns' => 3,
                'grid_rows' => 4,
                'per_page' => 12,
                'label_width' => '64mm',
                'label_height' => '32mm',
                'label_gap' => '5mm',
                'grid_width' => '202mm',
                'grid_height' => '143mm',
                'barcode_width' => 1.15,
                'barcode_height' => 30,
                'capture_scale' => 2.5,
                'file_suffix' => 'label-tj-107',
            ],
            'label103' => [
                'button_label' => 'Label 103',
                'label' => 'Label T&J 103',
                'paper_brand' => 'Tom & Jerry (T&J)',
                'paper_series' => 'No. 103',
                'dimensions' => '103 x 50 mm',
                'paper_label' => 'A4 (210 x 297 mm)',
                'paper_width' => 210,
                'paper_height' => 297,
                'orientation' => 'Portrait',
                'sheet_width' => '210mm',
                'sheet_height' => '297mm',
                'sheet_padding_top' => '58.5mm',
                'sheet_padding_right' => '30mm',
                'sheet_padding_bottom' => '58.5mm',
                'sheet_padding_left' => '30mm',
                'grid_columns' => 3,
                'grid_rows' => 10,
                'per_page' => 30,
                'label_width' => '50mm',
                'label_height' => '18mm',
                'label_gap' => '0mm',
                'grid_width' => '150mm',
                'grid_height' => '180mm',
                'barcode_width' => 1,
                'barcode_height' => 26,
                'capture_scale' => 2.6,
                'file_suffix' => 'label-tj-103',
            ],
        ];

        $gridAliases = [
            '2x3' => 'ringkas',
            '2x4' => 'standar',
            '3x4' => 'rapat',
        ];

        $selectedGridKey = strtolower((string) request('grid', 'rapat'));
        $selectedGridKey = $gridAliases[$selectedGridKey] ?? $selectedGridKey;
        $selectedGridKey = array_key_exists($selectedGridKey, $a4GridVariants) ? $selectedGridKey : 'rapat';
        $selectedGrid = $a4GridVariants[$selectedGridKey];
        $selectedLabelKey = array_key_exists($printFormat, $labelVariants) ? $printFormat : 'label107';
        $selectedLabel = $labelVariants[$selectedLabelKey];
        $allAssets = $allAssets ?? $assets;
        $selectedAssetIds = array_values(array_unique(array_map(static fn ($id): int => (int) $id, $selectedAssetIds ?? [])));
        $selectedAssetIdMap = array_fill_keys($selectedAssetIds, true);
        $totalAssetsCount = $allAssets->count();
        $selectedAssetsCount = $assets->count();
        $selectionResetQuery = ['format' => $printFormat];
        $conditionLabelMap = [
            'good' => 'Baik',
            'minor_damage' => 'Rusak Ringan',
            'major_damage' => 'Rusak Berat',
            '-' => 'Tidak Diisi',
        ];
        $masterAssetOptions = is_array($masterAssetOptions ?? null) ? $masterAssetOptions : [];
        $selectorCategoryOptions = collect($masterAssetOptions['categories'] ?? [])
            ->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(static fn ($value): string => trim((string) $value))
            ->unique(static fn (string $value): string => strtolower($value))
            ->values();
        $selectorConditionOptions = collect($masterAssetOptions['conditions'] ?? [])
            ->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(static fn ($value): string => trim((string) $value))
            ->unique(static fn (string $value): string => strtolower($value))
            ->values();
        $selectorCategoryOptionMap = $selectorCategoryOptions
            ->mapWithKeys(static fn (string $value): array => [strtolower(trim($value)) => true])
            ->all();
        $selectorConditionOptionMap = $selectorConditionOptions
            ->mapWithKeys(static fn (string $value): array => [strtolower(trim($value)) => true])
            ->all();
        $selectorSearchKeyword = trim((string) request('selector_search', ''));
        $requestedSelectorCategoryFilter = strtolower(trim((string) request('selector_category', '')));
        $requestedSelectorConditionFilter = strtolower(trim((string) request('selector_condition', '')));
        $selectorCategoryFilter = array_key_exists($requestedSelectorCategoryFilter, $selectorCategoryOptionMap)
            ? $requestedSelectorCategoryFilter
            : '';
        $selectorConditionFilter = array_key_exists($requestedSelectorConditionFilter, $selectorConditionOptionMap)
            ? $requestedSelectorConditionFilter
            : '';

        if ($printFormat === 'a4') {
            $selectionResetQuery['grid'] = $selectedGridKey;
        }

        $a4Pages = $assets->chunk($selectedGrid['per_page']);
        $labelChunks = $assets->chunk($selectedLabel['per_page']);
        $printDate = now()->locale('id')->translatedFormat('d F Y, H:i');
    @endphp

    <div class="barcode-page-shell" id="barcodePageShell">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
            <div>
                <h4 class="mb-1">Barcode Barang</h4>
                <p class="text-muted mb-0">Pilih ukuran kertas dulu, lalu preset grid A4 muncul di panel kiri untuk mengatur kepadatan kartu barcode.</p>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white fw-semibold d-flex justify-content-between align-items-center">
                        <span>Setting Barcode</span>
                        <span class="badge text-bg-light text-primary">
                            {{ $printFormat === 'a4' ? $selectedGrid['label'] : $selectedLabel['label'] }}
                        </span>
                    </div>
                    <div class="card-body d-flex flex-column gap-3">
                        <div>
                            <label class="barcode-settings-label">Ukuran kertas</label>
                            <div class="d-flex flex-wrap gap-2">
                                <a
                                    href="{{ request()->fullUrlWithQuery(['format' => 'a4']) }}"
                                    class="btn {{ $printFormat === 'a4' ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill px-4"
                                >
                                    A4 Grid
                                </a>
                                <a
                                    href="{{ request()->fullUrlWithQuery(['format' => 'label107']) }}"
                                    class="btn {{ $printFormat === 'label107' ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill px-4"
                                >
                                    Label 107
                                </a>
                                <a
                                    href="{{ request()->fullUrlWithQuery(['format' => 'label103']) }}"
                                    class="btn {{ $printFormat === 'label103' ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill px-4"
                                >
                                    Label 103
                                </a>
                            </div>
                        </div>

                        @if($printFormat === 'a4')
                            <div>
                                <label class="barcode-settings-label">Preset grid A4</label>
                                <div class="dropdown barcode-download-group w-100">
                                    <button class="btn btn-outline-primary dropdown-toggle w-100 d-flex align-items-center justify-content-between" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <span><i class="fa-solid fa-border-all me-2"></i>{{ $selectedGrid['label'] }}</span>
                                    </button>
                                    <ul class="dropdown-menu w-100">
                                        @foreach($a4GridVariants as $gridKey => $gridVariant)
                                            <li>
                                                <a
                                                    class="dropdown-item {{ $selectedGridKey === $gridKey ? 'active' : '' }}"
                                                    href="{{ request()->fullUrlWithQuery(['grid' => $gridKey]) }}"
                                                >
                                                    {{ $gridVariant['label'] }} <span class="text-muted">({{ $gridVariant['description'] }})</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        <div>
                            <label class="barcode-settings-label">Pilih Barcode yang Dicetak</label>
                            <form method="GET" action="{{ route('admin.loans.index') }}" id="barcodeSelectionForm" class="d-flex flex-column gap-2">
                                <input type="hidden" name="format" value="{{ $printFormat }}">
                                <input type="hidden" name="selection_mode" value="custom">
                                @if($printFormat === 'a4')
                                    <input type="hidden" name="grid" value="{{ $selectedGridKey }}">
                                @endif

                                <div class="d-flex align-items-center gap-2">
                                    <input
                                        type="search"
                                        id="barcodeSelectorSearchInput"
                                        name="selector_search"
                                        value="{{ $selectorSearchKeyword }}"
                                        class="form-control form-control-sm"
                                        placeholder="Cari serial, barcode, merek, model"
                                        autocomplete="off"
                                    >
                                    <span class="badge text-bg-primary text-nowrap" id="barcodeSelectedCountBadge">
                                        {{ number_format($selectedAssetsCount) }}
                                    </span>
                                </div>

                                <div class="barcode-selector-filters">
                                    <div>
                                        <label class="form-label form-label-sm mb-1 small text-muted" for="barcodeCategoryFilterInput">Filter kategori</label>
                                        <select id="barcodeCategoryFilterInput" name="selector_category" class="form-select form-select-sm">
                                            <option value="">Semua kategori</option>
                                            @foreach($selectorCategoryOptions as $categoryOption)
                                                @php
                                                    $categoryFilterValue = strtolower(trim((string) $categoryOption));
                                                @endphp
                                                <option value="{{ $categoryFilterValue }}" @selected($selectorCategoryFilter === $categoryFilterValue)>{{ $categoryOption }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label form-label-sm mb-1 small text-muted" for="barcodeConditionFilterInput">Filter kondisi</label>
                                        <select id="barcodeConditionFilterInput" name="selector_condition" class="form-select form-select-sm">
                                            <option value="">Semua kondisi</option>
                                            @foreach($selectorConditionOptions as $conditionOption)
                                                @php
                                                    $conditionFilterValue = strtolower(trim((string) $conditionOption));
                                                    $conditionFilterLabel = (string) $conditionOption;
                                                @endphp
                                                <option value="{{ $conditionFilterValue }}" @selected($selectorConditionFilter === $conditionFilterValue)>{{ $conditionFilterLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-check user-select-none">
                                    <input class="form-check-input" type="checkbox" id="barcodeSelectAllToggle" @checked($totalAssetsCount > 0 && $selectedAssetsCount === $totalAssetsCount)>
                                    <label class="form-check-label small fw-semibold" for="barcodeSelectAllToggle">
                                        Pilih semua barcode
                                    </label>
                                </div>

                                <div class="barcode-selector-list border rounded-3 p-2">
                                    @forelse($allAssets as $selectorAsset)
                                        @php
                                            $selectorBarcode = (string) ($selectorAsset->barcode ?: $selectorAsset->serial_number);
                                            $selectorCategory = ($value = trim((string) ($selectorAsset->category ?? ''))) !== '' ? $value : '-';
                                            $selectorCategoryFilter = strtolower($selectorCategory);
                                            $selectorCondition = ($value = strtolower(trim((string) ($selectorAsset->condition ?? '')))) !== '' ? $value : '-';
                                            $selectorConditionLabel = $conditionLabelMap[$selectorCondition] ?? ucwords(str_replace('_', ' ', $selectorCondition));
                                            $searchLabel = strtolower(trim($selectorBarcode . ' ' . $selectorAsset->serial_number . ' ' . $selectorAsset->brand . ' ' . $selectorAsset->model . ' ' . $selectorCategory . ' ' . $selectorConditionLabel));
                                        @endphp
                                        <label class="barcode-selector-item form-check mb-0" data-barcode-selector-row data-search-text="{{ $searchLabel }}" data-category="{{ $selectorCategoryFilter }}" data-condition="{{ $selectorCondition }}">
                                            <input
                                                class="form-check-input barcode-selector-checkbox"
                                                type="checkbox"
                                                name="selected_assets[]"
                                                value="{{ $selectorAsset->id }}"
                                                data-barcode-selector-item
                                                @checked(isset($selectedAssetIdMap[(int) $selectorAsset->id]))
                                            >
                                            <span class="barcode-selector-label">
                                                <span class="barcode-selector-code">{{ $selectorBarcode }}</span>
                                                <span class="barcode-selector-meta">{{ $selectorAsset->brand }} {{ $selectorAsset->model }} • {{ $selectorCategory }} • {{ $selectorConditionLabel }}</span>
                                            </span>
                                        </label>
                                    @empty
                                        <div class="small text-muted py-2 px-1">Belum ada data aset untuk dipilih.</div>
                                    @endforelse
                                </div>

                                <div class="d-flex flex-wrap gap-2">
                                    <button type="submit" class="btn btn-sm btn-outline-primary flex-fill">
                                        <i class="fa-solid fa-check me-1"></i>Terapkan Pilihan
                                    </button>
                                    <a href="{{ route('admin.loans.index', $selectionResetQuery) }}" class="btn btn-sm btn-outline-secondary">
                                        Reset
                                    </a>
                                </div>

                                <div class="small text-muted">
                                    Preview dan print hanya menampilkan barcode yang dipilih.
                                </div>
                            </form>
                        </div>

                        @if($printFormat !== 'a4')
                            <div class="barcode-inline-settings-row">
                                <div class="barcode-inline-settings-item">
                                    <label class="barcode-settings-label">Border Konten Label</label>
                                    <div class="dropdown barcode-download-group w-100">
                                        <button class="btn btn-outline-secondary dropdown-toggle w-100 d-flex align-items-center justify-content-between" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="labelBorderToggleButton">
                                            <span><i class="fa-solid fa-border-all me-2"></i><span id="labelBorderToggleText">Border ON</span></span>
                                        </button>
                                        <ul class="dropdown-menu w-100">
                                            <li>
                                                <button class="dropdown-item active" type="button" data-label-content-border="on">Border ON</button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item" type="button" data-label-content-border="off">Border OFF</button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="barcode-inline-settings-item">
                                    <label class="barcode-settings-label">Border Grid Label</label>
                                    <div class="dropdown barcode-download-group w-100">
                                        <button class="btn btn-outline-secondary dropdown-toggle w-100 d-flex align-items-center justify-content-between" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="labelGridBorderToggleButton">
                                            <span><i class="fa-solid fa-border-none me-2"></i><span id="labelGridBorderToggleText">Grid Border ON</span></span>
                                        </button>
                                        <ul class="dropdown-menu w-100">
                                            <li>
                                                <button class="dropdown-item active" type="button" data-label-grid-border="on">Grid Border ON</button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item" type="button" data-label-grid-border="off">Grid Border OFF</button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="barcode-action-buttons">
                            <button type="button" class="btn btn-primary" id="printBarcodeButton">
                                <i class="fa-solid fa-print me-2"></i>Print Epson L4150
                            </button>
                        </div>

                        <div class="small text-muted">
                            @if($printFormat === 'a4')
                                {{ $selectedGrid['label'] }} menampilkan {{ $selectedGrid['per_page'] }} kartu per halaman dari total {{ number_format($selectedAssetsCount) }} aset terpilih.
                            @else
                                {{ $selectedLabel['label'] }} ({{ $selectedLabel['dimensions'] }}) — {{ $selectedLabel['grid_columns'] }} kolom x {{ $selectedLabel['grid_rows'] }} baris, {{ $selectedLabel['per_page'] }} label per lembar {{ $selectedLabel['paper_label'] }}, untuk {{ number_format($selectedAssetsCount) }} aset terpilih.
                            @endif
                        </div>

                        @if($printFormat !== 'a4')
                        {{-- Dropdown info spesifikasi kertas label --}}
                        <div>
                            <div class="accordion accordion-flush" id="labelInfoAccordion">
                                <div class="accordion-item border rounded">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed py-2 px-3 small fw-semibold" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#labelInfoBody"
                                                aria-expanded="false" aria-controls="labelInfoBody">
                                            <i class="fa-solid fa-circle-info me-2 text-primary"></i>
                                            Spesifikasi Kertas
                                        </button>
                                    </h2>
                                    <div id="labelInfoBody" class="accordion-collapse collapse">
                                        <div class="accordion-body py-2 px-3">
                                            <table class="table table-sm table-borderless small mb-0">
                                                <tbody>
                                                    <tr><td class="text-muted ps-0 py-1">Merek</td><td class="fw-semibold">{{ $selectedLabel['paper_brand'] }}</td></tr>
                                                    <tr><td class="text-muted ps-0 py-1">Nomor seri</td><td class="fw-semibold">{{ $selectedLabel['paper_series'] }}</td></tr>
                                                    <tr><td class="text-muted ps-0 py-1">Ukuran lembar</td><td class="fw-semibold">{{ $selectedLabel['paper_label'] }}</td></tr>
                                                    <tr><td class="text-muted ps-0 py-1">Orientasi</td><td class="fw-semibold">{{ $selectedLabel['orientation'] }}</td></tr>
                                                    <tr><td class="text-muted ps-0 py-1">Ukuran label</td><td class="fw-semibold">{{ $selectedLabel['dimensions'] }}</td></tr>
                                                    <tr><td class="text-muted ps-0 py-1">Susunan</td><td class="fw-semibold">{{ $selectedLabel['grid_columns'] }} kolom × {{ $selectedLabel['grid_rows'] }} baris</td></tr>
                                                    <tr><td class="text-muted ps-0 py-1">Jarak antar label</td><td class="fw-semibold">{{ str_replace('mm', ' mm', $selectedLabel['label_gap']) }}</td></tr>
                                                    <tr><td class="text-muted ps-0 py-1">Area grid</td><td class="fw-semibold">{{ str_replace('mm', ' mm', $selectedLabel['grid_width']) }} × {{ str_replace('mm', ' mm', $selectedLabel['grid_height']) }}</td></tr>
                                                    <tr><td class="text-muted ps-0 py-1">Padding lembar (T/R/B/L)</td><td class="fw-semibold">{{ str_replace('mm', ' mm', $selectedLabel['sheet_padding_top']) }} / {{ str_replace('mm', ' mm', $selectedLabel['sheet_padding_right']) }} / {{ str_replace('mm', ' mm', $selectedLabel['sheet_padding_bottom']) }} / {{ str_replace('mm', ' mm', $selectedLabel['sheet_padding_left']) }}</td></tr>
                                                    <tr><td class="text-muted ps-0 py-1">Label / hal</td><td class="fw-semibold">{{ $selectedLabel['per_page'] }} label</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($printFormat !== 'a4')
                        <div class="accordion accordion-flush" id="labelCalibrationAccordion">
                            <div class="accordion-item border rounded">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed py-2 px-3 small fw-semibold" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#labelCalibrationBody"
                                            aria-expanded="false" aria-controls="labelCalibrationBody">
                                        <i class="fa-solid fa-sliders me-2 text-primary"></i>
                                        Kalibrasi Posisi Cetak
                                    </button>
                                </h2>
                                <div id="labelCalibrationBody" class="accordion-collapse collapse">
                                    <div class="accordion-body py-2 px-3" id="labelCalibrationPanel">
                                        <div class="cal-hint mb-2">
                                            Penyesuaian berlaku di Preview <strong>dan</strong> saat Cetak/PDF.
                                            Gunakan langkah kecil 0.5 mm untuk akurasi tinggi.
                                        </div>

                                        {{-- Offset X --}}
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="small text-muted">Geser Horizontal</span>
                                                <span class="badge text-bg-secondary" id="calXDisplay">0 mm</span>
                                            </div>
                                            <input type="range" class="form-range" id="calOffsetX"
                                                   min="-15" max="15" step="0.5" value="0">
                                            <div class="d-flex gap-1 mt-1">
                                                <button type="button" class="btn btn-outline-secondary btn-sm flex-fill cal-step-btn"
                                                        data-axis="x" data-dir="-1">
                                                    <i class="fa-solid fa-arrow-left"></i> Kiri
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm flex-fill cal-step-btn"
                                                        data-axis="x" data-dir="1">
                                                    Kanan <i class="fa-solid fa-arrow-right"></i>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Offset Y --}}
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="small text-muted">Geser Vertikal</span>
                                                <span class="badge text-bg-secondary" id="calYDisplay">0 mm</span>
                                            </div>
                                            <input type="range" class="form-range" id="calOffsetY"
                                                   min="-15" max="15" step="0.5" value="0">
                                            <div class="d-flex gap-1 mt-1">
                                                <button type="button" class="btn btn-outline-secondary btn-sm flex-fill cal-step-btn"
                                                        data-axis="y" data-dir="-1">
                                                    <i class="fa-solid fa-arrow-up"></i> Atas
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm flex-fill cal-step-btn"
                                                        data-axis="y" data-dir="1">
                                                    Bawah <i class="fa-solid fa-arrow-down"></i>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Skala --}}
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="small text-muted">Skala Grid</span>
                                                <span class="badge text-bg-secondary" id="calScaleDisplay">100%</span>
                                            </div>
                                            <input type="range" class="form-range" id="calScale"
                                                   min="90" max="110" step="0.5" value="100">
                                        </div>

                                        <button type="button" class="btn btn-outline-danger btn-sm w-100" id="calResetBtn">
                                            <i class="fa-solid fa-rotate-left me-1"></i> Reset Posisi
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span>Container Preview</span>
                            <div class="small text-muted fw-normal">Format {{ $printFormat === 'a4' ? $selectedGrid['label'] : $selectedLabel['label'] }}</div>
                        </div>
                        <span class="badge text-bg-primary">{{ number_format($selectedAssetsCount) }} / {{ number_format($totalAssetsCount) }} aset</span>
                    </div>
                    <div class="card-body">
                        <div class="barcode-preview-stage">
                            @if($assets->isNotEmpty())
                                @if($printFormat === 'a4')
                                    <div class="barcode-sheet-stack">
                                        @foreach($a4Pages as $pageIndex => $pageAssets)
                                            <div
                                                class="barcode-sheet barcode-sheet--a4"
                                                data-barcode-page
                                                data-page-index="{{ $pageIndex }}"
                                                data-page-total="{{ $a4Pages->count() }}"
                                                style="--barcode-a4-grid-columns: {{ $selectedGrid['columns'] }}; --barcode-a4-grid-card-min-height: {{ $selectedGrid['card_min_height'] }}; --barcode-a4-grid-barcode-min-height: {{ $selectedGrid['barcode_min_height'] }};"
                                            >
                                                <div class="barcode-sheet-topbar"></div>
                                                <div class="barcode-sheet-header barcode-sheet-header--grid">
                                                    <div>
                                                        <div class="barcode-sheet-kicker">Inventory Barang</div>
                                                        <h2 class="barcode-sheet-title">A4 Grid {{ $selectedGrid['label'] }}</h2>
                                                        <div class="barcode-sheet-subtitle">{{ $selectedGrid['description'] }} • {{ $pageAssets->count() }} kartu pada halaman ini</div>
                                                    </div>
                                                    <div class="barcode-sheet-page-chip">Halaman {{ $pageIndex + 1 }}/{{ $a4Pages->count() }}</div>
                                                </div>

                                                <div class="barcode-a4-grid">
                                                    @foreach($pageAssets as $asset)
                                                        @php
                                                            $assetBarcode = (string) ($asset->barcode ?: $asset->serial_number);
                                                            $barcodeSvgId = 'barcode-grid-svg-' . $asset->id . '-' . $pageIndex;
                                                        @endphp
                                                        <div class="barcode-grid-card">
                                                            <div class="barcode-grid-card-meta">
                                                                <div class="barcode-grid-card-brand">{{ $asset->brand }} {{ $asset->model }}</div>
                                                                <div class="barcode-grid-card-serial">{{ $asset->serial_number }}</div>
                                                            </div>

                                                            <div class="barcode-grid-card-barcode">
                                                                <svg
                                                                    id="{{ $barcodeSvgId }}"
                                                                    class="barcode-grid-svg"
                                                                    data-barcode-svg
                                                                    data-barcode-value="{{ $assetBarcode }}"
                                                                    data-barcode-width="{{ $selectedGrid['barcode_width'] }}"
                                                                    data-barcode-height="{{ $selectedGrid['barcode_height'] }}"
                                                                    role="img"
                                                                    aria-label="Barcode {{ $assetBarcode }}"
                                                                ></svg>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    {{-- Dynamic label grid per selected format --}}
                                    <div class="barcode-sheet-stack">
                                        @foreach($labelChunks as $lblPageIndex => $lblPageAssets)
                                            <div
                                                class="barcode-sheet barcode-label-sheet"
                                                data-barcode-page
                                                data-page-index="{{ $lblPageIndex }}"
                                                data-page-total="{{ $labelChunks->count() }}"
                                                style="--barcode-label-sheet-width: {{ $selectedLabel['sheet_width'] }}; --barcode-label-sheet-height: {{ $selectedLabel['sheet_height'] }}; --barcode-label-sheet-padding-top: {{ $selectedLabel['sheet_padding_top'] }}; --barcode-label-sheet-padding-right: {{ $selectedLabel['sheet_padding_right'] }}; --barcode-label-sheet-padding-bottom: {{ $selectedLabel['sheet_padding_bottom'] }}; --barcode-label-sheet-padding-left: {{ $selectedLabel['sheet_padding_left'] }}; --barcode-label-grid-columns: {{ $selectedLabel['grid_columns'] }}; --barcode-label-grid-rows: {{ $selectedLabel['grid_rows'] }}; --barcode-label-width: {{ $selectedLabel['label_width'] }}; --barcode-label-height: {{ $selectedLabel['label_height'] }}; --barcode-label-gap: {{ $selectedLabel['label_gap'] }}; --barcode-label-grid-width: {{ $selectedLabel['grid_width'] }}; --barcode-label-grid-height: {{ $selectedLabel['grid_height'] }};"
                                            >
                                                <div class="barcode-sheet-topbar"></div>
                                                <div class="barcode-sheet-header barcode-sheet-header--grid">
                                                    <div>
                                                        <div class="barcode-sheet-kicker">Inventory Barang</div>
                                                        <h2 class="barcode-sheet-title">{{ $selectedLabel['label'] }}</h2>
                                                        <div class="barcode-sheet-subtitle">{{ $selectedLabel['paper_label'] }} · {{ $selectedLabel['grid_columns'] }}×{{ $selectedLabel['grid_rows'] }} · {{ $lblPageAssets->count() }} label pada halaman ini</div>
                                                    </div>
                                                    <div class="barcode-sheet-page-chip">
                                                        Hal {{ $lblPageIndex + 1 }}/{{ $labelChunks->count() }}
                                                    </div>
                                                </div>

                                                <div class="barcode-label-grid">
                                                    @foreach($lblPageAssets as $lblAsset)
                                                        @php $lblBarcode = (string)($lblAsset->barcode ?: $lblAsset->serial_number); @endphp
                                                        <div class="barcode-label-cell {{ $selectedLabelKey === 'label107' ? 'barcode-label-cell--label107' : '' }}">
                                                            <div class="barcode-label-content {{ $selectedLabelKey === 'label107' ? 'barcode-label-content--label107' : '' }}">
                                                                <div class="barcode-label-category">{{ $lblAsset->category }}</div>
                                                                <div class="barcode-label-name">{{ $lblAsset->brand }} {{ $lblAsset->model }}</div>
                                                                <div class="barcode-label-barcode">
                                                                    <svg
                                                                        class="barcode-label-svg"
                                                                        data-barcode-svg
                                                                        data-barcode-value="{{ $lblBarcode }}"
                                                                        data-barcode-width="{{ $selectedLabel['barcode_width'] }}"
                                                                        data-barcode-height="{{ $selectedLabel['barcode_height'] }}"
                                                                        role="img"
                                                                        aria-label="Barcode {{ $lblBarcode }}"
                                                                    ></svg>
                                                                </div>
                                                                <div class="barcode-label-date">Cetak: {{ $printDate }}</div>
                                                                <div class="barcode-label-dept">Pengembangan Perangkat Lunak &amp; Gim</div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                    @for($i = $lblPageAssets->count(); $i < $selectedLabel['per_page']; $i++)
                                                        <div class="barcode-label-cell barcode-label-cell--empty {{ $selectedLabelKey === 'label107' ? 'barcode-label-cell--label107' : '' }}"></div>
                                                    @endfor
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @elseif($allAssets->isNotEmpty())
                                <div class="barcode-empty-state w-100">
                                    <div class="barcode-empty-icon"><i class="fa-solid fa-list-check"></i></div>
                                    <h5 class="mb-2">Belum ada barcode dipilih untuk dicetak</h5>
                                    <p class="text-muted mb-0">Pilih minimal satu barcode pada panel Setting Barcode agar preview langsung muncul di sini.</p>
                                </div>
                            @else
                                <div class="barcode-empty-state w-100">
                                    <div class="barcode-empty-icon"><i class="fa-solid fa-barcode"></i></div>
                                    <h5 class="mb-2">Belum ada aset</h5>
                                    <p class="text-muted mb-0">Tambahkan data aset terlebih dahulu agar barcode bisa dipreview dan dicetak.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .barcode-page-shell {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding-bottom: 1rem;
        }

        .barcode-settings-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #64748b;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .barcode-download-group .dropdown-menu {
            min-width: 100%;
        }

        .barcode-selector-list {
            max-height: 270px;
            overflow: auto;
            background: #f8fbff;
        }

        .barcode-selector-filters {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.5rem;
        }

        .barcode-selector-item {
            display: flex;
            align-items: flex-start;
            gap: 0.55rem;
            padding: 0.45rem 0.5rem;
            border-radius: 0.6rem;
            cursor: pointer;
        }

        .barcode-selector-item:hover {
            background: #ebf3ff;
        }

        .barcode-selector-item .form-check-input {
            margin-top: 0.18rem;
            margin-left: 0;
        }

        .barcode-selector-item.is-hidden {
            display: none !important;
        }

        .barcode-selector-label {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .barcode-selector-code {
            color: #0f172a;
            font-size: 0.82rem;
            font-weight: 700;
            line-height: 1.25;
            word-break: break-word;
        }

        .barcode-selector-meta {
            color: #64748b;
            font-size: 0.76rem;
            line-height: 1.2;
            word-break: break-word;
        }

        .barcode-inline-settings-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.6rem;
            align-items: end;
        }

        .barcode-inline-settings-item {
            min-width: 0;
        }

        .barcode-action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .barcode-action-buttons > .btn {
            flex: 1 1 100%;
            width: 100%;
        }

        #barcodePageShell.barcode-content-border-off .barcode-label-content {
            border: none !important;
        }

        #barcodePageShell.barcode-grid-border-off .barcode-label-grid {
            border: none !important;
        }

        #barcodePageShell.barcode-grid-border-off .barcode-label-cell {
            border: none !important;
        }

        .barcode-preview-stage {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            min-height: 650px;
            padding: 1rem;
            overflow: auto;
            border: 1px dashed #c9d6ea;
            border-radius: 1.25rem;
            background: linear-gradient(180deg, rgba(13, 110, 253, 0.08), rgba(255, 255, 255, 0.75));
        }

        .barcode-sheet-stack {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            align-items: center;
            width: 100%;
        }

        .barcode-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 520px;
            padding: 2rem;
            border: 1px dashed #d4dcec;
            border-radius: 1rem;
            background: #fff;
            text-align: center;
        }

        .barcode-empty-icon {
            width: 72px;
            height: 72px;
            margin-bottom: 1rem;
            border-radius: 999px;
            background: #e7f0ff;
            color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .barcode-sheet {
            position: relative;
            overflow: hidden;
            border: 1px solid #d4ddec;
            border-radius: 1rem;
            color: #1f2937;
            background: #fff;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16);
        }

        .barcode-sheet::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, #0d6efd, #2d7ff9, #6bb4ff);
        }

        .barcode-sheet--a4 {
            width: 210mm;
            min-height: 297mm;
            padding: 10mm 9mm 9mm;
        }

        .barcode-sheet--label {
            width: var(--barcode-label-sheet-width, 107mm);
            min-height: var(--barcode-label-sheet-height, 50mm);
            padding: 4.5mm 5mm 4mm;
        }

        .barcode-sheet-topbar {
            height: 2px;
            background: rgba(13, 110, 253, 0.12);
        }

        .barcode-sheet-header {
            margin-bottom: 1rem;
            text-align: center;
        }

        .barcode-sheet-header--label {
            margin-bottom: 0.65rem;
        }

        .barcode-sheet-header--grid {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            text-align: left;
            margin-bottom: 0.85rem;
        }

        .barcode-sheet-page-chip {
            flex-shrink: 0;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: #eaf2ff;
            color: #0d6efd;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .barcode-sheet-kicker {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.6rem;
            padding: 0.28rem 0.7rem;
            border-radius: 999px;
            background: #eaf2ff;
            color: #0d6efd;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .barcode-sheet-title {
            margin: 0;
            color: #0f172a;
            font-weight: 800;
            line-height: 1.15;
        }

        .barcode-sheet--a4 .barcode-sheet-title {
            font-size: 1.15rem;
        }

        .barcode-sheet--label .barcode-sheet-title {
            font-size: 1rem;
        }

        .barcode-a4-grid {
            display: grid;
            grid-template-columns: repeat(var(--barcode-a4-grid-columns, 3), minmax(0, 1fr));
            gap: 2.5mm;
        }

        .barcode-grid-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 2mm;
            min-height: var(--barcode-a4-grid-card-min-height, 57mm);
            padding: 2.8mm;
            border: 1px solid #dbe6f3;
            border-radius: 0.8rem;
            background: linear-gradient(180deg, #ffffff, #f8fbff);
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.04);
            break-inside: avoid;
        }

        .barcode-grid-card-meta {
            min-width: 0;
        }

        .barcode-grid-card-brand {
            color: #0f172a;
            font-size: 0.84rem;
            font-weight: 800;
            line-height: 1.08;
        }

        .barcode-grid-card-serial {
            margin-top: 0.15rem;
            color: #64748b;
            font-size: 0.68rem;
        }

        .barcode-grid-card-barcode {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1.2mm;
            padding: 1.5mm 1.8mm;
            border: 1px dashed #d4e0f0;
            border-radius: 0.6rem;
            background: #fff;
        }

        .barcode-grid-svg {
            width: 100%;
            height: auto;
            min-height: var(--barcode-a4-grid-barcode-min-height, 17mm);
        }

        .barcode-sheet-barcode-zone {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            padding: 1rem;
            border: 1px dashed #cbd9ee;
            border-radius: 1rem;
            background: linear-gradient(180deg, #ffffff, #f8fbff);
        }

        .barcode-preview-svg {
            width: 100%;
            height: auto;
            min-height: 56px;
        }

        .barcode-sheet--label .barcode-preview-svg {
            min-height: 58px;
        }

        .barcode-sheet-footer {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        @media (max-width: 1199.98px) {
            .barcode-preview-stage {
                min-height: 520px;
            }
        }

        @media (max-width: 575.98px) {
            .barcode-selector-filters {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        /* ── Calibration Panel ──────────────────────────────────── */
        #labelCalibrationPanel {
            padding-top: 0;
            border-top: none;
            margin-top: 0;
        }
        .cal-hint {
            font-size: .68rem;
            color: #6d7f9d;
            line-height: 1.35;
        }
        #calOffsetX, #calOffsetY, #calScale {
            accent-color: #0d6efd;
        }

        /* ── Label 107 / 103 grid ───────────────────────────────── */
        .barcode-label-sheet {
            width: var(--barcode-label-sheet-width, 210mm);
            min-height: var(--barcode-label-sheet-height, 297mm);
            padding: var(--barcode-label-sheet-padding-top, 58.5mm)
                var(--barcode-label-sheet-padding-right, 30mm)
                var(--barcode-label-sheet-padding-bottom, 58.5mm)
                var(--barcode-label-sheet-padding-left, 30mm) !important;
        }

        .barcode-label-sheet .barcode-sheet-title {
            font-size: 1rem;
        }

        .barcode-label-grid {
            display: grid;
            grid-template-columns: repeat(var(--barcode-label-grid-columns, 3), var(--barcode-label-width, 50mm));
            grid-template-rows: repeat(var(--barcode-label-grid-rows, 10), var(--barcode-label-height, 18mm));
            width: var(--barcode-label-grid-width, 150mm);
            height: var(--barcode-label-grid-height, 180mm);
            gap: var(--barcode-label-gap, 0mm);
            margin: 0 auto;
            box-sizing: border-box;
            border: 0.3px solid #b9c7db;
        }

        .barcode-label-cell {
            width: var(--barcode-label-width, 50mm);
            height: var(--barcode-label-height, 18mm);
            border: 0.3px solid #bbb;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 1mm 1.5mm;
            overflow: hidden;
            position: relative;
            box-sizing: border-box;
        }

        .barcode-label-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            width: 100%;
            height: 100%;
            min-width: 0;
            min-height: 0;
            box-sizing: border-box;
            border: 0.3px solid #b9c7db;
        }

        .barcode-label-content--label107 {
            margin: 0;
            padding: 0.3mm;
            box-sizing: border-box;
        }

        .barcode-label-cell--label107 {
            padding: 0;
        }

        .barcode-label-cell--label107 .barcode-label-barcode {
            height: 10mm;
            margin: 3px 0;
        }

        .barcode-label-cell--label107 .barcode-label-svg {
            height: 10mm !important;
        }

        .barcode-label-cell--label107 .barcode-label-category {
            font-size: 4.3pt;
        }

        .barcode-label-cell--label107 .barcode-label-name {
            font-size: 12px;
        }

        .barcode-label-cell--label107 .barcode-label-date {
            font-size: 3.9pt;
        }

        .barcode-label-cell--label107 .barcode-label-dept {
            font-size: 10px;
        }

        .barcode-label-cell--label107 .barcode-label-category,
        .barcode-label-cell--label107 .barcode-label-name,
        .barcode-label-cell--label107 .barcode-label-date,
        .barcode-label-cell--label107 .barcode-label-dept {
            margin: 0;
        }

        .barcode-label-cell--empty::after {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                -45deg,
                transparent, transparent 4px,
                rgba(0,0,0,.03) 4px, rgba(0,0,0,.03) 5px
            );
        }

        .barcode-label-category {
            font-size: 5pt;
            color: #666;
            text-transform: uppercase;
            letter-spacing: .3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.1;
        }
        .barcode-label-name {
            font-size: 12px;
            font-weight: 700;
            color: #111;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
        }
        .barcode-label-barcode {
            display: block;
            width: 100%;
            height: 10mm;
            margin: 3px 0;
            overflow: hidden;
        }
        .barcode-label-svg {
            width: 100% !important;
            height: 10mm !important;
            display: block;
        }
        .barcode-label-date {
            font-size: 4.5pt;
            color: #555;
            letter-spacing: .1px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
        }
        .barcode-label-dept {
            font-size: 10px;
            font-weight: 700;
            color: #111;
            text-transform: uppercase;
            letter-spacing: .2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
        }

        /* ── Print rules for label format ───────────────────────── */
        @media print {
            .barcode-label-sheet {
                width: var(--barcode-label-sheet-width, 210mm) !important;
                min-height: var(--barcode-label-sheet-height, 297mm) !important;
                height: var(--barcode-label-sheet-height, 297mm) !important;
                padding: var(--barcode-label-sheet-padding-top, 58.5mm)
                    var(--barcode-label-sheet-padding-right, 30mm)
                    var(--barcode-label-sheet-padding-bottom, 58.5mm)
                    var(--barcode-label-sheet-padding-left, 30mm) !important;
            }
            .barcode-label-grid {
                margin: 0 !important;
                width: var(--barcode-label-grid-width, 150mm) !important;
                height: var(--barcode-label-grid-height, 180mm) !important;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var selectedFormat = @json($printFormat);
            var selectedGrid = @json($selectedGrid);
            var selectedLabel = @json($selectedLabel);
            var barcodeSelectionForm = document.getElementById('barcodeSelectionForm');
            var barcodeSelectorSearchInput = document.getElementById('barcodeSelectorSearchInput');
            var barcodeCategoryFilterInput = document.getElementById('barcodeCategoryFilterInput');
            var barcodeConditionFilterInput = document.getElementById('barcodeConditionFilterInput');
            var barcodeSelectorRows = Array.from(document.querySelectorAll('[data-barcode-selector-row]'));
            var barcodeSelectorItems = Array.from(document.querySelectorAll('[data-barcode-selector-item]'));
            var barcodeSelectAllToggle = document.getElementById('barcodeSelectAllToggle');
            var barcodeSelectedCountBadge = document.getElementById('barcodeSelectedCountBadge');
            var printButton = document.getElementById('printBarcodeButton');
            var barcodePageShell = document.getElementById('barcodePageShell');
            var labelBorderToggleText = document.getElementById('labelBorderToggleText');
            var labelBorderOptions = document.querySelectorAll('[data-label-content-border]');
            var labelContentBorderStorageKey = 'barcodeLabelContentBorderMode';
            var labelGridBorderToggleText = document.getElementById('labelGridBorderToggleText');
            var labelGridBorderOptions = document.querySelectorAll('[data-label-grid-border]');
            var labelGridBorderStorageKey = 'barcodeLabelGridBorderMode';
            var barcodePages = Array.from(document.querySelectorAll('[data-barcode-page]'));
            var barcodeSvgs = document.querySelectorAll('[data-barcode-svg]');
            var labelContentBorderMode = 'on';
            var labelGridBorderMode = 'on';
            var barcodeSelectionSubmitTimer = null;

            var applyLabelContentBorderMode = function (mode) {
                var normalizedMode = mode === 'off' ? 'off' : 'on';
                labelContentBorderMode = normalizedMode;

                if (barcodePageShell) {
                    barcodePageShell.classList.toggle('barcode-content-border-off', normalizedMode === 'off');
                }

                if (labelBorderToggleText) {
                    labelBorderToggleText.textContent = normalizedMode === 'off' ? 'Border OFF' : 'Border ON';
                }

                labelBorderOptions.forEach(function (button) {
                    var isActive = button.dataset.labelContentBorder === normalizedMode;
                    button.classList.toggle('active', isActive);
                });

                try {
                    window.localStorage.setItem(labelContentBorderStorageKey, normalizedMode);
                } catch (error) {
                    // Ignore storage errors (e.g. private mode) and keep current mode in memory.
                }
            };

            var getInitialLabelContentBorderMode = function () {
                var fallbackMode = 'on';

                try {
                    var storedMode = window.localStorage.getItem(labelContentBorderStorageKey);
                    if (storedMode === 'on' || storedMode === 'off') {
                        return storedMode;
                    }
                } catch (error) {
                    return fallbackMode;
                }

                return fallbackMode;
            };

            var applyLabelGridBorderMode = function (mode) {
                var normalizedMode = mode === 'off' ? 'off' : 'on';
                labelGridBorderMode = normalizedMode;

                if (barcodePageShell) {
                    barcodePageShell.classList.toggle('barcode-grid-border-off', normalizedMode === 'off');
                }

                if (labelGridBorderToggleText) {
                    labelGridBorderToggleText.textContent = normalizedMode === 'off' ? 'Grid Border OFF' : 'Grid Border ON';
                }

                labelGridBorderOptions.forEach(function (button) {
                    var isActive = button.dataset.labelGridBorder === normalizedMode;
                    button.classList.toggle('active', isActive);
                });

                try {
                    window.localStorage.setItem(labelGridBorderStorageKey, normalizedMode);
                } catch (error) {
                    // Ignore storage errors (e.g. private mode) and keep current mode in memory.
                }
            };

            var getInitialLabelGridBorderMode = function () {
                var fallbackMode = 'on';

                try {
                    var storedMode = window.localStorage.getItem(labelGridBorderStorageKey);
                    if (storedMode === 'on' || storedMode === 'off') {
                        return storedMode;
                    }
                } catch (error) {
                    return fallbackMode;
                }

                return fallbackMode;
            };

            var updateSelectedBarcodeCount = function () {
                if (!barcodeSelectedCountBadge) {
                    return;
                }

                var selectedCount = barcodeSelectorItems.reduce(function (count, checkbox) {
                    return checkbox.checked ? count + 1 : count;
                }, 0);

                barcodeSelectedCountBadge.textContent = selectedCount.toLocaleString('id-ID');
            };

            var getVisibleBarcodeSelectorItems = function () {
                return barcodeSelectorItems.filter(function (checkbox) {
                    var row = checkbox.closest('[data-barcode-selector-row]');

                    if (!row) {
                        return true;
                    }

                    return !row.classList.contains('is-hidden');
                });
            };

            var updateSelectAllState = function () {
                if (!barcodeSelectAllToggle) {
                    return;
                }

                var targetItems = getVisibleBarcodeSelectorItems();
                var selectedCount = targetItems.reduce(function (count, checkbox) {
                    return checkbox.checked ? count + 1 : count;
                }, 0);

                if (targetItems.length === 0) {
                    barcodeSelectAllToggle.checked = false;
                    barcodeSelectAllToggle.indeterminate = false;
                    return;
                }

                barcodeSelectAllToggle.checked = selectedCount === targetItems.length;
                barcodeSelectAllToggle.indeterminate = selectedCount > 0 && selectedCount < targetItems.length;
            };

            var submitBarcodeSelection = function () {
                if (!barcodeSelectionForm) {
                    return;
                }

                if (barcodeSelectionSubmitTimer) {
                    window.clearTimeout(barcodeSelectionSubmitTimer);
                }

                barcodeSelectionSubmitTimer = window.setTimeout(function () {
                    if (typeof barcodeSelectionForm.requestSubmit === 'function') {
                        barcodeSelectionForm.requestSubmit();
                        return;
                    }

                    barcodeSelectionForm.submit();
                }, 220);
            };

            var syncBarcodeFilterQueryState = function () {
                if (typeof window.history.replaceState !== 'function') {
                    return;
                }

                var currentUrl = null;

                try {
                    currentUrl = new URL(window.location.href);
                } catch (error) {
                    return;
                }

                var searchValue = barcodeSelectorSearchInput
                    ? barcodeSelectorSearchInput.value.toString().trim()
                    : '';
                var categoryValue = barcodeCategoryFilterInput
                    ? barcodeCategoryFilterInput.value.toString().trim().toLowerCase()
                    : '';
                var conditionValue = barcodeConditionFilterInput
                    ? barcodeConditionFilterInput.value.toString().trim().toLowerCase()
                    : '';

                if (searchValue !== '') {
                    currentUrl.searchParams.set('selector_search', searchValue);
                } else {
                    currentUrl.searchParams.delete('selector_search');
                }

                if (categoryValue !== '') {
                    currentUrl.searchParams.set('selector_category', categoryValue);
                } else {
                    currentUrl.searchParams.delete('selector_category');
                }

                if (conditionValue !== '') {
                    currentUrl.searchParams.set('selector_condition', conditionValue);
                } else {
                    currentUrl.searchParams.delete('selector_condition');
                }

                window.history.replaceState({}, '', currentUrl.toString());
            };

            var filterBarcodeSelectorRows = function () {
                var normalizedKeyword = barcodeSelectorSearchInput
                    ? barcodeSelectorSearchInput.value.toString().trim().toLowerCase()
                    : '';
                var normalizedCategory = barcodeCategoryFilterInput
                    ? barcodeCategoryFilterInput.value.toString().trim().toLowerCase()
                    : '';
                var normalizedCondition = barcodeConditionFilterInput
                    ? barcodeConditionFilterInput.value.toString().trim().toLowerCase()
                    : '';

                barcodeSelectorRows.forEach(function (row) {
                    var rowLabel = row.dataset.searchText || '';
                    var rowCategory = (row.dataset.category || '').toString().trim().toLowerCase();
                    var rowCondition = (row.dataset.condition || '').toString().trim().toLowerCase();
                    var matchKeyword = normalizedKeyword === '' || rowLabel.indexOf(normalizedKeyword) !== -1;
                    var matchCategory = normalizedCategory === '' || rowCategory === normalizedCategory;
                    var matchCondition = normalizedCondition === '' || rowCondition === normalizedCondition;
                    var isMatch = matchKeyword && matchCategory && matchCondition;

                    row.classList.toggle('is-hidden', !isMatch);
                });

                updateSelectAllState();
            };

            var renderBarcodes = function () {
                if (typeof JsBarcode !== 'function') {
                    return;
                }

                barcodeSvgs.forEach(function (svg) {
                    var barcodeValue = svg.dataset.barcodeValue || '';

                    if (!barcodeValue) {
                        return;
                    }

                    var width = parseFloat(svg.dataset.barcodeWidth || (selectedFormat === 'a4' ? String(selectedGrid.barcode_width || 1.05) : String(selectedLabel.barcode_width || 1.45)));
                    var height = parseInt(svg.dataset.barcodeHeight || (selectedFormat === 'a4' ? String(selectedGrid.barcode_height || 36) : String(selectedLabel.barcode_height || 62)), 10);

                    svg.innerHTML = '';

                    try {
                        JsBarcode(svg, barcodeValue, {
                            format: 'CODE128',
                            displayValue: false,
                            margin: 0,
                            lineColor: '#12233f',
                            background: 'transparent',
                            width: width,
                            height: height,
                        });
                    } catch (error) {
                        svg.innerHTML = '';
                    }
                });
            };

            var capturePageCanvas = async function (pageElement) {
                if (!pageElement || typeof html2canvas !== 'function') {
                    alert('Preview barcode belum siap untuk diunduh.');
                    return null;
                }

                return await html2canvas(pageElement, {
                    backgroundColor: '#ffffff',
                    scale: selectedFormat === 'a4' ? parseFloat(selectedGrid.capture_scale || 2.2) : parseFloat(selectedLabel.capture_scale || 2.6),
                    useCORS: true,
                    logging: false,
                });
            };

            var getPageCanvases = async function () {
                var canvases = [];

                for (var index = 0; index < barcodePages.length; index += 1) {
                    // Capture each sheet separately so PDF and print keep proper page breaks.
                    // eslint-disable-next-line no-await-in-loop
                    var canvas = await capturePageCanvas(barcodePages[index]);
                    if (canvas) {
                        canvases.push(canvas);
                    }
                }

                return canvases;
            };


            var getLabelPrintPages = function () {
                return barcodePages.map(function (pageElement) {
                    var labelGrid = pageElement.querySelector('.barcode-label-grid');

                    if (!labelGrid) {
                        return '';
                    }

                    var gridClone = labelGrid.cloneNode(true);
                    gridClone.style.margin = '0';
                    gridClone.style.transformOrigin = 'top left';

                    return '<section class="print-label-sheet">' + gridClone.outerHTML + '</section>';
                }).filter(function (markup) {
                    return Boolean(markup);
                }).join('');
            };

            var openLabelPrintWindow = function () {
                var printWindow = window.open('', '_blank', 'width=1280,height=900');

                if (!printWindow) {
                    alert('Popup print diblokir browser.');
                    return;
                }

                var printPages = getLabelPrintPages();

                if (!printPages) {
                    alert('Layout label belum siap untuk dicetak.');
                    printWindow.close();
                    return;
                }

                var labelPaperWidth = parseFloat(selectedLabel.paper_width || 210);
                var labelPaperHeight = parseFloat(selectedLabel.paper_height || 297);
                var labelSheetWidth = selectedLabel.sheet_width || (labelPaperWidth + 'mm');
                var labelSheetHeight = selectedLabel.sheet_height || (labelPaperHeight + 'mm');
                var labelSheetPaddingTop = selectedLabel.sheet_padding_top || '58.5mm';
                var labelSheetPaddingRight = selectedLabel.sheet_padding_right || '30mm';
                var labelSheetPaddingBottom = selectedLabel.sheet_padding_bottom || '58.5mm';
                var labelSheetPaddingLeft = selectedLabel.sheet_padding_left || '30mm';
                var labelGridColumns = parseInt(selectedLabel.grid_columns || 3, 10);
                var labelGridRows = parseInt(selectedLabel.grid_rows || 10, 10);
                var labelWidth = selectedLabel.label_width || '50mm';
                var labelHeight = selectedLabel.label_height || '18mm';
                var labelGap = selectedLabel.label_gap || '0mm';
                var labelGridWidth = selectedLabel.grid_width || '150mm';
                var labelGridHeight = selectedLabel.grid_height || '180mm';
                var labelContentBorderStyle = labelContentBorderMode === 'off' ? 'none' : '0.3px solid #b9c7db';
                var labelGridBorderStyle = labelGridBorderMode === 'off' ? 'none' : '0.3px solid #b9c7db';
                var labelCellBorderStyle = labelGridBorderMode === 'off' ? 'none' : '0.3px solid #bbb';

                printWindow.document.write(
                    '<!doctype html><html><head><title>Print Label Inventory</title>' +
                    '<style>' +
                    '@page { size: ' + labelPaperWidth + 'mm ' + labelPaperHeight + 'mm; margin: 0; }' +
                    'html, body { margin: 0; padding: 0; background: #ffffff; }' +
                    '.print-label-sheet { width: ' + labelSheetWidth + '; height: ' + labelSheetHeight + '; padding: ' + labelSheetPaddingTop + ' ' + labelSheetPaddingRight + ' ' + labelSheetPaddingBottom + ' ' + labelSheetPaddingLeft + '; box-sizing: border-box; overflow: hidden; page-break-after: always; break-after: page; }' +
                    '.print-label-sheet:last-child { page-break-after: auto; break-after: avoid; }' +
                    '.barcode-label-grid { display: grid; grid-template-columns: repeat(' + labelGridColumns + ', ' + labelWidth + '); grid-template-rows: repeat(' + labelGridRows + ', ' + labelHeight + '); width: ' + labelGridWidth + '; height: ' + labelGridHeight + '; gap: ' + labelGap + '; margin: 0; box-sizing: border-box; border: ' + labelGridBorderStyle + '; }' +
                    '.barcode-label-cell { width: ' + labelWidth + '; height: ' + labelHeight + '; border: ' + labelCellBorderStyle + '; display: flex; flex-direction: column; justify-content: center; padding: 1mm 1.5mm; overflow: hidden; position: relative; box-sizing: border-box; }' +
                    '.barcode-label-content { display: flex; flex-direction: column; justify-content: center; width: 100%; height: 100%; min-width: 0; min-height: 0; box-sizing: border-box; border: ' + labelContentBorderStyle + '; }' +
                    '.barcode-label-content--label107 { margin: 0; padding: 0.3mm; box-sizing: border-box; }' +
                    '.barcode-label-cell--label107 { padding: 0; }' +
                    '.barcode-label-cell--label107 .barcode-label-barcode { height: 10mm; margin: 3px 0; }' +
                    '.barcode-label-cell--label107 .barcode-label-svg { height: 10mm !important; }' +
                    '.barcode-label-cell--label107 .barcode-label-category { font-size: 4.3pt; }' +
                    '.barcode-label-cell--label107 .barcode-label-name { font-size: 12px; }' +
                    '.barcode-label-cell--label107 .barcode-label-date { font-size: 3.9pt; }' +
                    '.barcode-label-cell--label107 .barcode-label-dept { font-size: 10px; }' +
                    '.barcode-label-cell--label107 .barcode-label-category, .barcode-label-cell--label107 .barcode-label-name, .barcode-label-cell--label107 .barcode-label-date, .barcode-label-cell--label107 .barcode-label-dept { margin: 0; }' +
                    '.barcode-label-cell--empty::after { content: ""; position: absolute; inset: 0; background: repeating-linear-gradient(-45deg, transparent, transparent 4px, rgba(0,0,0,.03) 4px, rgba(0,0,0,.03) 5px); }' +
                    '.barcode-label-category { font-size: 5pt; color: #666; text-transform: uppercase; letter-spacing: .3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.1; }' +
                    '.barcode-label-name { font-size: 12px; font-weight: 700; color: #111; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.2; }' +
                    '.barcode-label-barcode { display: block; width: 100%; height: 10mm; margin: 3px 0; overflow: hidden; }' +
                    '.barcode-label-svg { width: 100% !important; height: 10mm !important; display: block; }' +
                    '.barcode-label-date { font-size: 4.5pt; color: #555; letter-spacing: .1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.2; }' +
                    '.barcode-label-dept { font-size: 10px; font-weight: 700; color: #111; text-transform: uppercase; letter-spacing: .2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.2; }' +
                    '</style></head><body>' +
                    printPages +
                    '</body></html>'
                );
                printWindow.document.close();

                setTimeout(function () {
                    printWindow.focus();
                    printWindow.print();
                    setTimeout(function () {
                        printWindow.close();
                    }, 500);
                }, 250);
            };

            var openPrintWindow = async function () {
                if (selectedFormat !== 'a4') {
                    openLabelPrintWindow();
                    return;
                }

                var canvases = await getPageCanvases();

                if (!canvases.length) {
                    return;
                }

                var pageSize = 'A4';
                var printWindow = window.open('', '_blank', 'width=1280,height=900');

                if (!printWindow) {
                    alert('Popup print diblokir browser.');
                    return;
                }

                var printPages = canvases.map(function (canvas) {
                    return '<div class="print-page"><img src="' + canvas.toDataURL('image/png') + '" alt="Barcode Preview"></div>';
                }).join('');

                printWindow.document.write(
                    '<!doctype html><html><head><title>Print Barcode</title>' +
                    '<style>' +
                    '@page { size: ' + pageSize + '; margin: 0; }' +
                    'html, body { margin: 0; padding: 0; background: #ffffff; }' +
                    '.print-page { page-break-after: always; width: 100%; height: 100vh; }' +
                    '.print-page:last-child { page-break-after: auto; }' +
                    'img { width: 100%; height: 100%; object-fit: fill; display: block; }' +
                    '</style></head><body>' +
                    printPages +
                    '</body></html>'
                );
                printWindow.document.close();

                setTimeout(function () {
                    printWindow.focus();
                    printWindow.print();
                    setTimeout(function () {
                        printWindow.close();
                    }, 500);
                }, 250);
            };

            applyLabelContentBorderMode(getInitialLabelContentBorderMode());
            applyLabelGridBorderMode(getInitialLabelGridBorderMode());

            labelBorderOptions.forEach(function (button) {
                button.addEventListener('click', function () {
                    applyLabelContentBorderMode(button.dataset.labelContentBorder);
                });
            });

            labelGridBorderOptions.forEach(function (button) {
                button.addEventListener('click', function () {
                    applyLabelGridBorderMode(button.dataset.labelGridBorder);
                });
            });

            if (barcodeSelectorSearchInput) {
                barcodeSelectorSearchInput.addEventListener('input', function () {
                    filterBarcodeSelectorRows();
                    syncBarcodeFilterQueryState();
                });
            }

            if (barcodeCategoryFilterInput) {
                barcodeCategoryFilterInput.addEventListener('change', function () {
                    filterBarcodeSelectorRows();
                    syncBarcodeFilterQueryState();
                });
            }

            if (barcodeConditionFilterInput) {
                barcodeConditionFilterInput.addEventListener('change', function () {
                    filterBarcodeSelectorRows();
                    syncBarcodeFilterQueryState();
                });
            }

            if (barcodeSelectAllToggle) {
                barcodeSelectAllToggle.addEventListener('change', function () {
                    getVisibleBarcodeSelectorItems().forEach(function (checkbox) {
                        checkbox.checked = barcodeSelectAllToggle.checked;
                    });

                    updateSelectedBarcodeCount();
                    updateSelectAllState();
                    submitBarcodeSelection();
                });
            }

            barcodeSelectorItems.forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    updateSelectedBarcodeCount();
                    updateSelectAllState();
                    submitBarcodeSelection();
                });
            });

            updateSelectedBarcodeCount();
            updateSelectAllState();
            filterBarcodeSelectorRows();
            syncBarcodeFilterQueryState();

            renderBarcodes();

            if (printButton) {
                printButton.addEventListener('click', function () {
                    openPrintWindow();
                });
            }

            /* ── Kalibrasi Posisi Cetak ─────────────────────────── */
            (function () {
                var calOffsetX   = document.getElementById('calOffsetX');
                var calOffsetY   = document.getElementById('calOffsetY');
                var calScale     = document.getElementById('calScale');
                var calXDisplay  = document.getElementById('calXDisplay');
                var calYDisplay  = document.getElementById('calYDisplay');
                var calScaleDisp = document.getElementById('calScaleDisplay');
                var calResetBtn  = document.getElementById('calResetBtn');
                var stepBtns     = document.querySelectorAll('.cal-step-btn');

                if (!calOffsetX || !calOffsetY || !calScale) return; // not a label format

                /* Inject a dynamic <style> tag that carries the transform into @media print */
                var calPrintStyle = document.createElement('style');
                calPrintStyle.id  = 'calPrintStyle';
                document.head.appendChild(calPrintStyle);

                var applyCalibration = function () {
                    var x     = parseFloat(calOffsetX.value);
                    var y     = parseFloat(calOffsetY.value);
                    var scale = parseFloat(calScale.value) / 100;

                    /* Format display badges */
                    calXDisplay.textContent  = (x >= 0 ? '+' : '') + x.toFixed(1) + ' mm';
                    calYDisplay.textContent  = (y >= 0 ? '+' : '') + y.toFixed(1) + ' mm';
                    calScaleDisp.textContent = (scale * 100).toFixed(1) + '%';

                    /* Colour badge based on offset direction */
                    calXDisplay.className  = 'badge ' + (x !== 0 ? 'text-bg-primary' : 'text-bg-secondary');
                    calYDisplay.className  = 'badge ' + (y !== 0 ? 'text-bg-primary' : 'text-bg-secondary');
                    calScaleDisp.className = 'badge ' + (scale !== 1 ? 'text-bg-warning text-dark' : 'text-bg-secondary');

                    var transformVal = 'translate(' + x + 'mm, ' + y + 'mm) scale(' + scale + ')';

                    /* Apply to all label grids on screen */
                    document.querySelectorAll('.barcode-label-grid').forEach(function (grid) {
                        grid.style.transform       = transformVal;
                        grid.style.transformOrigin = 'top left';
                    });

                    /* Inject print CSS (overrides default margin:0 auto) */
                    calPrintStyle.textContent =
                        '@media print {' +
                        '  .barcode-label-grid {' +
                        '    transform: translate(' + x + 'mm, ' + y + 'mm) scale(' + scale + ') !important;' +
                        '    transform-origin: top left !important;' +
                        '    margin: 0 !important;' +
                        '  }' +
                        '}';
                };

                /* Slider input events */
                calOffsetX.addEventListener('input', applyCalibration);
                calOffsetY.addEventListener('input', applyCalibration);
                calScale.addEventListener('input', applyCalibration);

                /* Step buttons: nudge by 0.5 mm */
                stepBtns.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var axis = btn.dataset.axis;
                        var dir  = parseFloat(btn.dataset.dir);
                        var step = 0.5;

                        if (axis === 'x') {
                            var newX = Math.max(-15, Math.min(15, parseFloat(calOffsetX.value) + dir * step));
                            calOffsetX.value = newX;
                        } else {
                            var newY = Math.max(-15, Math.min(15, parseFloat(calOffsetY.value) + dir * step));
                            calOffsetY.value = newY;
                        }
                        applyCalibration();
                    });
                });

                /* Reset */
                if (calResetBtn) {
                    calResetBtn.addEventListener('click', function () {
                        calOffsetX.value = '0';
                        calOffsetY.value = '0';
                        calScale.value   = '100';
                        applyCalibration();
                    });
                }

                /* Run once on load */
                applyCalibration();
            })();

        });
    </script>
@endpush