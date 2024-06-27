<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Help extends Model
{
    use HasFactory;

    protected $table = "help";

    public function editor(){
        return $this->belongsTo(User::class,'editor_id','id');
    }

    public function carpetas(){
        return $this->hasMany(FolderHelp::class);
    }

}
