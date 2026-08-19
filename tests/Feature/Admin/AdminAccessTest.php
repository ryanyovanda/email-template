<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function adminRoutes(): array
    {
        return [
            'dashboard' => ['admin.dashboard'],
            'users' => ['admin.users.index'],
            'templates' => ['admin.templates.index'],
            'ai usage' => ['admin.ai-usage.index'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_the_cms_is_closed_to_regular_users(string $route): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route($route))
            ->assertForbidden();
    }

    #[DataProvider('adminRoutes')]
    public function test_the_cms_is_open_to_admins(string $route): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        $this->actingAs($admin)->get(route($route))->assertOk();
    }

    public function test_the_cms_is_closed_to_guests(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }
}
