<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organos extends Model
{
    use HasFactory;

    protected $fillable = ['url'];

    public function empresa(){
        return $this->belongsTo(Entidad::class,'id','idOrganismo');
    }

    public function ministerios(){
        return $this->belongsTo(Ministerios::class,'id_ministerio','id');
    }
}
