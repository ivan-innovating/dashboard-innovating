<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatGPTProyectosKeywords extends Model
{
    use HasFactory;

    protected $table = "chatgpt_proyectos_keywords";

    public function proyecto()
    {
        return $this->belongsTo(Proyectos::class,'id_proyecto','id');
    }

}
