<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElasticDataTable extends Model
{
    use HasFactory;

    protected $table = "elastic_data";
    protected $guarded = [];  

    protected $casts = [
        'IDOrgDeptConcedido' => 'array',
        'IDInteresesConcedido' => 'array',
        'TipoFinanciacionConcedido' => 'array',
        'IDAyudasConcedidas' => 'array',
        'DominiosEmpresa' => 'array',
        'FlagsEntidad' => 'array',
        'Naturaleza' => 'array',
        'ListadoEmails' => 'array',
        'PerfilesFinanciacion' => 'array',
        'ComunidadAutonoma' => 'array',
        'FiltroLider' => 'boolean',
        'XPLider' => 'boolean',
        'SPIAuto' => 'boolean',
        'Featured' => 'boolean',
        'SelloPyme' => 'boolean',
        'UltimoEjercicioFinanciero' => 'integer'
    ];

    public function entidad()
    {
        return $this->belongsTo(Entidad::class,'NIF','CIF');
    }

}
