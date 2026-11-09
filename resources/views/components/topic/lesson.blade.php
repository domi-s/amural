@props(['lesson', 'number'])

<li class="grid grid-cols-[56px_1fr] items-start gap-x-4 mag:grid-cols-[72px_1fr]">
    <span class="font-display text-4xl italic leading-none mag:text-[44px]" aria-hidden="true">{{ str_pad((string) $number, 2, '0', STR_PAD_LEFT) }}</span>

    <div class="min-w-0">
        @if ($lesson->url)
            <a href="{{ $lesson->url }}" lang="{{ $lesson->locale->language }}" class="text-[19px] font-[450] leading-snug underline-offset-4 hover:underline mag:text-[22px]">{{ $lesson->title }}</a>
        @else
            <span lang="{{ $lesson->locale->language }}" class="text-[19px] font-[450] leading-snug mag:text-[22px]">{{ $lesson->title }}</span>
        @endif

        <p class="mt-1 text-sm text-muted"><em>{{ $lesson->author }}</em> · {{ \Illuminate\Support\Str::ucfirst($lesson->locale->native_name) }} · {{ trans_choice('{1} :count version|[2,*] :count versions', $lesson->versions) }}</p>
    </div>
</li>
