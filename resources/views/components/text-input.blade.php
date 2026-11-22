@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'block min-h-[48px] w-full rounded-lg border border-[#D1D5DB] bg-white px-4 font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/15 disabled:bg-surface-container-low']) }}>