@extends('layouts.portal')
@section('title', 'Detail Lamaran — '.(($portalCompanyProfile ?? null)?->company_name ?: config('app.name')))
@section('content')
@php
    $portalStatus = match ($application->status) {
        'applied', 'screening', 'test' => 'Lamaran Sedang Diproses',
        'interview_hr', 'interview_user' => 'Tahap Interview',
        'background_check', 'mcu_simper' => 'Tahap Verifikasi',
        'offering', 'hiring_decision' => 'Tahap Penawaran',
        'pkwt', 'hired' => 'Diterima',
        'rejected' => 'Tidak Dilanjutkan',
        'withdrawn' => 'Lamaran Dibatalkan',
        default => 'Lamaran Sedang Diproses',
    };
@endphp
<section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('candidate.applications.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">← Kembali ke daftar lamaran</a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-blue-600">Detail Lamaran</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-900">{{ $application->jobPosting?->position_name ?? '-' }}</h1>
                <div class="mt-5 grid gap-4 text-sm md:grid-cols-2">
                    <div>
                        <p class="text-slate-500">PT</p>
                        <p class="font-semibold text-slate-900">{{ $application->jobPosting?->entity?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Status</p>
                        <p class="font-semibold text-slate-900">{{ $portalStatus }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Tanggal Lamar</p>
                        <p class="font-semibold text-slate-900">{{ $application->created_at?->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Departemen</p>
                        <p class="font-semibold text-slate-900">{{ $application->jobPosting?->department?->name ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Timeline Lamaran</h2>
                @if($application->pipelineLogs->isEmpty())
                    <p class="mt-4 rounded-lg border border-dashed p-4 text-sm text-slate-600">Belum ada update pipeline.</p>
                @else
                    <div class="mt-5 space-y-4">
                        @foreach($application->pipelineLogs->sortByDesc('created_at') as $log)
                            <div class="rounded-lg border p-4">
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="font-semibold text-slate-900">{{ str($log->to_stage)->replace('_', ' ')->title() }}</p>
                                    <p class="text-xs text-slate-500">{{ $log->created_at?->format('d M Y H:i') }}</p>
                                </div>
                                @if($log->from_stage)
                                    <p class="mt-1 text-sm text-slate-600">Dari {{ str($log->from_stage)->replace('_', ' ')->title() }} ke {{ str($log->to_stage)->replace('_', ' ')->title() }}</p>
                                @endif
                                @if($log->notes)
                                    <p class="mt-2 text-sm text-slate-700">{{ $log->notes }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Upload Dokumen Tambahan</h2>
                <p class="mt-2 text-sm text-slate-600">Format PDF/JPG/PNG, maksimal 5 MB.</p>
                <form method="POST" action="{{ route('candidate.applications.documents.store', $application) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                    @csrf
                    <input name="document_type" value="{{ old('document_type') }}" placeholder="Jenis dokumen" class="w-full rounded-md border-slate-300 text-sm" required>
                    @error('document_type')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <input type="file" name="file" accept="application/pdf,image/jpeg,image/png" class="w-full rounded-md border border-slate-300 p-2 text-sm" required>
                    @error('file')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <button class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Upload Dokumen</button>
                </form>
            </div>

            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Dokumen Terunggah</h2>
                @if($application->documents->isEmpty())
                    <p class="mt-3 text-sm text-slate-600">Belum ada dokumen tambahan.</p>
                @else
                    <div class="mt-4 space-y-3">
                        @foreach($application->documents as $document)
                            <div class="rounded-lg border p-3">
                                <p class="font-semibold text-slate-900">{{ $document->document_type }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ $document->original_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $document->uploaded_at?->format('d M Y H:i') }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
