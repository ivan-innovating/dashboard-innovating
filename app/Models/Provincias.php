<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provincias extends Model
{
    use HasFactory;

    protected $table = "provincias";

    public function ccaa()
    {
        return $this->belongsTo(Ccaa::class, 'id_ccaa', 'id');
    }
}
