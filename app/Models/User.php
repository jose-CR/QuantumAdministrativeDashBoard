<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Quién puede entrar al panel.
     *
     * Sin este método (y sin implementar FilamentUser), Filament solo deja pasar
     * cuando APP_ENV es "local": en producción el panel entero respondía 403.
     * Cualquier usuario con un rol asignado entra; qué ve lo deciden los permisos.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->roles()->exists();
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function sessions()
    {
        return $this->hasMany(Session::class, 'user_id');
    }

    public function getLastSeenAttribute()
    {
        $session = $this->sessions()
            ->latest('last_activity')
            ->first()
        ;

        if (!$session) {
            return 'Nunca ha iniciado sesión';
        }

        return Carbon::createFromTimestamp(
            $session->last_activity
        )->diffForHumans();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
