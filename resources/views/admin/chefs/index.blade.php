@extends('layouts.app')

@section('title', __('chef.manage_title') . ' - ' . $store->name)

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-6xl px-6 py-10 lg:px-8">
        <x-backend-header
            :title="__('chef.manage_title')"
            :subtitle="__('chef.manage_subtitle', ['store' => $store->name])"
        >
            <x-slot name="actions">
                @if($store->is_active)
                    <a href="{{ route('admin.stores.boards', $store) }}" class="inline-flex items-center rounded-2xl border border-orange-300/70 bg-orange-500/20 px-4 py-3 text-sm font-semibold text-orange-100 transition hover:bg-orange-500/30">{{ __('admin.board_all_title') }}</a>
                @endif
                <a href="{{ route('admin.stores.index') }}" class="inline-flex items-center rounded-2xl border border-slate-300/70 bg-white/10 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/20">{{ __('admin.back_to_stores') }}</a>
            </x-slot>
        </x-backend-header>

        @if(session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">{{ __('chef.add_title') }}</h2>
                <form method="POST" action="{{ route('admin.stores.chefs.store', $store) }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">{{ __('chef.name_label') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">{{ __('chef.email_label') }}</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">{{ __('chef.password_label') }}</label>
                        <input type="password" name="password" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">{{ __('chef.password_confirmation_label') }}</label>
                        <input type="password" name="password_confirmation" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <button type="submit" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('chef.add_button') }}</button>
                </form>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">{{ __('chef.list_title') }}</h2>
                <div class="mt-4 space-y-3">
                    @forelse($chefs as $chef)
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div>
                                <div class="font-semibold text-slate-900">{{ $chef->name }}</div>
                                <div class="text-xs text-slate-500">{{ $chef->email }}</div>
                            </div>
                            <form method="POST" action="{{ route('admin.stores.chefs.destroy', [$store, $chef]) }}" onsubmit="return confirm('{{ __('chef.delete_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-500">{{ __('chef.delete_button') }}</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('chef.empty') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
