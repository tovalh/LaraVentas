<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Dato de referencia global (gen_comuna) — sin empresa ni auditoría. */
class Comuna extends Model
{
    protected $table = 'gen_comuna';
    protected $primaryKey = 'idgen_comuna';
    public $timestamps = false;

    protected $fillable = ['idgen_region', 'descripcion', 'habilitado'];
    protected $casts = ['habilitado' => 'boolean'];

    public function region()
    {
        return $this->belongsTo(Region::class, 'idgen_region', 'idgen_region');
    }
}
