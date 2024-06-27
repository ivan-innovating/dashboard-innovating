<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuperAdminEmails extends Model
{
    use HasFactory;

    protected $table = "mails_superadmin";
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class,'creator_id');
    }
}
