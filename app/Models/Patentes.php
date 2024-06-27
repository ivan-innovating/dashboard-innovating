<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patentes extends Model
{
    use HasFactory;

    protected $table = "patentes";

    function entidad(){
        return $this->belongsTo(Entidad::class, 'CIF', 'CIF');
    }
}
