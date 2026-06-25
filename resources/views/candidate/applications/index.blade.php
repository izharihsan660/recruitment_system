@extends('layouts.portal')
@section('title', 'Daftar Lamaran — '.(($portalCompanyProfile ?? null)?->company_name ?: config('app.name')))
@section('content')
<section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-blue-600">Portal Kandidat</p>
            <h1 class="mt-1 text-3xl font-bold text-slate-900">Daftar Lamaran</h1>
            <p class="mt-2 text-sm text-slate-600">Pantau semua lamaran yang pernah Anda kirim.</p>
        </div>
        <a href="{{ route('portal.jobs.index') }}" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Cari Lowongan</a>
    </div>

    @if(session('success'))
        <div class="mt-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="mt-6 rounded-xl border bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('candidate.applications.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="sm:w-72">
                <label for="status" class="text-sm font-medium text-slate-700">Filter Status</label>
                <select id="status" name="status" class="mt-1 w-full rounded-md border-slate-300 text-sm">
                    <option value="">Semua status</option>
                    @foreach(['applied', 'screening', 'test', 'interview_hr', 'interview_user', 'background_check', 'mcu_simper', 'offering', 'hiring_decision', 'pkwt', 'hired', 'rejected', 'withdrawn'] as $statusOption)
                        <option value="{{ $statusOption }}" @selected(($filters['status'] ?? null) === $statusOption)>{{ str($statusOption)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>
            </div>
            <button class="rounded-md border border-blue-600 px-4 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50">Terapkan</button>
            @if($filters['status'] ?? null)
                <a href="{{ route('candidate.applications.index') }}" class="rounded-md px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Reset</a>
            @endif
        </form>
    </div>

    <div class="mt-6 rounded-xl border bg-white shadow-sm">
        @if($applications->isEmpty())
            <div class="p-8 text-center">
                <p class="font-semibold text-slate-900">Belum ada lamaran</p>
                <p class="mt-1 text-sm text-slate-600">Lowongan yang Anda lamar akan muncul di sini.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3">Posisi</th>
                            <th class="px-5 py-3">PT</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Tanggal Lamar</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($applications as $application)
                            <tr>
                                <td class="px-5 py-4 font-medium text-slate-900">{{ $application->jobPosting?->position_name ?? '-' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $application->jobPosting?->entity?->name ?? '-' }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ $application->portalStatusLabel() }}</span></td>
                                <td class="px-5 py-4 text-slate-600">{{ $application->created_at?->format('d M Y') }}</td>
                                <td class="px-5 py-4 text-right"><a href="{{ route('candidate.applications.show', $application) }}" class="font-semibold text-blue-600 hover:text-blue-700">Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t px-5 py-4">{{ $applications->links() }}</div>
        @endif
    </div>
</section>
@endsection
