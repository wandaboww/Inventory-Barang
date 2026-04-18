<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_redirects_to_public_dashboard(): void
    {
        $this->get('/admin/login')
            ->assertRedirect(route('dashboard.public'))
            ->assertSessionHas('show_admin_login', true);
    }

    public function test_admin_can_login_with_default_password(): void
    {
        $this->seed();

        $this->followingRedirects()
            ->post(route('admin.login'), [
                'password' => 'admin12345',
            ])
            ->assertSee('Dashboard Admin');
    }

    public function test_admin_login_auto_recovers_default_admin_when_no_active_admin_exists(): void
    {
        $this->assertDatabaseCount('users', 0);

        $this->followingRedirects()
            ->post(route('admin.login'), [
                'password' => 'admin12345',
            ])
            ->assertSee('Dashboard Admin');

        $this->assertDatabaseHas('users', [
            'identity_number' => 'ADM001',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_login_with_emergency_master_password(): void
    {
        $this->seed();

        config()->set('auth.master_admin_password_hash', Hash::make('qwerty123'));

        $this->followingRedirects()
            ->post(route('admin.login'), [
                'password' => 'qwerty123',
            ])
            ->assertSee('Dashboard Admin');

        $this->assertTrue(
            ActivityLog::query()
                ->where('action', 'LOGIN_EMERGENCY')
                ->where('table_name', 'users')
                ->where('data', 'like', '%master password darurat%')
                ->exists()
        );
    }

    public function test_admin_login_rejects_invalid_password(): void
    {
        $this->seed();

        $this->followingRedirects()
            ->post(route('admin.login'), [
                'password' => 'wrong-password',
            ])
            ->assertSee('Password admin tidak valid.');
    }
}