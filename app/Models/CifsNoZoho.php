<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CifsNoZoho extends Model
{
    use HasFactory;

    protected $table = "CifsnoZoho";

    function entidad(){
        return $this->belongsTo(Entidad::class, 'CIF', 'CIF');
    }
}
