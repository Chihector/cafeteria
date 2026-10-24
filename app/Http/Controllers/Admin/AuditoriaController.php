<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $auditorias = Auditoria::query()
            ->with('usuario')
            ->when($request->filled('modulo'), fn ($query) => $query->where('modulo', $request->input('modulo')))
            ->when($request->filled('accion'), fn ($query) => $query->where('accion', $request->input('accion')))
            ->when($request->filled('desde'), fn ($query) => $query->whereDate('created_at', '>=', $request->input('desde')))
            ->when($request->filled('hasta'), fn ($query) => $query->whereDate('created_at', '<=', $request->input('hasta')))
            ->when($request->filled('buscar'), function ($query) use ($request) {
                $buscar = '%'.$request->input('buscar').'%';
                $query->where(function ($subquery) use ($buscar) {
                    $subquery->where('descripcion', 'like', $buscar)
                        ->orWhere('ip', 'like', $buscar);
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.auditoria.index', [
            'auditorias' => $auditorias,
            'modulos' => Auditoria::query()->distinct()->orderBy('modulo')->pluck('modulo'),
            'acciones' => Auditoria::query()->distinct()->orderBy('accion')->pluck('accion'),
        ]);
    }
}
