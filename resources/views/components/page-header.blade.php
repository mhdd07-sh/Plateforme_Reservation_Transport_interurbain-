@props(['titre', 'soustitre' => null, 'surtitre' => null])

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        @if ($surtitre)
            <p class="font-label-md text-label-md uppercase tracking-wider text-primary-container">{{ $surtitre }}</p>
        @endif
        <h1 class="mt-1 font-headline-lg-mobile text-headline-lg-mobile sm:font-headline-lg sm:text-headline-lg">{{ $titre }}</h1>
        @if ($soustitre)
            <p class="mt-1 max-w-2xl font-body-md text-body-md text-on-surface-variant">{{ $soustitre }}</p>
        @endif
    </div>

    @if (trim((string) $slot) !== '')
        <div class="flex flex-wrap items-center gap-3">{{ $slot }}</div>
    @endif
</div>