<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ValidateCompany extends Model
{
    use HasFactory;

    protected $table = 'validate_companies';

    public function solicitante()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
