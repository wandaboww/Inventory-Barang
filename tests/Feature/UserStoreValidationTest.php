<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStoreValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_store_requires_phone_number(): void
    {
        $this->seed();

        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $this->withSession([
            'admin_access' => [
                'user_id' => $admin->id,
                'user_name' => $admin->name,
                'granted_at' => now()->getTimestamp(),
            ],
        ])->post(route('admin.users.store'), [
            'name' => 'Siswa Baru',
            'identity_number' => '2024999',
            'role' => 'student',
            'kelas' => '10 PPLG 1',
            'email' => 'siswa-baru@example.com',
        ])->assertSessionHasErrors(['phone']);
    }

    public function test_admin_user_update_blocks_disabling_last_active_admin(): void
    {
        $this->seed();

        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $this->withSession([
            'admin_access' => [
                'user_id' => $admin->id,
                'user_name' => $admin->name,
                'granted_at' => now()->getTimestamp(),
            ],
        ])->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'identity_number' => $admin->identity_number,
            'role' => 'admin',
            'kelas' => $admin->kelas,
            'email' => $admin->email,
            'phone' => $admin->phone,
            'is_active' => false,
        ])->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Minimal harus ada satu akun admin aktif untuk login.');

        $admin->refresh();

        $this->assertTrue($admin->is_active);
        $this->assertSame('admin', $admin->role);
    }

    public function test_admin_user_destroy_blocks_deleting_last_active_admin(): void
    {
        $this->seed();

        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $this->withSession([
            'admin_access' => [
                'user_id' => $admin->id,
                'user_name' => $admin->name,
                'granted_at' => now()->getTimestamp(),
            ],
        ])->delete(route('admin.users.destroy', $admin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Akun admin aktif terakhir tidak bisa dihapus.');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}