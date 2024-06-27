<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntidadesDepartamentosUsers extends Model
{
    use HasFactory;

    protected $table = "entidades_departamentos_users";

    public function user(){
        return $this->belongsTo(User::class,'id_user','id');
    }

    public function datadpto(){
        return $this->belongsTo(EntidadesDepartamentos::class,'id_departamento','id');
    }
}
