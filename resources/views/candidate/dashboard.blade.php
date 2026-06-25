@extends('layouts.portal')
@section('title', 'Dashboard Kandidat — '.(($portalCompanyProfile ?? null)?->company_name ?: config('app.name')))
@section('content')
<section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-blue-600">Portal Kandidat</p>
            <h1 class="mt-1 text-3xl font-bold text-slate-900">Halo, {{ $candidate->name }}</h1>
            <p class="mt-2 text-sm text-slate-600">Pantau status lamaran dan lengkapi profil Anda.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('candidate.profile') }}" class="rounded-md border border-blue-600 px-4 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50">Profil</a>
            <form method="POST" action="{{ route('candidate.logout') }}">
                @csrf
                <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Keluar</button>
            </form>
        </div>
    </div>

    @if(! $candidate->hasCv())
        <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
            <strong>Lengkapi profil Anda</strong> — upload CV agar bisa melamar lowongan.
        </div>
    @endif

    @if(session('success'))
        <div class="mt-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="mt-6 grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Lamaran Aktif</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $summary['active'] }}</p>
        </div>
        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Sedang Diproses</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $summary['processed'] }}</p>
        </div>
        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Diterima</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $summary['accepted'] }}</p>
        </div>
    </div>

    <div class="mt-6 rounded-xl border bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold text-slate-900">5 Lamaran Terbaru</h2>
            <a href="{{ route('candidate.applications.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Lihat semua lamaran</a>
        </div>

        @if($latestApplications->isEmpty())
            <div class="rounded-lg border border-dashed p-8 text-center">
                <p class="font-semibold text-slate-900">Belum ada lamaran</p>
                <p class="mt-1 text-sm text-slate-600">Mulai cari lowongan dan kirim lamaran pertama Anda.</p>
                <a href="{{ route('portal.jobs.index') }}" class="mt-4 inline-flex rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Cari Lowongan</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="py-3 pr-4">Posisi</th>
                            <th class="px-4 py-3">Departemen</th>
                            <th class="px-4 py-3">PT</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="py-3 pl-4">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($latestApplications as $application)
                            <tr>
                                <td class="py-3 pr-4 font-medium text-slate-900">{{ $application->jobPosting?->position_name ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $application->jobPosting?->department?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $application->jobPosting?->entity?->name ?? '-' }}</td>
                                <td class="px-4 py-3"><span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ $application->portalStatusLabel() }}</span></td>
                                <td class="py-3 pl-4 text-slate-600">{{ $application->created_at?->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
@endsection
