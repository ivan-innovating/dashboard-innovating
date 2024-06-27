<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntidadesDepartamentos extends Model
{
    use HasFactory;

    protected $table = "entidades_departamentos";

    public function users(){
        return $this->hasMany(EntidadesDepartamentosUsers::class,'id_departamento','id');
    }

    public function entidad(){
        return $this->belongsTo(Entidad::class,'id_entidad','id');
    }

}
