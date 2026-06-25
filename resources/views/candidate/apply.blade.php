@extends('layouts.portal')
@section('title', 'Lamar '.$job->position_name.' — '.(($portalCompanyProfile ?? null)?->company_name ?: config('app.name')))
@section('content')
<section class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('portal.jobs.show', $job) }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">← Kembali ke detail lowongan</a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="rounded-xl border bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold text-blue-600">Form Lamaran</p>
        <h1 class="mt-2 text-3xl font-bold text-slate-900">{{ $job->position_name }}</h1>
        <div class="mt-5 grid gap-4 text-sm md:grid-cols-2">
            <div>
                <p class="text-slate-500">Departemen</p>
                <p class="font-semibold text-slate-900">{{ $job->department?->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-slate-500">PT</p>
                <p class="font-semibold text-slate-900">{{ $job->entity?->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-slate-500">Lokasi</p>
                <p class="font-semibold text-slate-900">{{ $job->work_location ?: '-' }}</p>
            </div>
            <div>
                <p class="text-slate-500">Tipe Kontrak</p>
                <p class="font-semibold text-slate-900">{{ $job->employment_status ?: '-' }}</p>
            </div>
        </div>
    </div>

    @if($existingApplicationId)
        <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-6 text-blue-900">
            <h2 class="text-lg font-semibold">Anda sudah melamar lowongan ini.</h2>
            <p class="mt-2 text-sm">Silakan pantau perkembangan lamaran melalui halaman detail lamaran.</p>
            <a href="{{ route('candidate.applications.show', $existingApplicationId) }}" class="mt-4 inline-flex rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Lihat Lamaran</a>
        </div>
    @else
        <div class="mt-6 rounded-xl border bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Konfirmasi Lamaran</h2>

            @if(! $hasCv)
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    Anda belum upload CV. Silakan lengkapi CV di profil sebelum mengirim lamaran.
                    <a href="{{ route('candidate.profile') }}" class="font-semibold underline">Upload CV</a>
                </div>
            @endif

            @if($errors->any())
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <p class="font-semibold">Lamaran belum bisa dikirim.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('candidate.jobs.apply', $job) }}" class="mt-5 space-y-5">
                @csrf
                <label class="flex items-start gap-3 rounded-lg border p-4 text-sm text-slate-700">
                    <input type="checkbox" name="consent" value="1" @checked(old('consent')) required class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-600">
                    <span>Saya menyetujui data pribadi saya diproses untuk kebutuhan rekrutmen dan menyatakan informasi yang saya berikan benar.</span>
                </label>
                @error('consent')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

                <button class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300" @disabled(! $hasCv)>Submit Lamaran</button>
            </form>
        </div>
    @endif
</section>
@endsection
