<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'idgen_empresa',
        'name',
        'username',
        'email',
        'password',
        'habilitado',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'habilitado' => 'boolean',
        ];
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'idgen_empresa', 'idgen_empresa');
    }
}
