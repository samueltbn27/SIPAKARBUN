<?php

namespace Tests\Unit\WebGIS;

use Tests\TestCase;

class RbacAccessContractTest extends TestCase
{
    public function test_dashboard_monitoring_route_has_backend_role_guard(): void
    {
        $route = app('router')->getRoutes()->getByName('monitoring.dashboard');
        $middleware = $route->gatherMiddleware();

        $this->assertContains('auth', $middleware);
        $this->assertContains('role:admin|operator_uptd|pimpinan', $middleware);
    }

    public function test_webgis_route_preserves_existing_role_guard(): void
    {
        $route = app('router')->getRoutes()->getByName('webgis.index');
        $middleware = $route->gatherMiddleware();

        $this->assertContains('auth', $middleware);
        $this->assertContains('role:admin|operator_uptd|popt|pimpinan', $middleware);
    }

    public function test_access_contract_documents_pending_case_modules(): void
    {
        $contract = file_get_contents(base_path('docs/mahasiswa-3/RBAC_ACCESS_CONTRACT.md'));

        $this->assertIsString($contract);
        $this->assertStringContainsString('case-verification', $contract);
        $this->assertStringContainsString('case-assignment', $contract);
        $this->assertStringContainsString('401', $contract);
        $this->assertStringContainsString('403', $contract);
    }
}
