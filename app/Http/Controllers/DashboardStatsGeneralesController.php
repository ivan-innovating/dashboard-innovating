<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardStatsGeneralesController extends Controller
{
    //
    public function statsGenerales(){

        $entidades = \App\Models\Entidad::get();
        $entidadesEs = $entidades->where('pais', 'ES')->count();
        $entidadesNoEs = $entidades->where('pais', '!=', 'ES')->count();
        $empresassintrl = $entidades->where('pais', 'ES')->whereNull('valorTrl')->count();
        $empresastrlmenor4 = $entidades->where('pais', 'ES')->where('valorTrl', '<', 4)->whereNotNull('valorTrl')->count();
        $empresastrl4 = $entidades->where('pais', 'ES')->where('valorTrl', '=', 4)->count();
        $empresastrl5 = $entidades->where('pais', 'ES')->where('valorTrl', '=', 5)->count();
        $empresastrl6 = $entidades->where('pais', 'ES')->where('valorTrl', '=', 6)->count();
        $empresastrl7 = $entidades->where('pais', 'ES')->where('valorTrl', '=', 7)->count();
        $empresastrlmayor7 = $entidades->where('pais', 'ES')->where('valorTrl', '>', 7)->count();
        $empresastrl5masnospain = \App\Models\Entidad::where('valorTrl', '>=', 5)->where('pais', '!=', 'ES')->count();
		$empresastrl5menosnospain = \App\Models\Entidad::where('valorTrl', '<', 5)->where('pais', '!=', 'ES')->count();
        $cifsnozoho = \App\Models\CifsNoZoho::where('movidoEntidad', 0)->count();
        $einformas = \App\Models\Einforma::where('lastEditor','einforma')->count();
        $axesors = \App\Models\Einforma::where('lastEditor','axesor')->count();
        $manuales = \App\Models\Einforma::where('lastEditor', '!=', 'einforma')->where('lastEditor', '!=', 'axesor')->count();
        $concesiones = \App\Models\Concessions::count();
        $patentes = \App\Models\Patentes::count();
        $proyectos = \App\Models\Proyectos::count();
        $proyectosaei = \App\Models\Proyectos::where('organismo', 3319)->count();
        $proyectoscdti = \App\Models\Proyectos::where('organismo', 1768)->count();
        $ayudas = \App\Models\Ayudas::count();
        $encajes = \App\Models\Encaje::count();
        $centros = \App\Models\Entidad::where('esCentroTecnologico',1)->count();

        return view('admin.statsgenerales.statsgenerales', [
            'entidadesEs' => $entidadesEs,
            'entidadesNoEs' => $entidadesNoEs,
            'centros' => $centros,
            'empresassintrl' => $empresassintrl,
            'empresastrlmenor4' => $empresastrlmenor4,
            'empresastrl4' => $empresastrl4,
            'empresastrl5' => $empresastrl5,
            'empresastrl6' => $empresastrl6,
            'empresastrl7' => $empresastrl7,
            'empresastrlmayor7' => $empresastrlmayor7,
            'empresastrl5masnospain' => $empresastrl5masnospain,
            'empresastrl5menosnospain' => $empresastrl5menosnospain,
            'cifsnozoho' => $cifsnozoho,
            'einformas' => $einformas,
            'axesors' => $axesors,
            'manuales' => $manuales,
            'concesiones' => $concesiones,
            'patentes' => $patentes,
            'proyectos' => $proyectos,
            'proyectosaei' => $proyectosaei,
            'proyectoscdti' => $proyectoscdti,
            'ayudas' => $ayudas,
            'encajes' => $encajes
        ]);
    }
}
