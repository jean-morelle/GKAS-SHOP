@props(['icon' => 'cube', 'title', 'text' => null])
<div class="px-6 py-14 text-center">
    <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
        <x-icon :name="$icon" class="size-7" />
    </span>
    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $title }}</h3>
    @if ($text)<p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">{{ $text }}</p>@endif
    @if (trim($slot))<div class="mt-5">{{ $slot }}</div>@endif
</div>
