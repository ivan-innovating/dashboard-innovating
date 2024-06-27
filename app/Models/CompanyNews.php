<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyNews extends Model
{
    use HasFactory;

    protected $table = 'company_news';

    protected $guarded = [];

    function entity(){
        return $this->belongsTo(Entidad::class,'company_id', 'CIF');
    }
}
