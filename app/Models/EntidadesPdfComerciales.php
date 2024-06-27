<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntidadesPdfComerciales extends Model
{
    use HasFactory;

    protected $table = "entidades_pdf_comerciales";

    public function creator(){
        return $this->belongsTo(User::class, 'user_creator', 'id');
    }

    public function editor(){
        return $this->belongsTo(User::class, 'user_editor', 'id');
    }
}
