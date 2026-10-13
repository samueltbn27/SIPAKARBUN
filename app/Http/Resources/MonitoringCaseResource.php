<?php

namespace App\Http\Resources;

use App\Services\MonitoringStatusService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** Shared read projection for the map, charts, filters and monitoring table. */
class MonitoringCaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $case = $this->kasus;
        $status = app(MonitoringStatusService::class)->resolveRequest($this->resource);
        if ($case !== null) {
            $case->setRelation('permohonan', $this->resource);
            $record = (new KasusPenangananResource($case))->toArray($request);
        } else {
            $disease = $this->diagnosis?->results->first();
            $record = [
                'kasus_id' => null,
                'kasus_code' => null,
                'permohonan_id' => $this->id,
                'latitude_kasus' => $this->latitude_kasus,
                'longitude_kasus' => $this->longitude_kasus,
                'current_status' => null,
                'request_status' => $this->status,
                'monitoring_status' => $status['key'],
                'monitoring_status_label' => $status['label'],
                'is_overdue' => false,
                'history_incomplete' => $status['history_incomplete'],
                'history_issues' => $status['history_issues'],
                'history_note' => $status['history_note'],
                'effective_deadline_at' => null,
                'overdue_since' => null,
                'kelompok_tani' => ['id' => $this->kelompok_tani_id, 'nama' => $this->kelompok_tani_name_snapshot],
                'komoditas' => [
                    'id' => $this->diagnosis?->commodity_id,
                    'kode' => $this->monitoring_commodity_code,
                    'nama' => $this->monitoring_commodity_name,
                ],
                'penyakit' => [
                    'id' => $disease?->disease_id,
                    'kode' => null,
                    'nama' => $disease?->disease_name_snapshot,
                ],
                'wilayah' => [
                    'kode_kabupaten' => $this->kode_kabupaten, 'kabupaten' => $this->kabupaten,
                    'kode_kecamatan' => $this->kode_kecamatan, 'kecamatan' => $this->kecamatan,
                ],
                'penugasan_popt' => null,
                'latest_progress' => null,
                'final_report_exists' => false,
                'completed_at' => null,
                'can_delete_case' => false,
                'last_note' => $this->keputusan?->catatan ?? $this->catatan_pemohon,
                'last_status_at' => ($this->keputusan?->decided_at ?? $this->reviewed_at ?? $this->created_at)?->toIso8601String(),
                'riwayat_status' => [],
                'created_at' => $this->created_at?->toIso8601String(),
            ];
        }

        return $record + [
            'monitoring_id' => 'permohonan-'.$this->id,
            'permohonan_code' => $this->permohonan_code,
            'monitoring_recorded_at' => Carbon::parse($this->monitoring_recorded_at)->toIso8601String(),
            'rejection_reason' => $this->status === 'ditolak' ? $this->keputusan?->catatan : null,
        ];
    }
}
