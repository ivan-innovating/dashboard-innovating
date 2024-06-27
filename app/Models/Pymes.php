<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pymes extends Model
{
    use HasFactory;

    protected $table = "pymes";

    function entidad(){
        return $this->belongsTo(Entidad::class, 'CIF', 'CIF');
    }
}
