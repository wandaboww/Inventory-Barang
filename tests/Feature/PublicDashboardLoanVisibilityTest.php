<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDashboardLoanVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_dashboard_hides_teacher_loans_and_shows_non_teacher_loans(): void
    {
        $this->seed();

        $this->get(route('dashboard.public'))
            ->assertOk()
            ->assertSee('Daftar Barang Dipinjam')
            ->assertSee('Ani (Siswa)')
            ->assertDontSee('Pak Budi (Guru)');
    }

    public function test_admin_dashboard_still_shows_teacher_loans(): void
    {
        $this->seed();

        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $this->withSession([
            'admin_access' => [
                'user_id' => $admin->id,
                'user_name' => $admin->name,
                'granted_at' => now()->getTimestamp(),
            ],
        ])->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee('Pak Budi (Guru)');
    }
}
