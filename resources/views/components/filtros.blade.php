@props(['resumen' => null])

{{--
    Los filtros de un listado. En la computadora van siempre abiertos; en el
    celular quedan plegados en una línea con lo elegido ("Control Union ·
    30/07 al 28/09"), para que el listado empiece arriba y no después de una
    pantalla de selectores.
--}}
<details class="filtros group bg-white rounded-lg shadow mb-5">
    <summary class="sm:hidden flex items-center justify-between gap-3 px-4 py-3 cursor-pointer select-none">
        <span class="min-w-0">
            <span class="block text-xs font-medium text-gray-500">Filtros</span>
            <span class="block text-sm font-medium text-gray-800 truncate">{{ $resumen }}</span>
        </span>
        <svg class="w-5 h-5 flex-shrink-0 text-gray-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </summary>
    <div class="px-4 pb-4 sm:pt-4 border-t border-gray-100 pt-3 sm:border-0">
        {{ $slot }}
    </div>
</details>
<script>
    // En la computadora, abiertos desde el primer momento (sin parpadeo).
    if (window.matchMedia('(min-width: 640px)').matches) {
        document.currentScript.previousElementSibling.open = true;
    }
</script>
