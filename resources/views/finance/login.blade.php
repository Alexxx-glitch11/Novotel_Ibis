@extends('layouts.app')

@section('title', 'Masuk Finance')

@section('content')
<div class="mx-auto max-w-md px-4 py-16">
    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 md:p-8">
        <p class="text-xs font-bold uppercase tracking-[.18em] text-[#5b568b]">Reservation Management</p>
        <h1 class="mt-2 text-2xl font-bold text-[#24215f]">Masuk Finance</h1>
        <p class="mt-2 text-sm text-slate-500">Masuk dengan akun finance hotel untuk memeriksa bukti pembayaran.</p>
        @if ($errors->any())<div class="mt-5 rounded-lg bg-red-50 p-3 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('finance.login.store') }}" class="mt-6 space-y-4">
            @csrf
            <div><label for="email" class="block text-sm font-semibold text-slate-700">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-3 text-sm focus:border-[#24215f] focus:outline-none focus:ring-2 focus:ring-[#24215f]/15"></div>
            <div><label for="password" class="block text-sm font-semibold text-slate-700">Kata sandi</label><input id="password" name="password" type="password" autocomplete="current-password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-3 text-sm focus:border-[#24215f] focus:outline-none focus:ring-2 focus:ring-[#24215f]/15"></div>
            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" value="1" class="size-4 accent-[#24215f]">Ingat saya</label>
            <button class="min-h-12 w-full rounded-lg bg-[#24215f] px-4 py-3 text-sm font-semibold text-white hover:bg-[#181644]">Masuk</button>
        </form>
    </section>
</div>
@endsection
