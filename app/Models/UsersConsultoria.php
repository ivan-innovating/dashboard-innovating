<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersConsultoria extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id', 'entidad', 'entidad_id','bonificando','admin'
    ];
}
