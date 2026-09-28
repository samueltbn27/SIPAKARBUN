<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCfMethodRequest;
use App\Http\Requests\UpdateCfMethodRequest;
use App\Models\ActivityLog;
use App\Models\CfMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CfMethodController extends Controller
{
    public function index(Request $request): View
    {
        $methods = CfMethod::query()
            ->withCount('aturanCf')
            ->when($request->boolean('aktif_saja'), fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->orderByDesc('version')
            ->paginate(15)
            ->withQueryString();

        return view('knowledge.cf-methods.index', compact('methods'));
    }

    public function show(CfMethod $cfMethod): View
    {
        return view('knowledge.cf-methods.show', compact('cfMethod'));
    }

    public function create(): View
    {
        return view('knowledge.cf-methods.create', ['cfMethod' => null]);
    }

    public function store(StoreCfMethodRequest $request): RedirectResponse
    {
        $method = CfMethod::create($request->validated());
        ActivityLog::record('Metode CF', 'created', $method->name, $method->id, "Menambahkan metode CF {$method->name} v{$method->version}.");

        return redirect()->route('knowledge.cf-methods.show', $method)
            ->with('success', 'Metode CF berhasil dibuat.');
    }

    public function edit(CfMethod $cfMethod): View
    {
        return view('knowledge.cf-methods.edit', compact('cfMethod'));
    }

    public function update(UpdateCfMethodRequest $request, CfMethod $cfMethod): RedirectResponse
    {
        $cfMethod->update($request->validated());
        ActivityLog::record('Metode CF', 'updated', $cfMethod->name, $cfMethod->id, "Mengubah metode CF {$cfMethod->name} v{$cfMethod->version}.");

        return redirect()->route('knowledge.cf-methods.show', $cfMethod)
            ->with('success', 'Metode CF berhasil diperbarui.');
    }
}
