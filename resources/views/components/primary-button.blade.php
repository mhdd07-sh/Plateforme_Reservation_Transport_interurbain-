<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-[48px] items-center justify-center gap-2 rounded-lg bg-primary-container px-6 font-label-lg text-label-lg text-on-primary transition hover:bg-primary active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-primary-container/30 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>