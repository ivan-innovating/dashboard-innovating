<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecompensasTecnologicas extends Model
{
    use HasFactory;

    protected $table = "recompensas_tecnologicas";

    protected $guarded = [];

    public function cnae()
    {
        return $this->belongsTo(Cnaes::class, 'cnae_id', 'id');
    }
}
