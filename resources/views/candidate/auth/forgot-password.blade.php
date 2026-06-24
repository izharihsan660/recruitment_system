@extends('layouts.portal')
@section('title', 'Lupa Password — '.($portalCompanyProfile?->company_name ?: config('app.name')))
@section('content')
<section class="mx-auto max-w-md px-4 py-16"><div class="rounded-xl border bg-white p-6 shadow-sm"><h1 class="text-2xl font-bold">Lupa Password</h1><p class="mt-2 text-sm text-slate-600">Masukkan email kandidat Anda. Alur reset password kandidat akan mengirim tautan reset setelah broker email kandidat diaktifkan.</p><form method="POST" action="{{ route('candidate.password.email') }}" class="mt-6 space-y-4">@csrf<div><label class="text-sm font-medium">Email</label><input type="email" name="email" class="mt-1 w-full rounded-md border-slate-300" required></div><button class="w-full rounded-md bg-blue-600 px-4 py-2 font-semibold text-white">Kirim Link Reset</button></form></div></section>
@endsection
