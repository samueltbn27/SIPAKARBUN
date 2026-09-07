@extends('layouts.app')

@section('title', 'WebGIS & Monitoring Kasus')
@section('subtitle', 'Pantau persebaran, status penanganan, dan perkembangan kasus perkebunan.')

@section('content')
<div class="mx-auto max-w-[900px]">
    <section class="soft-card rounded-xl border border-[#e6eee8] bg-white p-6 sm:p-8" aria-labelledby="monitoring-moved-heading">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#89968e]">Monitoring</p>
        <h1 id="monitoring-moved-heading" class="mt-2 text-2xl font-bold tracking-tight text-[#173b29]">WebGIS &amp; Monitoring Kasus</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-[#77847c]">Halaman monitoring kini tersedia bersama peta dan ringkasan kasus pada satu halaman.</p>
        <a href="{{ route('webgis.index') }}" class="mt-6 inline-flex items-center justify-center rounded-lg bg-[#176b45] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#125437] focus:outline-none focus:ring-2 focus:ring-[#b8d7c3]">Buka WebGIS &amp; Monitoring</a>
    </section>
</div>
@endsection
