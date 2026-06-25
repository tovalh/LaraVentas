<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Dato de referencia global (gen_region) — sin empresa ni auditoría. */
class Region extends Model
{
    protected $table = 'gen_region';
    protected $primaryKey = 'idgen_region';
    public $timestamps = false;

    protected $fillable = ['descripcion', 'habilitado'];
    protected $casts = ['habilitado' => 'boolean'];

    public function comunas()
    {
        return $this->hasMany(Comuna::class, 'idgen_region', 'idgen_region');
    }
}
