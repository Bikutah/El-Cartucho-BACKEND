<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('nombre')) {
            $query->where('name', 'like', '%' . $request->input('nombre') . '%');
        }

        if ($request->filled('apellido')) {
            $query->where('apellido', 'like', '%' . $request->input('apellido') . '%');
        }

        // Paginación
        $usuarios = $query->orderBy('name')->paginate(10)->withQueryString();

        $renderFila = function($usuario) {
            return '
                <div class="table-cell">
                    <span class="table-cell-label">Id:</span>
                    <span>' . e($usuario->id) . '</span>
                </div>
                <div class="table-cell">
                    <span class="table-cell-label">Nombre:</span>
                    <span>' . e($usuario->name) . '</span>
                </div>
                <div class="table-cell">
                    <span class="table-cell-label">Apellido:</span>
                    <span>' . e($usuario->apellido ?? 'No especificado') . '</span>
                </div>
                <div class="table-cell">
                    <span class="table-cell-label">Email:</span>
                    <span>' . e($usuario->email) . '</span>
                </div>
                <div class="table-cell">
                    <span class="table-cell-label">ID Firebase:</span>
                    <span class="truncate-15 truncate-with-tooltip"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="' . e($usuario->firebase_uid) . '">' 
                        . e(\Illuminate\Support\Str::limit($usuario->firebase_uid, 12)) . 
                    '</span>
                </div>';
        };

        if ($request->ajax()) {
            return view('base.partials.tabla', [
                'items' => $usuarios,
                'columnas' => [
                    ['label' => 'Id'],
                    ['label' => 'Nombre'],
                    ['label' => 'Apellido'],
                    ['label' => 'Email'],
                    ['label' => 'ID Firebase']
                ],
                'renderFila' => $renderFila
            ])->render();
        }

        return view('usuario.usuario_listar', compact('usuarios', 'renderFila'));
    }
}
