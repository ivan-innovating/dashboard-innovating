<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Convocatorias extends Model
{
    use HasFactory;

    protected $table = 'ayuda';

    function cashflow(){
        return $this->belongsTo(\App\Models\AyudasCashFlow::class, 'id', 'id_ayuda');
    }
}
