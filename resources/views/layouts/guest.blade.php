<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>

        @include('layouts.head')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-surface font-body-md text-body-md text-on-surface antialiased">
        <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 py-10">
            <div class="pointer-events-none absolute -left-24 top-10 h-72 w-72 rounded-full bg-primary-fixed/30 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-24 bottom-10 h-72 w-72 rounded-full bg-secondary-fixed/30 blur-3xl"></div>

            <div class="relative w-full {{ $attributes->has('wide') ? 'max-w-[1000px]' : 'max-w-[440px]' }}">
                <a href="{{ route('home') }}"
                   class="mb-8 flex items-center justify-center gap-1 font-headline-sm text-headline-sm font-extrabold text-primary-container">
                    {{ config('app.name') }}<span class="h-2 w-2 rounded-full bg-secondary-container"></span>
                </a>

                {{ $slot }}
            </div>
        </div>
    </body>
</html>