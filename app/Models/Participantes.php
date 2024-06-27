<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Participantes extends Model
{
    use HasFactory;

    protected $table = 'participantes_proyectos';

    protected $guarded = [];

    public function proyecto()
    {
        return $this->belongsTo(Proyectos::class,'id_proyecto');
    }

    public function entidad()
    {
        return $this->belongsTo(Entidad::class, 'cif_participante', 'CIF');
    }

    public function cifnozoho()
    {
        return $this->belongsTo(CifsNoZoho::class, 'cif_participante', 'CIF');
    }

    public function concesion()
    {
        return $this->belongsTo(Concessions::class, 'id_concesion', 'id');
    }
}
