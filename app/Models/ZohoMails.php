<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZohoMails extends Model
{
    use HasFactory;

    protected $table = "zoho_mails";

    function entidad(){
        return $this->belongsTo(Entidad::class,'Cif','CIF');

    }
}
