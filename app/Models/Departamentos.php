<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamentos extends Model
{
    use HasFactory;

    protected $fillable = ['url'];

    public function empresa(){
        return $this->belongsTo(Entidad::class,'id','idOrganismo');
    }

    public function ccaa(){
        return $this->belongsTo(Ccaa::class,'id_ccaa','id');
    }
}
