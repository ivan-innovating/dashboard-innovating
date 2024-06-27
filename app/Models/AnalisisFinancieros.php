<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalisisFinancieros extends Model
{
    use HasFactory;

    protected $table = 'analisis_financieros';

    public function ayuda()
    {
        return $this->belongsTo(Ayudas::class, 'id_ayuda','id');
    }

    public function company()
    {
        return $this->belongsTo(Entidad::class, 'entidad_analisis','id');
    }

    public function company_creator()
    {
        return $this->belongsTo(Entidad::class, 'id_entidad','id');
    }

    public function simulated_company()
    {
        return $this->belongsTo(EntidadesSimuladas::class, 'entidad_simulada','id');   
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'id_user','id');
    }

}
