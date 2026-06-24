@php
    $company = $portalCompanyProfile ?? $companyProfile ?? null;
    $companyName = $company?->company_name ?: config('app.name');
    $logo = $company?->hero_image_path ? Storage::url($company->hero_image_path) : null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $companyName)</title>
    <meta name="description" content="@yield('meta_description', Str::limit(strip_tags((string) $company?->about), 160, ''))">
    @hasSection('canonical')<link rel="canonical" href="@yield('canonical')">@endif
    <meta property="og:title" content="@yield('og_title', View::yieldContent('title', $companyName))">
    <meta property="og:description" content="@yield('og_description', View::yieldContent('meta_description', Str::limit(strip_tags((string) $company?->about), 160, '')))">
    @if($company?->hero_image_path)<meta property="og:image" content="{{ Storage::url($company->hero_image_path) }}">@endif
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-screen">
        <header class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ route('portal.home') }}" class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-lg bg-blue-600 text-sm font-bold text-white">
                        @if($logo)<img src="{{ $logo }}" alt="Logo {{ $companyName }}" class="h-full w-full object-cover">@else{{ Str::substr($companyName, 0, 2) }}@endif
                    </span>
                    <span class="font-semibold text-slate-900">{{ $companyName }}</span>
                </a>
                <button type="button" class="rounded-md p-2 text-slate-600 md:hidden" data-portal-menu-button aria-label="Buka menu">
                    <span class="block h-0.5 w-6 bg-current"></span><span class="mt-1.5 block h-0.5 w-6 bg-current"></span><span class="mt-1.5 block h-0.5 w-6 bg-current"></span>
                </button>
                <div class="hidden items-center gap-6 md:flex">
                    <a class="text-sm font-medium text-slate-600 hover:text-blue-600" href="{{ route('portal.home') }}">Beranda</a>
                    <a class="text-sm font-medium text-slate-600 hover:text-blue-600" href="{{ route('portal.jobs.index') }}">Lowongan</a>
                    <a class="text-sm font-medium text-slate-600 hover:text-blue-600" href="{{ route('portal.home') }}#tentang-kami">Tentang Kami</a>
                    <a class="rounded-md border border-blue-600 px-4 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50" href="{{ route('candidate.login.form') }}">Masuk</a>
                    <a class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700" href="{{ route('candidate.register.form') }}">Daftar</a>
                </div>
            </nav>
            <div data-portal-menu class="hidden border-t border-slate-200 bg-white px-4 py-4 md:hidden">
                <div class="flex flex-col gap-3">
                    <a href="{{ route('portal.home') }}">Beranda</a><a href="{{ route('portal.jobs.index') }}">Lowongan</a><a href="{{ route('portal.home') }}#tentang-kami">Tentang Kami</a>
                    <a class="rounded-md border border-blue-600 px-4 py-2 text-center text-blue-600" href="{{ route('candidate.login.form') }}">Masuk</a>
                    <a class="rounded-md bg-blue-600 px-4 py-2 text-center text-white" href="{{ route('candidate.register.form') }}">Daftar</a>
                </div>
            </div>
        </header>
        <main>@yield('content')</main>
        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 text-sm text-slate-600 sm:px-6 md:grid-cols-2 lg:px-8">
                <div><p class="font-semibold text-slate-900">{{ $companyName }}</p><p class="mt-2">{{ $company?->address ?: 'Alamat perusahaan belum diatur.' }}</p></div>
                <div class="md:text-right"><p>{{ $company?->email ?: '-' }}</p><p>{{ $company?->phone ?: '-' }}</p><p class="mt-2">© {{ now()->year }} {{ $companyName }}. Semua hak dilindungi.</p></div>
            </div>
        </footer>
    </div>
<script>document.querySelectorAll('[data-portal-menu-button]').forEach((button) => button.addEventListener('click', () => document.querySelector('[data-portal-menu]')?.classList.toggle('hidden')));</script>
</body>
</html>
