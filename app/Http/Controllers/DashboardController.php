<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Loan;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{
    public function showAdminLogin(): RedirectResponse
    {
        return redirect()
            ->route('dashboard.public')
            ->with('show_admin_login', true);
    }

    public function publicIndex(): View
    {
        $publicSettingDefaults = [
            'public_running_text' => 'Kembalikan barang sebelum pukul 15.30 WIB !',
            'public_reminder_enabled' => '1',
            'public_reminder_background' => '#0A0A0A',
            'public_reminder_text_color' => '#FFFFFF',
            'public_running_text_speed' => '15',
            'public_running_text_font_size' => '17',
            'public_running_text_font_family' => 'system',
            'public_header_title' => 'Dashboard Inventaris',
            'public_header_subtitle' => 'Sistem Peminjaman & Pengembalian Aset Sekolah',
            'public_borrow_button_label' => 'Peminjaman Barang',
            'public_return_button_label' => 'Pengembalian Barang',
        ];

        $storedSettingValues = Setting::query()
            ->whereIn('setting_key', array_keys($publicSettingDefaults))
            ->pluck('setting_value', 'setting_key');

        $publicSettings = $publicSettingDefaults;
        foreach ($storedSettingValues as $key => $value) {
            if (!is_string($key) || !array_key_exists($key, $publicSettings)) {
                continue;
            }

            if ($value !== null && trim((string) $value) !== '') {
                $publicSettings[$key] = (string) $value;
            }
        }

        $runningText = $publicSettings['public_running_text'];

        $fontFamilyMap = [
            'system' => 'system-ui, sans-serif',
            'arial' => 'Arial, sans-serif',
            'verdana' => 'Verdana, sans-serif',
            'tahoma' => 'Tahoma, sans-serif',
            'georgia' => 'Georgia, serif',
            'mono' => 'monospace',
        ];

        $speed = (int) ($publicSettings['public_running_text_speed'] ?? 15);
        $fontSize = (int) ($publicSettings['public_running_text_font_size'] ?? 17);
        $fontFamilyKey = (string) ($publicSettings['public_running_text_font_family'] ?? 'system');
        $backgroundColor = strtoupper((string) ($publicSettings['public_reminder_background'] ?? '#0A0A0A'));
        $textColor = strtoupper((string) ($publicSettings['public_reminder_text_color'] ?? '#FFFFFF'));

        if (!preg_match('/^#[0-9A-F]{6}$/', $backgroundColor)) {
            $backgroundColor = '#0A0A0A';
        }

        if (!preg_match('/^#[0-9A-F]{6}$/', $textColor)) {
            $textColor = '#FFFFFF';
        }

        $publicSettings['public_running_text_speed'] = (string) max(5, min(40, $speed));
        $publicSettings['public_running_text_font_size'] = (string) max(12, min(36, $fontSize));
        $publicSettings['public_running_text_font_family'] = $fontFamilyMap[$fontFamilyKey] ?? $fontFamilyMap['system'];
        $publicSettings['public_reminder_background'] = $backgroundColor;
        $publicSettings['public_reminder_text_color'] = $textColor;

        $availableAssets = Asset::query()
            ->where('status', 'available')
            ->orderBy('brand')
            ->orderBy('model')
            ->get();

        $activeLoans = Loan::query()
            ->with([
                'user:id,name,identity_number,kelas,phone,role',
                'asset:id,brand,model',
            ])
            ->whereIn('status', ['active', 'overdue'])
            ->whereHas('user', function ($query): void {
                $query->where('role', '!=', 'teacher');
            })
            ->latest('loan_date')
            ->get();

        return view('dashboard.public', [
            'availableAssets' => $availableAssets,
            'activeLoans' => $activeLoans,
            'runningText' => $runningText,
            'publicSettings' => $publicSettings,
        ]);
    }

    public function loginAdmin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $admin = User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->get(['id', 'name', 'password'])
            ->first(function (User $user) use ($validated): bool {
                return filled($user->password) && Hash::check($validated['password'], $user->password);
            });

        if (!$admin) {
            return redirect()
                ->route('dashboard.public')
                ->with('error', 'Password admin tidak valid.')
                ->with('show_admin_login', true);
        }

        $request->session()->regenerate();
        $request->session()->put('admin_access', [
            'user_id' => $admin->id,
            'user_name' => $admin->name,
            'granted_at' => now()->getTimestamp(),
        ]);

        return redirect()
            ->route('dashboard.admin')
            ->with('success', 'Login admin berhasil.');
    }

    public function logoutAdmin(Request $request): RedirectResponse
    {
        $request->session()->forget('admin_access');
        $request->session()->regenerateToken();

        return redirect()
            ->route('dashboard.public')
            ->with('success', 'Logout admin berhasil.');
    }

    public function adminIndex(): View
    {
        $assets = Asset::query()->get();
        $activeLoans = Loan::query()
            ->with([
                'user:id,name,identity_number,kelas,phone',
                'asset:id,brand,model,serial_number',
            ])
            ->where('status', 'active')
            ->latest('loan_date')
            ->get();

        $normalizeCategory = static function (?string $category): string {
            $value = trim((string) $category);

            return $value !== '' ? $value : 'Tanpa Kategori';
        };

        $isLaptopCategory = static function (?string $category): bool {
            $value = strtolower(trim((string) $category));

            return str_contains($value, 'laptop') || str_contains($value, 'notebook');
        };

        $toSlides = static function ($items, callable $groupBy): Collection {
            return $items
                ->groupBy($groupBy)
                ->map(fn ($group, $label) => [
                    'label' => (string) $label,
                    'count' => $group->count(),
                ])
                ->sortByDesc('count')
                ->values();
        };

        $totalCategorySlides = $toSlides(
            $assets->reject(fn (Asset $asset) => $isLaptopCategory($asset->category)),
            fn (Asset $asset) => $normalizeCategory($asset->category)
        );

        $laptopAssets = $assets->filter(
            fn (Asset $asset) => $isLaptopCategory($asset->category)
        );

        $totalLaptopBrandSlides = $toSlides(
            $laptopAssets,
            fn (Asset $asset) => ($brand = trim((string) $asset->brand)) !== '' ? $brand : 'Tanpa Merek'
        );

        $availableLaptopAssets = $laptopAssets
            ->where('status', 'available')
            ->sortBy(fn (Asset $asset) => strtolower(trim((string) $asset->brand . ' ' . (string) $asset->model)))
            ->values();

        $availableLaptopBrandSlides = $toSlides(
            $availableLaptopAssets,
            fn (Asset $asset) => ($brand = trim((string) $asset->brand)) !== '' ? $brand : 'Tanpa Merek'
        );

        $borrowedCategorySlides = $toSlides(
            $activeLoans
                ->filter(fn (Loan $loan) => $loan->asset !== null)
                ->map(fn (Loan $loan) => $loan->asset),
            fn (Asset $asset) => $normalizeCategory($asset->category)
        );

        $damagedAssets = $assets->filter(function (Asset $asset): bool {
            return in_array((string) $asset->condition, ['minor_damage', 'major_damage'], true)
                || $asset->status === 'maintenance';
        });

        $damagedCategorySlides = $toSlides(
            $damagedAssets,
            fn (Asset $asset) => $normalizeCategory($asset->category)
        );

        return view('dashboard.admin', [
            'totalAssets' => $assets->count(),
            'totalLaptopAssets' => $laptopAssets->count(),
            'availableLaptopAssetsCount' => $availableLaptopAssets->count(),
            'borrowedAssets' => $activeLoans->count(),
            'damagedAssets' => $damagedAssets->count(),
            'totalCategorySlides' => $totalCategorySlides,
            'totalLaptopBrandSlides' => $totalLaptopBrandSlides,
            'availableLaptopBrandSlides' => $availableLaptopBrandSlides,
            'borrowedCategorySlides' => $borrowedCategorySlides,
            'damagedCategorySlides' => $damagedCategorySlides,
            'activeLoans' => $activeLoans,
            'availableAssets' => $availableLaptopAssets,
        ]);
    }
}
