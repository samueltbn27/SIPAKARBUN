<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UatUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UatUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_uat_accounts_are_idempotent_and_use_configured_password(): void
    {
        config(['services.uat.password' => 'test-only-uat-password']);

        $this->seed(RoleSeeder::class);
        $this->seed(UatUserSeeder::class);
        $this->seed(UatUserSeeder::class);

        $this->assertSame(5, User::query()->where('email', 'like', '%.uat@sipakarbun.local')->count());
        $this->assertSame(
            ['admin', 'operator_uptd', 'pimpinan', 'poktan', 'popt'],
            User::query()
                ->where('email', 'like', '%.uat@sipakarbun.local')
                ->get()
                ->flatMap(fn (User $user) => $user->getRoleNames())
                ->sort()
                ->values()
                ->all(),
        );
    }
}
