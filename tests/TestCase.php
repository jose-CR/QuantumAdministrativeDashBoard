<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    /**
     * Usuario con rol para entrar al panel.
     *
     * Se crea en la base de pruebas: los tests no pueden depender de que exista
     * un usuario en la base de desarrollo (ahora son bases distintas).
     */
    protected function admin(): User
    {
        $usuario = User::query()
            ->where('email', 'pruebas@quantum.test')
            ->first();

        if (! $usuario) {
            $usuario = User::factory()->create([
                'name' => 'Usuario de pruebas',
                'email' => 'pruebas@quantum.test',
            ]);
        }

        if (! $usuario->roles()->exists()) {
            $usuario->assignRole(
                Role::query()->firstOrCreate([
                    'name' => 'super_admin',
                    'guard_name' => 'web',
                ])
            );
        }

        return $usuario;
    }
}
