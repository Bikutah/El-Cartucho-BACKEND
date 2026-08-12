<?php

namespace App\Http\Controllers;

use App\Models\ZonaEnvio;
use Illuminate\Http\Request;

class ZonaEnvioController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'nombre'   => 'nullable|string|max:255',
            'cp'       => 'nullable|integer',
            'activa'   => 'nullable|in:0,1',
        ]);

        session(['listado_url.zonas_envio' => url()->full()]);
        $query = ZonaEnvio::query();

        if ($request->filled('nombre')) {
            $query->where('nombre', 'like', '%' . $request->nombre . '%');
        }

        if ($request->filled('cp')) {
            $cpVal = (int) $request->cp;
            $query->where('cp_desde', '<=', $cpVal)
                  ->where('cp_hasta', '>=', $cpVal);
        }

        if ($request->filled('activa')) {
            $query->where('activa', (bool) $request->activa);
        }

        $zonas = $query->orderBy('orden', 'asc')->orderBy('id', 'asc')->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('base.partials.tabla', [
                'items' => $zonas,
                'columnas' => [
                    ['label' => 'Id'],
                    ['label' => 'Nombre'],
                    ['label' => 'Rango CP'],
                    ['label' => 'Costo'],
                    ['label' => 'Orden'],
                    ['label' => 'Estado'],
                ],
                'rutaEditar' => 'zonas-envio.edit',
                'renderFila' => function ($zona) {
                    $estadoBadge = $zona->activa
                        ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Activa</span>'
                        : '<span class="badge bg-secondary"><i class="fas fa-times-circle me-1"></i>Inactiva</span>';

                    return '
                        <div class="table-cell" data-label="Id">
                            <span class="table-cell-label">Id:</span>
                            <span>' . e($zona->id) . '</span>
                        </div>
                        <div class="table-cell" data-label="Nombre">
                            <span class="table-cell-label">Nombre:</span>
                            <span class="fw-bold">' . e($zona->nombre) . '</span>
                        </div>
                        <div class="table-cell" data-label="Rango CP">
                            <span class="table-cell-label">Rango CP:</span>
                            <span>' . e($zona->cp_desde) . ' - ' . e($zona->cp_hasta) . '</span>
                        </div>
                        <div class="table-cell" data-label="Costo">
                            <span class="table-cell-label">Costo:</span>
                            <span>$' . number_format($zona->costo, 2, ',', '.') . ' ARS</span>
                        </div>
                        <div class="table-cell" data-label="Orden">
                            <span class="table-cell-label">Orden:</span>
                            <span>' . e($zona->orden) . '</span>
                        </div>
                        <div class="table-cell" data-label="Estado">
                            <span class="table-cell-label">Estado:</span>
                            ' . $estadoBadge . '
                        </div>';
                }
            ])->render();
        }

        return view('zona_envio.zona_envio_listar', compact('zonas'));
    }

    public function create()
    {
        return view('zona_envio.zona_envio_crear');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'   => 'required|string|max:255',
            'cp_desde' => 'required|integer|min:0|lte:cp_hasta',
            'cp_hasta' => 'required|integer|min:0|gte:cp_desde',
            'costo'    => 'required|numeric|min:0.01',
            'activa'   => 'nullable|boolean',
            'orden'    => 'required|integer|min:0',
        ], [
            'nombre.required'   => 'El nombre de la zona es obligatorio.',
            'cp_desde.required' => 'El CP inicial es obligatorio.',
            'cp_desde.lte'      => 'El CP desde debe ser menor o igual al CP hasta.',
            'cp_hasta.required' => 'El CP final es obligatorio.',
            'cp_hasta.gte'      => 'El CP hasta debe ser mayor o igual al CP desde.',
            'costo.required'    => 'El costo de envío es obligatorio.',
            'costo.min'         => 'El costo de envío debe ser mayor a 0.',
            'orden.required'    => 'El orden es obligatorio.',
        ]);

        $validated['activa'] = $request->has('activa') ? (bool) $request->activa : true;

        $overlap = ZonaEnvio::where('activa', true)
            ->where('orden', $validated['orden'])
            ->where('cp_desde', '<=', $validated['cp_hasta'])
            ->where('cp_hasta', '>=', $validated['cp_desde'])
            ->first();

        $zona = ZonaEnvio::create($validated);

        $redirect = redirect()->route('zonas-envio.index')->with('success', 'Zona de envío creada correctamente.');

        if ($overlap) {
            $redirect->with('warning', 'Advertencia: El rango de CP (' . $zona->cp_desde . '-' . $zona->cp_hasta . ') se solapa con la zona activa "' . $overlap->nombre . '" de igual orden (' . $zona->orden . ').');
        }

        return $redirect;
    }

    public function edit(ZonaEnvio $zonas_envio)
    {
        $zona = $zonas_envio;
        return view('zona_envio.zona_envio_editar', compact('zona'));
    }

    public function update(Request $request, ZonaEnvio $zonas_envio)
    {
        $zona = $zonas_envio;

        $validated = $request->validate([
            'nombre'   => 'required|string|max:255',
            'cp_desde' => 'required|integer|min:0|lte:cp_hasta',
            'cp_hasta' => 'required|integer|min:0|gte:cp_desde',
            'costo'    => 'required|numeric|min:0.01',
            'activa'   => 'nullable|boolean',
            'orden'    => 'required|integer|min:0',
        ], [
            'nombre.required'   => 'El nombre de la zona es obligatorio.',
            'cp_desde.required' => 'El CP inicial es obligatorio.',
            'cp_desde.lte'      => 'El CP desde debe ser menor o igual al CP hasta.',
            'cp_hasta.required' => 'El CP final es obligatorio.',
            'cp_hasta.gte'      => 'El CP hasta debe ser mayor o igual al CP desde.',
            'costo.required'    => 'El costo de envío es obligatorio.',
            'costo.min'         => 'El costo de envío debe ser mayor a 0.',
            'orden.required'    => 'El orden es obligatorio.',
        ]);

        $validated['activa'] = $request->has('activa') ? (bool) $request->activa : false;

        $overlap = ZonaEnvio::where('id', '!=', $zona->id)
            ->where('activa', true)
            ->where('orden', $validated['orden'])
            ->where('cp_desde', '<=', $validated['cp_hasta'])
            ->where('cp_hasta', '>=', $validated['cp_desde'])
            ->first();

        $zona->update($validated);

        $redirect = redirect()->route('zonas-envio.index')->with('success', 'Zona de envío actualizada correctamente.');

        if ($overlap) {
            $redirect->with('warning', 'Advertencia: El rango de CP (' . $zona->cp_desde . '-' . $zona->cp_hasta . ') se solapa con la zona activa "' . $overlap->nombre . '" de igual orden (' . $zona->orden . ').');
        }

        return $redirect;
    }

    public function destroy(ZonaEnvio $zonas_envio)
    {
        $zona = $zonas_envio;
        $zona->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Zona de envío eliminada correctamente.']);
        }

        return redirect()->route('zonas-envio.index')->with('success', 'Zona de envío eliminada correctamente.');
    }
}
