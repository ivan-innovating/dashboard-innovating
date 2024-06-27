<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatGPTCompanyKeywords extends Model
{
    use HasFactory;

    protected $table = "chatgpt_company_keywords";

    public function entidad()
    {
        return $this->belongsTo(Entidad::class,'id_entidad','id');
    }

}
