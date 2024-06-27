<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Capitulos extends Model
{
    use HasFactory;

    protected $table = "capitulos";

    public function subfodosPaises(){
        return $this->hasMany(CapitulosPaises::class,'id_fondo_general','id');
    }

}
