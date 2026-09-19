<?php

namespace App\Http\Requests;

use App\Services\MonitoringStatusService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MonitoringReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'regency' => ['nullable', 'string', 'max:150'],
            'commodity' => ['nullable', 'string', 'max:150'],
            'disease' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(array_keys(app(MonitoringStatusService::class)->labels()))],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'date_to.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'status.in' => 'Status laporan tidak dikenali.',
        ];
    }
}
