<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Analisis360 extends Model
{
    use HasFactory;

    protected $table = "analisis_360";

    public function basefile(){
        return $this->belongsTo(EntidadesPdfComerciales::class, 'base_file', 'id');
    }

    public function creador(){
        return $this->belongsTo(User::class, 'user_creator', 'id');
    }

    public function editor(){
        return $this->belongsTo(User::class, 'user_editor', 'id');
    }

    public function simulacion(){
        return $this->belongsTo(EntidadesSimuladas::class, 'simulacion_id', 'id');
    }

    public function entidadSimulada(){
        return $this->belongsTo(Entidad::class, 'entidad_simulada_id', 'id');
    }
}
