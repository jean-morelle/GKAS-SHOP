@extends('layouts.site', ['title' => $title])

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $title }}</h1>
        @if (! empty($platform[$key]))
            <div class="mt-8 space-y-4 leading-relaxed whitespace-pre-line text-slate-700">{{ $platform[$key] }}</div>
        @else
            <p class="mt-8 rounded-xl bg-slate-50 p-6 text-slate-600">
                Ce document n'a pas encore été publié par {{ $platform['company_name'] }}.
                Pour toute question, <a href="{{ route('site.contact') }}" class="font-semibold text-brand-700 underline">contactez-nous</a>.
            </p>
        @endif
    </section>
@endsection
