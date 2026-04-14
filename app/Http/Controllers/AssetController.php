<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query = Asset::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($builder) use ($search): void {
                $builder->where('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $assets = $query->latest('id')->paginate(20)->withQueryString();
        $allAssets = Asset::query()->get();

        $categoryCounts = $allAssets
            ->groupBy(fn (Asset $asset) => trim((string) ($asset->category ?: 'Tanpa Kategori')))
            ->map(fn ($group) => $group->count())
            ->sortDesc();

        $laptopBrandCounts = $allAssets
            ->filter(fn (Asset $asset) => strtolower((string) $asset->category) === 'laptop')
            ->groupBy(fn (Asset $asset) => trim((string) ($asset->brand ?: 'Tanpa Merk')))
            ->map(fn ($group) => $group->count())
            ->sortDesc();

        return view('admin.assets.index', [
            'assets' => $assets,
            'allCategories' => Asset::query()
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->select('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
            'allBrands' => Asset::query()
                ->whereNotNull('brand')
                ->where('brand', '!=', '')
                ->select('brand')
                ->distinct()
                ->orderBy('brand')
                ->pluck('brand'),
            'totalAssets' => $allAssets->count(),
            'categoryCounts' => $categoryCounts,
            'laptopBrandCounts' => $laptopBrandCounts,
            'totalLaptopAssets' => $laptopBrandCounts->sum(),
            'filters' => [
                'search' => (string) $request->input('search', ''),
                'category' => (string) $request->input('category', ''),
                'status' => (string) $request->input('status', ''),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand' => ['required', 'string', 'max:120'],
            'model' => ['required', 'string', 'max:120'],
            'serial_number' => ['required', 'string', 'max:120', 'unique:assets,serial_number'],
            'category' => ['required', 'string', 'max:120'],
            'barcode' => ['nullable', 'string', 'max:120', 'unique:assets,barcode'],
            'status' => ['required', Rule::in(['available', 'borrowed', 'maintenance', 'lost'])],
            'condition' => ['required', Rule::in(['good', 'minor_damage', 'major_damage', 'under_repair'])],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['barcode'] = $validated['barcode'] ?? $validated['serial_number'];
        $validated['qr_code_hash'] = hash('sha256', $validated['serial_number']);

        Asset::create($validated);

        return redirect()->route('admin.assets.index')->with('success', 'Barang berhasil ditambahkan.');
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'brand' => ['required', 'string', 'max:120'],
            'model' => ['required', 'string', 'max:120'],
            'serial_number' => ['required', 'string', 'max:120', Rule::unique('assets', 'serial_number')->ignore($asset->id)],
            'category' => ['required', 'string', 'max:120'],
            'barcode' => ['nullable', 'string', 'max:120', Rule::unique('assets', 'barcode')->ignore($asset->id)],
            'status' => ['required', Rule::in(['available', 'borrowed', 'maintenance', 'lost'])],
            'condition' => ['required', Rule::in(['good', 'minor_damage', 'major_damage', 'under_repair'])],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['barcode'] = $validated['barcode'] ?? $validated['serial_number'];
        $validated['qr_code_hash'] = hash('sha256', $validated['serial_number']);

        $asset->update($validated);

        return redirect()->route('admin.assets.index')->with('success', 'Data barang berhasil diperbarui.');
    }

    public function destroy(Asset $asset)
    {
        if ($asset->loans()->exists()) {
            return redirect()->route('admin.assets.index')
                ->with('error', 'Barang tidak bisa dihapus karena memiliki riwayat peminjaman.');
        }

        $asset->delete();

        return redirect()->route('admin.assets.index')->with('success', 'Barang berhasil dihapus.');
    }
}
