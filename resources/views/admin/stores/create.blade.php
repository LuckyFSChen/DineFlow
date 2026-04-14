@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-4xl px-6 py-10 lg:px-8">
        <x-backend-header
            :title="__('admin.add_store')"
            :subtitle="__('admin.create_store_page_desc')"
        />

        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <form method="POST" action="{{ route('admin.stores.store') }}" enctype="multipart/form-data">
                @include('admin.stores._form')
            </form>
        </div>
    </div>
</div>
@endsection