<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessagesThreadsEntidad extends Model
{
    use HasFactory;

    public function thread(){
        return $this->belongsTo(\App\Models\MessagesThread::class,'messages_threads_id','id');
    }
}
