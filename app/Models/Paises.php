<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paises extends Model
{
    use HasFactory;

    protected $fillable = [

        'Nombre_es', 'Nombre_en', 'iso2', 'iso3', 'mostrar'

    ];
}
