<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessagesThread extends Model
{
    use HasFactory;


    public function encaje(){
        return $this->belongsTo(Encaje::class);
    }
}
