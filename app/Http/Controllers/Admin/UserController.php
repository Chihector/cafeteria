<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $usuarios = User::query()
            ->orderByRaw("CASE role WHEN 'admin' THEN 0 WHEN 'barista' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get();

        return view('admin.usuarios.index', [
            'usuarios' => $usuarios,
            'totalAdmins' => $usuarios->where('role', 'admin')->where('activo', true)->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.usuarios.create', [
            'roles' => User::ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => 'required|in:admin,barista,cocina',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $usuario = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => $validated['password'],
            'role' => $validated['role'],
            'activo' => true,
        ]);

        Auditoria::registrar(
            'crear',
            'usuarios',
            "Usuario {$usuario->email} creado con rol {$usuario->role}",
            $usuario,
            ['nombre' => $usuario->name, 'email' => $usuario->email, 'rol' => $usuario->role],
        );

        return redirect()->route('admin.usuarios.index')
            ->with('success', "Usuario {$usuario->name} creado correctamente.");
    }

    public function edit(User $usuario): View
    {
        return view('admin.usuarios.edit', [
            'usuario' => $usuario,
            'roles' => User::ROLES,
        ]);
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'role' => 'required|in:admin,barista,cocina',
        ]);

        $antes = $usuario->only(['name', 'email', 'role']);

        // No puedes cambiarte el rol a ti mismo.
        if ($usuario->id === $request->user()->id && $validated['role'] !== $usuario->role) {
            return back()->with('error', 'No puedes cambiar tu propio rol.');
        }

        // Protege al último administrador activo: no puede ser degradado ni desactivado.
        if ($this->esUltimoAdminActivo($usuario) && $validated['role'] !== 'admin') {
            return back()->with('error', 'No se puede degradar al último administrador activo del sistema.');
        }

        $usuario->update($validated);

        Auditoria::registrar(
            'editar',
            'usuarios',
            "Usuario {$usuario->email} actualizado",
            $usuario,
            [
                'antes' => $antes,
                'despues' => $usuario->only(['name', 'email', 'role']),
            ],
        );

        return redirect()->route('admin.usuarios.index')
            ->with('success', "Usuario {$usuario->name} actualizado.");
    }

    /**
     * Resetea la contraseña de un usuario sin conocer la anterior.
     */
    public function resetPassword(Request $request, User $usuario): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $usuario->update(['password' => $validated['password']]);

        Auditoria::registrar(
            'resetear_password',
            'usuarios',
            "Contraseña restablecida para {$usuario->email}",
            $usuario,
        );

        return redirect()->route('admin.usuarios.index')
            ->with('success', "Contraseña de {$usuario->name} actualizada correctamente.");
    }

    /**
     * Activa o desactiva la cuenta (no se elimina para conservar historial).
     */
    public function toggle(User $usuario): RedirectResponse
    {
        // No puedes desactivarte a ti mismo.
        if ($usuario->id === request()->user()->id && $usuario->activo) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        // No puedes desactivar al último administrador activo.
        if ($this->esUltimoAdminActivo($usuario) && $usuario->activo) {
            return back()->with('error', 'No se puede desactivar al último administrador activo del sistema.');
        }

        $usuario->update(['activo' => ! $usuario->activo]);

        Auditoria::registrar(
            $usuario->activo ? 'activar' : 'desactivar',
            'usuarios',
            ($usuario->activo ? 'Usuario activado: ' : 'Usuario desactivado: ').$usuario->email,
            $usuario,
        );

        return redirect()->route('admin.usuarios.index')
            ->with('success', $usuario->activo
                ? "{$usuario->name} puede iniciar sesión nuevamente."
                : "{$usuario->name} ya no puede iniciar sesión.");
    }

    /**
     * Indica si el usuario es admin y no queda otro admin activo.
     */
    private function esUltimoAdminActivo(User $usuario): bool
    {
        if ($usuario->role !== 'admin' || ! $usuario->activo) {
            return false;
        }

        return User::query()
            ->where('role', 'admin')
            ->where('activo', true)
            ->whereKeyNot($usuario->id)
            ->doesntExist();
    }
}
