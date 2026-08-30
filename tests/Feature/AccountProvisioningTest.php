<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccountProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create([
            'name' => 'Admin Bootstrap',
            'email' => 'admin.bootstrap@example.test',
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');
    }

    private function accountPayload(string $role, ?string $email = null): array
    {
        return [
            'name' => ucfirst(str_replace('_', ' ', $role)).' UAT',
            'email' => $email ?? $role.'-new@example.test',
            'password' => 'Valid2026!',
            'password_confirmation' => 'Valid2026!',
            'role' => $role,
            'phone' => '081234567890',
            'agree_terms' => '1',
        ];
    }

    public function test_admin_can_open_create_account_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('register'));

        $response->assertOk()
            ->assertSee('Operator UPTD')
            ->assertSee('POPT')
            ->assertSee('Poktan / Gapoktan')
            ->assertSee('Pimpinan')
            ->assertDontSee('value="admin"')
            ->assertDontSee('value="pakar"');
    }

    public function test_non_admin_cannot_open_or_submit_create_account(): void
    {
        $operator = User::factory()->create(['is_active' => true]);
        $operator->assignRole('operator_uptd');

        $this->actingAs($operator)->get(route('register'))->assertForbidden();
        $this->actingAs($operator)->post(route('register.store'), $this->accountPayload('popt'))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'popt-new@example.test']);
    }

    #[DataProvider('nonAdminRoles')]
    public function test_admin_can_create_each_non_admin_role(string $role): void
    {
        $email = $role.'-created@example.test';

        $this->actingAs($this->admin)
            ->post(route('register.store'), $this->accountPayload($role, $email))
            ->assertRedirect(route('login'));

        $user = User::where('email', $email)->firstOrFail();

        $this->assertSame([$role], $user->getRoleNames()->all());
        $this->assertFalse($user->is_active);
        $this->assertTrue(Hash::check('Valid2026!', $user->password));
    }

    public static function nonAdminRoles(): array
    {
        return [
            'operator_uptd' => ['operator_uptd'],
            'popt' => ['popt'],
            'poktan' => ['poktan'],
            'pimpinan' => ['pimpinan'],
        ];
    }

    #[DataProvider('forbiddenRoles')]
    public function test_forged_or_unknown_role_is_rejected(string $role): void
    {
        $email = $role.'-forged@example.test';

        $this->actingAs($this->admin)
            ->from(route('register'))
            ->post(route('register.store'), $this->accountPayload($role, $email))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public static function forbiddenRoles(): array
    {
        return [
            'admin' => ['admin'],
            'pakar' => ['pakar'],
            'unknown' => ['superadmin'],
        ];
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $email = 'duplicate@example.test';
        $this->actingAs($this->admin)
            ->post(route('register.store'), $this->accountPayload('popt', $email));

        $this->actingAs($this->admin)
            ->from(route('register'))
            ->post(route('register.store'), $this->accountPayload('pimpinan', $email))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::where('email', $email)->count());
    }

    public function test_password_must_follow_application_password_policy(): void
    {
        $this->actingAs($this->admin)
            ->from(route('register'))
            ->post(route('register.store'), array_merge(
                $this->accountPayload('popt', 'weak-password@example.test'),
                ['password' => 'password123', 'password_confirmation' => 'password123'],
            ))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'weak-password@example.test']);
    }

    public function test_existing_admin_is_not_changed_by_account_flow(): void
    {
        $this->admin->update(['password' => Hash::make('BootstrapOnly2026!')]);
        $originalId = $this->admin->id;

        $this->actingAs($this->admin)
            ->post(route('register.store'), $this->accountPayload('operator_uptd', 'admin-attempt@example.test'))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', ['id' => $originalId, 'email' => 'admin.bootstrap@example.test']);
        $this->assertTrue(Hash::check('BootstrapOnly2026!', $this->admin->fresh()->password));
        $this->assertDatabaseHas('users', ['email' => 'admin-attempt@example.test']);
    }
}
