<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Asset;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarcodePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_barcode_page(): void
    {
        $this->seed();

        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $asset = Asset::query()->orderBy('category')->orderBy('brand')->orderBy('model')->firstOrFail();

        $response = $this->withSession([
            'admin_access' => [
                'user_id' => $admin->id,
                'user_name' => $admin->name,
                'granted_at' => now()->getTimestamp(),
            ],
        ])->get(route('admin.loans.index'));

        $response->assertOk();
        $response->assertSee('Barcode Barang');
        $response->assertSee('Setting Barcode');
        $response->assertSee('Ukuran kertas');
        $response->assertSee('Preset grid A4');
        $response->assertSee('Pilih Barcode yang Dicetak');
        $response->assertSee('Filter kategori');
        $response->assertSee('Filter kondisi');
        $response->assertSee('Semua kategori');
        $response->assertSee('Semua kondisi');
        $response->assertSee('Container Preview');
        $response->assertSee('Ringkas');
        $response->assertSee('Standar');
        $response->assertSee('Rapat');
        $response->assertSee('Label 107');
        $response->assertSee('Label 103');
        $response->assertSee('3 x 4 Grid (12 kartu)');
        $response->assertSee('12 kartu per halaman');
        $response->assertSee($asset->serial_number);
        $response->assertSee('Print Epson L4150');
        $response->assertSee('Label 107');
        $response->assertDontSee('Format download gambar');
        $response->assertDontSee('Download PDF');
        $response->assertDontSee('barcode-grid-code');
        $response->assertDontSee('barcode-preview-code');
        $response->assertDontSee('Kategori');
        $response->assertDontSee('Kondisi');
        $response->assertDontSee('Pilih Aset');
    }

    public function test_label107_uses_tj_review_size(): void
    {
        $this->seed();

        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $asset = Asset::query()->orderBy('category')->orderBy('brand')->orderBy('model')->firstOrFail();

        $response = $this->withSession([
            'admin_access' => [
                'user_id' => $admin->id,
                'user_name' => $admin->name,
                'granted_at' => now()->getTimestamp(),
            ],
        ])->get(route('admin.loans.index', ['format' => 'label107']));

        $response->assertOk();
        $response->assertSee('Label T&J 107');
        $response->assertSee('64 x 32 mm');
        $response->assertSee('21 x 16.5 cm');
        $response->assertSee('12 label');
        $response->assertSee($asset->brand . ' ' . $asset->model);
        $response->assertDontSee('Preset grid A4');
        $response->assertDontSee('Ringkas');
        $response->assertDontSee('Standar');
        $response->assertDontSee('Serial Number');
        $response->assertDontSee('barcode-preview-code');
        $response->assertDontSee('Kategori');
        $response->assertDontSee('Kondisi');
    }

    public function test_label103_uses_tj_review_size(): void
    {
        $this->seed();

        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $asset = Asset::query()->orderBy('category')->orderBy('brand')->orderBy('model')->firstOrFail();

        $response = $this->withSession([
            'admin_access' => [
                'user_id' => $admin->id,
                'user_name' => $admin->name,
                'granted_at' => now()->getTimestamp(),
            ],
        ])->get(route('admin.loans.index', ['format' => 'label103']));

        $response->assertOk();
        $response->assertSee('Label T&J 103');
        $response->assertSee('103 x 50 mm');
        $response->assertSee($asset->brand . ' ' . $asset->model);
        $response->assertDontSee('Preset grid A4');
        $response->assertDontSee('Ringkas');
        $response->assertDontSee('Standar');
        $response->assertDontSee('Serial Number');
        $response->assertDontSee('barcode-preview-code');
        $response->assertDontSee('Kategori');
        $response->assertDontSee('Kondisi');
    }

    public function test_admin_can_select_ringkas_a4_preset(): void
    {
        $this->seed();

        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $asset = Asset::query()->orderBy('category')->orderBy('brand')->orderBy('model')->firstOrFail();

        $response = $this->withSession([
            'admin_access' => [
                'user_id' => $admin->id,
                'user_name' => $admin->name,
                'granted_at' => now()->getTimestamp(),
            ],
        ])->get(route('admin.loans.index', ['format' => 'a4', 'grid' => 'ringkas']));

        $response->assertOk();
        $response->assertSee('Ringkas');
        $response->assertSee('2 x 3 Grid (6 kartu)');
        $response->assertSee('kartu pada halaman ini');
        $response->assertSee('grid=ringkas');
        $response->assertSee($asset->serial_number);
    }

    public function test_admin_can_choose_specific_barcodes_for_preview_and_print(): void
    {
        $this->seed();

        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $assets = Asset::query()
            ->orderBy('category')
            ->orderBy('brand')
            ->orderBy('model')
            ->take(2)
            ->get();

        $selectedAsset = $assets->first();
        $unselectedAsset = $assets->last();

        $this->assertNotNull($selectedAsset);
        $this->assertNotNull($unselectedAsset);

        $response = $this->withSession([
            'admin_access' => [
                'user_id' => $admin->id,
                'user_name' => $admin->name,
                'granted_at' => now()->getTimestamp(),
            ],
        ])->get(route('admin.loans.index', [
            'format' => 'a4',
            'grid' => 'rapat',
            'selection_mode' => 'custom',
            'selected_assets' => [$selectedAsset->id],
        ]));

        $response->assertOk();
        $response->assertSee('Pilih Barcode yang Dicetak');
        $response->assertSee('barcode-grid-svg-' . $selectedAsset->id . '-');
        $response->assertDontSee('barcode-grid-svg-' . $unselectedAsset->id . '-');
        $response->assertSee('Preview dan print hanya menampilkan barcode yang dipilih.');
    }

    public function test_preview_shows_empty_state_when_custom_selection_has_no_barcode(): void
    {
        $this->seed();

        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $response = $this->withSession([
            'admin_access' => [
                'user_id' => $admin->id,
                'user_name' => $admin->name,
                'granted_at' => now()->getTimestamp(),
            ],
        ])->get(route('admin.loans.index', [
            'format' => 'a4',
            'grid' => 'rapat',
            'selection_mode' => 'custom',
        ]));

        $response->assertOk();
        $response->assertSee('Belum ada barcode dipilih untuk dicetak');
        $response->assertSee('Pilih minimal satu barcode pada panel Setting Barcode agar preview langsung muncul di sini.');
        $response->assertDontSee('barcode-grid-svg-');
    }

    public function test_barcode_filter_dropdown_options_follow_master_data_system_settings(): void
    {
        $this->seed();

        Setting::query()->updateOrCreate(
            ['setting_key' => 'asset_categories'],
            ['setting_value' => json_encode(['Kategori Uji Alpha', 'Kategori Uji Beta'])]
        );

        Setting::query()->updateOrCreate(
            ['setting_key' => 'asset_conditions'],
            ['setting_value' => json_encode(['Kondisi Uji Prima', 'Kondisi Uji Servis'])]
        );

        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $response = $this->withSession([
            'admin_access' => [
                'user_id' => $admin->id,
                'user_name' => $admin->name,
                'granted_at' => now()->getTimestamp(),
            ],
        ])->get(route('admin.loans.index'));

        $response->assertOk();
        $response->assertSee('Kategori Uji Alpha');
        $response->assertSee('Kategori Uji Beta');
        $response->assertSee('Kondisi Uji Prima');
        $response->assertSee('Kondisi Uji Servis');
    }

    public function test_barcode_filter_values_are_restored_from_query_string(): void
    {
        $this->seed();

        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $response = $this->withSession([
            'admin_access' => [
                'user_id' => $admin->id,
                'user_name' => $admin->name,
                'granted_at' => now()->getTimestamp(),
            ],
        ])->get(route('admin.loans.index', [
            'selector_search' => 'Laptop Uji Query',
            'selector_category' => 'laptop',
            'selector_condition' => 'good',
        ]));

        $response->assertOk();
        $response->assertSee('name="selector_search"', false);
        $response->assertSee('value="Laptop Uji Query"', false);
        $response->assertSee('name="selector_category"', false);
        $response->assertSee('value="laptop" selected', false);
        $response->assertSee('name="selector_condition"', false);
        $response->assertSee('value="good" selected', false);
    }

    public function test_a4_preview_can_render_five_pages_for_selected_barcodes(): void
    {
        $this->seed();

        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $selectedAssetIds = [];

        for ($index = 1; $index <= 60; $index += 1) {
            $serialNumber = sprintf('TEST-5PAGE-%03d', $index);

            $asset = Asset::query()->create([
                'category' => 'Laptop',
                'brand' => 'Brand Uji',
                'model' => 'Model ' . $index,
                'serial_number' => $serialNumber,
                'barcode' => $serialNumber,
                'qr_code_hash' => hash('sha256', $serialNumber),
                'condition' => 'good',
                'status' => 'available',
            ]);

            $selectedAssetIds[] = $asset->id;
        }

        $response = $this->withSession([
            'admin_access' => [
                'user_id' => $admin->id,
                'user_name' => $admin->name,
                'granted_at' => now()->getTimestamp(),
            ],
        ])->get(route('admin.loans.index', [
            'format' => 'a4',
            'grid' => 'rapat',
            'selection_mode' => 'custom',
            'selected_assets' => $selectedAssetIds,
        ]));

        $response->assertOk();
        $response->assertSee('Halaman 1/5');
        $response->assertSee('Halaman 5/5');
        $response->assertSee('data-page-total="5"', false);
        $response->assertSee('TEST-5PAGE-001');
        $response->assertSee('TEST-5PAGE-060');
    }
}
