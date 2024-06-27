<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessagesThreadLast extends Model
{
    use HasFactory;

    protected $table = "messages_threads_lastmessages";

    public function mensaje(){
        return $this->belongsTo(MessagesThreadsMessage::class,'id_ultimo_mensaje','id');
    }

    public function entidad_principal(){
        return $this->belongsTo(MessagesThreadsMessage::class,'entity_principal','id');
    }

    public function entidad_participante(){
        return $this->belongsTo(Entidad::class,'entity_participante','id');
    }
}


