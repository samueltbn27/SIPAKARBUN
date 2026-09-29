<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesUsersWithRoles;

class SidebarNavigationTest extends TestCase
{
    use CreatesUsersWithRoles;
    use RefreshDatabase;

    private const KNOWLEDGE_ROUTES = [
        'knowledge.penyakit.index',
        'knowledge.gejala.index',
        'knowledge.solusi.index',
        'knowledge.aturan-cf.index',
        'knowledge.cf-methods.index',
    ];

    public function test_operator_memiliki_satu_grup_knowledge_canonical_tanpa_duplikasi(): void
    {
        $response = $this->actingAs($this->createOperator())
            ->get(route('webgis.index'))
            ->assertOk()
            ->assertSee('Knowledge Management')
            ->assertDontSee('Referensi Knowledge');

        $html = $response->getContent();

        foreach (self::KNOWLEDGE_ROUTES as $routeName) {
            $this->assertSame(1, substr_count($html, 'href="'.route($routeName).'"'), $routeName.' harus tampil satu kali.');
        }

        $this->assertSame(1, substr_count($html, 'href="'.route('knowledge.komoditas.index').'"'));
        $this->assertStringContainsString('Master Data', $html);
    }

    public function test_operator_active_state_mengikuti_route_knowledge(): void
    {
        $operator = $this->createOperator();

        foreach (self::KNOWLEDGE_ROUTES as $routeName) {
            $response = $this->actingAs($operator)->get(route($routeName))->assertOk();
            $this->assertStringContainsString('aria-current="page"', $response->getContent(), $routeName.' harus memiliki active state.');
        }
    }

    public function test_admin_dan_popt_menggunakan_grup_knowledge_yang_sama_tanpa_master_duplikat(): void
    {
        $adminResponse = $this->actingAs($this->createAdmin())->get(route('webgis.index'))->assertOk();
        $this->assertKnowledgeLinksOnce($adminResponse->getContent());
        $this->assertStringContainsString('Master Data', $adminResponse->getContent());

        $poptResponse = $this->actingAs($this->createPopt())->get(route('knowledge.cf-methods.index'))->assertOk();
        $this->assertKnowledgeLinksOnce($poptResponse->getContent());
        $this->assertStringNotContainsString('Master Data', $poptResponse->getContent());
        $this->assertStringNotContainsString('Referensi Knowledge', $poptResponse->getContent());
    }

    public function test_pimpinan_tidak_mendapatkan_menu_mutasi_knowledge(): void
    {
        $response = $this->actingAs($this->createUserWithRole('pimpinan'))
            ->get(route('webgis.index'))
            ->assertOk();

        $this->assertStringNotContainsString('Knowledge Management', $response->getContent());
        $this->assertStringNotContainsString('Master Data', $response->getContent());
        $this->assertStringNotContainsString(route('knowledge.penyakit.index'), $response->getContent());
    }

    private function assertKnowledgeLinksOnce(string $html): void
    {
        $this->assertStringContainsString('Knowledge Management', $html);
        $this->assertStringNotContainsString('Referensi Knowledge', $html);

        foreach (self::KNOWLEDGE_ROUTES as $routeName) {
            $this->assertSame(1, substr_count($html, 'href="'.route($routeName).'"'), $routeName.' harus tampil satu kali.');
        }
    }

    private function createUserWithRole(string $role): User
    {
        Role::firstOrCreate(['name' => $role]);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
