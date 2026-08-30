<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class DisbunScheduleTest extends TestCase
{
    public function test_disbun_reference_sync_dijadwalkan_setiap_hari_tanpa_overlap(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event): bool => str_contains($event->command, 'disbun:sync-references'));

        $this->assertNotNull($event);
        $this->assertSame('0 2 * * *', $event->expression);
        $this->assertSame('Asia/Jakarta', $event->timezone);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertSame(120, $event->expiresAt);
    }
}
