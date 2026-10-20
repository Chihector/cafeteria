<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Canales de broadcasting
|--------------------------------------------------------------------------
|
| Canales privados por módulo. La autorización reutiliza los roles de la
| Fase 11 (admin | barista | cocina).
|
*/

Broadcast::channel('pos', function (User $user) {
    return $user->hasRole('admin', 'barista');
});

Broadcast::channel('cocina', function (User $user) {
    return $user->hasRole('admin', 'cocina');
});

Broadcast::channel('App.Models.User.{id}', function (User $user, $id) {
    return (int) $user->id === (int) $id;
});
