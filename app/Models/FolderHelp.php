<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FolderHelp extends Model
{
    use HasFactory;

    protected $table = "carpetas_help";

    public function creator(){
        return $this->belongsTo(User::class,'creator_id','id');
    }

    public function editor(){
        return $this->belongsTo(User::class,'editor_id','id');
    }

    public function paginas(){

        $paginas = $this->hasMany(Help::class);

        $paginas->setQuery(            
            Help::where('id_carpeta', 'LIKE', '%"'.$this->id.'"%')->getQuery()
        );

        return $paginas;
    }
    

}
