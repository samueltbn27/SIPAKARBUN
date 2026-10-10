<?php

namespace DatabaseFactories;

use App\Models\KasusPenanganan;
use App\Models\PenugasanPopt;
use App\Models\PerpanjanganPenugasan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PerpanjanganPenugasanFactory extends Factory
{
    protected $model = PerpanjanganPenugasan::class;

    public function definition(): array
    {
        $currentDeadline = now()->addDays(2);

        return [
            'kasus_id' => KasusPenanganan::factory(),
            'penugasan_popt_id' => PenugasanPopt::factory(),
            'requested_by' => User::factory(),
            'current_deadline_at' => $currentDeadline,
            'proposed_deadline_at' => $currentDeadline->copy()->addDays(2),
            'reason' => 'Cuaca menghambat pemeriksaan lapangan.',
            'status' => PerpanjanganPenugasan::STATUS_PENDING,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_note' => null,
        ];
    }
}
