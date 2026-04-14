<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Asset;
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
        $response->assertSee('Format download gambar');
        $response->assertSee('Container Preview');
        $response->assertSee('Ringkas');
        $response->assertSee('Standar');
        $response->assertSee('Rapat');
        $response->assertSee('Label 107');
        $response->assertSee('Label 103');
        $response->assertSee('3 x 4 Grid (12 kartu)');
        $response->assertSee('12 kartu per halaman');
        $response->assertSee($asset->serial_number);
        $response->assertSee('Download PDF');
        $response->assertSee('Print Epson L4150');
        $response->assertSee('Label 107');
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
        $response->assertSee('107 x 50 mm');
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
}
