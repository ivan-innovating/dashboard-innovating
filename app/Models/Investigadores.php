<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investigadores extends Model
{
    use HasFactory;

    protected $table = "investigadores";

    protected $fillable = [
        'orcid_id',
        'investigador',
        'ultima_experiencia',
        'universidad_name',
        'total_works',
        'scholar_link',
        'id_ultima_experiencia',
        'descripcion',
        'keywords',
        'fecha_inicio_ultima_experiencia',
        'acces_token',
        'id_token',
        'tokenId'
    ];

    protected $casts = [
        'keywords' => 'array'
    ];


    public function entidad()
    {
        return $this->belongsTo(Entidad::class, 'id_ultima_experiencia', 'id');
    }

}
