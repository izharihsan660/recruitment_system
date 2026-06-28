@extends('layouts.portal')
@section('title', trim(($companyProfile?->company_name ?: config('app.name')).' '.$companyProfile?->tagline))
@section('meta_description', Str::limit(strip_tags((string) $companyProfile?->about), 160, ''))
@section('og_image', $companyProfile?->hero_image_path ? Storage::url($companyProfile->hero_image_path) : '')
@section('content')
<section class="relative overflow-hidden bg-slate-900 text-white">
    @if($companyProfile?->hero_image_path)<img src="{{ Storage::url($companyProfile->hero_image_path) }}" alt="{{ $companyProfile->company_name }}" class="absolute inset-0 h-full w-full object-cover opacity-40">@endif
    <div class="relative mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
        <p class="text-sm font-semibold uppercase tracking-wide text-blue-200">Portal Karier</p>
        <h1 class="mt-4 max-w-3xl text-4xl font-bold md:text-6xl">{{ $companyProfile?->company_name ?: config('app.name') }}</h1>
        <p class="mt-4 max-w-2xl text-lg text-slate-100">{{ $companyProfile?->tagline ?: 'Bergabung dan tumbuh bersama kami.' }}</p>
        <a href="#lowongan" class="mt-8 inline-flex rounded-md bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700">Lihat Lowongan</a>
    </div>
</section>
<section id="tentang-kami" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8"><h2 class="text-2xl font-bold">Tentang Kami</h2><p class="mt-4 max-w-4xl leading-7 text-slate-600">{{ $companyProfile?->about ?: 'Profil perusahaan belum tersedia.' }}</p></section>
<section class="bg-white py-16"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"><h2 class="text-2xl font-bold">Nilai & Kultur</h2><div class="mt-6 grid gap-4 md:grid-cols-3">@forelse(($companyProfile?->values ?? []) as $value)<div class="rounded-xl border bg-white p-5 shadow-sm"><h3 class="font-semibold">{{ $value['title'] ?? $value['name'] ?? 'Nilai Perusahaan' }}</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $value['description'] ?? '' }}</p></div>@empty<div class="rounded-xl border border-dashed p-6 text-slate-500 md:col-span-3">Nilai perusahaan belum diatur di CMS.</div>@endforelse</div></div></section>
<section id="lowongan" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8"><div class="flex items-end justify-between gap-4"><div><h2 class="text-2xl font-bold">Lowongan Terbuka</h2><p class="mt-1 text-slate-600">Pilih posisi yang sesuai dengan pengalaman Anda.</p></div><a href="{{ route('portal.jobs.index') }}" class="hidden text-sm font-semibold text-blue-600 sm:block">Lihat Semua Lowongan</a></div><div class="mt-6 grid gap-5 md:grid-cols-3">@forelse($jobs as $job)<x-portal.job-card :job="$job" />@empty<div class="rounded-xl border border-dashed bg-white p-8 text-center text-slate-500 md:col-span-3">Belum ada lowongan terbuka.</div>@endforelse</div></section>
@if(!empty($companyProfile->gallery) && count($companyProfile->gallery) > 0)
<section class="py-16 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-center mb-8">Galeri</h2>
        <div class="relative overflow-hidden rounded-2xl bg-slate-100" id="gallery-carousel">
            <div class="flex transition-transform duration-500 ease-in-out" id="gallery-track">
                @foreach($companyProfile->gallery as $index => $image)
                    <div class="min-w-full aspect-[16/9] h-64 md:h-72">
                        <img
                            src="{{ Storage::url($image) }}"
                            alt="Galeri {{ $index + 1 }}"
                            class="w-full h-full object-cover"
                        />
                    </div>
                @endforeach
            </div>
            @if(count($companyProfile->gallery) > 1)
            <button onclick="prevSlide()" class="absolute left-3 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white rounded-full w-10 h-10 flex items-center justify-center shadow text-slate-700 font-bold">‹</button>
            <button onclick="nextSlide()" class="absolute right-3 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white rounded-full w-10 h-10 flex items-center justify-center shadow text-slate-700 font-bold">›</button>
            @endif
        </div>
        @if(count($companyProfile->gallery) > 1)
        <div class="flex justify-center gap-2 mt-4" id="gallery-dots">
            @foreach($companyProfile->gallery as $index => $image)
                <button onclick="goToSlide({{ $index }})" class="w-2 h-2 rounded-full bg-slate-300 hover:bg-slate-700 transition-colors" id="dot-{{ $index }}"></button>
            @endforeach
        </div>
        @endif
    </div>
</section>
<script>
    let currentSlide = 0;
    const total = {{ count($companyProfile->gallery) }};
    function updateCarousel() {
        document.getElementById('gallery-track').style.transform = `translateX(-${currentSlide * 100}%)`;
        document.querySelectorAll('#gallery-dots button').forEach((dot, i) => {
            dot.style.backgroundColor = i === currentSlide ? '#334155' : '#cbd5e1';
        });
    }
    function nextSlide() { currentSlide = (currentSlide + 1) % total; updateCarousel(); }
    function prevSlide() { currentSlide = (currentSlide - 1 + total) % total; updateCarousel(); }
    function goToSlide(index) { currentSlide = index; updateCarousel(); }
    updateCarousel();
</script>
@endif
<section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8"><h2 class="text-2xl font-bold">Kontak</h2><div class="mt-4 grid gap-4 text-slate-600 md:grid-cols-3"><p>{{ $companyProfile?->address ?: '-' }}</p><p>{{ $companyProfile?->email ?: '-' }}</p><p>{{ $companyProfile?->phone ?: '-' }}</p></div></section>
@endsection
