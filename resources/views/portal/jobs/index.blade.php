@extends('layouts.portal')
@section('title', 'Lowongan Kerja — '.($companyProfile?->company_name ?: config('app.name')))
@section('meta_description', 'Temukan lowongan kerja terbaru dan kirim lamaran melalui portal kandidat resmi '.($companyProfile?->company_name ?: config('app.name')).'.')
@section('content')
<section class="bg-white"><div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8"><h1 class="text-3xl font-bold">Lowongan Kerja</h1><p class="mt-2 text-slate-600">Cari posisi yang sesuai dengan minat dan pengalaman Anda.</p></div></section>
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <form method="GET" action="{{ route('portal.jobs.index') }}" class="grid gap-3 rounded-xl border bg-white p-4 shadow-sm md:grid-cols-5">
        <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari posisi atau lokasi" class="rounded-md border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 md:col-span-2">
        <select name="department_id" class="rounded-md border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="">Semua Departemen</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(($filters['department_id'] ?? '') == $department->id)>{{ $department->name }}</option>@endforeach</select>
        <select name="employment_status" class="rounded-md border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="">Semua Kontrak</option>@foreach($employmentStatuses as $status)<option value="{{ $status }}" @selected(($filters['employment_status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select>
        <select name="work_location" class="rounded-md border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="">Semua Lokasi</option>@foreach($locations as $location)<option value="{{ $location }}" @selected(($filters['work_location'] ?? '') === $location)>{{ $location }}</option>@endforeach</select>
        <div class="flex gap-2 md:col-span-5"><button class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Cari Lowongan</button><a href="{{ route('portal.jobs.index') }}" class="rounded-md border px-4 py-2 text-sm font-semibold text-slate-600">Reset</a></div>
    </form>
    <div class="mt-8 grid gap-5 md:grid-cols-3">@forelse($jobs as $job)<x-portal.job-card :job="$job" />@empty<div class="rounded-xl border border-dashed bg-white p-8 text-center text-slate-500 md:col-span-3"><p class="font-semibold text-slate-900">Lowongan tidak ditemukan</p><p class="mt-1 text-sm">Coba ubah kata kunci atau filter pencarian Anda.</p></div>@endforelse</div>
    <div class="mt-8">{{ $jobs->links() }}</div>
</section>
@endsection
