<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Einforma extends Model
{
    use HasFactory;

    protected $table = 'einforma';

    public function entidad()
    {
        return $this->belongsTo(Entidad::class,'identificativo','CIF');
    }

}
