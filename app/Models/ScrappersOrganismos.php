<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScrappersOrganismos extends Model
{
    use HasFactory;

    protected $table = "program_scrapper";

    function organo(){
        return $this->belongsTo(Organos::class, 'id_organismo', 'id');
    }

    function departamento(){
        return $this->belongsTo(Departamentos::class, 'id_organismo', 'id');
    }

    function user(){
        return $this->belongsTo(User::class, 'id_user', 'id');
    }
}
