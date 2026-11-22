<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-[48px] items-center justify-center gap-2 rounded-lg bg-error px-6 font-label-lg text-label-lg text-on-error transition hover:opacity-90 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-error/30']) }}>
    {{ $slot }}
</button>