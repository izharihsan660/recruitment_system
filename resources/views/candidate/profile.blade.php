@extends('layouts.portal')
@section('title', 'Profil Kandidat — '.(($portalCompanyProfile ?? null)?->company_name ?: config('app.name')))
@section('content')
@php
    $educationRows = old('education', $candidate->education ?? []);
    $experienceRows = old('experience', $candidate->experience ?? []);
@endphp
<section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-blue-600">Portal Kandidat</p>
            <h1 class="mt-1 text-3xl font-bold text-slate-900">Profil Kandidat</h1>
            <p class="mt-2 text-sm text-slate-600">Lengkapi data diri, CV, pendidikan, dan pengalaman kerja.</p>
        </div>
        <a href="{{ route('candidate.dashboard') }}" class="rounded-md border border-blue-600 px-4 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50">Kembali ke Dashboard</a>
    </div>

    @if(session('success'))
        <div class="mt-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="mt-6 space-y-6">
        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Data Profil</h2>
            <form method="POST" action="{{ route('candidate.profile.update') }}" class="mt-5 space-y-5">
                @csrf
                @method('PUT')
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Nama</label>
                        <input name="name" value="{{ old('name', $candidate->name) }}" class="mt-1 w-full rounded-md border-slate-300" required>
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Email</label>
                        <input type="email" value="{{ $candidate->email }}" class="mt-1 w-full rounded-md border-slate-300 bg-slate-100 text-slate-600" readonly>
                        <p class="mt-1 text-xs text-slate-500">Email login belum dapat diubah dari portal kandidat.</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Telepon</label>
                        <input name="phone" value="{{ old('phone', $candidate->phone) }}" class="mt-1 w-full rounded-md border-slate-300">
                        @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Tanggal Lahir</label>
                        <input type="date" name="birth_date" value="{{ old('birth_date', $candidate->birth_date?->toDateString()) }}" class="mt-1 w-full rounded-md border-slate-300">
                        @error('birth_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Jenis Kelamin</label>
                        <select name="gender" class="mt-1 w-full rounded-md border-slate-300">
                            <option value="">Pilih</option>
                            <option value="male" @selected(old('gender', $candidate->gender) === 'male')>Laki-laki</option>
                            <option value="female" @selected(old('gender', $candidate->gender) === 'female')>Perempuan</option>
                        </select>
                        @error('gender')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-slate-700">Alamat</label>
                        <textarea name="address" rows="3" class="mt-1 w-full rounded-md border-slate-300">{{ old('address', $candidate->address) }}</textarea>
                        @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="rounded-lg border p-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-semibold text-slate-900">Pendidikan</h3>
                        <button type="button" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white" data-add-education>Tambah</button>
                    </div>
                    <div class="mt-4 space-y-3" data-education-list>
                        @foreach($educationRows as $index => $row)
                            <div class="grid gap-2 rounded-lg border p-3 md:grid-cols-5">
                                <input name="education[{{ $index }}][degree]" value="{{ $row['degree'] ?? '' }}" placeholder="Jenjang" class="rounded-md border-slate-300 text-sm">
                                <input name="education[{{ $index }}][major]" value="{{ $row['major'] ?? '' }}" placeholder="Jurusan" class="rounded-md border-slate-300 text-sm">
                                <input name="education[{{ $index }}][institution]" value="{{ $row['institution'] ?? '' }}" placeholder="Institusi" class="rounded-md border-slate-300 text-sm">
                                <input type="number" name="education[{{ $index }}][year]" value="{{ $row['year'] ?? '' }}" placeholder="Tahun" class="rounded-md border-slate-300 text-sm">
                                <button type="button" class="rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-600" data-remove-row>Hapus</button>
                            </div>
                        @endforeach
                    </div>
                    @error('education')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('education.*.*')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="rounded-lg border p-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-semibold text-slate-900">Pengalaman</h3>
                        <button type="button" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white" data-add-experience>Tambah</button>
                    </div>
                    <div class="mt-4 space-y-3" data-experience-list>
                        @foreach($experienceRows as $index => $row)
                            <div class="grid gap-2 rounded-lg border p-3 md:grid-cols-6">
                                <input name="experience[{{ $index }}][company]" value="{{ $row['company'] ?? '' }}" placeholder="Perusahaan" class="rounded-md border-slate-300 text-sm">
                                <input name="experience[{{ $index }}][position]" value="{{ $row['position'] ?? '' }}" placeholder="Posisi" class="rounded-md border-slate-300 text-sm">
                                <input type="number" name="experience[{{ $index }}][start_year]" value="{{ $row['start_year'] ?? '' }}" placeholder="Mulai" class="rounded-md border-slate-300 text-sm">
                                <input type="number" name="experience[{{ $index }}][end_year]" value="{{ $row['end_year'] ?? '' }}" placeholder="Selesai" class="rounded-md border-slate-300 text-sm">
                                <input name="experience[{{ $index }}][description]" value="{{ $row['description'] ?? '' }}" placeholder="Deskripsi" class="rounded-md border-slate-300 text-sm">
                                <button type="button" class="rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-600" data-remove-row>Hapus</button>
                            </div>
                        @endforeach
                    </div>
                    @error('experience')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('experience.*.*')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <button class="rounded-md bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">Simpan Profil</button>
            </form>
        </div>

        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Upload CV</h2>
            <p class="mt-2 text-sm text-slate-600">File saat ini: {{ $candidate->cv_original_name ?: 'Belum ada CV' }}</p>
            <form method="POST" action="{{ route('candidate.cv.store') }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 sm:flex-row">
                @csrf
                <input type="file" name="cv" accept="application/pdf" class="rounded-md border border-slate-300 p-2 text-sm" required>
                <button class="rounded-md bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">Upload CV</button>
            </form>
            @error('cv')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
</section>

<template data-education-template>
    <div class="grid gap-2 rounded-lg border p-3 md:grid-cols-5">
        <input data-name="education[__INDEX__][degree]" placeholder="Jenjang" class="rounded-md border-slate-300 text-sm">
        <input data-name="education[__INDEX__][major]" placeholder="Jurusan" class="rounded-md border-slate-300 text-sm">
        <input data-name="education[__INDEX__][institution]" placeholder="Institusi" class="rounded-md border-slate-300 text-sm">
        <input type="number" data-name="education[__INDEX__][year]" placeholder="Tahun" class="rounded-md border-slate-300 text-sm">
        <button type="button" class="rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-600" data-remove-row>Hapus</button>
    </div>
</template>
<template data-experience-template>
    <div class="grid gap-2 rounded-lg border p-3 md:grid-cols-6">
        <input data-name="experience[__INDEX__][company]" placeholder="Perusahaan" class="rounded-md border-slate-300 text-sm">
        <input data-name="experience[__INDEX__][position]" placeholder="Posisi" class="rounded-md border-slate-300 text-sm">
        <input type="number" data-name="experience[__INDEX__][start_year]" placeholder="Mulai" class="rounded-md border-slate-300 text-sm">
        <input type="number" data-name="experience[__INDEX__][end_year]" placeholder="Selesai" class="rounded-md border-slate-300 text-sm">
        <input data-name="experience[__INDEX__][description]" placeholder="Deskripsi" class="rounded-md border-slate-300 text-sm">
        <button type="button" class="rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-600" data-remove-row>Hapus</button>
    </div>
</template>
<script>
    document.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-row]');
        if (removeButton) {
            removeButton.closest('.grid')?.remove();
        }
    });

    function appendPortalRow(listSelector, templateSelector, nextIndex) {
        const list = document.querySelector(listSelector);
        const template = document.querySelector(templateSelector);
        const fragment = template.content.cloneNode(true);
        fragment.querySelectorAll('[data-name]').forEach((input) => {
            input.name = input.dataset.name.replace('__INDEX__', nextIndex.value);
            input.removeAttribute('data-name');
        });
        nextIndex.value += 1;
        list.appendChild(fragment);
    }

    const educationIndex = { value: {{ count($educationRows) }} };
    const experienceIndex = { value: {{ count($experienceRows) }} };

    document.querySelector('[data-add-education]')?.addEventListener('click', () => appendPortalRow('[data-education-list]', '[data-education-template]', educationIndex));
    document.querySelector('[data-add-experience]')?.addEventListener('click', () => appendPortalRow('[data-experience-list]', '[data-experience-template]', experienceIndex));
</script>
@endsection
