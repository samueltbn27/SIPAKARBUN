<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PlaceholderController — halaman sementara untuk menu yang belum
 * diimplementasikan (TAHAP 1). Alur diagnosis & kasus menyusul.
 *
 * Judul/subjudul diturunkan dari nama route supaya tidak duplikasi.
 */
class PlaceholderController extends Controller
{
    private const MAP = [
        'operator.permohonan' => ['Permohonan Masuk', 'Review & putuskan permohonan penanganan dari Poktan.'],
        'kasus.index' => ['Kasus', 'Pantau kasus penanganan yang telah diterima.'],
        'popt.penugasan' => ['Penugasan Saya', 'Kasus yang sedang Anda tangani.'],
        'pengguna.index' => ['Manajemen Pengguna', 'Kelola & setujui akun pengguna.'],
    ];

    public function __invoke(Request $request): View
    {
        $routeName = (string) $request->route()?->getName();

        [$title, $subtitle] = self::MAP[$routeName] ?? [
            ucwords(str_replace(['.', '-'], ' ', $routeName)),
            'Modul sedang disiapkan.',
        ];

        return view('pages.placeholder', compact('title', 'subtitle'));
    }
}
