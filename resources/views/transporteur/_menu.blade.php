@php
    $liens = [
        ['route' => 'transporteur.dashboard', 'actif' => 'transporteur.dashboard', 'label' => 'Tableau de bord'],
        ['route' => 'transporteur.vehicules.index', 'actif' => 'transporteur.vehicules.*', 'label' => 'Mes véhicules'],
        ['route' => 'transporteur.trajets.index', 'actif' => 'transporteur.trajets.*', 'label' => 'Mes trajets'],
    ];
@endphp

<div class="mb-6">
    <nav class="flex flex-wrap gap-2 text-sm">
        @foreach ($liens as $lien)
            @if (Route::has($lien['route']))
                <a href="{{ route($lien['route']) }}"
                   class="px-3 py-2 rounded-md font-medium {{ request()->routeIs($lien['actif']) ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                    {{ $lien['label'] }}
                </a>
            @endif
        @endforeach
    </nav>

    @if (session('succes'))
        <div class="mt-4 rounded-md bg-green-50 p-3 text-sm text-green-800 dark:bg-green-900/40 dark:text-green-200">
            {{ session('succes') }}
        </div>
    @endif

    @if (session('erreur'))
        <div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-800 dark:bg-red-900/40 dark:text-red-200">
            {{ session('erreur') }}
        </div>
    @endif
</div>