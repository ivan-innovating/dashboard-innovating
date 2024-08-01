<?php

namespace App\Http\Controllers;

use App\Http\Settings\GeneralSettings;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    public function config(){

        $config = \App\Models\Config::get();
        $umbral_ayudas = app(\App\Http\Settings\GeneralSettings::class)->umbral_ayudas;
        $umbral_proyectos = app(\App\Http\Settings\GeneralSettings::class)->umbral_proyectos;
        $allow_register = app(\App\Http\Settings\GeneralSettings::class)->allow_register;
        $enlace_evento = app(\App\Http\Settings\GeneralSettings::class)->enlace_evento;
        $texto_evento = app(\App\Http\Settings\GeneralSettings::class)->texto_evento;
        $enable_axesor = app(\App\Http\Settings\GeneralSettings::class)->enable_axesor;
        $enable_einforma = app(\App\Http\Settings\GeneralSettings::class)->enable_einforma;
        $master_featured = app(\App\Http\Settings\GeneralSettings::class)->master_featured;
        $cnaes = \App\Models\Cnaes::get();
        $recompensas = \App\Models\RecompensasTecnologicas::paginate(1000);
        $condicionesrecompensas = \App\Models\CondicionesRecompensas::all();
        $scrappersdata = \App\Models\Settings::where('group', 'scrapper')->get();

        foreach($scrappersdata as $data){

            $datos = json_decode($data->payload,true);
            $data->datos = $datos;
            $search = explode("-",$data->name);
            if($search[0] == "organo"){
                $orgdpto = \App\Models\Organos::where('id', $search[1])->select(['Nombre'])->first();
            }
            if($search[0] == "departamento"){
                $orgdpto = \App\Models\Departamentos::where('id', $search[1])->select(['Nombre'])->first();
            }

            $data->orgdpto = $orgdpto;
        }

        $alarmas = \App\Models\Alarms::get();

        return view('admin.configuration.configuration', [
            'configuration' => $config,
            'umbral_ayudas' => $umbral_ayudas,
            'umbral_proyectos' => $umbral_proyectos,
            'allow_register' => $allow_register,
            'enlace_evento' => $enlace_evento,
            'texto_evento' => $texto_evento,
            'enable_einforma' => $enable_einforma,
            'enable_axesor' => $enable_axesor,
            'master_featured' => $master_featured,
            'scrappersdata' => $scrappersdata,
            'alarmas' => $alarmas,
            'cnaes' => $cnaes,
            'recompensas' => $recompensas,
            'condicionesrecompensas' => $condicionesrecompensas

        ]);

    }

    public function updateUmbrales(Request $request, GeneralSettings $settings){

        try{
            $settings->umbral_ayudas = $request->input('umbralayudas');
            $settings->umbral_proyectos = $request->input('umbralproyectos');
            $settings->allow_register = ($request->input('allow') === null) ? false : true;
            $settings->enlace_evento = ($request->input('enlaceevento') === null) ? "" : $request->input('enlaceevento');
            $settings->texto_evento = ($request->input('textoevento') === null) ? "Ver evento" : $request->input('textoevento');
            $settings->enable_einforma = ($request->input('enable_einforma') === null) ? false : true;
            $settings->enable_axesor = ($request->input('enable_axesor') === null) ? false : true;
            $settings->master_featured = ($request->input('master_featured') === null) ? false : true;
            $settings->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se han podido actualizar los datos, intentalo de nuevo en unos minutos');
        }

        return redirect()->back()->withSuccess('Umbrales actualizados');
    }

    public function editarCnae($id){

        $cnae = \App\Models\Cnaes::where('id', $id)->first();

        return view('admin.cnaes.editar', [
            'cnae' => $cnae

        ]);

    }

    public function editCnae(Request $request){

        try{
           \App\Models\Cnaes::where('id', $request->get('id'))->update([
                'Nombre' => $request->get('nombre'),
                'Tipo' => $request->get('tipo'),
                'TrlMedio' => $request->get('trl'),
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido editar el CNAE en este momento, intentalo de nuevo en unos minutos');
        }

        return redirect()->back()->withSuccess('Cnae actualizado');
    }

    public function editarScrapper(Request $request){

        $scrapper = \App\Models\Settings::where('group', 'scrapper')->where('id', $request->route('id'))->first();

        if(!$scrapper){
            return abort(404);
        }

        $datos = json_decode($scrapper->payload,true);
        $scrapper->datos = $datos;

        $search = explode("-",$scrapper->name);
        if($search[0] == "organo"){
            $orgdpto = \App\Models\Organos::where('id', $search[1])->select(['Nombre'])->first();
        }
        if($search[0] == "departamento"){
            $orgdpto = \App\Models\Departamentos::where('id', $search[1])->select(['Nombre'])->first();
        }

        $scrapper->orgdpto = $orgdpto;

        return view('admin.scrappers.editar', [
            'scrapper' => $scrapper,
        ]);
    }


    public function editScrapper(Request $request){

        if($request->get('setnull')){
            $update = null;
        }else{
            $update = Carbon::createFromFormat('d/m/Y H:i:s', $request->get('inicio'))->format('Y-m-d H:i:s');
        }

        $jsonddbb =  \App\Models\Settings::where('id', $request->get('id'))->select(['payload'])->first();

        $jsondata = json_decode($jsonddbb->payload, true);

        $jsondata['current'] = $request->get('ultima');

        try{
            \App\Models\Settings::where('id', $request->get('id'))->update([
                'payload' => json_encode($jsondata),
                'updated_at' => $update,
            ]);
        }catch(Exception $e){
            return redirect()->back()->withErrors('Error al actualizar los datos');
        }

        return redirect()->back()->withSuccess('Scrapper actualizado correctamente');

    }

    public function crearCondicion(Request $request){

        try{
            $condicion = new \App\Models\CondicionesRecompensas();
            $condicion->tipo_premio = $request->get('tipo');
            $condicion->dato = $request->get('dato');
            $condicion->condicion = $request->get('condicion_incumple');
            $condicion->dato2 = $request->get('dato2');
            $condicion->valor = $request->get('valor');
            $condicion->operacion = $request->get('operacion');
            $condicion->estado = 1;
            $condicion->es_porcentaje = ($request->get('esporcentaje') === null) ? 0 : 1;
            $condicion->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('Error en el guardado de la condición de recompensa');
        }

        return redirect()->back()->withSuccess('Se ha credo correctamente la condición de recompensa');
    }

    public function editarCondicion($id){

        $condicion = \App\Models\CondicionesRecompensas::find($id);

        if(!$condicion){
            return abort(404);
        }

        return view('admin.condiciones.editar', [
            'condicion' => $condicion
        ]);
    }

    public function editCondicion(Request $request){

        try{
            $condicion = \App\Models\CondicionesRecompensas::find($request->get('id'));

            if(!$condicion){
                return redirect()->back()->withErrors('# Error en la actualización de la condición de recompensa');
            }

            $condicion->tipo_premio = $request->get('tipo');
            $condicion->dato = $request->get('dato');
            $condicion->condicion = $request->get('condicion');
            $condicion->dato2 = $request->get('dato2');
            $condicion->valor = $request->get('valor');
            $condicion->operacion = $request->get('operacion');
            $condicion->estado = 1;
            $condicion->es_porcentaje = ($request->get('esporcentaje') === null) ? 0 : 1;
            $condicion->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('* Error en la actualización de la condición de recompensa');
        }

        return redirect()->back()->withSuccess('Se ha actualizado correctamente la condición de recompensa');
    }


}
