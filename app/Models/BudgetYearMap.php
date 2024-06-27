<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetYearMap extends Model
{
    use HasFactory;

    protected $table = "budget_year_map";

    function convocatoria(){        
        return $this->belongsTo(\App\Models\Ayudas::class, 'convocatoria_id', 'id');        
    }
}
