<?php

namespace Tests\Feature;

use App\Models\RefKelompokTani;
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
        // Akun Poktan hanya butuh Kelompok Tani + Password (nama/email
        // dibuat otomatis dari referensi Disbun).
        if ($role === 'poktan') {
            return [
                'role' => 'poktan',
                'kelompok_tani_id' => $this->poktanTersedia()->id,
                'password' => 'Valid2026!',
                'password_confirmation' => 'Valid2026!',
            ];
        }

        return [
            'name' => ucfirst(str_replace('_', ' ', $role)).' UAT',
            'email' => $email ?? $role.'-new@example.test',
            'password' => 'Valid2026!',
            'password_confirmation' => 'Valid2026!',
            'role' => $role,
            'phone' => '081234567890',
        ];
    }

    private function poktanTersedia(): RefKelompokTani
    {
        return RefKelompokTani::firstOrCreate(
            ['source' => RefKelompokTani::SOURCE_DISBUN, 'disbun_record_id' => 'TEST-001'],
            [
                'kode' => 'TEST-001',
                'kode_kelompok' => 'TEST-001',
                'nama' => 'Poktan UAT',
                'kabupaten' => 'KAB UAT',
                'kecamatan' => 'Kec UAT',
                'desa' => 'Desa UAT',
                'source_is_active' => true,
                'is_verified' => true,
                'sync_status' => RefKelompokTani::SYNC_SYNCED,
            ],
        );
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

    public function test_guest_can_open_poktan_registration_page_only(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Kelompok Tani')
            ->assertSee('Password')
            ->assertDontSee('Nama Lengkap')
            ->assertDontSee('No. HP')
            ->assertDontSee('Syarat')
            ->assertDontSee('Operator UPTD')
            ->assertDontSee('POPT')
            ->assertDontSee('Pimpinan')
            ->assertDontSee('value="admin"')
            ->assertDontSee('value="pakar"');
    }

    public function test_authenticated_non_admin_sees_poktan_form_only(): void
    {
        $operator = User::factory()->create(['is_active' => true]);
        $operator->assignRole('operator_uptd');

        $this->actingAs($operator)->get(route('register'))
            ->assertOk()
            ->assertSee('Kelompok Tani')
            ->assertDontSee('Operator UPTD')
            ->assertDontSee('POPT')
            ->assertDontSee('Pimpinan');
    }

    public function test_authenticated_non_admin_cannot_provision_privileged_account(): void
    {
        $operator = User::factory()->create(['is_active' => true]);
        $operator->assignRole('operator_uptd');

        $this->actingAs($operator)->get(route('register'))
            ->assertOk()
            ->assertSee('Kelompok Tani')
            ->assertDontSee('Operator UPTD')
            ->assertDontSee('POPT')
            ->assertDontSee('Pimpinan');

        $this->actingAs($operator)
            ->from(route('register'))
            ->post(route('register.store'), array_merge(
                $this->accountPayload('popt'),
                ['name' => 'Popt UAT', 'email' => 'popt-new@example.test', 'phone' => '081234567890'],
            ))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'popt-new@example.test']);
    }

    #[DataProvider('nonAdminRoles')]
    public function test_admin_can_create_each_non_admin_role(string $role): void
    {
        $email = $role.'-created@example.test';

        $this->actingAs($this->admin)
            ->post(route('register.store'), $this->accountPayload($role, $email))
            ->assertRedirect(route('login'));

        if ($role === 'poktan') {
            $user = User::where('kelompok_tani_id', $this->poktanTersedia()->id)->firstOrFail();
            $this->assertSame('Poktan UAT', $user->name);
            $this->assertSame('poktan-TEST-001@sipakarbun.local', $user->email);
        } else {
            $user = User::where('email', $email)->firstOrFail();
        }

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

    public function test_guest_poktan_registration_requires_kelompok_tani(): void
    {
        $this->from(route('register'))
            ->post(route('register.store'), [
                'password' => 'Valid2026!',
                'password_confirmation' => 'Valid2026!',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('kelompok_tani_id');

        $this->assertSame(0, User::where('kelompok_tani_nama', 'Poktan UAT')->count());
    }

    public function test_guest_poktan_registration_rejects_unknown_kelompok_tani(): void
    {
        $this->from(route('register'))
            ->post(route('register.store'), [
                'kelompok_tani_id' => 999999,
                'password' => 'Valid2026!',
                'password_confirmation' => 'Valid2026!',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('kelompok_tani_id');
    }

    public function test_guest_poktan_registration_rejects_quarantined_kelompok_tani(): void
    {
        $quarantined = RefKelompokTani::create([
            'disbun_record_id' => 'TEST-QUARANTINE',
            'source' => RefKelompokTani::SOURCE_DISBUN,
            'kode' => 'TEST-Q',
            'kode_kelompok' => 'TEST-Q',
            'nama' => 'Poktan Karantina',
            'source_is_active' => true,
            'is_verified' => true,
            'sync_status' => RefKelompokTani::SYNC_QUARANTINED,
        ]);

        $payload = [
            'role' => 'poktan',
            'kelompok_tani_id' => $quarantined->id,
            'password' => 'Valid2026!',
            'password_confirmation' => 'Valid2026!',
        ];

        $this->from(route('register'))
            ->post(route('register.store'), $payload)
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('kelompok_tani_id');
    }

    public function test_guest_poktan_registration_links_kelompok_tani_and_stays_pending(): void
    {
        $poktan = $this->poktanTersedia();

        // Tanpa field role sekalipun, default-nya Poktan.
        $this->post(route('register.store'), [
            'kelompok_tani_id' => $poktan->id,
            'password' => 'Valid2026!',
            'password_confirmation' => 'Valid2026!',
        ])->assertRedirect(route('login'));

        $user = User::where('kelompok_tani_id', $poktan->id)->firstOrFail();

        $this->assertSame('Poktan UAT', $user->name);
        $this->assertSame('poktan-TEST-001@sipakarbun.local', $user->email);
        $this->assertSame('TEST-001', $user->kelompok_tani_kode);
        $this->assertSame('Poktan UAT', $user->kelompok_tani_nama);
        $this->assertSame(['poktan'], $user->getRoleNames()->all());
        $this->assertFalse($user->is_active);
    }

    public function test_guest_cannot_register_same_kelompok_tani_twice(): void
    {
        $poktan = $this->poktanTersedia();
        $payload = [
            'kelompok_tani_id' => $poktan->id,
            'password' => 'Valid2026!',
            'password_confirmation' => 'Valid2026!',
        ];

        $this->post(route('register.store'), $payload)->assertRedirect(route('login'));

        $this->from(route('register'))
            ->post(route('register.store'), $payload)
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('kelompok_tani_id');

        $this->assertSame(1, User::where('kelompok_tani_id', $poktan->id)->count());
    }

    public function test_poktan_can_login_with_kode_poktan(): void
    {
        $poktan = $this->poktanTersedia();

        $this->post(route('register.store'), [
            'kelompok_tani_id' => $poktan->id,
            'password' => 'Valid2026!',
            'password_confirmation' => 'Valid2026!',
        ])->assertRedirect(route('login'));

        $user = User::where('kelompok_tani_id', $poktan->id)->firstOrFail();
        $user->update(['is_active' => true]);

        $this->post(route('login.store'), [
            'identitas' => 'TEST-001',
            'password' => 'Valid2026!',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_wrong_kode_poktan_is_rejected(): void
    {
        $this->from(route('login'))
            ->post(route('login.store'), [
                'identitas' => 'TIDAK-ADA-000',
                'password' => 'Valid2026!',
            ])
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_register_kelompok_tani_options_are_public_but_limited_to_tersedia(): void
    {
        $poktan = $this->poktanTersedia();

        $this->getJson(route('register.kelompok-tani', ['q' => 'Poktan UAT']))
            ->assertOk()
            ->assertJsonFragment(['id' => $poktan->id, 'nama' => 'Poktan UAT']);
    }
}
