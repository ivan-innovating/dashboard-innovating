<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AyudasRelacionadas extends Model
{
    use HasFactory;

    protected $table = "ayudas_relacionadas";
    protected $guarded = [];
    
    function ayuda(){
        return $this->belongsTo(Ayudas::class, 'ayuda_id_relacionada', 'id');
    }

}
