<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatGPTAyudasKeywords extends Model
{
    use HasFactory;

    protected $table = "chatgpt_ayudas_keywords";


    public function convocatoria()
    {
        return $this->belongsTo(Ayudas::class,'id_ayuda','id');
    }

}
