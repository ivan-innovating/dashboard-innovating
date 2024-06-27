<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadExcels extends Model
{
    use HasFactory;

    protected $table = "upload_excels";
    protected $guarded = [];

    function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
