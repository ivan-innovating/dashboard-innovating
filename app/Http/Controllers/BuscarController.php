<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BuscarController extends Controller
{
    //
    public function buscar(Request $request){

        $cif = $request->get('cif');

        $encontrada = null;
        $encontrada = DB::table('pymes')->where('CIF', $cif)->first();

        if(!$encontrada){
            $encontrada = DB::table('entidades')->where('CIF', $cif)->first();
            if($encontrada){
                $encontrada->razon_social = $encontrada->Nombre;
                $encontrada->ccaa = $encontrada->Ccaa;
                $encontrada->id_pyme = $encontrada->Id_zoho;
            }
        }

        return $encontrada;

    }

    public function buscarEmpresas(Request $request){

        $text = $request->get('text');
        $tipo = $request->get('tipo');
        $encontradas = DB::table('entidades')->where('CIF', $text)->get();

        if($encontradas->isEmpty()){

            $encontradas = DB::table('CifsnoZoho')->where('CIF', $text)->get();

            if($encontradas->isEmpty()){

                header('HTTP/1.0 404 Not Found');
                die(json_encode(array('No se han encontrado empresas')));

            }
        }

        $html = '';

        if(!$encontradas->isEmpty()){
            $html = "<ul>";
        }

        foreach($encontradas as $encontrada){
            if(isset($tipo) && $tipo == "investigador"){
                if(isset($encontrada->movidoEntidad)){
                    $html .= "<li><span class='text-info'>Primero tienes que mover la empresa a innovating:</span> <a href=".route('admineditarempresa',[$encontrada->CIF, $encontrada->id])." target='_blank'>".$encontrada->Nombre."(".$encontrada->CIF.")</a></li>";
                }else{
                    $html .= "<li><button type='button' class='asociarinvestigador btn btn-outline-warning btn-sm' data-item=".$encontrada->id."> Asociar a: ".$encontrada->Nombre."(".$encontrada->CIF.")</button></li>";
                }
            }elseif(isset($tipo) && $tipo == "asociar"){
                $html .= "<li><button type='button' class='asociarempresa btn btn-outline-warning btn-sm' data-item=".$encontrada->CIF."> Asociar a proyecto: ".$encontrada->Nombre."(".$encontrada->CIF.")</button></li>";
            }else{
                $html .= "<li><a href=".route('admineditarempresa',[$encontrada->CIF, $encontrada->id])." target='_blank'>".$encontrada->Nombre."(".$encontrada->CIF.")</a></li>";
            }
        }

        $html .= "</ul>";

        return $html;

    }

    public function buscarEmpresasUser(Request $request){

        $text = $request->get('text');
        $encontrada = DB::table('entidades')->where('CIF', $text)->first();

        if($encontrada === null){

            $encontrada = DB::table('CifsnoZoho')->where('CIF', $text)->first();
            if($encontrada === null){
                $checkprioriza = \App\Models\PriorizaEmpresas::where('solicitante', Auth::user()->email)->where('cifPrioritario', $text)->first();

                if($checkprioriza === null){
                    try{
                        $prioriza = new \App\Models\PriorizaEmpresas();
                        $prioriza->solicitante = Auth::user()->email;
                        $prioriza->cifPrioritario = $text;
                        $prioriza->esOrgano = 0;
                        $prioriza->sacadoEinforma = 0;
                        $prioriza->save();
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                    }
        
                    $message = "El usuario: ".Auth::user()->email." ha solicitado añadir/priorizar la empresa con CIF: ".$text." desde el buscador";
                    try{
                        \Artisan::call('send:telegram_notification', [
                            'message' =>  $message
                        ]);
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                    }
                }
                return response()->json('No se han encontrado empresas', 404);            
            }
            
        }

        return response()->json(route('empresa', $encontrada->uri), 200);
        
    }

    public function buscarInvestigador(Request $request){

        $text = $request->get('text');
        $encontrados = \App\Models\Investigadores::where('investigador', 'LIKE', $text."%")->take(50)->get();

        $html = '';

        if(!$encontrados->isEmpty()){
            $html = "<ul>";
        }
        if($encontrados->count() >= 50){
            $html .= "<li><span class='text-info'>* Si la búsqueda obtiene muchos resultados probar a completar con nombre y apellidos</span></li>";
        }

        foreach($encontrados as $encontrada){
            $html .= "<li><a href=".route('editinvestigador',[$encontrada->id])." target='_blank'>".$encontrada->investigador."(".$encontrada->orcid_id.")</a></li>";
        }

        $html .= "</ul>";

        return $html;

    }

    public function buscarconcesiones(Request $request){

        $patentes = DB::table('buffer_patentes')->get();
        $einforma = DB::table('einforma')->where('web', '=', '[]')->get();
        if($request->get('cif') === null){
            $concesiones = DB::table('buffer_concesiones')
            ->where('id_departamento', $request->get('organo'))->orWhere('id_organo', $request->get('organo'))->get();
        }else{
            if($request->get('cif') == "con"){
                $concesiones = DB::table('buffer_concesiones')
                ->where('id_departamento', $request->get('organo'))->orWhere('id_organo', $request->get('organo'))
                ->whereNotNull('custom_field_cif')->get();
            }else{
                $concesiones = DB::table('buffer_concesiones')
                ->where('id_departamento', $request->get('organo'))->orWhere('id_organo', $request->get('organo'))
                ->whereNull('custom_field_cif')->get();
            }
        }

        return view('dashboard/scrappers', [
            'patentes' => $patentes,
            'einforma' => $einforma,
            'concesiones' => $concesiones,
            'option' => 0,
        ]);
    }

    public function buscarpatentes(Request $request){

        $concesiones = array();
        $einforma = DB::table('einforma')->where('web', '=', '[]')->get();
        $solicitudes = array();

        if($request->get('cif') === null){
            $patentes = DB::table('buffer_patentes')->get();
        }else{
            if($request->get('cif') == "con"){
                $patentes = DB::table('buffer_patentes')->orwhere('CIF', '!=', '')->get();
            }else{
                $patentes = DB::table('buffer_patentes')->orWhere('CIF', '=', '')->get();
            }
        }

        return view('dashboard/scrappers', [
            'patentes' => $patentes,
            'concesiones' => $concesiones,
            'einforma' => $einforma,
            'solicitudes' => $solicitudes,
            'option' => 1,
        ]);
    }

    public function buscarprioridades(Request $request){

        $concesiones = array();
        $patentes = DB::table('buffer_patentes')->get();
        $einforma = DB::table('einforma')->where('web', '=', '[]')->get();
        if($request->get('priorizar') == "Organos"){
            $solicitudes = DB::table('prioriza_empresas')->where('esOrgano', 1)
            ->leftJoin('organos', 'organos.id', '=', 'prioriza_empresas.idOrgano')
            ->leftJoin('departamentos', 'departamentos.id', '=', 'prioriza_empresas.idOrgano')
            ->select('departamentos.nombre as nombredepartamento', 'organos.nombre as nombreorgano','prioriza_empresas.*')->get();
        }
        if($request->get('priorizar') == "Empresas"){
            $solicitudes = DB::table('prioriza_empresas')->where('esOrgano', 0)->where('sacadoEinforma', 0)
            ->leftJoin('CifsnoZoho', 'CifsnoZoho.CIF', '=', 'prioriza_empresas.cifPrioritario')
            ->select('CifsnoZoho.Nombre as nombreempresa','prioriza_empresas.*')->get();
        }

        return view('dashboard/scrappers', [
            'patentes' => $patentes,
            'concesiones' => $concesiones,
            'einforma' => $einforma,
            'solicitudes' => $solicitudes,
            'option' => 2,
        ]);
    }


    public function buscarConvocatorias(Request $request){

        $text = $request->get('text');
        
        $convocatorias = \App\Models\Ayudas::where('Acronimo', 'LIKE', '%'.$text.'%')->orWhere('Titulo', 'LIKE', '%'.$text.'%')->get();

        if($convocatorias->isEmpty()){

            header('HTTP/1.0 404 Not Found');
            die(json_encode(array('No se han encontrado convocatorias')));        
        }

        $html = '';

        if($convocatorias->isNotEmpty()){
            $html = "<ul>";
        }

        foreach($convocatorias as $convocatoria){                        
            $html .= "<li><a href=".route('editconvocatoria', $convocatoria->id)." target='_blank'>(".$convocatoria->Acronimo."): ".$convocatoria->Titulo."</a></li>";            
        }

        $html .= "</ul>";

        return $html;

    }


    public function buscarOrganismos(Request $request){

        $text = $request->get('text');
        
        $organos = \App\Models\Organos::where('Acronimo', 'LIKE', '%'.$text.'%')->orWhere('Nombre', 'LIKE', '%'.$text.'%')->get();
        $departamentos = \App\Models\Departamentos::where('Acronimo', 'LIKE', '%'.$text.'%')->orWhere('Nombre', 'LIKE', '%'.$text.'%')->get();
        $organismos = $organos->merge($departamentos);

        if($organismos->isEmpty()){

            header('HTTP/1.0 404 Not Found');
            die(json_encode(array('No se han encontrado convocatorias')));        
        }

        $html = '';

        if($organismos->isNotEmpty()){
            $html = "<ul>";
        }

        foreach($organos as $org){ 
            if($request->get('url') === null){                       
                $html .= "<li><a type='button' class='setorganismo txt-azul' data-item='".$org->id."'>(".$org->Acronimo."): ".$org->Nombre."</a></li>";            
            }else{
                $html .= "<li><a href='".route('admineeditarorgano', $org->id)."' class='txt-azul'>(".$org->Acronimo."): ".$org->Nombre."</a></li>";            
            }
        }

        foreach($departamentos as $org){ 
            if($request->get('url') === null){                       
                $html .= "<li><a type='button' class='setorganismo txt-azul' data-item='".$org->id."'>(".$org->Acronimo."): ".$org->Nombre."</a></li>";            
            }else{
                $html .= "<li><a href='".route('admineditardepartamento', $org->id)."' class='txt-azul'>(".$org->Acronimo."): ".$org->Nombre."</a></li>";            
            }
        }

        $html .= "</ul>";

        return $html;
    }

}

