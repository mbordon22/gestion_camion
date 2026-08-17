<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Gestión Camión') — Gestión Camión</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af' }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.tailwindcss.min.css">
    <style>
        /* DataTables: selector y campo de búsqueda */
        div.dt-container select,
        div.dt-container input[type="search"] {
            background-color: #fff;
            color: #374151;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.25rem 0.5rem;
        }
        div.dt-container select:focus,
        div.dt-container input[type="search"]:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 0;
        }
        /* Sin zebra striping en DataTables */
        #tabla-viajes tbody tr.odd,
        #tabla-viajes tbody tr.even,
        table.dataTable tbody tr.odd,
        table.dataTable tbody tr.even {
            background-color: #ffffff;
        }
        table.dataTable tbody tr:hover {
            background-color: #e5e7eb !important;
        }
        /* Botones de paginación */
        div.dt-container .dt-paging button {
            background-color: #fff;
            color: #374151;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.25rem 0.6rem;
        }
        div.dt-container .dt-paging button.current,
        div.dt-container .dt-paging button:hover:not(:disabled) {
            background-color: #2563eb;
            color: #fff;
            border-color: #2563eb;
        }
        div.dt-container .dt-paging button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
    </style>
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen">

<nav class="bg-blue-700 text-white shadow-lg">
    <div class="mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <a href="{{ route('viajes.index') }}" class="flex items-center gap-2 font-bold text-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                </svg>
                <span class="hidden sm:inline">Gestión Camión</span>
            </a>

            <!-- Mobile menu button -->
            <button id="menu-btn" class="sm:hidden p-2 rounded hover:bg-blue-800 focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <!-- Desktop nav -->
            <div class="hidden sm:flex items-center gap-1">
                <a href="{{ route('viajes.index') }}"
                   class="px-3 py-2 rounded text-sm font-medium hover:bg-blue-800 transition
                          {{ request()->routeIs('viajes.*') ? 'bg-blue-900' : '' }}">
                    Viajes
                </a>
                <a href="{{ route('combustible.index') }}"
                   class="px-3 py-2 rounded text-sm font-medium hover:bg-blue-800 transition
                          {{ request()->routeIs('combustible.*') ? 'bg-blue-900' : '' }}">
                    Combustible
                </a>
                <a href="{{ route('camiones.index') }}"
                   class="px-3 py-2 rounded text-sm font-medium hover:bg-blue-800 transition
                          {{ request()->routeIs('camiones.*') ? 'bg-blue-900' : '' }}">
                    Camiones
                </a>
                <a href="{{ route('mantenimiento.index') }}"
                   class="px-3 py-2 rounded text-sm font-medium hover:bg-blue-800 transition
                          {{ request()->routeIs('mantenimiento.*') ? 'bg-blue-900' : '' }}">
                    Mantenimiento
                </a>
                <a href="{{ route('prestamos.index') }}"
                   class="px-3 py-2 rounded text-sm font-medium hover:bg-blue-800 transition
                          {{ request()->routeIs('prestamos.*') ? 'bg-blue-900' : '' }}">
                    Préstamos
                </a>
                <a href="{{ route('pagos.index') }}"
                   class="px-3 py-2 rounded text-sm font-medium hover:bg-blue-800 transition
                          {{ request()->routeIs('pagos.*') || request()->routeIs('medios-pago.*') ? 'bg-blue-900' : '' }}">
                    Pagos
                </a>
                <a href="{{ route('reportes.index') }}"
                   class="px-3 py-2 rounded text-sm font-medium hover:bg-blue-800 transition
                          {{ request()->routeIs('reportes.*') ? 'bg-blue-900' : '' }}">
                    Reportes
                </a>

                @auth
                    <span class="mx-2 h-6 w-px bg-blue-500"></span>
                    <a href="{{ route('profile.edit') }}"
                       class="px-3 py-2 rounded text-sm font-medium hover:bg-blue-800 transition flex items-center gap-1.5
                              {{ request()->routeIs('profile.*') ? 'bg-blue-900' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        {{ Auth::user()->name }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="px-3 py-2 rounded text-sm font-medium hover:bg-blue-800 transition">
                            Salir
                        </button>
                    </form>
                @endauth
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden sm:hidden pb-3 space-y-1">
            <a href="{{ route('camiones.index') }}"
               class="block px-3 py-2 rounded text-sm font-medium hover:bg-blue-800
                      {{ request()->routeIs('camiones.*') ? 'bg-blue-900' : '' }}">
                Camiones
            </a>
            <a href="{{ route('viajes.index') }}"
               class="block px-3 py-2 rounded text-sm font-medium hover:bg-blue-800
                      {{ request()->routeIs('viajes.*') ? 'bg-blue-900' : '' }}">
                Viajes
            </a>
            <a href="{{ route('combustible.index') }}"
               class="block px-3 py-2 rounded text-sm font-medium hover:bg-blue-800
                      {{ request()->routeIs('combustible.*') ? 'bg-blue-900' : '' }}">
                Combustible
            </a>
            <a href="{{ route('mantenimiento.index') }}"
               class="block px-3 py-2 rounded text-sm font-medium hover:bg-blue-800
                      {{ request()->routeIs('mantenimiento.*') ? 'bg-blue-900' : '' }}">
                Mantenimiento
            </a>
            <a href="{{ route('prestamos.index') }}"
               class="block px-3 py-2 rounded text-sm font-medium hover:bg-blue-800
                      {{ request()->routeIs('prestamos.*') ? 'bg-blue-900' : '' }}">
                Préstamos
            </a>
            <a href="{{ route('pagos.index') }}"
               class="block px-3 py-2 rounded text-sm font-medium hover:bg-blue-800
                      {{ request()->routeIs('pagos.*') || request()->routeIs('medios-pago.*') ? 'bg-blue-900' : '' }}">
                Pagos
            </a>
            <a href="{{ route('reportes.index') }}"
               class="block px-3 py-2 rounded text-sm font-medium hover:bg-blue-800
                      {{ request()->routeIs('reportes.*') ? 'bg-blue-900' : '' }}">
                Reportes
            </a>

            @auth
                <div class="border-t border-blue-600 mt-2 pt-2">
                    <p class="px-3 py-1 text-xs text-blue-200">{{ Auth::user()->name }} · {{ Auth::user()->email }}</p>
                    <a href="{{ route('profile.edit') }}"
                       class="block px-3 py-2 rounded text-sm font-medium hover:bg-blue-800
                              {{ request()->routeIs('profile.*') ? 'bg-blue-900' : '' }}">
                        Mi perfil
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left block px-3 py-2 rounded text-sm font-medium hover:bg-blue-800">
                            Salir
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </div>
</nav>

<main class="@yield('container-class', 'max-w-6xl') mx-auto px-4 py-6">

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded flex justify-between items-center">
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="ml-4 text-green-600 hover:text-green-900 font-bold">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded flex justify-between items-center">
            <span>{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="ml-4 text-red-600 hover:text-red-900 font-bold">&times;</button>
        </div>
    @endif

    @yield('content')
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.tailwindcss.min.js"></script>
<script>
    document.getElementById('menu-btn').addEventListener('click', function () {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    });
</script>
@stack('scripts')

</body>
</html>
