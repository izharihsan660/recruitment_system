@props(['job'])
<a href="{{ route('portal.jobs.show', $job) }}" class="block rounded-xl border bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">{{ $job->department?->name ?: 'Departemen' }}</p>
    <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ $job->position_name }}</h3>
    <p class="mt-2 text-sm text-slate-600">{{ $job->entity?->name ?: '-' }} · {{ $job->work_location ?: '-' }}</p>
    <p class="mt-4 text-sm text-slate-500">{{ Str::limit(strip_tags((string) $job->job_description), 120) }}</p>
    <span class="mt-4 inline-flex text-sm font-semibold text-blue-600">Lihat Detail</span>
</a>
