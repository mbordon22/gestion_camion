<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Tarifa;
use App\Models\Viaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::withCount('viajes')
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        return view('productos.index', compact('productos'));
    }

    public function create()
    {
        return view('productos.create', ['unidades' => Viaje::$unidades]);
    }

    public function store(Request $request)
    {
        Producto::create($this->validar($request));

        return redirect()->route('productos.index')->with('success', 'Producto creado correctamente.');
    }

    public function edit(Producto $producto)
    {
        return view('productos.edit', ['producto' => $producto, 'unidades' => Viaje::$unidades]);
    }

    /**
     * Cambiarle el nombre corrige también los viajes y las tarifas que lo
     * usan: guardan el nombre como texto, y la tarifa se busca por él. Sin
     * esto, corregir "Azucar" por "Azúcar" dejaría la tarifa sin encontrar.
     */
    public function update(Request $request, Producto $producto)
    {
        $validated = $this->validar($request, $producto);
        $anterior = $producto->nombre;
        $renombrado = $anterior !== $validated['nombre'];
        $viajes = 0;

        DB::transaction(function () use ($producto, $validated, $anterior, $renombrado, &$viajes) {
            $producto->update($validated);

            if ($renombrado) {
                $viajes = Viaje::where('producto', $anterior)->update(['producto' => $validated['nombre']]);
                Tarifa::where('producto', $anterior)->update(['producto' => $validated['nombre']]);
            }
        });

        $mensaje = 'Producto actualizado correctamente.';
        if ($viajes > 0) {
            $mensaje .= $viajes === 1
                ? ' También se corrigió en 1 viaje.'
                : " También se corrigió en {$viajes} viajes.";
        }

        return redirect()->route('productos.index')->with('success', $mensaje);
    }

    public function destroy(Producto $producto)
    {
        // Los viajes y las tarifas lo guardan como texto: borrarlo del catálogo
        // no los toca, pero si ya se usó se desactiva para que siga apareciendo
        // al editar esos viajes.
        if ($producto->viajes()->exists() || $producto->tarifas()->exists()) {
            $producto->update(['activo' => false]);
            return redirect()->route('productos.index')
                ->with('success', 'El producto ya se usó: se desactivó en lugar de borrarse.');
        }

        $producto->delete();
        return redirect()->route('productos.index')->with('success', 'Producto eliminado.');
    }

    private function validar(Request $request, ?Producto $producto = null): array
    {
        $request->merge([
            'nombre' => trim((string) $request->input('nombre')),
            'unidad' => trim((string) $request->input('unidad')) ?: null,
        ]);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:60', Rule::unique('productos', 'nombre')->ignore($producto)],
            'unidad' => 'nullable|string|max:20',
            'activo' => 'nullable|boolean',
        ], [
            'nombre.unique' => 'Ya tenés un producto con ese nombre.',
        ]);

        $validated['activo'] = $request->boolean('activo');

        return $validated;
    }
}
