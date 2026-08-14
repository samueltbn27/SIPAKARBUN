<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with('roles')
            ->when($request->q, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            })
            ->when($request->status === 'pending', fn ($q) => $q->where('is_active', false))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->latest();

        $users = $query->paginate(15)->withQueryString();
        $pendingCount = User::where('is_active', false)->count();

        return view('users.index', compact('users', 'pendingCount'));
    }

    public function approve(User $user): RedirectResponse
    {
        $user->update(['is_active' => true]);

        ActivityLog::record(
            'User',
            'activated',
            $user->name,
            $user->id,
            "Menyetujui akun \"{$user->name}\" ({$user->email})",
        );

        return back()->with('success', "Akun \"{$user->name}\" telah disetujui dan dapat digunakan untuk login.");
    }

    public function reject(User $user): RedirectResponse
    {
        $name = $user->name;
        $email = $user->email;
        $user->delete();

        ActivityLog::record(
            'User',
            'deleted',
            $name,
            null,
            "Menolak dan menghapus akun \"{$name}\" ({$email})",
        );

        return back()->with('success', "Akun \"{$name}\" telah ditolak dan dihapus.");
    }

    public function toggle(User $user): RedirectResponse
    {
        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $action = $user->is_active ? 'activated' : 'deactivated';

        ActivityLog::record(
            'User',
            $action,
            $user->name,
            $user->id,
            "Mengubah status akun \"{$user->name}\" menjadi " . ($user->is_active ? 'Aktif' : 'Nonaktif'),
        );

        return back()->with('success', "Akun \"{$user->name}\" telah {$status}.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $name = $user->name;
        $user->delete();

        ActivityLog::record(
            'User',
            'deleted',
            $name,
            null,
            "Menghapus akun \"{$name}\"",
        );

        return back()->with('success', "Akun \"{$name}\" telah dihapus.");
    }
}
