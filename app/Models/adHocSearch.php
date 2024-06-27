<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class adHocSearch extends Model
{
    use HasFactory;

    protected $table = "adhoc_searchs";


    public function entidad()
    {
        return $this->belongsTo(Entidad::class,'entidad_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
