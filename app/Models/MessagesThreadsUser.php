<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessagesThreadsUser extends Model
{
    use HasFactory;

    public function thread(){
        return $this->belongsTo(MessagesThread::class);
    }
}
