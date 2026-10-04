<?php

namespace Tests\Feature;

use App\Contracts\KnowledgeApiClient;
use App\Contracts\KomoditasReferensiClient;
use App\Exceptions\KnowledgeApiException;
use App\Models\AturanCf;
use App\Models\Gejala;
use App\Models\Penyakit;
use App\Models\PenyakitKomoditas;
use App\Models\User;
use App\Services\HttpKnowledgeApiClient;
use App\Services\LocalKnowledgeApiClient;
use App\Services\MockKomoditasReferensiClient;
use Dotenv\Dotenv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LocalKnowledgeSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_example_local_setup_loads_diagnosis_and_commodity_symptoms_without_http(): void
    {
        $defaults = Dotenv::parse(file_get_contents(base_path('.env.example')));
        $this->app->instance('env', 'local');
        config([
            'services.knowledge_api.base_url' => $defaults['KNOWLEDGE_API_BASE_URL'],
            'services.knowledge_api.token' => $defaults['KNOWLEDGE_API_TOKEN'],
        ]);
        $this->app->instance(KomoditasReferensiClient::class, new MockKomoditasReferensiClient);
        Http::preventStrayRequests();

        $disease = Penyakit::factory()->create(['status' => 'aktif']);
        $symptom = Gejala::factory()->create(['status' => 'aktif']);
        $inactive = Gejala::factory()->create(['status' => 'nonaktif']);
        PenyakitKomoditas::create(['penyakit_id' => $disease->id, 'komoditas_id' => 1]);
        AturanCf::factory()->create([
            'penyakit_id' => $disease->id,
            'gejala_id' => $symptom->id,
            'status' => 'aktif',
        ]);

        Role::findOrCreate('poktan', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('poktan');
        $response = $this->actingAs($user)->get('/diagnosis');
        $response->assertOk();
        $this->assertNull($response->viewData('knowledgeError'));
        $response->assertViewHas('komoditas', fn ($items) => collect($items)->contains('id', 1));
        $response->assertViewHas('komoditasGejalaMap', fn ($map) => ($map[1] ?? []) === [$symptom->id]);
        $response->assertViewHas('gejala', fn ($items) => collect($items)->contains('id', $symptom->id)
            && ! collect($items)->contains('id', $inactive->id));
        $this->assertInstanceOf(LocalKnowledgeApiClient::class, app(KnowledgeApiClient::class));
        Http::assertNothingSent();
    }

    public function test_production_does_not_use_local_adapter_when_url_is_empty(): void
    {
        $this->app->instance('env', 'production');
        config(['services.knowledge_api.base_url' => '', 'services.knowledge_api.token' => '']);
        $client = app(KnowledgeApiClient::class);
        $this->assertInstanceOf(HttpKnowledgeApiClient::class, $client);
        $this->expectException(KnowledgeApiException::class);
        $client->penyakit();
    }

    public function test_explicit_remote_url_still_requires_a_token_in_local_environment(): void
    {
        $this->app->instance('env', 'local');
        config(['services.knowledge_api.base_url' => 'https://knowledge.example', 'services.knowledge_api.token' => '']);
        $client = app(KnowledgeApiClient::class);
        $this->assertInstanceOf(HttpKnowledgeApiClient::class, $client);
        $this->expectException(KnowledgeApiException::class);
        $this->expectExceptionMessage('KNOWLEDGE_API_TOKEN');
        $client->penyakit();
    }
}
