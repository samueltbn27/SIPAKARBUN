<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UatUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UatUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_uat_accounts_are_idempotent_and_use_configured_passwords(): void
    {
        config(['services.uat.accounts' => [
            'operator_uptd' => ['password' => 'Operator2026!'],
            'popt' => ['password' => 'Popt2026!'],
            'poktan' => ['password' => 'Poktan2026!'],
            'pimpinan' => ['password' => 'Pimpinan2026!'],
        ]]);

        $this->seed(RoleSeeder::class);
        $this->seed(UatUserSeeder::class);
        $this->seed(UatUserSeeder::class);

        $this->assertSame(4, User::query()->where('email', 'like', '%@sipakarbun.local')->count());
        $this->assertDatabaseMissing('users', ['email' => 'admin.uat@sipakarbun.local']);
        $this->assertSame(
            ['operator_uptd', 'pimpinan', 'poktan', 'popt'],
            User::query()
                ->where('email', 'like', '%@sipakarbun.local')
                ->get()
                ->flatMap(fn (User $user) => $user->getRoleNames())
                ->sort()
                ->values()
                ->all(),
        );

        $this->assertTrue(Hash::check('Operator2026!', User::whereEmail('operator@sipakarbun.local')->value('password')));
        $this->assertTrue(Hash::check('Popt2026!', User::whereEmail('popt@sipakarbun.local')->value('password')));
        $this->assertTrue(Hash::check('Poktan2026!', User::whereEmail('poktan@sipakarbun.local')->value('password')));
        $this->assertTrue(Hash::check('Pimpinan2026!', User::whereEmail('pimpinan@sipakarbun.local')->value('password')));
    }
}
