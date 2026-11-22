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
        <div class="flex min-h-screen flex-col">
            @include('layouts.navigation')

            @isset($header)
                <header class="border-b border-outline-variant/40 bg-surface-container-lowest">
                    <div class="mx-auto max-w-[1200px] px-4 py-6 sm:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            @if (session('succes') || session('erreur'))
                <div class="mx-auto mt-4 w-full max-w-[1200px] space-y-2 px-4 sm:px-8">
                    @if (session('succes'))
                        <div class="flex items-center gap-2 rounded-lg bg-primary-fixed px-4 py-3 font-body-md text-body-md text-on-primary-fixed-variant">
                            <x-ui.icon>check_circle</x-ui.icon>
                            {{ session('succes') }}
                        </div>
                    @endif
                    @if (session('erreur'))
                        <div class="flex items-center gap-2 rounded-lg bg-error-container px-4 py-3 font-body-md text-body-md text-on-error-container">
                            <x-ui.icon>error</x-ui.icon>
                            {{ session('erreur') }}
                        </div>
                    @endif
                </div>
            @endif

            <main class="flex-1">
                {{ $slot }}
            </main>

            @include('layouts.footer')
        </div>
    </body>
</html>