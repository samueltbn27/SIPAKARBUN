<?php

namespace App\Http\Controllers;

use App\Models\LaporanGejala;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaporanGejalaImageController extends Controller
{
    public function __invoke(Request $request, LaporanGejala $laporanGejala): BinaryFileResponse
    {
        $user = $request->user();
        $isPopt = $user?->hasRole('popt') ?? false;
        $isOwner = $user?->hasRole('poktan') && (int) $laporanGejala->created_by === (int) $user->id;

        abort_unless($isPopt || $isOwner, 404);
        abort_unless(
            $laporanGejala->image_path !== null
                && str_starts_with($laporanGejala->image_path, 'laporan-gejala/')
                && Storage::disk('local')->exists($laporanGejala->image_path),
            404,
        );

        return response()->file(
            Storage::disk('local')->path($laporanGejala->image_path),
            ['Cache-Control' => 'private, no-store'],
        );
    }
}
