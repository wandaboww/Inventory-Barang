<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UserExcelImportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_users_excel(): void
    {
        $this->seed();

        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $response = $this->withSession($this->adminSession($admin))
            ->get(route('admin.users.export'));

        $response->assertOk();
        $response->assertHeader('content-type');
        $response->assertHeader('content-disposition');

        $this->assertStringContainsString(
            '.xlsx',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_admin_can_import_users_excel_and_skip_empty_rows(): void
    {
        $this->seed();

        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $csvContent = implode("\n", [
            'No,Identity,Nama,Kelas,No. HP,Role',
            '1,USR-TEST-001,Siswa Import,10 PPLG 1,08123450001,student',
            '2,ADM001,Administrator Update,-,081200000099,admin',
            ',,,,,',
            '3,USR-TEST-002,,11 TKJ 1,08123450002,student',
        ]);

        $excelFile = UploadedFile::fake()->createWithContent('import-data-pengguna.csv', $csvContent);

        $this->withSession($this->adminSession($admin))
            ->post(route('admin.users.import'), [
                'excel_file' => $excelFile,
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', function ($message): bool {
                return is_string($message)
                    && str_contains($message, 'Ditambahkan: 1')
                    && str_contains($message, 'Diperbarui: 1');
            });

        $this->assertDatabaseHas('users', [
            'identity_number' => 'USR-TEST-001',
            'name' => 'Siswa Import',
            'kelas' => '10 PPLG 1',
            'phone' => '08123450001',
            'role' => 'student',
        ]);

        $this->assertDatabaseHas('users', [
            'identity_number' => 'ADM001',
            'name' => 'Administrator Update',
            'phone' => '081200000099',
            'role' => 'admin',
        ]);

        $this->assertDatabaseMissing('users', [
            'identity_number' => 'USR-TEST-002',
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
