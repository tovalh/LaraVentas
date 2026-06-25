<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresas';
    protected $primaryKey = 'idgen_empresa';

    protected $fillable = ['razon_social', 'ruc', 'color', 'habilitado'];

    protected $casts = ['habilitado' => 'boolean'];

    public function usuarios()
    {
        return $this->hasMany(User::class, 'idgen_empresa', 'idgen_empresa');
    }
}
