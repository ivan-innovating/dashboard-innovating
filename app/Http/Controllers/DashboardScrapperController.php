<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardScrapperController extends Controller
{
    //
    public function scrappers(Request $request){

        if($request->get('organismo') === null || $request->get('organismo') == ""){
            $scrapperdata = \App\Models\ProyectosRawData::where('updated_at', '>=', Carbon::now()->subDays(30))->paginate(100);
        }else{
            $scrapperdata = \App\Models\ProyectosRawData::where('id_organismo', $request->get('organismo'))->where('updated_at', '>=', Carbon::now()->subDays(30))->paginate(100);
        }

        $organismos = \App\Models\ProyectosRawData::select('id_organismo')->distinct()->get();

        return view('admin.scrappers.scrappers',[
            'scrapperdata' => $scrapperdata,
            'organismos' => $organismos
        ]);
    }

    public function reglasScrappers($id){

        if($id === null || $id == ""){
            return abort(419);
        }

        $organismo = \App\Models\Organos::find($id);
        if(!$organismo){
            $organismo = \App\Models\Departamentos::find($id);
            if(!$organismo){
                return abort(404);
            }
        }

        $datos = \App\Models\ProyectosRawData::where('id_organismo', $id)->where('updated_at', '>=', Carbon::now()->subDays(30))->first();
        $columnas = array_keys(json_decode($datos->jsondata, true));        
        $convocatorias = \App\Models\Ayudas::whereNotNull('Acronimo')->where('Organismo', $id)->orderby('Acronimo')->get();
        asort($columnas);
        $reglas = \App\Models\ReglasScrappers::where('id_organismo', $id)->orderBy('prioridad')->get();

        $updatereglas = $reglas->where('updated_at', '>=', Carbon::now()->subHours(12));
        $enableButton = false;
        if($updatereglas->isNotEmpty()){
            $enableButton = true;
        }

        $applyRulePending = false;
        if($id !== null){
            $applyRules = \App\Models\ApplyRules::where('id_organismo', $id)->where('applied', 0)->get();
            if($applyRules->count() > 0){
                $applyRulePending = true;
            }
        }

        return view('admin.scrappers.reglas',[
            'organismo' => $organismo,
            'columnas' => $columnas,
            'convocatorias' => $convocatorias,
            'reglas' => $reglas,
            'enableButton' => $enableButton,
            'applyRulePending' => $applyRulePending
        ]); 
    }

    public function saveRegla(Request $request){

        try{
            $regla = new \App\Models\ReglasScrappers();
            $regla->id_organismo = $request->get('organismo');
            $regla->prioridad = $request->get('prioridad');
            $regla->campo_scrapper = $request->get('columna');
            $regla->condicion = $request->get('condicion');
            $regla->valores = json_encode($request->get('valores'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT);
            $regla->id_convocatoria = $request->get('convocatoria');
            $regla->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido guardar la regla en la bbdd');
        }

        return redirect()->back()->withSuccess('Regla de Scrapper de Organismo creada correctamente');
    
    }

    public function editarRegla($id){

        if($id === null || $id == ""){
            return abort(419);
        }

        $regla = \App\Models\ReglasScrappers::find($id);

        if(!$regla){
            return abort(419);
        }

        $datos = \App\Models\ProyectosRawData::where('id_organismo', $regla->id_organismo)->where('updated_at', '>=', Carbon::now()->subDays(30))->first();
        $columnas = array_keys(json_decode($datos->jsondata, true));
        asort($columnas);
        $organismos = \App\Models\ProyectosRawData::select('id_organismo')->distinct()->get();
        $convocatorias = \App\Models\Ayudas::whereNotNull('Acronimo')->where('Organismo', $regla->id_organismo)->orderby('Acronimo')->get();

        return view('admin.scrappers.reglas.editar',[
            'regla' => $regla,
            'columnas' => $columnas,
            'organismos' => $organismos,
            'convocatorias' => $convocatorias
        ]);
    }

    public function editRegla(Request $request){

        if($request->get('id') === null || $request->get('id') == ""){
            return abort(419);
        }

        $regla = \App\Models\ReglasScrappers::find($request->get('id'));

        if(!$regla){
            return abort(419);
        }

        try{
            $regla->prioridad = $request->get('prioridad');
            $regla->campo_scrapper = $request->get('columna');
            $regla->condicion = $request->get('condicion');
            $regla->valores = json_encode($request->get('valores'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT);
            $regla->id_convocatoria = $request->get('convocatoria');
            $regla->activo = ($request->get('activo') !== null) ? 1 : 0;
            $regla->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido actualizar la regla en la bbdd');
        }

        return redirect()->back()->withSuccess('Regla de Scrapper de Organismo actualizad correctamente');
    
    }

    public function deleteRegla(Request $request){

        if($request->get('id') === null || $request->get('id') == ""){
            return abort(419);
        }

        $regla = \App\Models\ReglasScrappers::find($request->get('id'));

        if(!$regla){
            return abort(419);
        }

        try{
            $regla->delete();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido borrar la regla en la bbdd');
        }

        return redirect()->back()->withSuccess('Regla de Scrapper de Organismo borrada correctamente');
    }

    public function datosAgrupados(Request $request){

        $datosagrupados = array();
        $columnas = array();

        if($request->get('organismo') !== null || $request->get('organismo') != ""){
            $datos = \App\Models\ProyectosRawData::where('id_organismo', $request->get('organismo'))->where('updated_at', '>=', Carbon::now()->subDays(30))->first();
            $columnas = array_keys(json_decode($datos->jsondata, true));
            
            if($request->get('columnas') !== null || !empty($request->get('columnas'))){
                $datoscolumnas = \App\Models\ProyectosRawData::where('id_organismo', $request->get('organismo'))->get();
                
                foreach($datoscolumnas as $data){
                    foreach(json_decode($data->jsondata, true) as $key => $value){
                        if(in_array($key, $request->get('columnas'))){
                            if(!isset($datosagrupados[$key])){
                                $datosagrupados[$key][] = $value;
                            }
                            if(!in_array($value, $datosagrupados[$key])){
                                $datosagrupados[$key][] = $value;
                            }
                        }
                    }                 
                }                
                                
            }            
        }

        asort($columnas);
        $organismos = \App\Models\ProyectosRawData::select('id_organismo')->distinct()->get();

        return view('admin.scrappers.datosagrupados',[
            'datosagrupados' => $datosagrupados,
            'columnas' => $columnas,
            'organismos' => $organismos
        ]);

    }

    public function ajaxGetValues(Request $request){

        $datoscolumnas = \App\Models\ProyectosRawData::where('id_organismo', $request->get('organismo'))->get();
        $datosagrupados = array();
        foreach($datoscolumnas as $data){
            foreach(json_decode($data->jsondata, true) as $key => $value){
                if($key == $request->get('columna')){
                    if(!isset($datosagrupados[$key])){
                        $datosagrupados[$key][] = $value;
                    }
                    if(!in_array($value, $datosagrupados[$key])){
                        $datosagrupados[$key][] = $value;
                    }
                }
            }                 
        }                

        if(empty($datosagrupados) || count($datosagrupados) > 60){
            return response()->json('No se han encontrado valores para esa columna o esa columna tiene más de 60 valores diferentes', 404);
        }

        return response()->json(json_encode($datosagrupados[$request->get('columna')], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT), 200);
    }

    public function aplicarReglas(Request $request){
        
        if($request->get('organismo') === null || $request->get('organismo') == ""){
            return abort(419);
        }

        $organismo = \App\Models\Organos::find($request->get('organismo'));
        if(!$organismo){
            $organismo = \App\Models\Departamentos::find($request->get('organismo'));
            if(!$organismo){
                return abort(419);
            }
        }

        $reglas = \App\Models\ReglasScrappers::where('id_organismo', $organismo->id)->orderBy('prioridad')->orderBy('created_at')->pluck('id')->toArray();
        $applyrules = \App\Models\ApplyRules::where('id_organismo', $organismo->id)->first();

        if(!$applyrules){
            $applyrules= new \App\Models\ApplyRules();
            $applyrules->id_organismo = $organismo->id;
        }

        try{            
            $applyrules->rules = json_encode($reglas);
            $applyrules->type = "proyectos";
            $applyrules->applied = 0;            
            $applyrules->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido crear el proceso de aplicar reglas de la bbdd');
        }

        return redirect()->back()->withSuccess('Proceso de alpicado de reglas creado, vuelve en un par de horas para ver si esta correcto');

    }

    public function programarScrapper(){

        $organosScrapper = \App\Models\Organos::where('scrapper', 1)->orderBy('Nombre', 'ASC')->pluck('Nombre', 'id')->toArray();
        $departamentosScrapper = \App\Models\Departamentos::where('scrapper', 1)->orderBy('Nombre', 'ASC')->pluck('Nombre', 'id')->toArray();
        $scrapperspendientes = \App\Models\ScrappersOrganismos::orderBy('created_at')->limit(100)->get();

        return view('admin.scrappers.programar', [
            'organos' => $organosScrapper,
            'departamentos' => $departamentosScrapper,
            'scrapperprogramados' => $scrapperspendientes
        ]);
    }

    public function createProgramScrapper(Request $request){

        if(\Carbon\Carbon::createFromFormat('d/m/Y', $request->get('desde')) >= \Carbon\Carbon::createFromFormat('d/m/Y', $request->get('hasta'))){
            return redirect()->back()->withErrors('La fecha desde es mayor o igual a la fecha hasta');
        }

        if($request->get('organo') !== null){
            $type = "organo";
            $organo = \App\Models\Organos::find($request->get('organo'));
            $idorganismo = $organo->id;
            $superior = $organo->ministerios;
        }else if($request->get('dpto') !== null){
            $type = "departamento";
            $dpto = \App\Models\Departamentos::find($request->get('dpto'));
            $idorganismo = $dpto->id;
            $superior = $dpto->ccaa;
        }else{
            return redirect()->back()->withErrors('No se ha podido crear la tarea programada');
        }

        $checkscrapper = \App\Models\ScrappersOrganismos::where('ejecutado', 0)->where('id_organismo', $idorganismo)->where('type', $type)->first();

        if($checkscrapper){
            return redirect()->back()->withErrors('Ya existe una tarea programada pendiente de ejecución para este organismo, si no es correcta puedes borrarla y crear una nueva.');
        }

        try{
            $scrapper = new \App\Models\ScrappersOrganismos();
            $scrapper->id_user = Auth::user()->id;
            $scrapper->id_organismo = $idorganismo;
            $scrapper->id_ministerio = ($type == "departamento") ? $superior->external_id : $superior->id;
            $scrapper->type = $type;
            $scrapper->desde = \Carbon\Carbon::createFromFormat('d/m/Y', $request->get('desde'))->format('Y-m-d');
            $scrapper->hasta = \Carbon\Carbon::createFromFormat('d/m/Y', $request->get('hasta'))->format('Y-m-d');
            $scrapper->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido crear la tarea programada');
        }

        return redirect()->back()->withSuccess('Se ha creado la tarea programada correctamente');
        
    }

    public function deleteProgramScrapper(Request $request){

        try{
            \App\Models\ScrappersOrganismos::find($request->get('id'))->delete();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido borra la tarea programada');
        }

        return redirect()->back()->withSuccess('Se ha borrado la tarea programada correctamente');
    }
}
