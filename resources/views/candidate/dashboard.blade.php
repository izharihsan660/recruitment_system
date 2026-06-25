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
            <h2 class="text-lg font-semibold text-slate-900">Lamaran Terbaru</h2>
            <a href="{{ route('candidate.applications.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Lihat semua lamaran</a>
        </div>

        @if($latestApplications->isEmpty())
            <div class="rounded-lg border border-dashed p-8 text-center">
                <p class="font-semibold text-slate-900">Belum ada lamaran</p>
                <p class="mt-1 text-sm text-slate-600">Mulai cari lowongan dan kirim lamaran pertama Anda.</p>
                <a href="{{ route('portal.jobs.index') }}" class="mt-4 inline-flex rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Cari Lowongan</a>
            </div>
        @else
            <div class="space-y-3">
                @foreach($latestApplications as $application)
                    @php
                        $safeStatus = match ($application->status) {
                            'applied', 'screening', 'test', 'test_psikotes' => 'Lamaran Sedang Diproses',
                            'interview_hr', 'interview_user' => 'Tahap Interview',
                            'background_check', 'mcu_simper' => 'Tahap Verifikasi',
                            'offering', 'hiring_decision' => 'Tahap Penawaran',
                            'pkwt', 'hired' => 'Diterima',
                            'rejected' => 'Tidak Dilanjutkan',
                            'withdrawn' => 'Lamaran Dibatalkan',
                            default => 'Lamaran Sedang Diproses',
                        };
                    @endphp
                    <div class="rounded-lg border p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $application->jobPosting?->position_name ?? '-' }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ $safeStatus }}</p>
                                <p class="mt-1 text-xs text-slate-500">Tanggal lamar: {{ $application->created_at?->format('d M Y') }}</p>
                            </div>
                            <a href="{{ route('candidate.applications.show', $application) }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Lihat Detail</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
