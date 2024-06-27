<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SEOPages extends Model
{
    use HasFactory;

    protected $table = "seo_pages";

    function getFondo(){
        return $this->belongsTo(Fondos::class, 'fondos', 'id');
    }

    function getCcaa(){
        return $this->belongsTo(Ccaa::class, 'ccaa', 'id');
    }
}
