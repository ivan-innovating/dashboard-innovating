<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Encaje extends Model
{
    protected $table = 'Encajes_zoho';

    use HasFactory;

    public function getEncajeTipo(){
        return $this->Tipo;
    }

    public function proyecto(){
        return $this->belongsTo(Proyectos::class, 'Proyecto_id', 'id');
    }

    public function ayuda(){
        return $this->belongsTo(Ayudas::class, 'Ayuda_id', 'id');
    }

    function keywords(){
        return $this->belongsTo(ChatGPTAyudasKeywords::class, 'id', 'id_encaje');
    }
}
