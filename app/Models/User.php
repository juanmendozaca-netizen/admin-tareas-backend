<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    //Definimos qué campos nunca se deben mostrar cuando el modelo se convierte a JSON o array
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relación: un usuario tiene muchas tareas.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

     //Requerido por JWTSubject: identificador único del token.
     
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }


     //Requerido por JWTSubject: claims personalizados 

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
