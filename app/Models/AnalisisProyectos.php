<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalisisProyectos extends Model
{
    use HasFactory;

    protected $table = "analisis_proyectos";

    protected $casts = [
        'tags' => 'array',
        'resultado' => 'array',
        'fake_data' => 'array'
    ];

    function creator(){
        return $this->belongsTo(EntidadesSimuladas::class, 'id', 'id_analisis');
    }

    function proyecto(){
        return $this->belongsTo(Proyectos::class, 'id', 'id_analisis');
    }

    function usuario_creador(){
        return $this->belongsTo(User::class, 'id_user_creator', 'id');
    }

    function usuario_editor(){
        return $this->belongsTo(User::class, 'id_user_editor', 'id');
    }
    
}
