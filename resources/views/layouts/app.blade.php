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

        /*
         * Listados en el celular: cada fila de una tabla .tabla-tarjetas se
         * muestra como una tarjeta, sin scroll de costado. Cada celda dice con
         * una clase qué lugar ocupa:
         *   t-marca     el tilde para elegir la fila, a la izquierda de todo
         *   t-pre       dato corto antes del título (la fecha)
         *   t-titulo    lo principal (el cliente, el nombre)
         *   t-monto     el número, a la derecha del título
         *   t-linea     un renglón entero (la ruta)
         *   t-dato      datos chicos, uno al lado del otro, separados por " · "
         *   t-pie       abajo a la izquierda (el switch de cobrado)
         *   t-acciones  abajo a la derecha (los botones)
         *   t-ocultar   no se muestra en el celular
         * En la computadora sigue siendo una tabla común.
         */
        @media (max-width: 639.98px) {
            table.tabla-tarjetas,
            table.tabla-tarjetas tbody { display: block; width: 100% !important; }
            table.tabla-tarjetas thead,
            table.tabla-tarjetas tfoot .t-ocultar { display: none; }

            /*
             * Tarjetas separadas y con sombra, para ver dónde termina una y
             * empieza la otra. Dentro de una sección con título van sobre un
             * fondo gris; en un listado suelto (contenedor .lista-tarjetas),
             * directamente sobre el fondo de la página.
             */
            table.tabla-tarjetas tbody {
                display: flex; flex-direction: column; gap: 0.75rem;
                padding: 0.75rem; background-color: #f3f4f6;
            }
            table.tabla-tarjetas tbody tr {
                background-color: #fff;
                border: 1px solid #e5e7eb !important;
                border-radius: 0.75rem;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08), 0 1px 2px rgba(15, 23, 42, 0.04);
            }
            /* Sólo si tiene la tabla: vacío, el aviso de "no hay…" conserva su caja blanca. */
            .lista-tarjetas:has(table.tabla-tarjetas) { background-color: transparent !important; box-shadow: none !important; overflow: visible !important; }
            .lista-tarjetas table.tabla-tarjetas tbody { padding: 0; background-color: transparent; }
            .lista-tarjetas div.dt-container > .grid { padding-left: 0; padding-right: 0; }
            table.tabla-tarjetas tbody tr,
            table.tabla-tarjetas tfoot tr {
                display: flex; flex-wrap: wrap; align-items: baseline;
                row-gap: 0.125rem; padding: 0.875rem 1rem;
            }
            table.tabla-tarjetas tfoot { display: block; }
            table.tabla-tarjetas td {
                display: block; padding: 0 !important; border: 0 !important;
                text-align: left; white-space: normal;
            }
            .tabla-tarjetas .t-marca    { order: 5; margin-right: 0.75rem; align-self: center; }
            .tabla-tarjetas .t-pre      { order: 10; margin-right: 0.5rem; font-weight: 600; color: #374151; white-space: nowrap; }
            /* El mínimo evita que el salto de renglón de abajo lo aplaste cuando no hay monto. */
            .tabla-tarjetas .t-titulo   { order: 20; flex: 1 1 0; min-width: 40%; font-weight: 600; color: #111827; }
            .tabla-tarjetas .t-monto    { order: 30; margin-left: 0.75rem; text-align: right !important; font-weight: 700; white-space: nowrap; }
            .tabla-tarjetas .t-linea    { order: 40; flex-basis: 100%; font-size: 0.8125rem; color: #4b5563; }
            .tabla-tarjetas .t-dato     { order: 50; margin-right: 0.375rem; font-size: 0.8125rem; color: #6b7280; }
            /* El separador va sólo si antes hay un dato que se ve. */
            .tabla-tarjetas .t-dato:not(.t-ocultar) ~ .t-dato::before { content: '· '; }
            /*
             * Dos saltos de renglón invisibles: después del título y el monto,
             * y antes del pie. Así los datos nunca se suben al renglón del
             * título ni se meten al lado de los botones.
             */
            table.tabla-tarjetas tbody tr::before,
            table.tabla-tarjetas tbody tr::after { content: ''; flex-basis: 100%; height: 0; }
            table.tabla-tarjetas tbody tr::before { order: 35; }
            table.tabla-tarjetas tbody tr::after  { order: 55; }
            .tabla-tarjetas .t-pie,
            .tabla-tarjetas .t-acciones {
                flex: 1 1 auto; margin-top: 0.625rem;
                padding-top: 0.625rem !important; border-top: 1px solid #f3f4f6 !important;
            }
            .tabla-tarjetas .t-pie      { order: 60; }
            .tabla-tarjetas .t-acciones { order: 70; }
            .tabla-tarjetas .t-acciones > div { justify-content: flex-end; }
            /*
             * Tarjeta compacta (tr.t-compacta): sin pie, los botones van a la
             * derecha del último renglón de datos en vez de ocupar uno propio.
             */
            table.tabla-tarjetas tbody tr.t-compacta { align-items: center; }
            table.tabla-tarjetas tbody tr.t-compacta::after { display: none; }
            .tabla-tarjetas tr.t-compacta .t-acciones {
                flex: 0 0 auto; margin-left: auto; margin-top: 0.25rem;
                padding-top: 0 !important; border-top: 0 !important;
            }
            .tabla-tarjetas .t-ocultar  { display: none !important; }
            .tabla-tarjetas tr[data-href] { cursor: pointer; }

            /* Filtros plegados (componente x-filtros): sin el triángulo del navegador. */
            details.filtros > summary { list-style: none; }
            details.filtros > summary::-webkit-details-marker { display: none; }

            /*
             * DataTables arma cada fila de controles como una grilla de dos
             * columnas: en el celular va una sola, sin "Mostrar N registros",
             * con el buscador a todo el ancho y la paginación centrada.
             */
            div.dt-container > .grid { grid-template-columns: 1fr; gap: 0.5rem; margin: 0; padding: 0.75rem 1rem; }
            div.dt-container > .grid > div { justify-self: stretch; grid-column: auto; }
            div.dt-container .dt-length { display: none; }
            div.dt-container .dt-search input { width: 100%; margin: 0; padding: 0.5rem 0.75rem; }
            div.dt-container .dt-info { font-size: 0.75rem; text-align: center; }
            div.dt-container .dt-paging { text-align: center; }
        }
    </style>
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen">

@php
    // Lo que se carga una vez y después sólo se elige al cargar un viaje o un
    // gasto. Van juntos en un desplegable para que la barra no se desborde.
    $catalogos = [
        ['ruta' => 'clientes.index', 'patron' => 'clientes.*', 'texto' => 'Clientes'],
        ['ruta' => 'choferes.index', 'patron' => 'choferes.*', 'texto' => 'Choferes'],
        ['ruta' => 'destinos.index', 'patron' => 'destinos.*', 'texto' => 'Destinos'],
        ['ruta' => 'productos.index', 'patron' => 'productos.*', 'texto' => 'Productos'],
        ['ruta' => 'tarifas.index', 'patron' => 'tarifas.*', 'texto' => 'Tarifas'],
        ['ruta' => 'camiones.index', 'patron' => 'camiones.*', 'texto' => 'Camiones'],
        ['ruta' => 'equipos.index', 'patron' => 'equipos.*', 'texto' => 'Equipos'],
        ['ruta' => 'medios-pago.index', 'patron' => 'medios-pago.*', 'texto' => 'Medios de pago'],
    ];

    $enCatalogos = collect($catalogos)->contains(fn ($c) => request()->routeIs($c['patron']));
@endphp

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
                <a href="{{ route('inicio') }}"
                   class="px-3 py-2 rounded text-sm font-medium hover:bg-blue-800 transition
                          {{ request()->routeIs('inicio') ? 'bg-blue-900' : '' }}">
                    Inicio
                </a>
                <a href="{{ route('viajes.index') }}"
                   class="px-3 py-2 rounded text-sm font-medium hover:bg-blue-800 transition
                          {{ request()->routeIs('viajes.*') ? 'bg-blue-900' : '' }}">
                    Viajes
                </a>
                <a href="{{ route('simulador.index') }}"
                   class="px-3 py-2 rounded text-sm font-medium hover:bg-blue-800 transition
                          {{ request()->routeIs('simulador.*') ? 'bg-blue-900' : '' }}">
                    Simular viaje
                </a>
                <a href="{{ route('combustible.index') }}"
                   class="px-3 py-2 rounded text-sm font-medium hover:bg-blue-800 transition
                          {{ request()->routeIs('combustible.*') ? 'bg-blue-900' : '' }}">
                    Combustible
                </a>
                <a href="{{ route('mantenimiento.index') }}"
                   class="px-3 py-2 rounded text-sm font-medium hover:bg-blue-800 transition
                          {{ request()->routeIs('mantenimiento.*') ? 'bg-blue-900' : '' }}">
                    Mantenimiento
                </a>
                <a href="{{ route('pagos.index') }}"
                   class="px-3 py-2 rounded text-sm font-medium hover:bg-blue-800 transition
                          {{ request()->routeIs('pagos.*') ? 'bg-blue-900' : '' }}">
                    Pagos
                </a>
                <a href="{{ route('reportes.index') }}"
                   class="px-3 py-2 rounded text-sm font-medium hover:bg-blue-800 transition
                          {{ request()->routeIs('reportes.*') ? 'bg-blue-900' : '' }}">
                    Reportes
                </a>

                <div class="relative" data-desplegable>
                    <button type="button" data-desplegable-boton aria-expanded="false" aria-haspopup="true"
                            class="px-3 py-2 rounded text-sm font-medium hover:bg-blue-800 transition flex items-center gap-1
                                   {{ $enCatalogos ? 'bg-blue-900' : '' }}">
                        Catálogos
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div data-desplegable-menu
                         class="hidden absolute right-0 mt-1 w-44 rounded-md bg-white py-1 shadow-lg ring-1 ring-black/10 z-20">
                        @foreach($catalogos as $catalogo)
                            <a href="{{ route($catalogo['ruta']) }}"
                               class="block px-4 py-2 text-sm hover:bg-gray-100
                                      {{ request()->routeIs($catalogo['patron']) ? 'bg-gray-100 font-medium text-blue-700' : 'text-gray-700' }}">
                                {{ $catalogo['texto'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

                @auth
                    <span class="mx-2 h-6 w-px bg-blue-500"></span>
                    @if(Auth::user()->esAdmin())
                        <a href="{{ route('admin.cuentas.index') }}"
                           class="px-3 py-2 rounded text-sm font-medium hover:bg-blue-800 transition
                                  {{ request()->routeIs('admin.*') ? 'bg-blue-900' : '' }}">
                            Cuentas
                        </a>
                    @endif
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
            <a href="{{ route('inicio') }}"
               class="block px-3 py-2 rounded text-sm font-medium hover:bg-blue-800
                      {{ request()->routeIs('inicio') ? 'bg-blue-900' : '' }}">
                Inicio
            </a>
            <a href="{{ route('viajes.index') }}"
               class="block px-3 py-2 rounded text-sm font-medium hover:bg-blue-800
                      {{ request()->routeIs('viajes.*') ? 'bg-blue-900' : '' }}">
                Viajes
            </a>
            <a href="{{ route('simulador.index') }}"
               class="block px-3 py-2 rounded text-sm font-medium hover:bg-blue-800
                      {{ request()->routeIs('simulador.*') ? 'bg-blue-900' : '' }}">
                Simular viaje
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
            <a href="{{ route('pagos.index') }}"
               class="block px-3 py-2 rounded text-sm font-medium hover:bg-blue-800
                      {{ request()->routeIs('pagos.*') ? 'bg-blue-900' : '' }}">
                Pagos
            </a>
            <a href="{{ route('reportes.index') }}"
               class="block px-3 py-2 rounded text-sm font-medium hover:bg-blue-800
                      {{ request()->routeIs('reportes.*') ? 'bg-blue-900' : '' }}">
                Reportes
            </a>

            <p class="px-3 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-blue-300">Catálogos</p>
            @foreach($catalogos as $catalogo)
                <a href="{{ route($catalogo['ruta']) }}"
                   class="block px-3 py-2 rounded text-sm font-medium hover:bg-blue-800
                          {{ request()->routeIs($catalogo['patron']) ? 'bg-blue-900' : '' }}">
                    {{ $catalogo['texto'] }}
                </a>
            @endforeach

            @auth
                <div class="border-t border-blue-600 mt-2 pt-2">
                    <p class="px-3 py-1 text-xs text-blue-200">{{ Auth::user()->name }} · {{ Auth::user()->email }}</p>
                    @if(Auth::user()->esAdmin())
                        <a href="{{ route('admin.cuentas.index') }}"
                           class="block px-3 py-2 rounded text-sm font-medium hover:bg-blue-800
                                  {{ request()->routeIs('admin.*') ? 'bg-blue-900' : '' }}">
                            Cuentas de clientes
                        </a>
                    @endif
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    /*
     * Confirmación antes de borrar o deshacer. El formulario la pide con
     *   data-confirmar="¿Eliminar este viaje?"
     *   data-confirmar-detalle="27/09 · Control Union · $ 251.910,73"  (opcional)
     *   data-confirmar-boton="Sí, deshacer"                             (opcional)
     * Escucha en el documento, así cubre también los botones con form="...".
     * Si SweetAlert no cargó, pregunta con el confirm() del navegador.
     */
    document.addEventListener('submit', function (evento) {
        const form = evento.target;
        if (! form.dataset.confirmar || form.dataset.confirmado) return;

        evento.preventDefault();

        if (! window.Swal) {
            if (confirm(form.dataset.confirmar + ' ' + (form.dataset.confirmarDetalle || ''))) form.submit();
            return;
        }

        Swal.fire({
            title: form.dataset.confirmar,
            text: form.dataset.confirmarDetalle || '',
            icon: 'warning',
            iconColor: '#dc2626',
            showCancelButton: true,
            confirmButtonText: form.dataset.confirmarBoton || 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            focusCancel: true,
            buttonsStyling: false,
            customClass: {
                popup: 'rounded-xl',
                title: 'text-xl font-bold text-gray-800',
                htmlContainer: 'text-sm text-gray-600',
                actions: 'gap-3',
                confirmButton: 'bg-red-600 hover:bg-red-700 text-white font-medium px-5 py-2.5 rounded transition',
                cancelButton: 'bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium px-5 py-2.5 rounded transition',
            },
        }).then(function (resultado) {
            if (! resultado.isConfirmed) return;
            // submit() no vuelve a disparar este evento, pero por las dudas.
            form.dataset.confirmado = '1';
            form.submit();
        });
    });

    // En el celular, tocar la tarjeta de un listado abre lo que corresponda
    // (casi siempre Editar), salvo que se toque un botón, un link o un switch.
    // Si la fila es para elegir (tiene un tilde t-marca), tocarla lo marca o desmarca.
    document.addEventListener('click', function (evento) {
        if (! window.matchMedia('(max-width: 639.98px)').matches) return;
        // Sólo los controles: la tabla puede estar dentro de un formulario (Liquidación).
        if (evento.target.closest('a, button, input, label, select, textarea')) return;

        const fila = evento.target.closest('table.tabla-tarjetas tbody tr, [data-href]');
        if (! fila) return;

        const tilde = fila.querySelector('.t-marca input[type="checkbox"]');
        if (tilde) {
            tilde.checked = ! tilde.checked;
            tilde.dispatchEvent(new Event('change', { bubbles: true }));
        } else if (fila.dataset.href) {
            window.location.href = fila.dataset.href;
        }
    });
</script>
<script>
    document.getElementById('menu-btn').addEventListener('click', function () {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    });

    // Desplegables de la barra (hoy sólo "Catálogos"). Se cierran al hacer
    // clic afuera o con Escape.
    function cerrarDesplegables() {
        document.querySelectorAll('[data-desplegable-menu]').forEach(function (menu) {
            menu.classList.add('hidden');
        });
        document.querySelectorAll('[data-desplegable-boton]').forEach(function (boton) {
            boton.setAttribute('aria-expanded', 'false');
        });
    }

    document.querySelectorAll('[data-desplegable]').forEach(function (contenedor) {
        const boton = contenedor.querySelector('[data-desplegable-boton]');
        const menu  = contenedor.querySelector('[data-desplegable-menu]');

        boton.addEventListener('click', function (evento) {
            // Sin esto el clic llega al document y lo vuelve a cerrar.
            evento.stopPropagation();

            const estaCerrado = menu.classList.contains('hidden');
            cerrarDesplegables();

            if (estaCerrado) {
                menu.classList.remove('hidden');
                boton.setAttribute('aria-expanded', 'true');
            }
        });
    });

    document.addEventListener('click', cerrarDesplegables);
    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') cerrarDesplegables();
    });
</script>
@stack('scripts')

</body>
</html>
