@extends('layouts.app')

@section('content')
    @php
        $initialMode = old('flow_mode', old('condition') ? 'return' : 'borrow');
        $publicSettings = $publicSettings ?? [];
        $publicHeaderTitle = $publicSettings['public_header_title'] ?? 'Dashboard Inventaris';
        $publicHeaderSubtitle = $publicSettings['public_header_subtitle'] ?? 'Sistem Peminjaman & Pengembalian Aset Sekolah';
        $publicBorrowButtonLabel = $publicSettings['public_borrow_button_label'] ?? 'Peminjaman Barang';
        $publicReturnButtonLabel = $publicSettings['public_return_button_label'] ?? 'Pengembalian Barang';
        $publicReminderEnabled = ($publicSettings['public_reminder_enabled'] ?? '1') === '1';
        $publicReminderBackground = $publicSettings['public_reminder_background'] ?? '#0A0A0A';
        $publicReminderTextColor = $publicSettings['public_reminder_text_color'] ?? '#FFFFFF';
        $publicReminderSpeed = (int) ($publicSettings['public_running_text_speed'] ?? 15);
        $publicReminderFontSize = (int) ($publicSettings['public_running_text_font_size'] ?? 17);
        $publicReminderFontFamily = $publicSettings['public_running_text_font_family'] ?? 'system-ui, sans-serif';
    @endphp

    <div class="public-dashboard">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3 pb-3 border-bottom public-header">
            <div>
                <h1 class="h2 fw-bold mb-1">{{ $publicHeaderTitle }}</h1>
                <p class="text-secondary mb-0">{{ $publicHeaderSubtitle }}</p>
            </div>
            <button type="button" class="btn btn-outline-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#howToUseModal">
                <i class="fa-solid fa-circle-info me-2"></i>Cara Pakai Aplikasi
            </button>
        </div>

        <div class="row g-3 justify-content-center mb-4">
            <div class="col-md-6 col-lg-4">
                <button type="button" class="btn mode-toggle-btn mode-borrow w-100" data-target-mode="borrow">
                    <i class="fa-solid fa-handshake-angle me-2"></i>{{ $publicBorrowButtonLabel }}
                </button>
            </div>
            <div class="col-md-6 col-lg-4">
                <button type="button" class="btn mode-toggle-btn mode-return w-100" data-target-mode="return">
                    <i class="fa-solid fa-rotate-left me-2"></i>{{ $publicReturnButtonLabel }}
                </button>
            </div>
        </div>

        <div class="row g-3 align-items-stretch">
            <div class="col-lg-4">
                <div class="card h-100 public-card">
                    <div id="scanCardHeader" class="card-header border-0 py-3 text-white bg-primary public-scan-header">
                        <i class="fa-solid fa-qrcode me-2"></i><span id="scanCardTitle">Scan Station Peminjaman</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.loans.borrow') }}" class="vstack gap-3" data-mode-form="borrow">
                            @csrf
                            <input type="hidden" name="flow_mode" value="borrow">
                            <div>
                                <label class="form-label text-secondary">Identitas Peminjam</label>
                                <input
                                    type="text"
                                    name="identity_number"
                                    class="form-control form-control-lg"
                                    placeholder="Input NISN / NIP..."
                                    value="{{ old('identity_number') }}"
                                    required
                                >
                            </div>
                            <div>
                                <label class="form-label text-secondary">Barcode Barang</label>
                                <input
                                    type="text"
                                    name="asset_code"
                                    class="form-control form-control-lg"
                                    placeholder="Scan barcode..."
                                    value="{{ old('asset_code') }}"
                                    required
                                >
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg fw-semibold w-100">
                                <i class="fa-solid fa-circle-check me-2"></i>Konfirmasi Peminjaman
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.loans.return') }}" class="vstack gap-3 d-none" data-mode-form="return">
                            @csrf
                            <input type="hidden" name="flow_mode" value="return">
                            <input type="hidden" name="condition" value="good">
                            <input type="hidden" name="notes" value="">
                            <div>
                                <label class="form-label text-secondary">Identitas Peminjam</label>
                                <input
                                    type="text"
                                    name="identity_number"
                                    class="form-control form-control-lg"
                                    placeholder="Input NISN / NIP..."
                                    value="{{ old('identity_number') }}"
                                    required
                                >
                            </div>
                            <div>
                                <label class="form-label text-secondary">Barcode Barang</label>
                                <input
                                    type="text"
                                    name="asset_code"
                                    class="form-control form-control-lg"
                                    placeholder="Scan barcode..."
                                    value="{{ old('asset_code') }}"
                                    required
                                >
                            </div>
                            <button type="submit" class="btn btn-success btn-lg fw-semibold w-100">
                                <i class="fa-solid fa-circle-check me-2"></i>Konfirmasi Pengembalian
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card h-100 public-card">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <div class="fw-bold fs-4 text-dark">
                            <i class="fa-solid fa-circle-check text-success me-2"></i>Stok Barang Tersedia
                        </div>
                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2">{{ $availableAssets->count() }} Items</span>
                    </div>

                    <div class="table-responsive public-stock-wrapper">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-secondary text-uppercase small">No</th>
                                    <th class="text-secondary text-uppercase small">Barang</th>
                                    <th class="text-secondary text-uppercase small">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($availableAssets as $asset)
                                    @php
                                        $category = strtolower((string) $asset->category);
                                        $icon = 'fa-box-archive';

                                        if (str_contains($category, 'projector')) {
                                            $icon = 'fa-video';
                                        } elseif (str_contains($category, 'mouse')) {
                                            $icon = 'fa-computer-mouse';
                                        } elseif (str_contains($category, 'laptop') || str_contains($category, 'notebook')) {
                                            $icon = 'fa-laptop';
                                        }
                                    @endphp
                                    <tr>
                                        <td class="text-secondary">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="asset-icon"><i class="fa-solid {{ $icon }}"></i></span>
                                                <div>
                                                    <div class="fw-semibold">{{ $asset->brand }}</div>
                                                    <div class="small text-secondary">{{ $asset->model }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2">Ready</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">Tidak ada stok tersedia saat ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="howToUseModal" tabindex="-1" aria-labelledby="howToUseModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title" id="howToUseModalLabel">Cara Pakai Aplikasi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <ol class="mb-0 ps-3">
                            <li>Pilih mode Peminjaman atau Pengembalian.</li>
                            <li>Scan atau isi NISN/NIP peminjam.</li>
                            <li>Scan barcode/serial barang. yang ada di bagian belakang laptop</li>
                            <li>Klik tombol konfirmasi untuk menyimpan transaksi.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        @if($publicReminderEnabled)
            <div
                class="public-reminder-banner"
                aria-label="Pengumuman waktu pengembalian barang"
                style="background: {{ $publicReminderBackground }}; color: {{ $publicReminderTextColor }}; font-size: {{ $publicReminderFontSize }}px; font-family: {{ $publicReminderFontFamily }};"
            >
                <div class="public-reminder-marquee" style="--public-reminder-speed: {{ $publicReminderSpeed }}s;">
                    {{ $runningText }}
                </div>
            </div>
        @endif
    </div>
@endsection

@push('styles')
    <style>
        .public-dashboard {
            padding-bottom: 64px;
        }

        .public-header {
            border-color: #d7dee9 !important;
        }

        .mode-toggle-btn {
            border-radius: 0.8rem;
            border: 2px solid #111111;
            min-height: 84px;
            font-size: 1.2rem;
            line-height: 1.25;
            font-weight: 700;
            padding: 1rem 1.2rem;
            transition: all 0.2s ease;
        }

        .mode-toggle-btn i {
            font-size: 1.05em;
        }

        .mode-toggle-btn:hover,
        .mode-toggle-btn:focus-visible,
        .mode-toggle-btn:active {
            border-color: #111111;
            outline: 0;
            box-shadow: 0 0 0 0.18rem rgba(17, 17, 17, 0.14);
        }

        .mode-borrow {
            color: #0d6efd;
            background: #ffffff;
        }

        .mode-return {
            color: #198754;
            background: #ffffff;
        }

        .mode-borrow.is-active {
            color: #ffffff;
            background: #2f66e0;
            border-color: #111111;
            box-shadow: 0 10px 24px rgba(47, 102, 224, 0.25);
        }

        .mode-return.is-active {
            color: #ffffff;
            background: #1f9462;
            border-color: #111111;
            box-shadow: 0 10px 24px rgba(31, 148, 98, 0.25);
        }

        .mode-borrow.is-active:hover,
        .mode-borrow.is-active:focus-visible,
        .mode-return.is-active:hover,
        .mode-return.is-active:focus-visible {
            border-color: #111111;
        }

        .public-card {
            border-radius: 0.75rem;
            border: 1px solid #dce4ef;
            box-shadow: 0 8px 18px rgba(40, 58, 90, 0.08);
            overflow: hidden;
        }

        .public-scan-header {
            font-weight: 700;
            font-size: 1.05rem;
        }

        .public-stock-wrapper {
            max-height: 410px;
        }

        .public-stock-wrapper thead th {
            border-top: 0;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .public-stock-wrapper tbody tr {
            border-color: #e6ebf3;
        }

        .asset-icon {
            width: 32px;
            height: 32px;
            border-radius: 0.5rem;
            background: #edf1f7;
            color: #7a8798;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .public-reminder-banner {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1050;
            background: #0a0a0a;
            color: #ffffff;
            overflow: hidden;
            padding: 0.68rem 1rem;
        }

        .public-reminder-marquee {
            display: inline-block;
            white-space: nowrap;
            font-weight: 700;
            letter-spacing: 0.02em;
            padding-left: 100%;
            animation: publicMarquee var(--public-reminder-speed, 15s) linear infinite;
            will-change: transform;
        }

        @keyframes publicMarquee {
            from {
                transform: translateX(0);
            }

            to {
                transform: translateX(-100%);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .public-reminder-marquee {
                animation: none;
                padding-left: 0;
                width: 100%;
                text-align: center;
            }
        }

        @media (min-width: 1600px) and (max-width: 2560px) and (min-height: 850px) {
            .public-dashboard {
                max-width: 1840px;
                margin-inline: auto;
                padding-bottom: 72px;
            }

            .public-header .h2 {
                font-size: clamp(2rem, 1.05vw + 1.05rem, 2.45rem);
            }

            .public-header p {
                font-size: 1.08rem;
            }

            .mode-toggle-btn {
                min-height: 92px;
                font-size: 1.26rem;
                padding: 1.05rem 1.3rem;
            }

            .public-scan-header {
                font-size: 1.12rem;
            }

            .public-card .card-body,
            .public-card .card-header {
                padding-left: 1.2rem;
                padding-right: 1.2rem;
            }

            .public-stock-wrapper {
                max-height: min(60vh, 560px);
            }

            .public-stock-wrapper .table {
                font-size: 1.02rem;
            }

            .public-stock-wrapper tbody td {
                padding-top: 0.88rem;
                padding-bottom: 0.88rem;
            }

            .asset-icon {
                width: 36px;
                height: 36px;
                font-size: 1rem;
            }

            .public-reminder-banner {
                padding-top: 0.76rem;
                padding-bottom: 0.76rem;
            }
        }

        @media (min-width: 2200px) and (max-width: 2560px) {
            .public-dashboard {
                max-width: 2060px;
            }

            .mode-toggle-btn {
                min-height: 98px;
                font-size: 1.34rem;
            }

            .public-stock-wrapper {
                max-height: min(62vh, 640px);
            }
        }

        @media (max-width: 991.98px) {
            .mode-toggle-btn {
                min-height: 70px;
                font-size: 1.05rem;
                padding: 0.85rem 1rem;
            }
        }

        @media (max-width: 575.98px) {
            .mode-toggle-btn {
                min-height: 62px;
                font-size: 0.98rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modeButtons = document.querySelectorAll('[data-target-mode]');
            var forms = document.querySelectorAll('[data-mode-form]');
            var title = document.getElementById('scanCardTitle');
            var scanHeader = document.getElementById('scanCardHeader');
            var initialMode = @json($initialMode);

            function setMode(mode) {
                modeButtons.forEach(function (button) {
                    button.classList.toggle('is-active', button.dataset.targetMode === mode);
                });

                forms.forEach(function (form) {
                    form.classList.toggle('d-none', form.dataset.modeForm !== mode);
                });

                if (title && scanHeader) {
                    title.textContent = mode === 'return' ? 'Scan Station Pengembalian' : 'Scan Station Peminjaman';
                    scanHeader.classList.toggle('bg-primary', mode !== 'return');
                    scanHeader.classList.toggle('bg-success', mode === 'return');
                }
            }

            modeButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    setMode(button.dataset.targetMode);
                });
            });

            setMode(initialMode === 'return' ? 'return' : 'borrow');
        });
    </script>
@endpush
