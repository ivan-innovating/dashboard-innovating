<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoCapital extends Model
{
    use HasFactory;
    protected $table = "historico_capital";

    public function entidad()
    {
        return $this->belongsTo(Entidad::class,'entidad_id','id');
    }
}
