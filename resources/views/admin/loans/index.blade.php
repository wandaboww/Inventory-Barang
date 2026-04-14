@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Transaksi Peminjaman</h4>
            <p class="text-muted mb-0">Proses pinjam dan pengembalian aset.</p>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white fw-semibold">Form Peminjaman</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.loans.borrow') }}" class="row g-2">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label">Identity Number User</label>
                            <input name="identity_number" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Serial / Barcode Asset</label>
                            <input name="asset_code" class="form-control" required>
                        </div>
                        <div class="col-12 d-grid">
                            <button class="btn btn-primary" type="submit">Proses Peminjaman</button>
                        </div>
                    </form>
                    <hr>
                    <div class="small text-muted">
                        <div class="fw-semibold mb-1">User tersedia:</div>
                        @foreach($users->take(8) as $user)
                            <div>{{ $user->identity_number }} - {{ $user->name }} ({{ $user->role }})</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white fw-semibold">Form Pengembalian</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.loans.return') }}" class="row g-2">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label">Identity Number User</label>
                            <input name="identity_number" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Serial / Barcode Asset</label>
                            <input name="asset_code" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kondisi</label>
                            <select class="form-select" name="condition" required>
                                <option value="good">good</option>
                                <option value="minor_damage">minor_damage</option>
                                <option value="major_damage">major_damage</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Catatan</label>
                            <input name="notes" class="form-control">
                        </div>
                        <div class="col-12 d-grid">
                            <button class="btn btn-success" type="submit">Proses Pengembalian</button>
                        </div>
                    </form>
                    <hr>
                    <div class="small text-muted">
                        <div class="fw-semibold mb-1">Asset tersedia:</div>
                        @foreach($assets->take(8) as $asset)
                            <div>{{ $asset->serial_number }} - {{ $asset->brand }} {{ $asset->model }} ({{ $asset->status }})</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white fw-semibold">Daftar Pinjaman Aktif</div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Peminjam</th>
                    <th>Kelas</th>
                    <th>No. HP</th>
                    <th>Asset</th>
                    <th>Status</th>
                    <th>Tanggal Pinjam</th>
                </tr>
                </thead>
                <tbody>
                @forelse($activeLoans as $loan)
                    <tr>
                        <td>{{ $activeLoans->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="fw-semibold">{{ $loan->user?->name ?? '-' }}</div>
                            <small class="text-muted">{{ $loan->user?->identity_number ?? '-' }}</small>
                        </td>
                        <td>{{ $loan->user?->kelas ?? '-' }}</td>
                        <td>{{ $loan->user?->phone ?? '-' }}</td>
                        <td>
                            <div>{{ $loan->asset?->brand }} {{ $loan->asset?->model }}</div>
                            <small class="text-muted">{{ $loan->asset?->serial_number }}</small>
                        </td>
                        <td><span class="badge text-bg-warning">{{ $loan->status }}</span></td>
                        <td>
                            @if($loan->loan_date)
                                <div>{{ $loan->loan_date->format('d/m/Y') }}</div>
                                <small class="text-muted">{{ $loan->loan_date->format('H:i:s') }}</small>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">Tidak ada pinjaman aktif.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($activeLoans->hasPages())
            <div class="card-footer bg-white">{{ $activeLoans->links() }}</div>
        @endif
    </div>
@endsection
