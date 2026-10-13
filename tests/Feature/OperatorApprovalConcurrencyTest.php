<?php

namespace Tests\Feature;

use App\Models\DiagnosisResult;
use App\Models\PermohonanPenanganan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/** Real competing processes, each with its own SQLite connection and stale model. */
class OperatorApprovalConcurrencyTest extends TestCase
{
    public function test_two_operators_approve_one_request_without_duplicate_decisions_or_assignments(): void
    {
        $directory = sys_get_temp_dir().'/sipakarbun-approval-race-'.bin2hex(random_bytes(8));
        File::makeDirectory($directory);
        $database = $directory.'/isolated.sqlite';
        touch($database);
        $original = config('database.connections.sqlite');
        $processes = [];
        try {
            config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database,
                'database.connections.sqlite.url' => null, 'database.connections.sqlite.busy_timeout' => 5000]);
            DB::purge('sqlite');
            $this->artisan('migrate', ['--force' => true])->assertSuccessful();
            foreach (['operator_uptd', 'popt'] as $role) {
                Role::findOrCreate($role);
            }
            $request = PermohonanPenanganan::factory()->sedangDireview()->create();
            DiagnosisResult::factory()->create(['diagnosis_id' => $request->diagnosis_id, 'ranking' => 1]);
            $target = now()->addDays(3)->toDateTimeString();
            $worker = <<<'WORKER'
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $argv[1],
    'database.connections.sqlite.url' => null, 'database.connections.sqlite.busy_timeout' => 5000,
    'cache.default' => 'array', 'session.driver' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
$app->instance(App\Contracts\KomoditasReferensiClient::class, new App\Services\MockKomoditasReferensiClient);
$request = App\Models\PermohonanPenanganan::findOrFail((int) $argv[2]);
$operator = App\Models\User::with('roles')->findOrFail((int) $argv[3]);
touch($argv[5]);
$until = microtime(true) + 8;
while (! is_file($argv[6])) {
    if (microtime(true) > $until) { throw new RuntimeException('Barrier timed out.'); }
    usleep(5000);
}
try {
    $case = $app->make(App\Services\PermohonanService::class)->terima($request, $operator, 'Concurrent approval test.', (int) $argv[4], $argv[7]);
    echo json_encode(['status' => 201, 'case_id' => $case->id]);
} catch (Illuminate\Validation\ValidationException $exception) {
    echo json_encode(['status' => 422, 'fields' => array_keys($exception->errors())]);
}
WORKER;
            for ($index = 0; $index < 2; $index++) {
                $operator = User::factory()->create();
                $operator->assignRole('operator_uptd');
                $popt = User::factory()->create();
                $popt->assignRole('popt');
                $process = new Process([PHP_BINARY, '-r', $worker, $database, (string) $request->id,
                    (string) $operator->id, (string) $popt->id, $directory.'/ready-'.$index, $directory.'/go', $target], base_path(), [
                        'APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $database, 'DB_URL' => '',
                        'APP_CONFIG_CACHE' => $directory.'/unused-config.php', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array',
                    ], timeout: 15);
                $process->start();
                $processes[] = $process;
            }
            $until = microtime(true) + 8;
            while (! is_file($directory.'/ready-0') || ! is_file($directory.'/ready-1')) {
                if (microtime(true) > $until) {
                    $this->fail('Both workers must load the pre-approval model before the barrier opens.');
                }
                usleep(5000);
            }
            touch($directory.'/go');
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
                $results[] = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            }
            $statuses = array_column($results, 'status');
            sort($statuses);
            $this->assertSame([201, 422], $statuses);
            $loser = collect($results)->firstWhere('status', 422);
            $this->assertContains('permohonan_id', $loser['fields']);
            $this->assertSame('diterima', $request->fresh()->status);
            $this->assertDatabaseCount('keputusan_permohonan', 1);
            $this->assertDatabaseCount('kasus_penanganan', 1);
            $this->assertDatabaseCount('penugasan_popt', 1);
            $this->assertDatabaseCount('riwayat_status_penanganan', 2);
            $this->assertDatabaseCount('progres_penanganan', 0);
            $this->assertNull($request->fresh()->kasus->penugasanAktif->accepted_at);
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            DB::purge('sqlite');
            config(['database.connections.sqlite' => $original]);
            File::deleteDirectory($directory);
        }
    }
}
