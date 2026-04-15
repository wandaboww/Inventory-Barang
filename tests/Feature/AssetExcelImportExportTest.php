<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AssetExcelImportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_assets_excel(): void
    {
        $this->seed();

        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $response = $this->withSession($this->adminSession($admin))
            ->get(route('admin.assets.export'));

        $response->assertOk();
        $response->assertHeader('content-type');
        $response->assertHeader('content-disposition');

        $this->assertStringContainsString(
            '.xlsx',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_admin_can_import_assets_excel_and_skip_empty_rows(): void
    {
        $this->seed();

        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $csvContent = implode("\n", [
            'No,Kategori,Merk,MODEL / TYPE / SERI,Serial Number,Kode Barcode,Barcode Batang,Status,Kondisi',
            '1,Laptop,Lenovo,ThinkPad X13,SN-IMP-001,BC-IMP-001,BC-IMP-001,Available,Good',
            ',,,,,,,,',
            '2,Tablet,Acer,Iconia Tab A10,SN-IMP-002,,,Borrowed,Minor Damage',
            '3,Printer,,G2010,SN-IMP-003,,BC-IMP-003,Maintenance,Good',
        ]);

        $excelFile = UploadedFile::fake()->createWithContent('import-data-barang.csv', $csvContent);

        $this->withSession($this->adminSession($admin))
            ->post(route('admin.assets.import'), [
                'excel_file' => $excelFile,
            ])
            ->assertRedirect(route('admin.assets.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('assets', [
            'serial_number' => 'SN-IMP-001',
            'category' => 'Laptop',
            'brand' => 'Lenovo',
            'model' => 'ThinkPad X13',
            'barcode' => 'BC-IMP-001',
            'status' => 'available',
            'condition' => 'good',
        ]);

        $this->assertDatabaseHas('assets', [
            'serial_number' => 'SN-IMP-002',
            'category' => 'Tablet',
            'brand' => 'Acer',
            'model' => 'Iconia Tab A10',
            'barcode' => 'SN-IMP-002',
            'status' => 'borrowed',
            'condition' => 'minor_damage',
        ]);
    }

    /**
     * @return array<string, array<string, int|string>>
     */
    private function adminSession(User $admin): array
    {
        return [
            'admin_access' => [
                'user_id' => $admin->id,
                'user_name' => $admin->name,
                'granted_at' => now()->getTimestamp(),
            ],
        ];
    }
}
