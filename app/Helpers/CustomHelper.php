<?php

use Carbon\Carbon;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use GuzzleHttp\Exception\GuzzleException;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use App\Http\Settings\GeneralSettings;
use Illuminate\Support\Facades\Request;

const SCOREPRESUPUESTO = 200000;

 /**
  * @param mixed $id
  * @return array
  * @throws BindingResolutionException
  * @throws NotFoundExceptionInterface
  * @throws ContainerExceptionInterface
  * @throws GuzzleException
  * @throws InvalidFormatException
  */
    function getElasticAyudas($id, $tipo = null, $intensidad = 0, $simulada = null, $trlproyecto = null, $page = null){

        if($page === null){
            $page = 1;
        }

        if($simulada){
            $cache = ($tipo === null) ? 'ayudas_empresas_'.$id.'_'.$intensidad.'_simulada.__page_'.$page : 'ayudas_empresas_'.$id.'_'.$intensidad.'_'.$tipo.'_'.$page.'_simulada';
        }else{
            $cache = ($tipo === null) ? 'ayudas_empresas_'.$id.'_'.$intensidad.'_page_'.$page : 'ayudas_empresas_'.$id.'_'.$intensidad.'_'.$tipo.'_'.$page;
        }

        $ayudaresponse = Cache::remember($cache, now()->addMinutes(120), function () use($id, $tipo, $trlproyecto, $simulada, $page) {

            $elasticEnvironment = config('services.elastic_ayudas.environment');
            $urlEndpoint = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.endpoint');
            $apikey = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.api_key');

            $client = new \GuzzleHttp\Client([
                'base_uri' => $urlEndpoint,
                'headers' => [
                    'x-api-key' => $apikey
                ]
            ]);

            if($tipo === null){
                $dataSend = array(
                    "type" => "COMPANY_ID",
                    "params" => [
                        "companyId" => $id,
                        "tipoEncaje" => "",
                        "organismoId" => ""
                    ]
                );
            }else{
                $dataSend = array(
                    "type" => "COMPANY_ID",
                    "params" => [
                        "companyId" => $id,
                        "tipoEncaje" => $tipo,
                        "organismoId" => ""
                    ]
                );
            }

            $ayudas = [];

            try{
                $response = $client->post('publicAid/search?numItemsPage=30&numPage='.$page,[
                    \GuzzleHttp\RequestOptions::JSON => $dataSend
                ]);


                $result = json_decode((string)$response->getBody());

                ##ONLY FOR DEV DEBUGGING
                if(\App::environment() != "prod"){
                    if(request()->get('debug') == "1"){
                        dump($result);
                    }
                }

                if($result->data){
                    $ayudas = $result->data;
                }

            }catch (\GuzzleHttp\Exception\ServerException $e){
                //dd("error");
                Log::error($dataSend);
                Log::error($e->getMessage());
                return 'ups';
            }         

            $idsAyudaInfo = [];

            if(isset($tipo) && $tipo == "proyecto"){

                $ayudascheck = collect($ayudas)->sortByDesc('score')->where('TipoEncaje', 'proyecto')->groupBy('ID');

                foreach ($ayudascheck as $ayuda) {

                    $firstItem =$ayuda->first();
                    $idAyuda = $firstItem->IDAyuda;

                    $idsAyudaInfo[] = [
                        'score' => $firstItem->score,
                        'idAyuda' => $idAyuda,
                        'organismo' => $firstItem->Organismo,
                        'idEncaje' => $firstItem->ID,
                        'TipoEncaje' => $firstItem->TipoEncaje,
                        'Recomendar' => 0,
                        'filtrada' => (isset($firstItem->filtrada)) ? 1 : 0,
                    ];
                }
                
            }elseif(isset($tipo) && $tipo == "consultoria"){

                $proyects = collect($ayudas)->sortByDesc('score')->where('TipoEncaje', 'consultoria');

                foreach ($proyects as $ayuda) {
                    $idsAyudaInfo[] = [
                        'score' => $ayuda->score,
                        'idAyuda' => $ayuda->IDAyuda,
                        'organismo' => $ayuda->Organismo,
                        'idEncaje' => $ayuda->ID,
                        'TipoEncaje' => $ayuda->TipoEncaje,
                        'Recomendar' => 0,
                        'filtrada' => (isset($ayuda->filtrada)) ? 1 : 0,
                    ];
                }

            }else{
                
                $targets = collect($ayudas)->where('TipoEncaje', 'target')->pluck('IDAyuda')->toArray();
                $notargets = collect($ayudas)->where('TipoEncaje', '!=', 'target')->pluck('IDAyuda')->toArray();
                $ayudas = collect($ayudas)->where('TipoEncaje', '!=', 'proyecto')->sortByDesc('score')->groupBy('IDAyuda');
                if(session()->get('umbral_ayudas') === null){
                    $settings = DB::table('settings')->where('group', 'general')->where('name','umbral_ayudas')->first();
                    $umbral = (int) $settings->payload;
                }else{
                    $umbral = session()->get('umbral_ayudas');
                }

                $totalfiltradasintensidad = 0;

                foreach ($ayudas as $key => $ayuda) {

                    /*if($ayuda->first()->ID == "231435000088800652"){
                        dump($ayuda);
                    }*/

                    $firstItem = $ayuda->first();        

                    $tipotarget = null;
                    if($firstItem->TematicaObligatoria === true){
                        $tipotarget = collect($ayudas)->where('TipoEncaje', 'target')->where('IDAyuda', $firstItem->IDAyuda)->pluck('ID')->toArray();
                    }

                    if((isset($firstItem->FiltroPerfilInteres) && $firstItem->FiltroPerfilInteres[0] === true) 
                    || (isset($firstItem->FiltroIntensidad) && $firstItem->FiltroIntensidad[0] === true)
                    || (isset($firstItem->FiltroConsorcio) && $firstItem->FiltroConsorcio[0] === true)
                    ){
                        $firstItem->filtrada = 1;                        
                    }

                    if(isset($firstItem->FiltroIntensidad) && $firstItem->FiltroIntensidad[0] === true){
                        $totalfiltradasintensidad++;
                    }

                    $checkTarget = null;
                    if($firstItem->TematicaObligatoria === true && ($tipotarget === null || empty($tipotarget))){
                        $checkTarget = \App\Models\Encaje::where('Tipo', 'target')->where('Ayuda_id', $firstItem->IDAyuda)->count();
                        if($checkTarget > 0){
                            $firstItem->score = -10;
                        }
                    }

                    if(userEntidadSelected()){                      
                        if($trlproyecto === null){
                            $recomendar = getAyudaRecomendada($firstItem, $targets, $notargets, $umbral, userEntidadSelected()->valorTrl, $tipotarget, userEntidadSelected());
                        }else{
                            $recomendar = getAyudaRecomendada($firstItem, $targets, $notargets, $umbral, $trlproyecto, $tipotarget, userEntidadSelected());
                        }
                    }else{
                        $recomendar = getAyudaRecomendada($firstItem, $targets, $notargets, $umbral, $tipotarget, null);
                    }

                    if($recomendar->valor == -2){
                        unset($ayudas[$key]);
                        continue;
                    }

                    if($recomendar->valor == -1 && $recomendar->tag == 0 && $recomendar->target == 1){
                        unset($ayudas[$key]);
                        continue;
                    }

                    if($firstItem->score < 0){
                        unset($ayudas[$key]);
                        continue;
                    }

                    $idsAyudaInfo[] = [
                        'score' => $firstItem->score,
                        'idAyuda' => $firstItem->IDAyuda,
                        'organismo' => $firstItem->Organismo,
                        'idEncaje' => $firstItem->ID,
                        'TipoEncaje' => $firstItem->TipoEncaje,
                        'Recomendar' => $recomendar,
                        'PresupuestoMin' => ($firstItem->PresupuestoMin === null) ? 0 : $firstItem->PresupuestoMin,
                        'Intensidad' => $firstItem->Intensidad,
                        'TematicaOBligatoria' => $firstItem->TematicaObligatoria,
                        'filtrada' => (isset($firstItem->filtrada)) ? 1 : 0,
                    ];
                    
                }

            }

            $ayudaresponse = array();
            $checkayudas = array();
            $i = 0;
            $check = 0;

            foreach($idsAyudaInfo as $key => $ayuda){

                if($ayuda['TipoEncaje'] == "proyecto"){

                    if($ayuda['score'] >= session()->get('umbral_proyectos')){
                        $i++;

                        $enc = \App\Models\Encaje::where('id', $ayuda['idEncaje'])->first();

                        if($enc){
                            $ayudaresponse[$i] = \App\Models\Proyectos::where('id', $enc->Proyecto_id)->first();
                            if(!$ayudaresponse[$i]){
                                unset($ayudaresponse[$i]);
                                continue;
                            }

                            $ayudaresponse[$i]->Organismo = $ayuda['organismo'];
                            $ayudaresponse[$i]->Encaje_id =  $ayuda['idEncaje'];
                            $ayudaresponse[$i]->score = $ayuda['score'];
                            $ayudaresponse[$i]->dpto = null;
                            $ayudaresponse[$i]->TipoEncaje = $ayuda['TipoEncaje'];
                            $ayudaresponse[$i]->idAyuda = $ayuda['idAyuda'];
                            $ayudaresponse[$i]->filtrada = ($ayuda['filtrada'] == 1) ? 1 : 0;
                            $checkayudas[$ayuda['idEncaje']] = $ayudaresponse[$i];
                            $ayudaresponse[$i]->Recomendar = $ayuda['Recomendar'];
                        }
                    }

                }elseif($ayuda['TipoEncaje'] == "consultoria"){

                    $enc = \App\Models\Encaje::where('id', $ayuda['idEncaje'])->first();

                    if($enc && $ayuda['score'] >= 0){
                        $i++;
                        $ayudaresponse[$i] = \App\Models\Proyectos::where('id', $enc->Proyecto_id)->first();
                        if(!$ayudaresponse[$i]){
                            unset($ayudaresponse[$i]);
                            continue;
                        }

                        $ayudaresponse[$i]->Organismo = $ayuda['organismo'];
                        $ayudaresponse[$i]->Encaje_id =  $ayuda['idEncaje'];
                        $ayudaresponse[$i]->score = $ayuda['score'];
                        $ayudaresponse[$i]->dpto = null;
                        $ayudaresponse[$i]->TipoEncaje = $ayuda['TipoEncaje'];
                        $ayudaresponse[$i]->idAyuda = $ayuda['idAyuda'];
                        $ayudaresponse[$i]->filtrada = ($ayuda['filtrada'] == 1) ? 1 : 0;
                        $checkayudas[$ayuda['idEncaje']] = $ayudaresponse[$i];
                        $ayudaresponse[$i]->Recomendar = $ayuda['Recomendar'];
                    }

                }else{

                    if($ayuda['score'] >= (int)session()->get('umbral_ayudas') && $ayuda['TematicaOBligatoria'] === true){
                        $i++;
                        $ayudaresponse[$i] = \App\Models\Ayudas::where('Id', $ayuda['idAyuda'])->first();

                        if(!$ayudaresponse[$i]){
                            unset($ayudaresponse[$i]);
                            continue;
                        }
                        $ayudaresponse[$i]->exige = 0;
                        if($ayuda['Recomendar']->valor == -1){
                            $ayudaresponse[$i]->exige = 1;    
                        }

                        $ayudaresponse[$i]->Encaje_id =  $ayuda['idEncaje'];
                        $ayudaresponse[$i]->TipoEncaje = $ayuda['TipoEncaje'];
                        $ayudaresponse[$i]->Recomendar = $ayuda['Recomendar'];
                        $ayudaresponse[$i]->idAyuda = $ayuda['idAyuda'];
                        $ayudaresponse[$i]->filtrada = ($ayuda['filtrada'] == 1) ? 1 : 0;
                        $ayudaresponse[$i]->TematicaOBligatoria = ($ayuda['TematicaOBligatoria'] === true) ? 1 : 0;
                        $ayudaresponse[$i]->Intensidad = $ayuda['Intensidad'];
                        $ayudaresponse[$i]->PresupuestoMin = $ayuda['PresupuestoMin'];
                        $checkayudas[$ayuda['idEncaje']] = $ayudaresponse[$i];

                        /* if($ayudaresponse[$i]->Fin === null){
                                unset($ayudaresponse[$i]);
                                continue;
                            }*/
                        if(isset($ayudaresponse[$i]->Fin)){
                            if(Carbon::parse($ayudaresponse[$i]->Fin) < Carbon::now()->subDays(30)){
                                unset($ayudaresponse[$i]);
                                continue;
                            }
                        }

                        $ayudaresponse[$i]->score = $ayuda['score'];

                    }elseif($ayuda['score'] >= 0){
                        $i++;
                        $ayudaresponse[$i] = \App\Models\Ayudas::where('Id', $ayuda['idAyuda'])->first();

                        if(!$ayudaresponse[$i]){
                            unset($ayudaresponse[$i]);
                            continue;
                        }

                        $ayudaresponse[$i]->exige = 0;
                        if($ayuda['Recomendar']->valor == -1){
                            $ayudaresponse[$i]->exige = 1;    
                        }

                        $ayudaresponse[$i]->Encaje_id =  $ayuda['idEncaje'];
                        $ayudaresponse[$i]->TipoEncaje = $ayuda['TipoEncaje'];
                        $ayudaresponse[$i]->Recomendar = $ayuda['Recomendar'];
                        $ayudaresponse[$i]->idAyuda = $ayuda['idAyuda'];
                        $ayudaresponse[$i]->filtrada = ($ayuda['filtrada'] == 1) ? 1 : 0;
                        $ayudaresponse[$i]->TematicaOBligatoria = ($ayuda['TematicaOBligatoria'] === true) ? 1 : 0;
                        $ayudaresponse[$i]->totalfiltrointensidad = $totalfiltradasintensidad;
                        $ayudaresponse[$i]->Intensidad = $ayuda['Intensidad'];
                        $ayudaresponse[$i]->PresupuestoMin = $ayuda['PresupuestoMin'];
                        $checkayudas[$ayuda['idEncaje']] = $ayudaresponse[$i];
                        /* if($ayudaresponse[$i]->Fin === null){
                                unset($ayudaresponse[$i]);
                                continue;
                            }*/
                        if(isset($ayudaresponse[$i]->Fin)){
                            if(Carbon::parse($ayudaresponse[$i]->Fin) < Carbon::now()->subDays(30)){
                                unset($ayudaresponse[$i]);
                                continue;
                            }
                        }

                        $ayudaresponse[$i]->score = $ayuda['score'];
                    }
                }
            }

            if($simulada == 1){
                usort($ayudaresponse, function($a, $b) {
                    return [$a['Intensidad'], $a['PresupuestoMin']]
                           <=>
                           [$b['Intensidad'], $b['PresupuestoMin']];
                });
            }

            $ayudaresponse['totalpages'] = 1;            
            if(isset($result->pagination) && isset($result->pagination->numTotalPages) && $result->pagination->numTotalPages > 1){
                $ayudaresponse['totalpages'] = 2;//$result->pagination->numTotalPages;
            }
            
            return $ayudaresponse;
        });
        
            
        return $ayudaresponse;

    }
 /**
  * @param mixed $id
  * @return array
  * @throws BindingResolutionException
  * @throws NotFoundExceptionInterface
  * @throws ContainerExceptionInterface
  * @throws GuzzleException
  * @throws InvalidFormatException
  */
  function getElasticAyudasByAyudaId($id, $ayudaId, $idanalisis = null){

    $elasticEnvironment = config('services.elastic_ayudas.environment');
    $urlEndpoint = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.endpoint');
    $apikey = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.api_key');

    $client = new \GuzzleHttp\Client([
        'base_uri' => $urlEndpoint,
        'headers' => [
            'x-api-key' => $apikey
        ]
    ]);

    if($idanalisis === null){
        $idanalisis = 0;
    }

    $ayudas = Cache::remember("elastic_ayudas_by_idayuda_".$id."_".$ayudaId."_".$idanalisis, now()->addMinutes(120), function () use($id,$ayudaId,$client,$idanalisis) {

        if($idanalisis !== null && $idanalisis > 0){
            $analisis = \App\Models\EntidadesSimuladas::find($idanalisis);
            $dataSend = array(
                "type" => "COMPANY_ID",
                "params" => [
                    "companyId" => $analisis->CIF
                ]
            );
        }else{

            $dataSend = array(
                "type" => "COMPANY_ID",
                "params" => [
                    "companyId" => $id
                ]
            );

        }

        $ayudas = [];

        try{
            ##NUEVO ENDPOINT publicAid/{idayuda}/score/{company}
            $response = $client->post('publicAid/search?numItemsPage=200',[
                \GuzzleHttp\RequestOptions::JSON => $dataSend
            ]);


            $result = json_decode((string)$response->getBody());

            ##ONLY FOR DEV DEBUGGING
            if(\App::environment() != "prod"){
                if(request()->get('debug') == "1"){
                    dump($result);
                }
            }

            if($result->data){
                $ayudas = $result->data;
            }

        }catch (\GuzzleHttp\Exception\ServerException $e){
            //dd("error");
            Log::error($dataSend);
            Log::error($e->getMessage());
            return 'ups';
        }

        if($ayudas){

            if(session()->get('umbral_ayudas') === null){
                $settings = DB::table('settings')->where('group', 'general')->where('name','umbral_ayudas')->first();
                $umbral = (int) $settings->payload;
            }else{
                $umbral = session()->get('umbral_ayudas');
            }

            foreach($ayudas as $key => $ayuda){
                if($ayuda->IDAyuda != $ayudaId){
                    unset($ayudas[$key]);
                    continue;
                }
                if($ayuda->TipoEncaje == 'proyecto'){
                    unset($ayudas[$key]);
                    continue;
                }

                $tipotarget = null;
                if($ayuda->TematicaObligatoria === true){
                    $tipotarget = collect($ayudas)->where('TipoEncaje', 'target')->where('IDAyuda', $ayuda->IDAyuda)->pluck('ID')->toArray();
                }

                $checkTarget = null;
                if($ayuda->TematicaObligatoria === true && ($tipotarget === null || empty($tipotarget))){
                    $checkTarget = \App\Models\Encaje::where('Tipo', 'target')->where('Ayuda_id', $ayuda->IDAyuda)->count();
                    if($checkTarget > 0){
                        $ayuda->score = -10;
                    }
                }

                $targets = collect($ayudas)->where('TipoEncaje', 'target')->where('IDAyuda', $ayudaId)->pluck('IDAyuda')->toArray();
                $notargets = collect($ayudas)->where('TipoEncaje', '!=', 'target')->where('IDAyuda', $ayudaId)->pluck('IDAyuda')->toArray();
                if(userEntidadSelected()){
                    if($idanalisis !== null && isset($analisis)){
                        $recomendar = getAyudaRecomendada($ayuda, $targets, $notargets, $umbral, $analisis->valorTrl, $tipotarget, $analisis);
                    }else{
                        $recomendar = getAyudaRecomendada($ayuda, $targets, $notargets, $umbral, userEntidadSelected()->valorTrl, $tipotarget, userEntidadSelected());
                    }
                }else{
                    $recomendar = getAyudaRecomendada($ayuda, $targets, $notargets, $umbral, $tipotarget, null);
                }

                if($recomendar->valor == -2){
                    $ayuda->score = -10;
                }

                $ayuda->Recomendar = $recomendar;

            }
        }

        return $ayudas;

    });

    return $ayudas;


}
// Shortens a number and attaches K, M, B, etc. accordingly
 /**
  * @param mixed $number
  * @param int $precision
  * @param mixed $divisors
  * @return string
  */
    function number_shorten($number, $precision = 3, $divisors = null) {

        if(is_numeric($number) === false){
            $number = 0.00;
        }

        // Setup default $divisors if not provided
        if (!isset($divisors)) {
            $divisors = array(
                pow(1000, 0) => '', // 1000^0 == 1
                pow(1000, 1) => 'K', // Thousand
                pow(1000, 2) => 'M', // Million
                pow(1000, 3) => 'B', // Billion
                pow(1000, 4) => 'T', // Trillion
                pow(1000, 5) => 'Qa', // Quadrillion
                pow(1000, 6) => 'Qi', // Quintillion
            );
        }

        // Loop through each $divisor and find the
        // lowest amount that matches
        foreach ($divisors as $divisor => $shorthand) {
            if(is_numeric($number)){
                if (abs($number ?? 0) < ($divisor * 1000)) {
                    // We found a match!
                    break;
                }
            }
        }

        // We found our match, or there were no matches.
        // Either way, use the last defined value for $divisor.
        return number_format($number / $divisor, $precision) . $shorthand;
    }

 /**
  * @param mixed $ccaas
  * @return array
  */
    function getBanderasPorCCAA($ccaas){

        $banderasData = array('nombre' => null, 'iso' => null, 'tooltip' => null);
        $ccaas = json_decode($ccaas,true);

        if(isset($ccaas)){
            if(count($ccaas) == 1){
                $ccaa = DB::table('ccaa')->where('Nombre', $ccaas)->first();
                $banderasData['nombre'] = $ccaa->Nombre;
                $banderasData['iso'] = $ccaa->iso;
            }elseif(count($ccaas) > 1){
                foreach($ccaas as $ccaa){
                    $banderasData['tooltip'] .= $ccaa."\n";
                }
                $banderasData['nombre'] = "Para ".count($ccaas)." CC.AA.";
            }

            $banderasData['Total'] = count($ccaas);
        }

        return $banderasData;

    }
    /**
  * @param mixed $id
  * @return array
  * @throws BindingResolutionException
  * @throws NotFoundExceptionInterface
  * @throws ContainerExceptionInterface
  * @throws GuzzleException
  * @throws InvalidArgumentException
  * @throws InvalidFormatException
  */
    function getDptoElasticAyudas($id, $idorgano, $simulada = null, $idanalisis = null){

        if(isset($simulada) && $simulada == 1){
            $cache = 'dpto_ayudas_'.$id.'-'.$idorgano."_simulada";
            if(isset($idanalisis) && $idanalisis > 0){
                $cache .= "_analisis-".$idanalisis;
            }
        }else{
            $cache = 'dpto_ayudas_'.$id.'-'.$idorgano;
        }

        $ayudaresponse = Cache::remember($cache, now()->addMinutes(120), function () use($id,$idorgano,$idanalisis) {

            $elasticEnvironment = config('services.elastic_ayudas.environment');
            $urlEndpoint = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.endpoint');
            $apikey = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.api_key');

            $client = new \GuzzleHttp\Client([
                'base_uri' => $urlEndpoint,
                'headers' => [
                    'x-api-key' => $apikey
                ]
            ]);

            if($idanalisis !== null && $idanalisis > 0){
                $analisis = \App\Models\EntidadesSimuladas::find($idanalisis);
                $dataSend = array(
                    "type" => "COMPANY_ID",
                    "params" => [
                        "companyId" => $analisis->CIF,
                        "tipoEncaje" => "",
                        "organismoId" => (string)$idorgano,
                    ]
                );
            }else{
                $dataSend = array(
                    "type" => "COMPANY_ID",
                    "params" => [
                        "companyId" => $id,
                        "tipoEncaje" => "",
                        "organismoId" => (string)$idorgano,
                    ]
                );

            }

            $ayudas = [];

            try{
                $response = $client->post('publicAid/search?numItemsPage=200',[
                    //'json' => $dataSend
                    \GuzzleHttp\RequestOptions::JSON => $dataSend
                ]);

                $result = json_decode((string)$response->getBody());

                ##ONLY FOR DEV DEBUGGING
                if(\App::environment() != "prod"){
                    if(request()->get('debug') == "1"){
                        dump($result);
                    }
                }

                if($result->data){
                    $ayudas = $result->data;
                }

            }catch (\GuzzleHttp\Exception\ServerException $e){
                //dd("error");
                Log::error($e->getMessage());
                return 'ups';
            }

            if(empty($ayudas)){
                return array();
            }

            $idsAyudaInfo = [];
            $targets = collect($ayudas)->where('TipoEncaje', 'target')->pluck('IDAyuda')->toArray();
            $notargets = collect($ayudas)->where('TipoEncaje', '!=', 'target')->pluck('IDAyuda')->toArray();
            $ayudas = collect($ayudas)->where('TipoEncaje', '!=', 'proyecto')->sortByDesc('score')->groupBy('IDAyuda');

            if(session()->get('umbral_ayudas') === null){
                $settings = DB::table('settings')->where('group', 'general')->where('name','umbral_ayudas')->first();
                $umbral = (int) $settings->payload;
            }else{
                $umbral = session()->get('umbral_ayudas');
            }

            foreach ($ayudas as $key => $ayuda) {

                $firstItem =$ayuda->first();
                $idAyuda = (!empty($firstItem->IDAyuda)) ? $firstItem->IDAyuda : $firstItem->ID;

                $tipotarget = null;
                if($firstItem->TematicaObligatoria === true){
                    $tipotarget = collect($ayudas)->where('TipoEncaje', 'target')->where('IDAyuda', $firstItem->IDAyuda)->pluck('ID')->toArray();
                }

                $checkTarget = null;
                if($firstItem->TematicaObligatoria === true && ($tipotarget === null || empty($tipotarget))){
                    $checkTarget = \App\Models\Encaje::where('Tipo', 'target')->where('Ayuda_id', $firstItem->IDAyuda)->count();
                    if($checkTarget > 0){
                        $firstItem->score = -10;
                    }
                }

                if(userEntidadSelected()){
                    if($idanalisis !== null && isset($analisis)){                            
                        $recomendar = getAyudaRecomendada($firstItem, $targets, $notargets, $umbral, $analisis->valorTrl, $tipotarget, $analisis);
                    }else{
                        $recomendar = getAyudaRecomendada($firstItem, $targets, $notargets, $umbral, userEntidadSelected()->valorTrl, $tipotarget, userEntidadSelected());
                    }
                }else{
                    $recomendar = getAyudaRecomendada($firstItem, $targets, $notargets, $umbral, $tipotarget, null);
                }

                if($recomendar->valor == -2){
                    unset($ayudas[$key]);
                    continue;
                }
      
                if($recomendar->valor == -1 && $recomendar->tag == 0 && $recomendar->target == 1){
                    unset($ayudas[$key]);
                    continue;
                }else{
                    $idsAyudaInfo[$idAyuda] = [
                        'score' => $firstItem->score,
                        'organismo' => $firstItem->Organismo,
                        'Recomendar' => $recomendar,
                        'FiltroPerfilInteres' => (isset($firstItem->FiltroPerfilInteres)) ? $firstItem->FiltroPerfilInteres[0] : false,
                        'FiltroIntensidad' => (isset($firstItem->FiltroIntensidad)) ? $firstItem->FiltroIntensidad[0] : false,
                        'FiltroConsorcio' => (isset($firstItem->FiltroConsorcio)) ? $firstItem->FiltroConsorcio[0] :false,
                    ];
                }

            }

            $ayudaresponse = array();
            $checkorgans = array();
            $checkayudas = array();
            $i = 0;

            foreach($idsAyudaInfo as $key => $ayuda){
                if($ayuda['score'] >= 0){
                    $i++;

                    if(isset($checkayudas[$key]) && !empty($checkayudas[$key])){
                        $ayudaresponse[$i] = $checkayudas[$key];
                    }else{

                        $ayudaresponse[$i] = \App\Models\Ayudas::where('Id', $key)->first();

                        if($ayudaresponse[$i] === null){
                            unset($ayudaresponse[$i]);
                            continue;
                        }

                        $checkayudas[$key] = $ayudaresponse[$i];

                    }

                    /*if($ayudaresponse[$i]->Fin){
                        if(Carbon::parse($ayudaresponse[$i]->Fin) < Carbon::now()->subDays(30)){
                            unset($ayudaresponse[$i]);
                            continue;
                        }
                    }*/

                    if($ayudaresponse[$i]->esDeGenero == 1 && $ayudaresponse[$i]->textoGenero !== null && $ayuda['score'] > 0){
                        $ayuda['score'] = 0.0;
                        $ayuda['Recomendar']->valor = 0;
                        $ayuda['Recomendar']->genero = 1;
                    }

                    $ayudaresponse[$i]->score = $ayuda['score'];
                    $ayudaresponse[$i]->Recomendar = $ayuda['Recomendar'];
                    $ayudaresponse[$i]->FiltroPerfilInteres = $ayuda['FiltroPerfilInteres'];
                    $ayudaresponse[$i]->FiltroIntensidad = $ayuda['FiltroIntensidad'];
                    $ayudaresponse[$i]->FiltroConsorcio = $ayuda['FiltroConsorcio'];
                    $ayudaresponse[$i]->dpto = null;
                    if($ayuda['organismo'] == "PROYECTOS"){
                        $ayudaresponse[$i]->Organismo = $ayuda['organismo'];
                    }else{
                        if(isset($checkorgans[$ayudaresponse[$i]->Organismo]) && !empty($checkorgans[$ayudaresponse[$i]->Organismo])){
                            $ayudaresponse[$i]->dpto = $checkorgans[$ayudaresponse[$i]->Organismo];
                        }else{
                            if($ayudaresponse[$i]->Organismo){
                                $dpto = DB::table('departamentos')->where('id', $ayudaresponse[$i]->Organismo)->first();
                                if(!$dpto){
                                    $dpto = DB::table('departamentos')->where('id', $ayudaresponse[$i]->Organismo)->first();
                                }
                                if($dpto){
                                    $ayudaresponse[$i]->dpto = ($dpto->Acronimo) ? mb_strtolower(str_replace(" ","-", $dpto->Acronimo)) : mb_strtolower(str_replace(" ","-", $dpto->Nombre));
                                    $checkorgans[$ayudaresponse[$i]->Organismo] = $dpto;
                                }
                            }
                        }
                    }
                }
            }

            return $ayudaresponse;

        });

        return $ayudaresponse;

    }

    function calculoGastoIdMax($company, $naturalezas){

        $gastoIDMax = 0.0;

        if($company->gastoAnual !== null){
            $coeficiente = null;
            if($company->categoriaEmpresa == "Micro"){
                $coeficiente = 0.85;
            }
            if($company->categoriaEmpresa == "Pequeña"){
                $coeficiente = 0.4;
            }
            if($company->categoriaEmpresa == "Mediana"){
                $coeficiente = 0.2;
            }
            if($company->categoriaEmpresa == "Grande"){
                $coeficiente = 0.1;
            }
            if(in_array("6668838", json_decode($naturalezas, true))){
                $coeficiente = 0.8;
            }
            if($company->categoriaEmpresa == "Micro" || $company->categoriaEmpresa == "Pequeña" && $company->anioBalance < Carbon::now()->subYears(2)->format('Y')){
                $gastoIDMax = round((float)$company->cantidadImasD*2, 2);
            }else{
                if($coeficiente !== null){
                    $gastoIDMax = round((float)$company->gastoAnual*$coeficiente, 2);
                }
            }
        }elseif($company->cantidadImasD !== null){
            $gastoIDMax = $company->cantidadImasD;
        }

        if($gastoIDMax == 0){
            $gastoIDMax = (isset($company->gastoAnual))? $company->gastoAnual : 0;
        }

        ### HOTFIX si empresa pequeña o micro enviar el dato calculado a traves de concesiones 07/12/2023
        if($company->categoriaEmpresa == "Micro" || $company->categoriaEmpresa == "Pequeña"){
            if($gastoIDMax === null){
                $gastoIDMax = 0;
            }
            if($company->entidad->cantidadImasD > $gastoIDMax){
                $gastoIDMax = (float)$company->entidad->cantidadImasD;
            }
        }

        return $gastoIDMax;
    }

    /**
     * @param mixed $id
     * @return bool
     * @throws InvalidArgumentException
     */
    function checkPerfilIncompleto($emp){

        if($emp->idOrganismo !== null){
            return false;
        }

        $response = array('intereses' => 0, 'textos' => 0, 'partneriados' => 0);

        /*$einforma = DB::table('einforma')->where('identificativo', $id)->first();
        if(!$einforma){
            $response['cuentas'] = 1;
        }*/

        /*$textos = DB::table('TextosElastic')->where('CIF', $emp->CIF)->first();
        if(!$textos){
            $response['textos'] = 1;
        }*/

        if($emp->Intereses === null){
            $response['intereses'] = 1;
        }

        if($emp->Intereses !== null){
            if($emp->Intereses == "null"){
                $response['intereses'] = 1;
            }
            if(is_array(json_decode($emp->Intereses)) && empty(array_filter(json_decode($emp->Intereses, true)))){
                $response['intereses'] = 1;
            }
            if(is_array(json_decode($emp->Intereses)) && count(json_decode($emp->Intereses)) == 2){
                if(in_array('Cooperación', json_decode($emp->Intereses)) && in_array('Subcontratación', json_decode($emp->Intereses))){
                    $response['intereses'] = 1;
                }
            }
            if(count(json_decode($emp->Intereses)) == 1 && in_array('Consultoría', json_decode($emp->Intereses))){
                $response['intereses'] = 1;
            }
        }

        if($emp->perfilFinanciero !== null){
            if($emp->perfilFinanciero->tags_tecnologias === null || empty(json_decode($emp->perfilFinanciero->tags_tecnologias))){
                $response['textos'] = 1;
            }
        }else{
            if($emp->TextosLineasTec === null || $emp->TextosLineasTec == "null" || empty(array_filter(json_decode($emp->TextosLineasTec, true)))){
                $response['textos'] = 1;
            }
        }

        $textos = json_decode($emp->TextosLineasTec ?? '', true);
        $total = 0;
        if($textos){
            foreach($textos as $texto){
                $linea = explode(",", $texto ?? '');
                if($linea[0] != "" && $linea[0] !== null){
                    $total += count(array_filter($linea));
                }
            }
        }

        $consorcios = json_decode($emp->Intereses, true);

        if($consorcios){
            if(!in_array('Cooperación', $consorcios) && !in_array('Subcontratación', $consorcios)){
                $response['partneriados'] = 1;
            }
        }else{
            $response['partneriados'] = 1;
        }

        /*if($einforma->anioBalance === null){
            $response['anio'] = 1;
        }else{
            $diff = Carbon::createFromDate($einforma->anioBalance, 1, 1)->diff(Carbon::now());
            if($diff->y >= 2){
                $response['anio'] = 1;
            }
        }*/

        if($response['intereses'] == 1 || $response['textos'] == 1 || $response['partneriados'] == 1){
            return $response;
        }

        if($emp->perfilCompleto == 0){

            if(isSuperAdmin()){
                try{
                    DB::table('entidades')->where('id', $emp->id)->update([
                        'perfilCompleto' => 1,
                        'updated_at' => Carbon::now()
                    ]);

                }catch(Exception $e){
                    Log::error($e->getMessage());
                }
            }else{
                try{
                    \App\Models\Entidad::where('id', $emp->id)->update([
                        'perfilCompleto' => 1,
                        'efectoWow->perfilcompleto' => 1,
                        'updated_at' => Carbon::now()
                    ]);

                }catch(Exception $e){
                    Log::error($e->getMessage());
                }
            }

        }

        return true;

    }

    function checkEfectoWow($company, $perfil){

        $efectoWow = json_decode($company->efectoWow, true);

        if($efectoWow['perfilentrayuda'] == 0 && !is_array($perfil)){
            try{
                DB::table('entidades')->where('id', $company->id)->update([
                    'efectoWow->perfilentrayuda' => 1
                ]);
            }catch(Exception $e){
                log::error($e->getMessage());
                return;
            }
        }

        return;

    }

    function sendPerfilCompletoMail($emp){

        if(checkPerfilIncompleto($emp)){

            $mail = new \App\Mail\PerfilCompleto(Auth::user(), $emp);
            $entidad = \App\Models\Entidad::find($emp->id);
            $equipo = $entidad->users;
            foreach($equipo as $user){
                if($user->role == 'tecnico'){
                    continue;
                }
                Mail::to($user->email)->queue($mail);
            }

        }

        return true;
    }

    /**
     * @param mixed $search
     * @param mixed $request
     * @param mixed $page
     * @return mixed
     * @throws BindingResolutionException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws GuzzleException
     * @throws RuntimeException
     */
    function getElasticCompanies($search, $request, $page, $type = null, $items = 10){

        $elasticEnvironment = config('services.elastic_ayudas.environment');
        $urlEndpoint = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.endpoint');
        $apikey = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.api_key');

        $ccaa = array();

        if($request->get('comunidad')){
            $array = (is_array($request->get('comunidad'))) ? $request->get('comunidad') : explode(",", $request->get('comunidad'));
            foreach($array as $comunidadId){
                $provincias = \App\Models\Provincias::where('id_ccaa', $comunidadId)->get();
                foreach($provincias as $provincia){
                    $ccaa[] = mb_strtolower($provincia->provincia);
                }
                $comunidad = \App\Models\Ccaa::where('id', $comunidadId)->first();
                if($comunidad){
                    $ccaa[] = mb_strtolower($comunidad->Nombre);
                }
            }
        }

        $category = [];
        if($request->get('categoria')){            
            $category = (is_array($request->get('categoria'))) ? $request->get('categoria') : explode(",",$request->get('categoria'));
        }

        if($type == "empresas" || $type == "centros" && $request->get('isfilter') == 1){
            $numPatentes     = ($request->get('patentes')) ? (int)$request->get('patentes') :  0;
            $numAyudas       = ($request->get('ayudas')) ? (int)$request->get('ayudas') :  0;
            $lastDateFin     = ($request->get('ultimaayuda')) ? Carbon::now()->subMonths($request->get('ultimaayuda'))->format('d-m-Y') : '01-01-1800';
            $trl             = ($request->get('trlmax')) ? (int)$request->get('trlmax') :  10;
            $fechaSelloPyme  = ($request->get('sellopyme')) ? $request->get('sellopyme') :  '';
            $numeroCNAE      = ($request->get('codigocnae')) ? $request->get('codigocnae') : "";
            $descripcionCNAE = ($request->get('descripcioncnae')) ? $request->get('descripcioncnae') :  '';
            $xpCooperacion = ($request->get('cooperacion')) ? str_replace(",", " o ",$request->get('cooperacion')) :  '';
            $minEmpleados = ($request->get('empleados')) ? (int)$request->get('empleados')-50 :  null;
            $maxEmpleados = ($request->get('empleados')) ? (int)$request->get('empleados') :  null;
            $country = ($request->get('pais')) ? $request->get('pais') :  '';
            if($request->get('lider') == "Si") {
                $xpLider = 1;
            }elseif($request->get('lider') == "No"){
                $xpLider = 0;
            }
            $fechaConstitucion = ($request->get('fecha')) ? Carbon::createFromFormat('m/Y', $request->get('fecha'))->format('01-m-Y') : '01-01-1800';
        }
        
        $userSearch = array();

        $orderByPr = false;
        if($request->get('orderby') !== null){
            $orderByPr = ($request->get('orderby') == "key") ? true : false;
        }

        $data = array();

        if($search !== null && !empty($search)){

            $search = str_replace("&","",$search);

            $search = mb_convert_encoding($search, 'UTF-8', 'UTF-8');

            if (preg_match_all('/"([^"]+)"/', $search, $filter)) {

                foreach($filter as $m){
                    if(!in_array($m, $data)){
                        $data = preg_replace("/[^a-zA-Z0-9À-ÿ\-\s.+]/", "", $m);
                    }

                    $arraysearch = explode(" ", preg_replace("/[^a-zA-Z0-9À-ÿ\-\s.+]/", "", str_replace($m, "", $search)));
                    foreach(array_filter($arraysearch) as $string){
                        $data[] = $string;
                    }
                }

            }else{
                $userSearch = explode(" ", preg_replace("/[^a-zA-Z0-9À-ÿ\-\s.+]/", "", $search));
            }

            if(!empty($data)){
                foreach($data as $text){
                    $userSearch[] = $text;
                }
            }
        }else{
            $userSearch = [];
        }

        //dump($request->all());

        $featured = app(GeneralSettings::class)->master_featured;

        if($type === null){
            $dataSend = array(
                "type" => "GLOBAL",
                "params" => [
                    'textSearch' => $userSearch,
                    'comunidadesAutonomas' => $ccaa,
                    'categories' => $category,
                    'naturaleza' => array("6668837","6668838","6668839"),
                    'orderByPagerank' => $orderByPr,
                    'orderByFeatured' => ($featured == "1") ? true : false
                ]
            );
        }else if($type == "empresas"){
            if($request->get('isfilter') == 1){
                $dataSend = array(
                    "type" => "GLOBAL",
                    "params" => [
                        'textSearch' => $userSearch,
                        'comunidadesAutonomas' => $ccaa,
                        'categories' => $category,
                        'naturaleza' => array("6668837","6668839"),
                        'orderByPagerank' => $orderByPr,
                        'numPatentes' => $numPatentes,
                        'numAyudas' => $numAyudas,
                        'lastDateFin' => $lastDateFin,
                        'trl' => $trl,
                        'estadoSelloPyme' => ucfirst($fechaSelloPyme),
                        'cnae' => $numeroCNAE,
                        'descriptionCnae' => mb_strtolower($descripcionCNAE),
                        //'fechaConstitucion' => $fechaConstitucion,
                        'country' => $country,
                        'minimumEstablishmentDate' => $fechaConstitucion
                    ]
                );

                if($minEmpleados !== null && $maxEmpleados !== null){
                    $dataSend['params']['numEmpleadosMin'] = ($maxEmpleados == 201) ? 201 : $minEmpleados;
                    $dataSend['params']['numEmpleadosMax'] = ($maxEmpleados == 201) ? 99999 : $maxEmpleados;
                }

            }else{
                $dataSend = array(
                    "type" => "GLOBAL",
                    "params" => [
                        'textSearch' => $userSearch,
                        'comunidadesAutonomas' => $ccaa,
                        'categories' => $category,
                        'naturaleza' => array("6668837","6668839"),
                        'orderByPagerank' => $orderByPr,
                        'orderByFeatured' => ($featured == "1") ? true : false
                        //'flagsEntidad' => false
                    ]
                );
            }
            if($request->get('isfilterfinanciero') == 1){
                if($request->get('patrimonionetomin') != "" && $request->get('patrimonionetomax') != ""){                
                    $dataSend['params']['patrimonioNetoMin'] = (int)$request->get('patrimonionetomin');
                    $dataSend['params']['patrimonioNetoMax'] = (int)$request->get('patrimonionetomax');                    
                }
                if($request->get('activocorrientemin') != "" && $request->get('activocorrientemax') != ""){
                    $dataSend['params']['activoCorrienteMin'] = (int)$request->get('activocorrientemin');
                    $dataSend['params']['activoCorrienteMax'] = (int)$request->get('activocorrientemax');
                }
                if($request->get('activofijomin') != "" && $request->get('activofijomax') != ""){
                    $dataSend['params']['activoFijoMin'] = (int)$request->get('activofijomin');
                    $dataSend['params']['activoFijoMax'] = (int)$request->get('activofijomax');
                }
                if($request->get('beneficioanualmin') != "" && $request->get('beneficioanualmax') != ""){
                    $dataSend['params']['beneficioAnualMin'] = (int)$request->get('beneficioanualmin');
                    $dataSend['params']['beneficioAnualMax'] = (int)$request->get('beneficioanualmax');
                }
                if($request->get('circulantemin') != "" && $request->get('circulantemax') != ""){
                    $dataSend['params']['circulanteMin'] = (int)$request->get('circulantemin');
                    $dataSend['params']['circulanteMax'] = (int)$request->get('circulantemax');
                }
                if($request->get('gastoanualmin') != "" && $request->get('gastoanualmax') != ""){
                    $dataSend['params']['gastosAnualMin'] = (int)$request->get('gastoanualmin');
                    $dataSend['params']['gastosAnualMax'] = (int)$request->get('gastoanualmax');
                }                
                if($request->get('ingresosmin') != "" && $request->get('ingresosmax') != ""){
                    $dataSend['params']['ingresosMin'] = (int)$request->get('ingresosmin');
                    $dataSend['params']['ingresosMax'] = (int)$request->get('ingresosmax');
                }
                if($request->get('margenendeudamientomin') != "" && $request->get('margenendeudamientomax') != ""){
                    $dataSend['params']['margenEndeudamientoMin'] = (int)$request->get('margenendeudamientomin');
                    $dataSend['params']['margenEndeudamientoMax'] = (int)$request->get('margenendeudamientomax');
                }
                if($request->get('pasivocorrientemin') != "" && $request->get('pasivocorrientemax') != ""){
                    $dataSend['params']['pasivoCorrienteMin'] = (int)$request->get('pasivocorrientemin');
                    $dataSend['params']['pasivoCorrienteMax'] = (int)$request->get('pasivocorrientemax');
                }
                if($request->get('pasivonocorrientemin') != "" && $request->get('pasivonocorrientemax') != ""){
                    $dataSend['params']['pasivoNoCorrienteMin'] = (int)$request->get('pasivonocorrientemin');
                    $dataSend['params']['pasivoNoCorrienteMax'] = (int)$request->get('pasivonocorrientemax');
                }
                if($request->get('trabajosinmovilizadosmin') != "" && $request->get('trabajosinmovilizadosmax') != ""){
                    $dataSend['params']['trabajosInmovilizadosMin'] = (int)$request->get('trabajosinmovilizadosmin');
                    $dataSend['params']['trabajosInmovilizadosMax'] = (int)$request->get('trabajosinmovilizadosmax');
                }
                if($request->get('gastoidmin') != "" && $request->get('gastoidmax') != ""){
                    $dataSend['params']['userGastoIdMin'] = (int)$request->get('gastoidmin');
                    $dataSend['params']['userGastoIdMax'] = (int)$request->get('gastoidmax');
                }
            }
            if($request->get('isfilterconcesiones') == 1){                
                if($request->get('tipo_concesion_1') !== null && $request->get('tipo_financiacion_concedida') !== null){
                    if($request->get('tipo_concesion_1') == 1){
                        $dataSend['params']['tipoFinanciacionConcedido'] = (is_array($request->get('tipo_financiacion_concedida'))) ? $request->get('tipo_financiacion_concedida') : explode(",",$request->get('tipo_financiacion_concedida'));
                    }
                    if($request->get('tipo_concesion_1') == 0){
                        $dataSend['params']['notTipoFinanciacionConcedido'] = (is_array($request->get('tipo_financiacion_concedida'))) ? $request->get('tipo_financiacion_concedida') : explode(",",$request->get('tipo_financiacion_concedida'));
                    }
                }
                if($request->get('tipo_concesion_2') !== null && $request->get('tipo_organismo_concedido') !== null){
                    if($request->get('tipo_concesion_2') == 1){
                        $dataSend['params']['idOrgDeptConcedido'] = (is_array($request->get('tipo_organismo_concedido'))) ? $request->get('tipo_organismo_concedido') : explode(",",$request->get('tipo_organismo_concedido'));
                    }
                    if($request->get('tipo_concesion_2') == 0){
                        $dataSend['params']['notIdOrgDeptConcedido'] = (is_array($request->get('tipo_organismo_concedido'))) ? $request->get('tipo_organismo_concedido') : explode(",",$request->get('tipo_organismo_concedido'));
                    }
                }
                if($request->get('tipo_concesion_3') !== null && $request->get('tipo_interes_concedido') !== null){
                    if($request->get('tipo_concesion_3') == 1){
                        $dataSend['params']['idInteresesConcedido'] = (is_array($request->get('tipo_interes_concedido'))) ? $request->get('tipo_interes_concedido') : explode(",",$request->get('tipo_interes_concedido'));
                    }
                    if($request->get('tipo_concesion_3') == 0){
                        $dataSend['params']['notIdInteresesConcedido'] = (is_array($request->get('tipo_interes_concedido'))) ? $request->get('tipo_interes_concedido') : explode(",",$request->get('tipo_interes_concedido'));
                    }
                }

            }
            
        }else if($type == "centros"){

            $naturalezas = array("6668838","6668840");

            if($request->get('naturaleza') !== null && $request->get('naturaleza') != ""){
                if($request->get('naturaleza') == "centros"){
                    $naturalezas =  array("6668838");
                }
                if($request->get('naturaleza') == "universidades"){
                    $naturalezas =  array("6668840");
                }
            }

            if($request->get('isfilter') == 1){
                $dataSend = array(
                    "type" => "GLOBAL",
                    "params" => [
                        'textSearch' => $userSearch,
                        'comunidadesAutonomas' => $ccaa,
                        'categories' => $category,
                        'naturaleza' => $naturalezas,
                        'orderByPagerank' => $orderByPr,
                        'orderByFeatured' => ($featured == "1") ? true : false,
                        'numPatentes' => $numPatentes,
                        'numAyudas' => $numAyudas,
                        'lastDateFin' => $lastDateFin,
                        'trl' => $trl,
                        'estadoSelloPyme' => ucfirst($fechaSelloPyme),
                        'cnae' => $numeroCNAE,
                        'descriptionCnae' => mb_strtolower($descripcionCNAE),
                        'fechaConstitucion' => $fechaConstitucion,
                    ]
                );

                if($minEmpleados !== null && $maxEmpleados !== null){
                    $dataSend['params']['numEmpleadosMin'] = ($maxEmpleados == 201) ? 201 : $minEmpleados;
                    $dataSend['params']['numEmpleadosMax'] = ($maxEmpleados == 201) ? 99999 : $maxEmpleados;
                }
            }else{
                $dataSend = array(
                    "type" => "GLOBAL",
                    "params" => [
                        'textSearch' => $userSearch,
                        'comunidadesAutonomas' => $ccaa,
                        'categories' => $category,
                        'naturaleza' => $naturalezas,
                        'orderByPagerank' => $orderByPr,
                        'orderByFeatured' => ($featured == "1") ? true : false
                    ]
                );
            }
        }

        if($request->get('isfilter') == 1){
            if(isset($xpCooperacion) && !empty($xpCooperacion)){
                if(is_array($xpCooperacion)){
                    $dataSend["params"]['XPCooperacion'] = str_replace(",", " o ", implode(",", $xpCooperacion));
                }else{
                    $dataSend["params"]['XPCooperacion'] = $xpCooperacion;
                }

            }
            if(isset($xpLider) && is_int($xpLider)){
                $dataSend["params"]['XPLider'] = ($xpLider == 1) ? true : false;
            }
        }

        $client = new \GuzzleHttp\Client([
            'base_uri' => $urlEndpoint,
            'headers' => [
                'x-api-key' => $apikey
            ]

        ]);

        if($request->get('soloclientes') !== null && (int) $request->get('soloclientes') == 1){
            $items = 1000;
        }

        try{
            $response = $client->post('company/search?numItemsPage='.$items.'&numPage='.$page, [
                \GuzzleHttp\RequestOptions::JSON => $dataSend
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return 'ups';
        }

        $body = $response->getBody();
        $data = json_decode($body->getContents());

        ##ONLY FOR DEV DEBUGGING
        if(\App::environment() != "prod"){
            if(request()->get('debug') == "1"){
                dump($data);
            }
        }

        foreach($data->data as $key => $d){
            if($request->get('soloclientes') !== null && (int) $request->get('soloclientes') == 1){
                if(!in_array(userEntidadSelected()->id, $d->FlagsEntidad)){
                    unset($data->data[$key]);
                    continue;
                }
            }
            $d->format_cantidadimasd = number_shorten($d->GastoIDI,0);
        }

        return $data;

    }

    /**
     * @param mixed $search 
     * @param mixed $request 
     * @param mixed $page 
     * @param mixed $type 
     * @param int $items 
     * @return void 
     */
    function getElasticCompaniesAggregated($search, $request, $page, $type = "empresas", $items = 10){

        $elasticEnvironment = config('services.elastic_ayudas.environment');
        $urlEndpoint = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.endpoint');
        $apikey = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.api_key');

        $ccaa = array();

        if($request->get('comunidad')){
            $array = (is_array($request->get('comunidad'))) ? $request->get('comunidad') : explode(",", $request->get('comunidad'));
            foreach($array as $comunidadId){
                $provincias = DB::table('provincias')->where('id_ccaa', $comunidadId)->get();
                foreach($provincias as $provincia){
                    $ccaa[] = mb_strtolower($provincia->provincia);
                }
                $comunidad = DB::table('ccaa')->where('id', $comunidadId)->first();
                if($comunidad){
                    $ccaa[] = mb_strtolower($comunidad->Nombre);
                }
            }
        }

        $category = [];
        if($request->get('categoria')){            
            $category = (is_array($request->get('categoria'))) ? $request->get('categoria') : explode(",",$request->get('categoria'));
        }

        if($type == "empresas" && $request->get('isfilter') == 1){
            $numPatentes     = ($request->get('patentes')) ? (int)$request->get('patentes') :  0;
            $numAyudas       = ($request->get('ayudas')) ? (int)$request->get('ayudas') :  0;
            $lastDateFin     = ($request->get('ultimaayuda')) ? Carbon::now()->subMonths($request->get('ultimaayuda'))->format('d-m-Y') : '01-01-1800';
            $trl             = ($request->get('trlmax')) ? (int)$request->get('trlmax') :  10;
            $fechaSelloPyme  = ($request->get('sellopyme')) ? $request->get('sellopyme') :  '';
            $numeroCNAE      = ($request->get('codigocnae')) ? $request->get('codigocnae') : "";
            $descripcionCNAE = ($request->get('descripcioncnae')) ? $request->get('descripcioncnae') :  '';
            $xpCooperacion = ($request->get('cooperacion')) ? str_replace(",", " o ",$request->get('cooperacion')) :  '';
            $minEmpleados = ($request->get('empleados')) ? (int)$request->get('empleados')-50 :  null;
            $maxEmpleados = ($request->get('empleados')) ? (int)$request->get('empleados') :  null;
            $country = ($request->get('pais')) ? $request->get('pais') :  '';
            if($request->get('lider') == "Si") {
                $xpLider = 1;
            }elseif($request->get('lider') == "No"){
                $xpLider = 0;
            }
            $fechaConstitucion = ($request->get('fecha')) ? Carbon::createFromFormat('m/Y', $request->get('fecha'))->format('01-m-Y') : '01-01-1800';
        }
        
        $userSearch = array();

        $orderByPr = false;
        if($request->get('orderby') !== null){
            $orderByPr = ($request->get('orderby') == "key") ? true : false;
        }

        $data = array();

        if($search !== null && !empty($search)){

            $search = str_replace("&","",$search);

            $search = mb_convert_encoding($search, 'UTF-8', 'UTF-8');

            if (preg_match_all('/"([^"]+)"/', $search, $filter)) {

                foreach($filter as $m){
                    if(!in_array($m, $data)){
                        $data = preg_replace("/[^a-zA-Z0-9À-ÿ\-\s.+]/", "", $m);
                    }

                    $arraysearch = explode(" ", preg_replace("/[^a-zA-Z0-9À-ÿ\-\s.+]/", "", str_replace($m, "", $search)));
                    foreach(array_filter($arraysearch) as $string){
                        $data[] = $string;
                    }
                }

            }else{
                $userSearch = explode(" ", preg_replace("/[^a-zA-Z0-9À-ÿ\-\s.+]/", "", $search));
            }

            if(!empty($data)){
                foreach($data as $text){
                    $userSearch[] = $text;
                }
            }
        }else{
            $userSearch = [];
        }

        //dump($request->all());

        $featured = app(GeneralSettings::class)->master_featured;

        if($request->get('isfilter') == 1){
            $dataSend = array(
                "type" => "GLOBAL",
                "params" => [
                    'textSearch' => $userSearch,
                    'comunidadesAutonomas' => $ccaa,
                    'categories' => $category,
                    'naturaleza' => array("6668837","6668839"),
                    'orderByPagerank' => $orderByPr,
                    'filterByLastUpdate' => true,
                    'sortByLastUpdate' => true,
                    'numPatentes' => $numPatentes,
                    'numAyudas' => $numAyudas,
                    'lastDateFin' => $lastDateFin,
                    'trl' => $trl,
                    'estadoSelloPyme' => ucfirst($fechaSelloPyme),
                    'cnae' => $numeroCNAE,
                    'descriptionCnae' => mb_strtolower($descripcionCNAE),
                    'fechaConstitucion' => $fechaConstitucion,
                    //'country' => $country,
                ]
            );

            if($minEmpleados !== null && $maxEmpleados !== null){
                $dataSend['params']['numEmpleadosMin'] = ($maxEmpleados == 201) ? 201 : $minEmpleados;
                $dataSend['params']['numEmpleadosMax'] = ($maxEmpleados == 201) ? 99999 : $maxEmpleados;
            }

        }else{
            $dataSend = array(
                "type" => "GLOBAL",
                "params" => [
                    'textSearch' => $userSearch,
                    'comunidadesAutonomas' => $ccaa,
                    'categories' => $category,
                    'naturaleza' => array("6668837","6668839"),
                    'orderByPagerank' => $orderByPr,
                    'orderByFeatured' => ($featured == "1") ? true : false,
                    'filterByLastUpdate' => true,
                    'sortByLastUpdate' => true,
                    //'flagsEntidad' => false
                ]
            );
        }

        if($request->get('isfilterfinanciero') == 1){
            if($request->get('patrimonionetomin') != "" && $request->get('patrimonionetomax') != ""){                
                $dataSend['params']['patrimonioNetoMin'] = (int)$request->get('patrimonionetomin');
                $dataSend['params']['patrimonioNetoMax'] = (int)$request->get('patrimonionetomax');                    
            }
            if($request->get('activocorrientemin') != "" && $request->get('activocorrientemax') != ""){
                $dataSend['params']['activoCorrienteMin'] = (int)$request->get('activocorrientemin');
                $dataSend['params']['activoCorrienteMax'] = (int)$request->get('activocorrientemax');
            }
            if($request->get('activofijomin') != "" && $request->get('activofijomax') != ""){
                $dataSend['params']['activoFijoMin'] = (int)$request->get('activofijomin');
                $dataSend['params']['activoFijoMax'] = (int)$request->get('activofijomax');
            }
            if($request->get('beneficioanualmin') != "" && $request->get('beneficioanualmax') != ""){
                $dataSend['params']['beneficioAnualMin'] = (int)$request->get('beneficioanualmin');
                $dataSend['params']['beneficioAnualMax'] = (int)$request->get('beneficioanualmax');
            }
            if($request->get('circulantemin') != "" && $request->get('circulantemax') != ""){
                $dataSend['params']['circulanteMin'] = (int)$request->get('circulantemin');
                $dataSend['params']['circulanteMax'] = (int)$request->get('circulantemax');
            }
            if($request->get('gastoanualmin') != "" && $request->get('gastoanualmax') != ""){
                $dataSend['params']['gastosAnualMin'] = (int)$request->get('gastoanualmin');
                $dataSend['params']['gastosAnualMax'] = (int)$request->get('gastoanualmax');
            }                
            if($request->get('ingresosmin') != "" && $request->get('ingresosmax') != ""){
                $dataSend['params']['ingresosMin'] = (int)$request->get('ingresosmin');
                $dataSend['params']['ingresosMax'] = (int)$request->get('ingresosmax');
            }
            if($request->get('margenendeudamientomin') != "" && $request->get('margenendeudamientomax') != ""){
                $dataSend['params']['margenEndeudamientoMin'] = (int)$request->get('margenendeudamientomin');
                $dataSend['params']['margenEndeudamientoMax'] = (int)$request->get('margenendeudamientomax');
            }
            if($request->get('pasivocorrientemin') != "" && $request->get('pasivocorrientemax') != ""){
                $dataSend['params']['pasivoCorrienteMin'] = (int)$request->get('pasivocorrientemin');
                $dataSend['params']['pasivoCorrienteMax'] = (int)$request->get('pasivocorrientemax');
            }
            if($request->get('pasivonocorrientemin') != "" && $request->get('pasivonocorrientemax') != ""){
                $dataSend['params']['pasivoNoCorrienteMin'] = (int)$request->get('pasivonocorrientemin');
                $dataSend['params']['pasivoNoCorrienteMax'] = (int)$request->get('pasivonocorrientemax');
            }
            if($request->get('trabajosinmovilizadosmin') != "" && $request->get('trabajosinmovilizadosmax') != ""){
                $dataSend['params']['trabajosInmovilizadosMin'] = (int)$request->get('trabajosinmovilizadosmin');
                $dataSend['params']['trabajosInmovilizadosMax'] = (int)$request->get('trabajosinmovilizadosmax');
            }
            if($request->get('gastoidmin') != "" && $request->get('gastoidmax') != ""){
                $dataSend['params']['userGastoIdMin'] = (int)$request->get('gastoidmin');
                $dataSend['params']['userGastoIdMax'] = (int)$request->get('gastoidmax');
            }
        }
        if($request->get('isfilterconcesiones') == 1){                
            if($request->get('tipo_concesion_1') !== null && $request->get('tipo_financiacion_concedida') !== null){
                if($request->get('tipo_concesion_1') == 1){
                    $dataSend['params']['tipoFinanciacionConcedido'] = (is_array($request->get('tipo_financiacion_concedida'))) ? $request->get('tipo_financiacion_concedida') : explode(",",$request->get('tipo_financiacion_concedida'));
                }
                if($request->get('tipo_concesion_1') == 0){
                    $dataSend['params']['notTipoFinanciacionConcedido'] = (is_array($request->get('tipo_financiacion_concedida'))) ? $request->get('tipo_financiacion_concedida') : explode(",",$request->get('tipo_financiacion_concedida'));
                }
            }
            if($request->get('tipo_concesion_2') !== null && $request->get('tipo_organismo_concedido') !== null){
                if($request->get('tipo_concesion_2') == 1){
                    $dataSend['params']['idOrgDeptConcedido'] = (is_array($request->get('tipo_organismo_concedido'))) ? $request->get('tipo_organismo_concedido') : explode(",",$request->get('tipo_organismo_concedido'));
                }
                if($request->get('tipo_concesion_2') == 0){
                    $dataSend['params']['notIdOrgDeptConcedido'] = (is_array($request->get('tipo_organismo_concedido'))) ? $request->get('tipo_organismo_concedido') : explode(",",$request->get('tipo_organismo_concedido'));
                }
            }
            if($request->get('tipo_concesion_3') !== null && $request->get('tipo_interes_concedido') !== null){
                if($request->get('tipo_concesion_3') == 1){
                    $dataSend['params']['idInteresesConcedido'] = (is_array($request->get('tipo_interes_concedido'))) ? $request->get('tipo_interes_concedido') : explode(",",$request->get('tipo_interes_concedido'));
                }
                if($request->get('tipo_concesion_3') == 0){
                    $dataSend['params']['notIdInteresesConcedido'] = (is_array($request->get('tipo_interes_concedido'))) ? $request->get('tipo_interes_concedido') : explode(",",$request->get('tipo_interes_concedido'));
                }
            }

        }
                    
        if($request->get('isfilter') == 1){
            if(isset($xpCooperacion) && !empty($xpCooperacion)){
                if(is_array($xpCooperacion)){
                    $dataSend["params"]['XPCooperacion'] = str_replace(",", " o ", implode(",", $xpCooperacion));
                }else{
                    $dataSend["params"]['XPCooperacion'] = $xpCooperacion;
                }

            }
            if(isset($xpLider) && is_int($xpLider)){
                $dataSend["params"]['XPLider'] = ($xpLider == 1) ? true : false;
            }
        }

        $client = new \GuzzleHttp\Client([
            'base_uri' => $urlEndpoint,
            'headers' => [
                'x-api-key' => $apikey
            ]
        ]);

        try{
            $response = $client->post('company/search?numItemsPage='.$items.'&numPage='.$page, [
                \GuzzleHttp\RequestOptions::JSON => $dataSend
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return 'ups';
        }


        $body = $response->getBody();
        $data = json_decode($body->getContents());

        ##ONLY FOR DEV DEBUGGING
        if(\App::environment() != "prod"){
            if(request()->get('debug') == "1"){
                dump($data);
            }
        }

        foreach($data->data as $key => $d){
            $d->format_cantidadimasd = number_shorten($d->GastoIDI,0);
        }

        return $data;

    }

    /**
     * @param mixed $search
     * @param mixed $request
     * @param mixed $page
     * @return mixed
     * @throws BindingResolutionException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws GuzzleException
     * @throws RuntimeException
     */
    function getElasticProyectos($search, $request, $page, $type = null, $items = 10){

        $elasticEnvironment = config('services.elastic_ayudas.environment');
        $urlEndpoint = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.endpoint');
        $apikey = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.api_key');

        $client = new \GuzzleHttp\Client([
            'base_uri' => $urlEndpoint,
            'headers' => [
                'x-api-key' => $apikey
            ]

        ]);

        $data = array();

        $search = str_replace("&","",$search);

        $search = mb_convert_encoding($search, 'UTF-8', 'UTF-8');

        if (preg_match_all('/"([^"]+)"/', $search, $filter)) {

            foreach($filter as $m){
                if(!in_array($m, $data)){
                    $data = preg_replace("/[^a-zA-Z0-9À-ÿ\-\s.+]/", "", $m);
                }

                $arraysearch = explode(" ", preg_replace("/[^a-zA-Z0-9À-ÿ\-\s.+]/", "", str_replace($m, "", $search)));
                foreach(array_filter($arraysearch) as $string){
                    $data[] = $string;
                }
            }

        }else{
            $userSearch = explode(" ", preg_replace("/[^a-zA-Z0-9À-ÿ\-\s.+]/", "", $search));
        }

        if(!empty($data)){
            foreach($data as $text){
                $userSearch[] = $text;
            }
        }

        $dataSend = array(
            "type" => "GLOBAL",
            "params" => [
                "textSearch" => $userSearch,
                "Estado" => ($request->get('estado')) ? $request->get('estado') : "",
                "IDOrganismo" => ($request->get('organismo')) ? $request->get('organismo') : ""
            ]
        );

        try{
            $response = $client->post('project/search?numItemsPage='.$items.'&numPage='.$page, [
                \GuzzleHttp\RequestOptions::JSON => $dataSend
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return 'ups';
        }

        $body = $response->getBody();
        $data = json_decode($body->getContents());

        ##ONLY FOR DEV DEBUGGING
        if(\App::environment() != "prod"){
            if(request()->get('debug') == "1"){
                dump($data);
            }
        }

        return $data;
    }

    /**
     * @param mixed $ayuda
     * @param mixed $empresa
     * @return object
     * @throws InvalidFormatException
     * @throws InvalidArgumentException
     */
    function getNoEncaja($ayuda, $empresa, $einforma, $score, $esproyecto = null, $idanalisis = null){

        $noencajes = (object) array('Ambito' => 0, 'FechaMax' => 0, 'Categoria' => 0, 'Perfiles' => 1, 'Cnaes' => 0, 'Intensidad' => 0, 'Presentacion' => 0, 'Tematica' => 0);

        if(userEntidadSelected()){

            if($esproyecto !== null){
                $empresa = \App\Models\EntidadesSimuladas::find($idanalisis);
            }

            #NO ENCAJA AMBITO
            if(isset($einforma)){
                if($ayuda->Ambito == "Comunidad Autónoma" && $empresa->Sedes !== null){
                    $checkcentral = 0;
                    $checksedes = 0;
                    $ccass = json_decode($ayuda->Ccaas, true);                        
                    $companySedes = json_decode($empresa->Sedes);

                    if(!in_array($companySedes->central, $ccass)){
                        $checkcentral++;
                    }
                   
                    if(!empty($companySedes->otrassedes)){
                        foreach($companySedes->otrassedes as $sede){
                            if(!in_array($sede, $ccass)){
                                $checksedes++;
                            }
                        }
                    }
                    
                    if($checkcentral == 1 && empty($companySedes->otrassedes)){
                        $noencajes->Ambito = 1;
                    }
                    if($checkcentral == 1 && is_array($companySedes->otrassedes) && $companySedes->otrassedes !== null 
                    && $checksedes == count($companySedes->otrassedes)){
                        $noencajes->Ambito = 1;
                    }
                }elseif($ayuda->Ambito == "Comunidad Autónoma" && $einforma->Ccaa !== null){                    
                    $ccass = json_decode($ayuda->Ccaas, true);   
                    if(!in_array($einforma->Ccaa, $ccass)){
                        $noencajes->Ambito = 1;
                    }                    
                }
                
            }
            if($ayuda->Ambito == "Nacional"){
                $noencajes->Ambito = 0;
            }            

            if(isset($einforma->fechaConstitucion) && $einforma->fechaConstitucion !== null && isset($score) && (is_array($score) && $score['score'] < 0)){
                #NO ENCAJA FECHAMAXCONSTITUCION
                if($ayuda->FechaMaxConstitucion !== null){
                    if(Carbon::parse($ayuda->FechaMaxConstitucion) < Carbon::parse($einforma->fechaConstitucion)){
                        $noencajes->FechaMax = 1;
                    }
                }
                if($ayuda->FechaMinConstitucion !== null){
                    if(Carbon::parse($ayuda->FechaMinConstitucion) > Carbon::parse($einforma->fechaConstitucion)){
                        $noencajes->FechaMax = 1;
                    }
                }
                if($ayuda->MesesMin !== null){          
                    if(Carbon::now()->subMonths($ayuda->MesesMin) > Carbon::parse($einforma->fechaConstitucion)){
                        $noencajes->FechaMax = 1;
                    }
                }
                if($ayuda->Meses !== null){
                    if(Carbon::now()->subMonths($ayuda->Meses) < Carbon::parse($einforma->fechaConstitucion)){
                        $noencajes->FechaMax = 1;
                    }
                }
            }elseif(isset($einforma->fechaConstitucion) && $einforma->fechaConstitucion !== null && isset($score) && (!is_array($score) && $score < 0)){
                #NO ENCAJA FECHAMAXCONSTITUCION
                if($ayuda->FechaMaxConstitucion !== null){
                    if(Carbon::parse($ayuda->FechaMaxConstitucion) < Carbon::parse($einforma->fechaConstitucion)){
                        $noencajes->FechaMax = 1;
                    }
                }
                if($ayuda->FechaMinConstitucion !== null){
                    if(Carbon::parse($ayuda->FechaMinConstitucion) > Carbon::parse($einforma->fechaConstitucion)){
                        $noencajes->FechaMax = 1;
                    }
                }
                if($ayuda->MesesMin !== null){          
                    if(Carbon::now()->subMonths($ayuda->MesesMin) > Carbon::parse($einforma->fechaConstitucion)){
                        $noencajes->FechaMax = 1;
                    }
                }
                if($ayuda->Meses !== null){
                    if(Carbon::now()->subMonths($ayuda->Meses) < Carbon::parse($einforma->fechaConstitucion)){
                        $noencajes->FechaMax = 1;
                    }
                }
            }

            #NO ENCAJA CATEGORIA
            if(!empty($ayuda->Categoria) && !empty($einforma->categoriaEmpresa)){
                if(is_array($ayuda->Categoria)){
                    if(!in_array($einforma->categoriaEmpresa, $ayuda->Categoria)){
                        $noencajes->Categoria = 1;
                    }
                }else{
                    if($ayuda->Categoria !== null){
                        if(isset($ayuda->Categoria) && $ayuda->Categoria !== "null" && json_decode($ayuda->Categoria, true) !== null){
                            if(!in_array($einforma->categoriaEmpresa, json_decode($ayuda->Categoria, true))){
                                $noencajes->Categoria = 1;
                            }
                        }
                    }
                }
            }
            #NO ENCAJA CATEGORIA CENTRO TECNOLOGICO
            $encajes = DB::table('Encajes_zoho')->where('Ayuda_id', $ayuda->id)->select('naturalezaPartner')->get();
            $naturalezas = array('otro' => 0, 'centro' => 0);

            if(count($encajes) > 0){
                foreach($encajes as $encaje){
                    if(in_array("6668838", json_decode($encaje->naturalezaPartner, true))){
                        $naturalezas['centro'] = 1;
                    }
                    if(in_array("6668837", json_decode($encaje->naturalezaPartner, true))
                    || in_array("6668839", json_decode($encaje->naturalezaPartner, true))){
                        $naturalezas['otro'] = 1;
                    }
                }
                if($naturalezas['otro'] == 1 && $naturalezas['centro'] == 0 && $empresa->esCentroTecnologico == 1){
                    $noencajes->Categoria = 1;
                }
                if($naturalezas['otro'] == 0 && $naturalezas['centro'] == 1 && $empresa->esCentroTecnologico == 1){
                    $noencajes->Categoria = 0;
                }
                if($naturalezas['otro'] == 1 && $naturalezas['centro'] == 1 && $empresa->esCentroTecnologico == 1){
                    $noencajes->Categoria = 0;
                }
                if($naturalezas['otro'] == 0 && $naturalezas['centro'] == 1 && $empresa->esCentroTecnologico == 0){
                    $noencajes->Categoria = 1;
                }

            }

            #NO ENCAJA INTERESES
            $ayuda->intereses = array();
            if(!empty($ayuda->PerfilFinanciacion) && $ayuda->PerfilFinanciacion != "null"){
                $ayuda->intereses = $ayuda->getInteresesAttribute();
            }
            if(!empty($ayuda->intereses) && !empty($empresa->Intereses)){
                foreach(json_decode($empresa->Intereses, true) as $interes){
                    $ok = str_replace('Pública - ','',$interes);
                    if($ok == "Cooperación - Consorcios"){
                    $ok = str_replace('Cooperación - ','',$ok);
                    }
                    $checkintereses[] = DB::table('Intereses')->where('Nombre', $ok)->select('Id_zoho')->first();
                }

                if(!empty($checkintereses)){
                    foreach(array_filter($checkintereses) as $int){
                        $checkintereses[] = $int->Id_zoho;
                    }
                    foreach(json_decode($ayuda->PerfilFinanciacion, true) as $inte){
                        if(in_array($inte, $checkintereses)){
                            $noencajes->Perfiles = 0;
                        }
                    }
                }
            }

            if(!$ayuda->PerfilFinanciacion || $ayuda->PerfilFinanciacion == "null"){
                $noencajes->Perfiles = 0;
            }

            $checkscore = 0;
            if(is_array($score)){
                $checkscore = $score['score'];
            }else{
                $checkscore = (is_integer($score)) ? $score : 0;
            }

            if(($ayuda->TematicaObligatoria === true || $ayuda->TematicaObligatoria == 1) && $checkscore < 0){
                $noencajes->Perfiles = 0;
                $noencajes->Tematica = 1;
            }

            #NO ENCAJA CNAES
            if($ayuda->OpcionCNAE == "Excluidos"){

                $cnaes = array();
                foreach(json_decode($ayuda->CNAES, true) as $ayudacnae){
                    $cnaesdb = DB::table('Cnaes')->where('Id_zoho', $ayudacnae)->select('Nombre')->first();
                    if($cnaesdb){
                        $cnaes[] = trim(substr($cnaesdb->Nombre,0, strpos($cnaesdb->Nombre, "-")));
                    }
                }

                if(isset($empresa->cnae) && !isset($empresa->Cnaes)){
                    foreach($cnaes as $cnae){
                        if (strpos($empresa->cnae, $cnae) !== false) {
                            $noencajes->Cnaes = 1;
                        }
                    }
                }

                if(isset($empresa->Cnaes) && !isset($empresa->cnae)){
                    $checkcnae = json_decode($empresa->Cnaes, true);

                    if($checkcnae !== null && !empty($checkcnae)){
                        foreach($cnaes as $cnae){
                            if(isset($checkcnae['display_value'])){
                                if (strpos($checkcnae['display_value'],$cnae) !== false) {
                                    $noencajes->Cnaes = 1;
                                }
                            }else{
                                if (strpos($checkcnae[0],$cnae) !== false) {
                                    $noencajes->Cnaes = 1;
                                }
                            }
                        }
                    }
                }

                if(isset($score['Recomendar'])){
                    #SOLO TIENE ENCAJE TIPO TARGET
                    if($score['Recomendar']->tag == 0 && $score['Recomendar']->target == 1){
                        $noencajes->Cnaes = 1;
                    }

                    #NO TIENE ENCAJES SCORE < 0
                    if($score['Recomendar']->tag == 0 && $score['Recomendar']->target == 0 && $score['score'] < 0){
                        $noencajes->Cnaes = 1;
                    }
                }

            }

            if($ayuda->OpcionCNAE == "Válidos"){
                $cnaes = array();
                foreach(json_decode($ayuda->CNAES, true) as $ayudacnae){
                    $cnaesdb = DB::table('Cnaes')->where('Id_zoho', $ayudacnae)->select('Nombre')->first();
                    if($cnaesdb){
                        $cnaes[] = trim(substr($cnaesdb->Nombre,0, strpos($cnaesdb->Nombre, "-")));
                    }
                }

                $check = 0;

                if(isset($empresa->cnae)){
                    if(!empty($empresa->cnae)){
                        foreach($cnaes as $cnae){
                            if (strpos($empresa->cnae, $cnae) !== false) {
                                $noencajes->Cnaes = 0;
                                $check = 1;
                            }
                        }
                    }
                }

                if(isset($einforma->cnae)){
                    $cnaeok = trim(substr($einforma->cnae, 0, strripos($einforma->cnae,"-")-1));
                    $cnaeok = str_replace('"', '', $cnaeok);
                    if(!empty($cnaeok)){
                        foreach($cnaes as $cnae){
                            if(str_starts_with($cnae, substr($cnaeok,0,2))){
                                $noencajes->Cnaes = 0;
                                $check = 1;
                            }
                            if(str_starts_with($cnae, substr($cnaeok,0,3))){
                                $noencajes->Cnaes = 0;
                                $check = 1;
                            }
                            if(str_starts_with($cnae, substr($cnaeok,0,4))){
                                $noencajes->Cnaes = 0;
                                $check = 1;
                            }
                        }
                    }
                }

                if(!empty($empresa->Cnaes)){
                    $cnaeok = trim(substr($empresa->Cnaes, 1, strripos($empresa->Cnaes,"-")-1));
                    $cnaeok = str_replace('"', '', $cnaeok);
                    if(!empty($cnaeok)){
                        foreach($cnaes as $cnae){
                            if(str_starts_with($cnae, substr($cnaeok,0,2))){
                                $noencajes->Cnaes = 0;
                                $check = 1;

                            }

                            if(str_starts_with($cnae, substr($cnaeok,0,3))){
                                $noencajes->Cnaes = 0;
                                $check = 1;
                            }

                            if(str_starts_with($cnae, substr($cnaeok,0,4))){
                                $noencajes->Cnaes = 0;
                                $check = 1;
                            }
                        }
                    }

                }

                if($check == 0){
                    $noencajes->Cnaes = 1;
                }

                if(isset($score['Recomendar'])){
                    #SOLO TIENE ENCAJE TIPO TARGET
                    if($score['Recomendar']->tag == 0 && $score['Recomendar']->target == 1){
                        $noencajes->Cnaes = 1;
                    }

                    #NO TIENE ENCAJES SCORE < 0
                    if($score['Recomendar']->tag == 0 && $score['Recomendar']->target == 0 && $score['score'] < 0){
                        $noencajes->Cnaes = 1;
                    }
                }
            }

            if($ayuda->OpcionCNAE == "Todos"){
                $noencajes->Cnaes = 0;
            }

        }

        if($ayuda->Intensidad < $empresa->IntensidadAyudas){
            $noencajes->Intensidad = 1;
        }

        #NO ENCAJA PRESENTACION
        if($empresa->perfilFinanciero !== null && $empresa->perfilFinanciero->liderar_consorcios == 0){
            if($ayuda->Presentacion == "Consorcio"){
                $noencajes->Presentacion = 1;
            }
        }elseif($empresa->perfilFinanciero === null && $empresa->LiderarConsorcios == 0){
            if($ayuda->Presentacion == "Consorcio"){
                $noencajes->Presentacion = 1;
            }
        }
        
        ##NO ENCAJA POR TEMATICA AYUDAS EUROPEAS SCORE <= 0
        if($ayuda->es_europea == 1 && $score['score'] <= 0){
            if($ayuda->encajes->count() > 0){                       
                if($ayuda->encajes->where('Tipo','Linea')->count() > 0){                
                    $noencajes->Tematica = 1;                    
                }                
            }
        }


        return $noencajes;
    }

    function getNoEncajaProyecto($encaje, $empresa){

        $noencajes = (object) array('Naturaleza' => 1, 'Ambito' => 0, 'Cnaes' => 0, 'Categoria' => 0, 'Partner' => 0);

        if($empresa){

            #no encaja naturaleza
            if($empresa->naturalezaEmpresa !== null && $empresa->naturalezaEmpresa != "null" && $encaje->naturalezaPartner !== null && $encaje->naturalezaPartner != "null"){
                $empresanaturaleza = json_decode($empresa->naturalezaEmpresa, true);
                foreach($empresanaturaleza as $naturaleza){
                    if(in_array($naturaleza, json_decode($encaje->naturalezaPartner, true))){
                        $noencajes->Naturaleza = 0;
                    }
                }
            }

            #no encaja Ambito
            if($encaje->Encaje_ambito != "Nacional" && $empresa->Ccaa != ""){
                if($encaje->Encaje_ccaa){
                    foreach(json_decode($encaje->Encaje_ccaa, true) as $ccaa){
                        if(strpos($empresa->Ccaa, $ccaa) >= 0){
                            $noencajes->Ambito = 1;
                        }
                    }
                }
            }
            if($encaje->Encaje_ambito == "Nacional"){
                $noencajes->Ambito = 0;
            }

            #no encaja CNAES
            if($encaje->Encaje_opcioncnaes == "Excluidos"){
                foreach(json_decode($encaje->Encaje_cnaes, true) as $ayudacnae){
                    $cnaesdb = DB::table('Cnaes')->where('Id_zoho', $ayudacnae)->select('Nombre')->first();
                    $cnaes[] = trim(substr($cnaesdb->Nombre,0, strpos($cnaesdb->Nombre, "-")));
                }
                if(isset($empresa->cnae)){
                    foreach($cnaes as $cnae){
                        if (strpos($cnae, $empresa->cnae) !== false) {
                            $noencajes->Cnaes = 1;
                        }
                    }
                }
            }

            if($encaje->Encaje_opcioncnaes == "Válidos"){
                foreach(json_decode($encaje->Encaje_cnaes, true) as $ayudacnae){
                    $cnaesdb = DB::table('Cnaes')->where('Id_zoho', $ayudacnae)->select('Nombre')->first();
                    $cnaes[] = trim(substr($cnaesdb->Nombre,0, strpos($cnaesdb->Nombre, "-")));
                }
                if(isset($empresa->cnae)){
                    foreach($cnaes as $cnae){
                        if (strpos($cnae, $empresa->cnae) !== false) {
                            $noencajes->Cnaes = 0;
                        }
                    }
                }
            }

            #no encaja Categoria
            if($empresa->einforma !== null && $empresa->einforma->categoriaEmpresa !== null && $empresa->einforma->categoriaEmpresa != "null" && $encaje->Encaje_categoria !== null && $encaje->Encaje_categoria != "null"){
                if(!empty($encaje->Encaje_categoria) && !empty($empresa->einforma->categoriaEmpresa)){
                    if(!in_array($empresa->einforma->categoriaEmpresa, json_decode($encaje->Encaje_categoria, true))){
                        $noencajes->Categoria = 1;
                    }
                }
            
                $checkcategories = 1;
                if($encaje->Encaje_categoria !== null && $empresa->einforma !== null && $empresa->einforma->categoriaEmpresa !== null){
                    $categoriaencaje = json_decode($encaje->Encaje_categoria, true);
                    if(in_array($empresa->einforma->categoriaEmpresa, $categoriaencaje)){
                        $checkcategories = 0;
                    }
                    $noencajes->Categoria = $checkcategories;
                }
            }

            if($encaje->tipoPartner == mb_strtolower("cooperacion") || $encaje->tipoPartner == mb_strtolower("cooperación")){
                $encaje->tipoPartner = "Cooperación";
            }

            if($encaje->tipoPartner == mb_strtolower("subcontratacion") || $encaje->tipoPartner == mb_strtolower("subcontratación")){
                $encaje->tipoPartner = "Subcontratación";
            }

            #no encaja Partner
            if(!in_array($encaje->tipoPartner, json_decode($empresa->Intereses, true)) || empty(json_decode($empresa->Intereses, true))){
                $noencajes->Partner = 1;
            }

        }

        return $noencajes;
    }

    function getElasticRecomendedCompanies($ayuda, $encajes, $page, $limit, $encaje = null){

        if($encaje === null){
            $cache = "centrostec_recomendados_".$ayuda->id."_".$page;
        }else{
            $cache = "centrostec_recomendados_".$ayuda->id."_".$page."_".$encaje->id;
        }

        $empresas = Cache::remember($cache, now()->addMinutes(120), function () use($ayuda, $encajes, $limit, $page, $encaje) {

            $elasticEnvironment = config('services.elastic_ayudas.environment');
            $urlEndpoint = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.endpoint');
            $apikey = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.api_key');

            $client = new \GuzzleHttp\Client([
                'base_uri' => $urlEndpoint,
                'headers' => [
                    'x-api-key' => $apikey
                ]
            ]);

            $naturalezas = array();

            if($ayuda->tiposObligatorios === null){
                $naturalezas[] = "6668838";
                $naturalezas[] = "6668840";
            }else{
                if(in_array('cti', json_decode($ayuda->tiposObligatorios, true)) && in_array('uni', json_decode($ayuda->tiposObligatorios, true))){
                    $naturalezas[] = "6668838";
                    $naturalezas[] = "6668840";
                }elseif(in_array('cti', json_decode($ayuda->tiposObligatorios, true))){
                    $naturalezas[] = "6668838";
                }elseif(in_array('uni', json_decode($ayuda->tiposObligatorios, true))){
                    $naturalezas[] = "6668840";
                }
            }

            if(empty($naturalezas)){
                return array();
            }

            $textSearch = array();
            if($encaje === null){
                foreach($encajes as $encaje){
                    if($encaje->TagsTec !== null && $encaje->TagsTec != ""){
                        if(is_array(json_decode($encaje->TagsTec))){
                            $tags = json_decode($encaje->TagsTec);
                        }else{
                            $tags = explode(",", $encaje->TagsTec);
                        }
                        $textSearch = array_merge($textSearch, $tags);
                    }
                }
            }else{
                if($encaje->TagsTec !== null && $encaje->TagsTec != ""){
                    if(is_array(json_decode($encaje->TagsTec))){
                        $tags = json_decode($encaje->TagsTec);
                    }else{
                        $tags = explode(",", $encaje->TagsTec);
                    }
                    $textSearch = array_merge($textSearch, $tags);
                }
            }

            $featured = app(GeneralSettings::class)->master_featured;

            $dataSend = array(
                "type" => "GLOBAL",
                "params" => [
                    'textSearch' => $textSearch,
                    'comunidadesAutonomas' => [],
                    'categories' =>[],
                    'naturaleza' => $naturalezas,
                    'orderByPagerank' => false,
                    'orderByFeatured' => ($featured == "1") ? true : false
                ]
            );            

            try{
                $response = $client->post('company/search?numItemsPage='.$limit.'&numPage='.$page,[
                    \GuzzleHttp\RequestOptions::JSON => $dataSend

                ]);

                $result = json_decode((string)$response->getBody());

                ##ONLY FOR DEV DEBUGGING
                if(\App::environment() != "prod"){
                    if(request()->get('debug') == "1"){
                        dump($result);
                    }
                }

            }catch (\GuzzleHttp\Exception\ServerException $e){
                //dd("error");
                Log::error($e->getMessage());
                return array();
            }

            $empresas = new stdClass;

            $empresas->data = collect($result->data);
            $empresas->pagination = (isset($result->pagination)) ? $result->pagination : null;

            return $empresas;

        });

        return $empresas;

    }

    function custompaginate($items, $perPage = 20, $page = null, $baseUrl = null, $options = [], $totalitems = null){

        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        if($totalitems !== null){
            $lap = new LengthAwarePaginator($items, $totalitems, $perPage, $page, $options);
        }else{
            $lap = new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
        }
        if ($baseUrl) {            
            $lap->setPath($baseUrl);
        }

        return $lap;

    }

    function seo_quitar_tildes($string) {

        if ( !preg_match('/[\x80-\xff]/', $string) )
            return $string;
    
        $chars = array(
        // Decompositions for Latin-1 Supplement
        chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
        chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
        chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
        chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
        chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
        chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
        chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
        chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
        chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
        chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
        chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
        chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
        chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
        chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
        chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
        chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
        chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
        chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
        chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
        chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
        chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
        chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
        chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
        chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
        chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
        chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
        chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
        chr(195).chr(191) => 'y',
        // Decompositions for Latin Extended-A
        chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
        chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
        chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
        chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
        chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
        chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
        chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
        chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
        chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
        chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
        chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
        chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
        chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
        chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
        chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
        chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
        chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
        chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
        chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
        chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
        chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
        chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
        chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
        chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
        chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
        chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
        chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
        chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
        chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
        chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
        chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
        chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
        chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
        chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
        chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
        chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
        chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
        chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
        chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
        chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
        chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
        chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
        chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
        chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
        chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
        chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
        chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
        chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
        chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
        chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
        chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
        chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
        chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
        chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
        chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
        chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
        chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
        chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
        chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
        chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
        chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
        chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
        chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
        chr(197).chr(190) => 'z', chr(197).chr(191) => 's'
        );
    
        $string = strtr($string, $chars);
    
        return $string;
        
    }

    function quitar_tildes($cadena) {
        return strtr(mb_convert_encoding($cadena, "UTF-8", mb_detect_encoding($cadena)), mb_convert_encoding('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÓÙÚÛÜÝ', "UTF-8", mb_detect_encoding($cadena)), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOOUUUUY');
    }

    function cleanUriBeforeSave($name){

        $ocurrences = array("s.a.", "s.l.", "S.A.", "S.L.", "SA", "SL", "SAU", "S.A.U.", "s.a.u", "sa.", "sl.", "sau.", "S.A.L.", "S.L.L", "S L", "-sl-profesional",
        "-slp", "-slu", "-sl-unipersonal", "-sl-", "-slne", "-slg", "-sll");

        $name = seo_quitar_tildes($name);
        $name = preg_replace("/[^a-zA-Z0-9\-\s]/", "", $name);
        $name = str_replace($ocurrences, '', $name);

        return $name;
    }

    function cleanUriProyectosBeforeSave($name){

        $name = preg_replace("/[^a-zA-Z0-9\-\s+]/", "", $name);
        return $name;
    }

    function generateRandomString($length = 10) {
        return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
    }

    function getElasticEmpresasByIdAyuda($id, $limit = null, $page = null, $televenta = null){

        if($televenta === 1){
            $cache = 'televenta_empresas_idayuda'.$id."-".$limit."-".$page;
        }else{
            $cache = 'empresas_idayuda'.$id."-".$limit."-".$page;
        }

        ##Este comando se va a utilizar manualmente como superadmin para evitar sobrecargas le pongo una cache de 45 minutos, pendiente de revision
        $empresas = Cache::remember($cache, now()->addMinutes(120), function () use($id, $limit, $page) {

            $elasticEnvironment = config('services.elastic_ayudas.environment');
            $urlEndpoint = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.endpoint');
            $apikey = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.api_key');

            $client = new \GuzzleHttp\Client([
                'base_uri' => $urlEndpoint,
            ]);

            if($limit === null){
                $limit = 50;
            }

            if($page === null){
                $page = 1;
            }

            $client = new \GuzzleHttp\Client([
                'base_uri' => $urlEndpoint,
                'headers' => [
                    'x-api-key' => $apikey
                ]
            ]);

            /*$dataSend = array(
                "type" => "PUBLIC_ID",
                "params" => [
                    "publicaidId" => (string) $id
                ]
            );*/

            $dataSend = array(
                "type" => "ID_AYUDA",
                "params" => [
                    "ayudaId" => (string) $id,
                    "ayudaPosition" => (int)$page
                ]
            );

            try{
                $response = $client->post('company/search?numItemsPage='.$limit.'&numPage='.$page,[
                    \GuzzleHttp\RequestOptions::JSON => $dataSend

                ]);

                $result = json_decode((string)$response->getBody());

                ##ONLY FOR DEV DEBUGGING
                if(\App::environment() != "prod"){
                    if(request()->get('debug') == "1"){
                        dump($result);
                    }
                }

            }catch (\GuzzleHttp\Exception\ServerException $e){
                //dd("error");
                Log::error($e->getMessage());
                return 'ups';
            }

            $empresas['data'] = collect($result->data)->sortByDesc('score');
            $empresas['pagination'] = (isset($result->pagination)) ? $result->pagination : null;

            return $empresas;

        });

        return $empresas;

    }

    function getElasticEmpresasProyecto($id, $limit = null, $page = null){

        $empresas = Cache::remember('empresas_proyecto'.$id, now()->addMinutes(120), function () use($id, $limit, $page) {
            $elasticEnvironment = config('services.elastic_ayudas.environment');
            $urlEndpoint = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.endpoint');
            $apikey = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.api_key');

            $client = new \GuzzleHttp\Client([
                'base_uri' => $urlEndpoint,
            ]);

            if($limit === null){
                $limit = 50;
            }

            if($page === null){
                $page = 1;
            }

            $client = new \GuzzleHttp\Client([
                'base_uri' => $urlEndpoint,
                'headers' => [
                    'x-api-key' => $apikey
                ]
            ]);

            $dataSend = array(
                "type" => "PUBLIC_ID",
                "params" => [
                    "publicaidId" => (string)$id,
                ]
            );

            try{
                $response = $client->post('company/search?numItemsPage='.$limit.'&numPage='.$page,[
                    \GuzzleHttp\RequestOptions::JSON => $dataSend

                ]);

                $result = json_decode((string)$response->getBody());

                ##ONLY FOR DEV DEBUGGING
                if(\App::environment() != "prod"){
                    if(request()->get('debug') == "1"){
                        dump($result);
                    }
                }

            }catch (\GuzzleHttp\Exception\ServerException $e){
                //dd("error");
                Log::error($e->getMessage());
                return 'ups';
            }

            $empresas['data'] = collect($result->data)->sortByDesc('score');
            $empresas['pagination'] = (isset($result->pagination)) ? $result->pagination : null;

            return $empresas;

        });

        return $empresas;
    }

    /**
     * @param mixed $currentayuda
     * @param mixed $empresa
     * @return void
     */
    function getElasticScore($currentayuda, $empresa, $simulada = null, $idanalisis = null){

        if(isset($simulada) && $simulada == 1){
            $cache = 'score_ayuda'.$empresa->CIF.'-'.$currentayuda->id.'_simulada';
            if(isset($idanalisis) && $idanalisis > 0){
                $cache .= '_idanalisis-'.$idanalisis;
            }
        }else{
            $cache = 'score_ayuda'.$empresa->CIF.'-'.$currentayuda->id;
        }

        $data = Cache::remember($cache, now()->addMinutes(120), function () use($empresa,$currentayuda,$simulada,$idanalisis) {
            $elasticEnvironment = config('services.elastic_ayudas.environment');
            $urlEndpoint = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.endpoint');
            $apikey = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.api_key');

            $client = new \GuzzleHttp\Client([
                'base_uri' => $urlEndpoint,
                'headers' => [
                    'x-api-key' => $apikey
                ]
            ]);

            if($idanalisis !== null){
                $analisis = \App\Models\EntidadesSimuladas::find($idanalisis);
                if(session()->get('user_analisis_proyecto_id') !== null && session()->get('user_analisis_proyecto_id') > 0){
                    $companyId = $analisis->CIF."-".$analisis->analisisproyectos->id;
                }else{
                    $companyId = $analisis->CIF."-".$analisis->id;
                }
                $dataSend = array(
                    "type" => "SCORE",
                    "params" => [
                        "companyId" => $companyId,
                        "ayudaId" => (string)$currentayuda->id
                    ]
                );
            }else{
                $dataSend = array(
                    "type" => "SCORE",
                    "params" => [
                        "companyId" => $empresa->CIF,
                        "ayudaId" => (string)$currentayuda->id
                    ]
                );
            }

            $ayudas = [];

            try{
                $response = $client->post('publicAid/search?numItemsPage=15',[
                    //'json' => $dataSend
                    \GuzzleHttp\RequestOptions::JSON => $dataSend

                ]);

                $result = json_decode((string)$response->getBody());

                ##ONLY FOR DEV DEBUGGING
                if(\App::environment() != "prod"){
                    if(request()->get('debug') == "1"){
                        dump($result);
                    }
                }

                if($result->data){
                    $ayudas = $result->data;
                }

            }catch (\GuzzleHttp\Exception\ServerException $e){
                //dd("error");
                Log::error($e->getMessage());
                return 'ups';
            }

            $targets = collect($ayudas)->where('TipoEncaje', 'target')->pluck('IDAyuda')->toArray();
            $notargets = collect($ayudas)->where('TipoEncaje', '!=', 'target')->pluck('IDAyuda')->toArray();

            if(session()->get('umbral_ayudas') === null){
                $settings = DB::table('settings')->where('group', 'general')->where('name','umbral_ayudas')->first();
                $umbral = (int) $settings->payload;
            }else{
                $umbral = session()->get('umbral_ayudas');
            }

            $data['tags'] = 0;
            $data['target'] = 0;
            $score = -10;
            $data['score'] = $score;

            foreach($ayudas as $ayuda){               

                if($ayuda->TipoEncaje == 'proyecto'){
                    continue;
                }

                if($data['score'] < $ayuda->score){
                    $data['score'] = $ayuda->score;    
                }elseif($data['score'] == -10){
                    $data['score'] = $ayuda->score;    
                }
                
                $data['tags'] = 1;
                $data['id'] = $ayuda->ID;

                $tipotarget = null;
                if($ayuda->TematicaObligatoria === true){
                    $tipotarget = collect($ayudas)->where('TipoEncaje', 'target')->where('IDAyuda', $ayuda->IDAyuda)->pluck('ID')->toArray();
                }

                $checkTarget = null;
                if($ayuda->TematicaObligatoria === true && ($tipotarget === null || empty($tipotarget))){
                    $checkTarget = \App\Models\Encaje::where('Tipo', 'target')->where('Ayuda_id', $ayuda->IDAyuda)->count();
                    if($checkTarget > 0){
                        $data['score'] = -10;
                    }
                }

                if(userEntidadSelected()){
                    if($idanalisis !== null && isset($analisis)){
                        $recomendar = getAyudaRecomendada($ayuda, $targets, $notargets, $umbral, $analisis->valorTrl, $tipotarget, $analisis);
                    }else{
                        $recomendar = getAyudaRecomendada($ayuda, $targets, $notargets, $umbral, userEntidadSelected()->valorTrl, $tipotarget, userEntidadSelected());
                    }
                }else{
                    
                    $recomendar = getAyudaRecomendada($ayuda, $targets, $notargets, $umbral, $tipotarget, null);
                }

                $data['Recomendar'] = $recomendar;

                if($recomendar->valor == -2){
                    $data['score'] = -10;
                }else{
                    if($currentayuda->esDeGenero == 1 && $currentayuda->textoGenero !== null && $data['score'] > 0){
                        $data['score'] = 0.0;
                        $data['Recomendar']->valor = 0;
                        $data['Recomendar']->genero = 1;
                    }
                }

                if($ayuda->Ambito == "europea" && $ayuda->score <= 0 && $ayuda->TipoEncaje == "linea"){
                    $data['score'] = -10;                    
                    $data['FiltroEuropea'] = $ayuda->TipoEncaje;
                }elseif($ayuda->Ambito == "europea" && $ayuda->score < 0 && $ayuda->TipoEncaje == "interna"){
                    $data['score'] = -10;                       
                    $data['FiltroEuropea'] = $ayuda->TipoEncaje;
                }

                $data['FiltroIntensidad'] = (isset($ayuda->FiltroIntensidad)) ? $ayuda->FiltroIntensidad[0] : false;
                $data['FiltroPerfilInteres'] = (isset($ayuda->FiltroPerfilInteres)) ? $ayuda->FiltroPerfilInteres[0] : false;
                $data['FiltroConsorcio'] = (isset($ayuda->FiltroConsorcio)) ? $ayuda->FiltroConsorcio[0] : false;
                
                return $data;
                
            }

          return $data;

        });

        return $data;
    }

    function getFiltersWithResults($empresas, $type){

        if($type == "ccaas"){

            $ccaas = array();
            if(!empty($empresas->data)){
                foreach($empresas->data as $empresa){
                    $ccaas[] = ucfirst(mb_strtolower($empresa->ComunidadAutonoma));
                }
            }

            return array_filter(array_unique($ccaas));
        }

        if($type == "categories"){
            $categories = array();

            if(!empty($empresas->data)){
                foreach($empresas->data as $empresa){
                    $categories[] = ucfirst(mb_strtolower($empresa->CategoriaEmpresa));
                }
            }

            return array_filter(array_unique($categories));
        }

    }

    function getAllCcaas(){

        $ccaas = DB::table('ccaa')->select(['id', 'Nombre'])->orderBy('Nombre', 'asc')->get();

        return collect($ccaas)->toArray();
        /* return array("Ãlava","Albacete","Alicante","AlmerÃ­a","Asturias","Ãvila","Badajoz","Baleares","Barcelona","Burgos","CÃ¡ceres","CÃ¡diz","Cantabria",
        "CastellÃ³n","Ceuta","Ciudad Real","CÃ³rdoba","Cuenca","Gerona","Granada","Guadalajara","GuipÃºzcoa","Huelva","Huesca","JaÃ©n","La CoruÃ±a","La Rioja",
        "Las Palmas","LeÃ³n","Lleida","Lugo","Madrid","MÃ¡laga","Melilla","Murcia","Navarra","Ourense","Palencia","Pontevedra","Salamanca","Segovia","Sevilla",
        "Soria","Tarragona","Tenerife","Teruel","Toledo","Valencia","Valladolid","Vizcaya","Zamora","Zaragoza");*/
    }

    function getAllCategories(){
        return array("Grande","Mediana","Micro","Pequeña");
    }

    function getAllIntereses(){
        return array("Cooperación","StartUp de Base Tecnológica","StartUp","I+D","Innovación","Digitalización","Compra/Inversión activos","Economía circular/sostenibilidad","Energía","Subcontratación","Consultoría");
    }

    function getInteresesSelect(){
        $intereses = \App\Models\Intereses::where('Defecto','true')->where('Categoria','Pública')->get();
        return $intereses->pluck('Nombre', 'Id_zoho')->toArray();
    }

    function getIntereses(){
        $intereses = \App\Models\Intereses::where('Defecto','true')->where('Categoria','Pública')->get();
        return $intereses;
    }

    function getAllOrganismos(){
        $organos = \App\Models\Organos::get();
        $departamentos = \App\Models\Departamentos::get();
        $organos->merge($departamentos);
        return $organos->pluck('Nombre', 'id')->toArray();
    }

    function getInteresesById($id){

        $interes = new stdClass();

        switch($id){
            case "231435000088214889":
                $interes->Nombre = "Cooperación";
                $interes->Id_zoho = "231435000088214889";
            break;
            case "231435000088223017":
                $interes->Nombre = "StartUp de Base Tecnológica";
                $interes->Id_zoho = "231435000088223017";
            break;
            case "231435000088214857":
                $interes->Nombre = "StartUp";
                $interes->Id_zoho = "231435000088214857";
            break;
            case "231435000088214861":
                $interes->Nombre = "I+D";
                $interes->Id_zoho = "231435000088214861";
            break;
            case "231435000088214865":
                $interes->Nombre = "Innovación";
                $interes->Id_zoho = "231435000088214865";
            break;
            case "231435000088214869":
                $interes->Nombre = "Digitalización";
                $interes->Id_zoho = "231435000088214869";
            break;
            case "231435000088214873":
                $interes->Nombre = "Compra/Inversión activos";
                $interes->Id_zoho = "231435000088214873";
            break;
            case "231435000088214877":
                $interes->Nombre = "Economía circular/sostenibilidad";
                $interes->Id_zoho = "231435000088214877";
            break;
            case "231435000089462012":
                $interes->Nombre = "Energía";
                $interes->Id_zoho = "231435000089462012";
            break;
            case "231435000088214862":
                $interes->Nombre = "Subcontratación";
                $interes->Id_zoho = "231435000088214862";
            break;
            case "231435000088214863":
                $interes->Nombre = "Consultoría";
                $interes->Id_zoho = "231435000088214863";
            break;
        }

        return $interes;

    }

    function getNewNotifications($empresa, $notifications){

        if($empresa->Intereses){
            #TODO meterle cache laravel
            $ayudas = Cache::remember('notifications'.$empresa->CIF, now()->addMinutes(120), function () use($empresa) {
                $ayudas = getElasticAyudas($empresa->CIF);
                    return $ayudas;
            });

            $total = collect($ayudas)->count();
            if($total > 0){
                $notifications['oportunidades'] = 1;
            }
        }

        $chats = \App\Models\MessagesThreadsUser::where('user_id', Auth::user()->id)->where('unread_messages', 1)->count();
        $proyectos = \App\Models\Proyectos::where('empresaPrincipal', userEntidadSelected()->CIF)->count();

        if($chats > 0){
            $notifications['mensajes'] = 1;
            if($proyectos > 0){
            $notifications['proyectos'] = 1;
            }
        }

        $totalnotifications = \App\Models\Notification::where('users_id', Auth::user()->id)->where('unread', 1)->count();

        if($totalnotifications > 0){
            $notifications['notificaciones'] = 1;
        }

        return $notifications;
    }

    function getRoleKey()
    {
        $role = NULL;

        if (\Auth::check()) {
            //miramos si es superAdmin
            if (\Auth::user()->super_admin) {
                $role = 'admin';
            } else {
                $userEntidadId = session()->get('user_entidad_id');
                if($userEntidadId !== NULL){
                    //Cache de un minuto...
                    $role = Cache::remember('user_entidad_id_'.$userEntidadId, now()->addMinutes(1), function () use($userEntidadId) {
                        $userEntidadInfo = \App\Models\UsersEntidad::find($userEntidadId);
                        if($userEntidadInfo){
                            return $userEntidadInfo->role;
                        }
                    });
                }
            }
        }
        return $role;
    }

    function getRoleName()
    {

        $roleName = NULL;

        $roleKey = getRoleKey();

        if ($roleKey == 'admin') {
            $roleName = 'Admin';
        } elseif ($roleKey == 'manager') {
            $roleName = 'Mánager';
        } elseif ($roleKey == 'tecnico') {
            $roleName = 'Técnico';
        }
        return $roleName;
    }


    function isSuperAdmin()
    {
        $return = false;
        if (\Auth::check()) {
            //miramos si es superAdmin
            if (\Auth::user()->super_admin) {
                $return = true;
            }
        }
        return $return;

    }


    function isAdmin()
    {
        $roleKey = getRoleKey();
        $return = false;

        if ($roleKey == 'admin') {
            $return = true;
        }
        return $return;
    }


    function isManager()
    {
        $roleKey = getRoleKey();
        $return = false;

        if ($roleKey == 'manager') {
            $return = true;
        }
        return $return;
    }

    function isTecnico()
    {
        $roleKey = getRoleKey();
        $return = false;

        if ($roleKey == 'tecnico') {
            $return = true;
        }

        return $return;
    }

    function userEntidadSelected()
    {
        $info = NULL;

        if (Auth::check()) {
            $userEntidadId = session()->get('user_entidad_id');

            if($userEntidadId !== NULL){
                if(isSuperAdmin()){
                    $info = Cache::remember('user_entidad_with_entidad_id_'.$userEntidadId, now()->addMinutes(1), function () use($userEntidadId) {
                        $entity = \App\Models\Entidad::where('id', $userEntidadId)->first();
                        if($entity){
                            return $entity;
                        }
                    });
                }
                if(session()->get('simulando') === true){
                   //Cache de un minuto...
                    $info = Cache::remember('simular_entidad_id_'.$userEntidadId, now()->addMinutes(1), function () use($userEntidadId) {
                        $userEntidadInfo = \App\Models\EntidadesSimuladas::where('entidad_id', $userEntidadId)->where('user_id', Auth::user()->id)
                        ->where('activo', 1)->first();
                        if($userEntidadInfo){
                            $userEntidadInfo->simulada = 1;
                            return $userEntidadInfo;
                        }
                    });
                }else{
                    //Cache de un minuto...
                    $info = Cache::remember('user_entidad_with_entidad_id_'.$userEntidadId, now()->addMinutes(1), function () use($userEntidadId) {
                        $userEntidadInfo = \App\Models\UsersEntidad::with(['entidad'])->find($userEntidadId);
                        if($userEntidadInfo){
                            return $userEntidadInfo->entidad;
                        }
                    });
                }

            }
        }
        return $info;
    }


    function getRoleById($id, $entidad)
    {
        $userEntidadInfo = \App\Models\UsersEntidad::where('users_id', $id)->where('entidad_id', $entidad)->first();
        if($userEntidadInfo){
            return $userEntidadInfo->role;
        }

        return null;
    }

    function getMyProjects($cif){

        $projects = DB::table('Encajes_zoho')->where('Encajes_zoho.Encaje_estado', 'activo')->where('Encajes_zoho.Tipo', 'proyecto')
        ->leftJoin('proyectos', 'proyectos.id', '=', 'Encajes_zoho.Proyecto_id')
        ->where('proyectos.empresaPrincipal', $cif)
        ->select('proyectos.Acronimo as acronimoProyecto','proyectos.Titulo as nombreProyecto','proyectos.*', 'Encajes_zoho.*')
        ->orderByDesc('proyectos.updated_at')->get();

        if($projects->isNotEmpty()){
            foreach($projects as $project){
                $project->format_presupuesto = number_shorten($project->Encaje_presupuesto, 0);
            }
        }

        return $projects;
    }

    function getRoleNameByKey($roleKey = NULL){

        $roleName = NULL;

        if ($roleKey == 'admin') {
            $roleName = 'Admin';
        } elseif ($roleKey == 'manager') {
            $roleName = 'Mánager';
        } elseif ($roleKey == 'tecnico') {
            $roleName = 'Técnico';
        }
        return $roleName;

    }

    function getPriorizarSolicitud($id){
        $solicitud =  DB::table('prioriza_empresas')->where('esOrgano',1)->where('idOrgano', $id)->where('solicitante', userEntidadSelected()->CIF)->first();
        return $solicitud;
    }

    function checkAdminInEmpresa($id){
        $totalAdmins = \App\Models\UsersEntidad::where('entidad_id', $id)->where('role', 'admin')->count();
        return ($totalAdmins > 0) ? true : false;

    }

    function checkDomainProperty($user, $company){

        $checkdomain = 0;
        if($user){
            $userprefix = substr($user->email, strrpos($user->email, '@') + 1 , strlen($user->email));
            $checkdomain = DB::table('entidades')->where('id', $company->id)->where('Web', 'LIKE', '%'.$userprefix.'%')->count();
            if(!$checkdomain){
                $checkdomain = DB::table('einforma')->where('web', 'LIKE', '%'.$userprefix.'%')->where('identificativo', $company->CIF)->count();
            }
        }

        return ($checkdomain > 0) ? true : false;

    }

    function setCustomOrder($ayudas){

        $ayudascerradas = $ayudas->filter(function ($item) {
            return ($item->Estado == "Cerrada") ? true: false;
        })->sortByDesc('Fin');

        $restoayudas = $ayudas->filter(function ($item) {
            return ($item->Estado != "Cerrada") ? true: false;
        });

        $returnayudas = $restoayudas->sortByDesc('esigual')->merge($ayudascerradas);

        return $returnayudas;

    }

    function getAyudaRecomendada($ayuda, $targets, $notargets, $umbral, $trl = null, $tipotarget = null, $empresa = null){

        $recomendar = new stdClass();
        $recomendar->valor = 0;
        $recomendar->tematica = 0;
        $recomendar->innovacion = 0;
        $recomendar->presupuesto = 0;
        $recomendar->target = 0;
        $recomendar->tag = 0;
        $recomendar->tematicaobligatoria = 0;
        $checkPrincipal = null;

        if($ayuda->score > 0 && $ayuda->score <= $umbral && $ayuda->Ambito != "europea"){
            return $recomendar;
        }

        if(is_array($ayuda->ScoreTRL)){
            $ayuda->ScoreTRL = $ayuda->ScoreTRL[0];
        }

        if($ayuda->TematicaObligatoria === true){

            if($empresa !== null && $empresa->TextosLineasTec === null){
                $recomendar->valor = -1;
                $recomendar->tematicaobligatoria = 1;
                $recomendar->tematica = 1;
                return $recomendar;
            }

            $checkPrincipal = \App\Models\Encaje::where('Ayuda_id', $ayuda->IDAyuda)->where('Tipo', 'Interna')
            ->whereNotNull('TagsTec')->where('TagsTec', '!=', '')->first();

            if($checkPrincipal){
                if($ayuda->score < $umbral){
                    $recomendar->valor = -1;
                    $recomendar->tematicaobligatoria = 1;
                }   
            }

            ### SI HAY LINEA TARGET 
            $checkTarget = \App\Models\Encaje::where('Ayuda_id', $ayuda->IDAyuda)->where('Tipo', 'Target')->first();
            if($checkTarget && $tipotarget !== null && $ayuda->score < 0){
                if(!in_array($checkTarget->IDAyuda, $tipotarget)){
                    $recomendar->valor = -2;
                }else{
                    $recomendar->valor = -1;
                    $recomendar->tematicaobligatoria = 1;
                    $recomendar->tematica = 1;
                }

                return $recomendar;

            }
            ### FIN LOGICA SI HAY LINEA TARGET

        }

        if(in_array($ayuda->IDAyuda, $targets) && in_array($ayuda->IDAyuda, $notargets) && $recomendar->tematicaobligatoria == 0){
            $recomendar->valor = 1;
        }

        if(!in_array($ayuda->IDAyuda, $targets) && in_array($ayuda->IDAyuda, $notargets)){
            $recomendar->tag = 1;
        }

        if(in_array($ayuda->IDAyuda, $targets) && !in_array($ayuda->IDAyuda, $notargets)){
            $recomendar->target = 1;
        }

        if(is_array($ayuda->PerfilIntereses)){
            if(in_array("231435000088214861", $ayuda->PerfilIntereses) && $ayuda->score > $umbral){
                $recomendar->valor = 1;
            }
        }

        if(isset($ayuda->FechaMaxConstitucion) && $ayuda->FechaMaxConstitucion > Carbon::now()->subYears(100) && $recomendar->tematicaobligatoria == 0){
            $recomendar->valor = 1;
        }

        #ENCAJE sin tematica
        if($ayuda->Ambito == "europea" && $ayuda->score <= 0 && $ayuda->TematicaObligatoria === true){
            $count = \App\Models\Encaje::where('Ayuda_id', $ayuda->IDAyuda)->where('Tipo','Linea')->count();
            if($count > 0){                
                $recomendar->valor = -1;
                $recomendar->tematica = 1;                    
            }                            
        }elseif($ayuda->TematicaObligatoria === true && $ayuda->score <= 0){

            if(in_array($ayuda->IDAyuda, $targets) && !in_array($ayuda->IDAyuda, $notargets)){
                $recomendar->valor = -1;
                $recomendar->tematica = 1;
            }

            if(in_array($ayuda->IDAyuda, $notargets) && $ayuda->score <= 0){
                $recomendar->valor = -1;
                $recomendar->tematica = 1;
            }

        }

        #ENCAJE sin innovacion
        if($recomendar->valor <= 0){
            if($ayuda->TRLMin && $ayuda->TRLMin <= 6){
                if(!in_array($ayuda->IDAyuda, $targets) && in_array($ayuda->IDAyuda, $notargets) && $ayuda->TematicaObligatoria === false && $ayuda->ScoreTRL < 0){
                    $recomendar->valor = -1;
                    $recomendar->innovacion = 1;
                }
            }
        }

        #ENCAJE sin presupuesto
        /*if($recomendar->valor <= 0){
            if($ayuda->ScorePresupuesto >= SCOREPRESUPUESTO){
                $recomendar->valor = -1;
                $recomendar->presupuesto = 1;
            }
        }*/

        #ENCAJE TRL diferencia > 2 comn TRL empresa

        if($trl && $ayuda->TRLMin && $ayuda->TRLMin <= 6){
            if($ayuda->TRLMin - $trl <= -2){          
                $recomendar->valor = -1;
                $recomendar->innovacion = 1;
            }
        }
        
        return $recomendar;

    }

    function formatSecretEmail($email){

        $name = explode("@", $email);

        $len = strlen($name[0]) -2;
        $first = substr($name[0], 0, 1);
        $last = substr($name[0], -1, 1);

        $codedemail = $first;

        if($len > 1){
            for($i = 0; $i <= $len; $i++){
                $codedemail .= "*";
            }
        }else{
            $codedemail .= "***";
        }

        $codedemail .= $last;
        $codedemail .= "@".$name[1];

        return $codedemail;

    }

    function getEntityAyudaProyectos($empresa, $ayuda){

        $proyectos = array();
        $projects = getElasticAyudas($empresa->CIF, 'proyecto');

        if($projects){

            $ids = array();
            $consultorias = collect($projects)->where('TipoEncaje', '=', 'consultoria');

            if(!is_object($projects)){
                return redirect()->route('ups');
            }

            foreach($projects as $key => $pro){

                if($pro->IdAyuda != $ayuda->id){
                    unset($projects[$key]);
                    continue;
                }
                if($pro->Tipo == "privado" && $pro->TipoEncaje != "proyecto"){
                    if($pro->TipoEncaje != "Consultoría" && $empresa->esConsultoria == 0){
                        unset($projects[$key]);
                        continue;
                    }
                }
                $enc = DB::table('Encajes_zoho')->where('Proyecto_id', $pro->id)->get();
                $pro->cooperacion = 0;
                $pro->subcontrata = 0;
                $pro->consultoria = 0;

                foreach($enc as $e){
                    if($e->tipoPartner == "Cooperación"){
                        $pro->cooperacion = 1;
                    }
                    if($e->tipoPartner == "Subcontratación"){
                        $pro->subcontrata = 1;
                    }
                    if($e->tipoPartner == "Consultoría"){
                        $pro->consultoria = 1;
                    }
                    if($e->tipoPartner == "Consultoría"){
                        if(isset($consultorias)){
                            foreach($consultorias as $consultoria){
                                if(isset($consultoria->Encaje_id)){
                                    if($e->id == $consultoria->Encaje_id){
                                        $pro->score = $consultoria->score;
                                    }
                                }
                            }
                        }

                    }
                }

                $empr = DB::table('entidades')->where('CIF', $pro->empresaPrincipal)->select('Nombre','uri')->first();
                if($empr){
                    $pro->empresaNombre = $empr->Nombre;
                    $pro->empresaUri = $empr->uri;
                }
                if($pro->IdAyuda){
                    $ayu = \App\Models\Ayudas::where('id', $pro->IdAyuda)->select('Acronimo', 'Titulo')->first();
                    if($ayu->Acronimo){
                        $pro->AyudaAcronimo = $ayu->Acronimo;
                    }else{
                        $pro->AyudaAcronimo = $ayu->Titulo;
                    }
                }
                $ids[] = $pro->id;
                $pro->Proyecto_id = $pro->id;
                $pro->proyecto_acronimo = $pro->Acronimo;
                $pro->proyecto_titulo = $pro->Titulo;
                $pro->proyecto_descripcion = $pro->Descripcion;
                $proyectos[] = $pro;
            }

            $projects = DB::table('proyectos')->where('IdAyuda', (string)$ayuda->id)->where('tipo','publico')->get();
            foreach($projects as $pro){
                if(in_array($pro->id, $ids)){
                    unset($projects[$key]);
                    continue;
                }
                $enc = DB::table('Encajes_zoho')->where('Proyecto_id', $pro->id)->get();
                $pro->cooperacion = 0;
                $pro->subcontrata = 0;
                $pro->score = -10;
                foreach($enc as $e){
                    if($e->tipoPartner == "Cooperación"){
                        $pro->cooperacion = 1;
                    }
                    if($e->tipoPartner == "Subcontratación"){
                        $pro->subcontrata = 1;
                    }
                }

                $empr = DB::table('entidades')->where('CIF', $pro->empresaPrincipal)->select('Nombre','uri')->first();
                if($empr){
                    $pro->empresaNombre = $empr->Nombre;
                    $pro->empresaUri = $empr->uri;
                }
                if($pro->IdAyuda){
                    $ayu = \App\Models\Ayudas::where('id', $pro->IdAyuda)->select('Acronimo', 'Titulo')->first();
                    if($ayu->Acronimo){
                        $pro->AyudaAcronimo = $ayu->Acronimo;
                    }else{
                        $pro->AyudaAcronimo = $ayu->Titulo;
                    }
                }
                $pro->Proyecto_id = $pro->id;
                $pro->proyecto_acronimo = $pro->Acronimo;
                $pro->proyecto_titulo = $pro->Titulo;
                $pro->proyecto_descripcion = $pro->Descripcion;
                $proyectos[] = $pro;
            }

            return $proyectos;

        }

    }

    function checkPerfiles($perfiles){

        $check = array('cooperacion' => 0, 'subcontratacion' => 0, 'otro' => 0, 'consultoria' => 0);

        foreach($perfiles as $perfil){
            if($perfil == "Cooperación"){
                $check['cooperacion'] = 1;
                continue;
            }

            if($perfil == "Subcontratación"){
                $check['subcontratacion'] = 1;
                continue;
            }
            if($perfil == "Consultoría"){
                $check['consultoria'] = 1;
                continue;
            }

            $check['otro'] = 1;

        }

        if($check['otro'] == 1){
            return false;
        }

        return true;
    }

    function checkCIF($cif){

        if(strlen($cif) > 9 || strlen($cif) < 9){
            return false;
        }

        if(strrpos($cif, ' ')){
            return false;
        }

        $first = substr($cif, 0, 1);
        if(is_numeric($first)){
            return false;
        }

        return true;
    }

    function setUmbrales(){
        $umbral = DB::table('settings')->where('group', 'general')->where('name','umbral_ayudas')->first();
        $umbral2 = DB::table('settings')->where('group', 'general')->where('name','umbral_proyectos')->first();
        request()->session()->put('umbral_ayudas', $umbral->payload);
        request()->session()->put('umbral_proyectos', $umbral2->payload);
    }

    function getCompanyAwards($trl, $imasd, $cif){

        $einforma = \App\Models\Einforma::where('identificativo', $cif)->orderBy('ultimaActualizacion', 'desc')->select('gastoAnual as gasto', 'cnae as cnae', 'categoriaEmpresa as categoria')->first();

        if(!$einforma || !$imasd || !$trl){
            return null;
        }

        $cache = 'recompensas_'.$cif.'_'.$trl.'_'.$imasd;
        $recompensas = Cache::remember($cache, now()->addHours(8), function () use($einforma, $trl, $imasd) {

            $recompensas = [
                'trl_medio_premio' => null,
                'trl_medio_mencion' => null,
                'trl_max_premio' => null,
                'trl_max_mencion' => null,
                'trl_min_premio' => null,
                'trl_min_mencion' => null,
                'gasto_medio_premio' => null,
                'gasto_medio_mencion' => null,
                'gasto_max_premio' => null,
                'gasto_max_mencion' => null,
                'gasto_min_premio' => null,
                'gasto_min_mencion' => null,
                'esfuerzo_medio_premio' => null,
                'esfuerzo_medio_mencion' => null,
                'esfuerzo_max_premio' => null,
                'esfuerzo_max_mencion' => null,
                'esfuerzo_min_premio' => null,
                'esfuerzo_min_mencion' => null,
                'extradata' => '',
                'stats' => null

            ];

            $condiciones = \App\Models\CondicionesRecompensas::where('estado', 1)->get();
            $cnae = \App\Models\Cnaes::where('Nombre', $einforma->cnae)->first();
            if(!$cnae){
                return $recompensas;
            }

            $statsCnae = \App\Models\RecompensasTecnologicas::where('categoria', $einforma->categoria)->where('cnae_id', $cnae->id)->first();
            if(!$statsCnae){
                return $recompensas;
            }

            $recompensas['stats'] = $statsCnae;
            if($statsCnae->num_empresas < 30){
                return $recompensas;
            }

            foreach($condiciones as $condicion){
                $recompensas = checkAward($condicion, $trl, $imasd, $einforma->gasto, $recompensas, $statsCnae);
            }

            return $recompensas;
        });

        return $recompensas;

    }

    function checkAward($condicion, $trl, $imasd, $gasto, $response, $stats){

        switch($condicion->dato){
            case "trl_medio":
                if($stats->trl_medio != null){
                    $value = getAwardValue($trl, $condicion->operacion, $condicion->valor, $condicion->es_porcentaje);
                    $check = version_compare($value, $stats->trl_medio, $condicion->condicion);


                    if($condicion->tipo_premio == "Premio"){
                        if($check === true){
                            $response['trl_medio_premio'] = 1;
                            $response['extradata'] .= '<br/>Trl Medio Premio, '.$value. ' '.$condicion->condicion.' '.$stats->trl_medio;
                        }
                    }
                    if($condicion->tipo_premio == "Mención"){
                        if($check === true){
                            $response['trl_medio_mencion'] = 1;
                            $response['extradata'] .= '<br/>Trl Medio Mención, '.$value. ' '.$condicion->condicion.' '.$stats->trl_medio;
                        }
                    }

                    if($check === false){
                        $response['extradata'] .= '<br/>Trl Medio calculo, '.$value. ' '.$condicion->condicion.' '.$stats->trl_medio;
                    }
                }
                break;
            case "trl_max":
                if($stats->trl_max != null){
                    $value = getAwardValue($trl, $condicion->operacion, $condicion->valor, $condicion->es_porcentaje);
                    $check = version_compare($value, $stats->trl_max, $condicion->condicion);

                    if($condicion->tipo_premio == "Premio"){
                        if($check === true){
                            $response['trl_max_premio'] = 1;
                            $response['extradata'] .= '<br/>Trl max Premio, '.$value. ' '.$condicion->condicion.' '.$stats->trl_max;
                        }
                    }
                    if($condicion->tipo_premio == "Mención"){
                        if($check === true){
                            $response['trl_max_mencion'] = 1;
                            $response['extradata'] .= '<br/>Trl max Mención, '.$value. ' '.$condicion->condicion.' '.$stats->trl_max;
                        }
                    }

                    if($check === false){
                        $response['extradata'] .= '<br/>Trl max calculo, '.$value. ' '.$condicion->condicion.' '.$stats->trl_medio;
                    }
                }
                break;
            case "trl_min":
                if($stats->trl_min != null){
                    $value = getAwardValue($trl, $condicion->operacion, $condicion->valor, $condicion->es_porcentaje);
                    $check = version_compare($value, $stats->trl_min, $condicion->condicion);
                    if($imasd > 0 && $imasd !== null){
                        if($condicion->tipo_premio == "Premio"){
                            if($check === true){
                                $response['trl_min_premio'] = 1;
                                $response['extradata'] .= '<br/>Trl min Premio, '.$value. ' '.$condicion->condicion.' '.$stats->trl_min;
                            }
                        }
                        if($condicion->tipo_premio == "Mención"){
                            if($check === true){
                                $response['trl_min_mencion'] = 1;
                                $response['extradata'] .= '<br/>Trl min Mención, '.$value. ' '.$condicion->condicion.' '.$stats->trl_min;
                            }
                        }

                        if($check === false){
                            $response['extradata'] .= '<br/>Trl min calculo, '.$value. ' '.$condicion->condicion.' '.$stats->trl_medio;
                        }
                    }else{
                        $response['extradata'] .= '<br/>Gasto no calculado gasto en I+D es 0';
                    }
                }
                break;
            case "gasto_medio":
                if($stats->gasto_medio != null){
                    if($imasd > 0 && $imasd !== null){
                        $gasto = $imasd;
                        $value = getAwardValue($gasto, $condicion->operacion, $condicion->valor, $condicion->es_porcentaje);
                        $check = version_compare($stats->gasto_medio, $value, $condicion->condicion);

                        if($condicion->tipo_premio == "Premio"){
                            if($check === true){
                                $response['gasto_medio_premio'] = 1;
                                $response['extradata'] .= '<br/>Gastos Medio da Premio si, '.$value. ' '.$condicion->condicion.' '.$stats->gasto_medio;
                            }
                        }
                        if($condicion->tipo_premio == "Mención"){
                            if($check === true){
                                $response['gasto_medio_mencion'] = 1;
                                $response['extradata'] .= '<br/>Gastos Medio da Mención si, '.$value. ' '.$condicion->condicion.' '.$stats->gasto_medio;
                            }
                        }

                        if($check === false){
                            $response['extradata'] .= '<br/>Gastos Medio resultado calculo, '.$value. ' '.$condicion->condicion.' '.$stats->gasto_medio;
                        }
                    }else{
                        $response['extradata'] .= '<br/>Gasto no calculado gasto en I+D es 0';
                    }
                }
                break;
            case "gasto_max":
                if($stats->gasto_max != null){
                    if($imasd > 0 && $imasd !== null){
                        $gasto = $imasd;
                        $value = getAwardValue($gasto, $condicion->operacion, $condicion->valor, $condicion->es_porcentaje);
                        $check = version_compare($stats->gasto_max, $value, $condicion->condicion);

                        if($condicion->tipo_premio == "Premio"){
                            if($check === true){
                                $response['gasto_max_premio'] = 1;
                                $response['extradata'] .= '<br/>Gastos max Premio, '.$stats->gasto_max. ' '.$condicion->condicion.' '.$value;
                            }
                        }
                        if($condicion->tipo_premio == "Mención"){
                            if($check === true){
                                $response['gasto_max_mencion'] = 1;
                                $response['extradata'] .= '<br/>Gastos max Mención, '.$stats->gasto_max. ' '.$condicion->condicion.' '.$value;
                            }
                        }

                        if($check === false){
                            $response['extradata'] .= '<br/>Gastos max calculo, '.$stats->gasto_max. ' '.$condicion->condicion.' '.$value;
                        }
                    }else{
                        $response['extradata'] .= '<br/>Gasto no calculado gasto en I+D es 0';
                    }
                }
                break;
            case "gasto_min":
                if($stats->gasto_min != null){

                    if($imasd > 0 && $imasd !== null){
                        $gasto = $imasd;
                        $value = getAwardValue($gasto, $condicion->operacion, $condicion->valor, $condicion->es_porcentaje);
                        $check = version_compare($stats->gasto_min, $value, $condicion->condicion);

                        if($condicion->tipo_premio == "Premio"){
                            if($check === true){
                                $response['gasto_min_premio'] = 1;
                                $response['extradata'] .= '<br/>Gastos min Premio, '.$stats->gasto_min. ' '.$condicion->condicion.' '.$value;
                            }
                        }
                        if($condicion->tipo_premio == "Mención"){
                            if($check === true){
                                $response['gasto_min_mencion'] = 1;
                                $response['extradata'] .= '<br/>Gastos min Mención, '.$stats->gasto_min. ' '.$condicion->condicion.' '.$value;
                            }
                        }

                        if($check === false){
                            $response['extradata'] .= '<br/>Gastos min calculo, '.$stats->gasto_min. ' '.$condicion->condicion.' '.$value;
                        }
                    }else{
                        $response['extradata'] .= '<br/>Gasto no calculado gasto en I+D es 0';
                    }
                }
                break;
            case "esfuerzo_medio":
                if($stats->esfuerzo_medio != null){
                    if($gasto !== null && $gasto > 0 && $imasd > 0){
                        $esfuerzo = 100*($imasd/$gasto);
                        $value = getAwardValue($esfuerzo, $condicion->operacion, $condicion->valor, $condicion->es_porcentaje);
                        $check = version_compare($stats->esfuerzo_medio, $value, $condicion->condicion);

                        if($condicion->tipo_premio == "Premio"){
                            if($check === true){
                                $response['esfuerzo_medio_premio'] = 1;
                                $response['extradata'] .= '<br/>Esfuerzo Medio da Premio si, '.$esfuerzo. ' '.$condicion->condicion.' '.$stats->esfuerzo_medio;
                            }
                        }
                        if($condicion->tipo_premio == "Mención"){
                            if($check === true){
                                $response['esfuerzo_medio_mencion'] = 1;
                                $response['extradata'] .= '<br/>Esfuerzo Medio de Mención si, '.$esfuerzo. ' '.$condicion->condicion.' '.$stats->esfuerzo_medio;
                            }
                        }

                        if($check === false){
                            $response['extradata'] .= '<br/>Esfuerzo Medio calculo, '.$esfuerzo. ' '.$condicion->condicion.' '.$stats->esfuerzo_medio;
                        }
                    }else{
                        $response['extradata'] .= '<br/>Esfuerzo no calculado gasto o I+D es 0';
                    }
                }
                break;
            case "esfuerzo_max":
                if($stats->esfuerzo_max != null){
                    if($gasto !== null && $gasto > 0 && $imasd > 0){
                        $esfuerzo = 100*($imasd/$gasto);
                        $value = getAwardValue($esfuerzo, $condicion->operacion, $condicion->valor, $condicion->es_porcentaje);
                        $check = version_compare($stats->esfuerzo_max, $value, $condicion->condicion);

                        if($condicion->tipo_premio == "Premio"){
                            if($check === true){
                                $response['esfuerzo_max_premio'] = 1;
                                $response['extradata'] .= '<br/>Esfuerzo max Premio, '.$stats->esfuerzo_max. ' '.$condicion->condicion.' '.$esfuerzo;
                            }
                        }
                        if($condicion->tipo_premio == "Mención"){
                            if($check === true){
                                $response['esfuerzo_max_mencion'] = 1;
                                $response['extradata'] .= '<br/>Esfuerzo max Mención, '.$stats->esfuerzo_max. ' '.$condicion->condicion.' '.$esfuerzo;
                            }
                        }

                        if($check === false){
                            $response['extradata'] .= '<br/>Esfuerzo max calculo, '.$stats->esfuerzo_max. ' '.$condicion->condicion.' '.$esfuerzo;
                        }
                    }else{
                        $response['extradata'] .= '<br/>Esfuerzo no calculado gasto o I+D es 0';
                    }
                }
                break;
            case "esfuerzo_min":
                if($stats->esfuerzo_min != null){

                    if($gasto !== null && $gasto > 0 && $imasd > 0){
                        $esfuerzo = 100*($imasd/$gasto);
                        $value = getAwardValue($esfuerzo, $condicion->operacion, $condicion->valor, $condicion->es_porcentaje);
                        $check = version_compare($stats->esfuerzo_min, $value, $condicion->condicion);

                        if($condicion->tipo_premio == "Premio"){
                            if($check === true){
                                $response['esfuerzo_min_premio'] = 1;
                                $response['extradata'] .= '<br/>Esfuerzo min Premio, '.$stats->esfuerzo_min. ' '.$condicion->condicion.' '.$esfuerzo;
                            }
                        }
                        if($condicion->tipo_premio == "Mención"){
                            if($check === true){
                                $response['esfuerzo_min_mencion'] = 1;
                                $response['extradata'] .= '<br/>Esfuerzo min Mención, '.$stats->esfuerzo_min. ' '.$condicion->condicion.' '.$esfuerzo;
                            }
                        }

                        if($check === false){
                            $response['extradata'] .= '<br/>Esfuerzo min calculo, '.$stats->esfuerzo_min. ' '.$condicion->condicion.' '.$esfuerzo;
                        }
                    }else{
                        $response['extradata'] .= '<br/>Esfuerzo no calculado gasto o I+D es 0';
                    }
                }
            break;
        }

        return $response;
    }

    function getAwardValue($value, $operacion, $valor, $porcentaje){

        if($operacion == "+"){
            if($porcentaje == 1){
                return ($value + $valor)/100;
            }
            return $value + $valor;
        }

        if($operacion == "-"){
            if($porcentaje == 1){
                return ($value - $valor)/100;
            }
            return $value - $valor;
        }

        if($operacion == "*"){
            if($porcentaje == 1){
                return ($value * $valor)/100;
            }
            return $value * $valor;
        }

    }

    function getGastoIdMax($company, $naturaleza){

        $gastoIDMax = 0.0;
        $coeficiente = null;

        if($company->categoriaEmpresa == "Micro"){
            $coeficiente = 0.85;
        }
        if($company->categoriaEmpresa == "Pequeña"){
            $coeficiente = 0.4;
        }
        if($company->categoriaEmpresa == "Mediana"){
            $coeficiente = 0.2;
        }
        if($company->categoriaEmpresa == "Grande"){
            $coeficiente = 0.1;
        }
        if(in_array("6668838", json_decode($naturaleza, true))){
            $coeficiente = 0.8;
        }

        if($coeficiente !== null){
            $gastoIDMax = round((float)$company->gastoAnual*$coeficiente, 2);
        }

        return $gastoIDMax;
    }

    function getElasticTotalProyectos($cif){

        $cache = 'total_oportunidades_gestor_proyectos_'.$cif;

        $ayudaresponse = Cache::remember($cache, now()->addMinutes(120), function () use($cif) {

            $elasticEnvironment = config('services.elastic_ayudas.environment');
            $urlEndpoint = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.endpoint');
            $apikey = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.api_key');
        
            $client = new \GuzzleHttp\Client([
                'base_uri' => $urlEndpoint,
                'headers' => [
                    'x-api-key' => $apikey
                ]
            ]);

            $dataSend = array(
                "type" => "COMPANY_ID",
                "params" => [
                    "companyId" => $cif,
                    "tipoEncaje" => "proyecto",
                    "organismoId" => ""
                ]
            );

            try{
                $response = $client->post('publicAid/search?numItemsPage=200',[
                    \GuzzleHttp\RequestOptions::JSON => $dataSend
                ]);

            }catch (\GuzzleHttp\Exception\ServerException $e){
                //dd("error");
                Log::error($dataSend);
                Log::error($e->getMessage());
                return 0;
            }

            $result = json_decode((string)$response->getBody());

            if(!isset($reult) || empty($result->data)){
                return 0;
            }

            return $result->pagination->totalItems;

        });

       return $ayudaresponse;

    }

    /**
     * @param mixed $id
    * @return array
    * @throws BindingResolutionException
    * @throws NotFoundExceptionInterface
    * @throws ContainerExceptionInterface
    * @throws GuzzleException
    * @throws InvalidFormatException
    */
    function getGraphData($id, $type, $id2 = null, $pais = null, $items = 3){

    if($id2 !== null){
        $cache = 'graph_data_'.$type.'_'.$id.'_'.$id2;
    }else{
        $cache = 'graph_data_'.$type.'_'.$id;
    }
    if($pais !== null){
        $cache .= '_'.strtolower($pais);
    }

    $graphdata = Cache::remember(mb_strtolower($cache), now()->addMinutes(120), function () use($id, $type, $id2, $pais, $items) {

        $elasticEnvironment = config('services.elastic_grafos.environment');
        $urlEndpoint = config('services.elastic_grafos.servers.'.$elasticEnvironment.'.endpoint');
        $apikey = config('services.elastic_grafos.servers.'.$elasticEnvironment.'.api_key');

        $client = new \GuzzleHttp\Client([
            'base_uri' => $urlEndpoint,
            'headers' => [
                'x-api-key' => $apikey
            ]
        ]);

        if($type == "PROJECT_BY_ENTITY"){

            $dataSend = array(
                "type" => $type,
                "params" => [
                    "nif" => $id
                ]
            );
        }

        if($type == "ENTITY_BY_PROJECT"){

            $dataSend = array(
                "type" => $type,
                "params" => [
                    "id" => 'p'.$id
                ]
            );
        }

        if($type == "ENTITY_WITH_PROJECTS_IN_COMMON"){

            $dataSend = array(
                "type" => $type,
                "params" => [
                    "nif1" => $id2,
                    "nif2" => $id,
                ]
            );
        }

        if($type == "ENTITY_BY_NIF"){

            $dataSend = array(
                "type" => $type,
                "params" => [
                    "nif" => $id,
                    "scope" => $pais,
                ]
            );
        }

        try{
            $response = $client->post('graph?numItemsPage='.$items.'&numPage=1',[
                \GuzzleHttp\RequestOptions::JSON => $dataSend
            ]);
            
        }catch (\GuzzleHttp\Exception\ServerException $e){
            //dd($e->getMessage());
            Log::error($e->getMessage());
            return;
        }

        $graphdata = json_decode((string)$response->getBody());
        return $graphdata;

    });

    return $graphdata;

    }


    /**
     * @param mixed $id
    * @return array
    * @throws BindingResolutionException
    * @throws NotFoundExceptionInterface
    * @throws ContainerExceptionInterface
    * @throws GuzzleException
    * @throws InvalidFormatException
    */
    function getResearchers($id = null, $type = null, $page = null, $string = null){

    if($type !== null && $type == "COMPANY_ID"){
        $cache = 'researchers_'.$id.'_'.$type.'_'.$page;
    }else{
        $stringcache = str_replace(" ","-", $string);
        $cache = 'researchers_'.$stringcache.'_'.$type.'_'.$page;
    }

    $researchers = Cache::remember(mb_strtolower($cache), now()->addMinutes(120), function () use($id, $type, $page, $string) {

        $elasticEnvironment = config('services.elastic_ayudas.environment');
        $urlEndpoint = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.endpoint');
        $apikey = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.api_key');

        $client = new \GuzzleHttp\Client([
            'base_uri' => $urlEndpoint,
            'headers' => [
                'x-api-key' => $apikey
            ]
        ]);

        if($type == "COMPANY_ID"){

            $dataSend = array(
                "type" => $type,
                "params" => [
                    "companyId" => (string) $id
                ]
            );
        }else{

            $search = str_replace("&","\AND",$string);

            if(preg_match('/[\x80-\xff]/', $search)){
                $sintilde = seo_quitar_tildes($search);
                $search .= " ".$sintilde;
            }

            if (preg_match('/"([^"]+)"/', $search, $m)) {
                $userSearch[] = preg_replace("/[^a-zA-Z0-9À-ÿ.\s+]/", "", $m[1]);
                $arraysearch = explode(" ", preg_replace("/[^a-zA-Z0-9À-ÿ.\s+]/", "", str_replace($m[1], "", $search)));
                foreach(array_filter($arraysearch) as $string){
                    $userSearch[] = $string;
                }
            }else{
                $userSearch = explode(" ", preg_replace("/[^a-zA-Z0-9À-ÿ.\s+]/", "", $search));
            }

            $dataSend = array(
                "type" => $type,
                "params" => [
                    "textSearchList" => $userSearch,
                ]
            );      
        }
        
        try{
            $response = $client->post('research/search?numItemsPage=20&numPage='.$page,[
                \GuzzleHttp\RequestOptions::JSON => $dataSend
            ]);

        }catch (\GuzzleHttp\Exception\ServerException $e){
            //dd($e->getMessage());
            Log::error($e->getMessage());
            return;
        }

        $researchers = json_decode((string)$response->getBody());
        return $researchers;

    });

    return $researchers;

    }

    /**
     * @param mixed $id
    * @return array
    * @throws BindingResolutionException
    * @throws NotFoundExceptionInterface
    * @throws ContainerExceptionInterface
    * @throws GuzzleException
    * @throws InvalidFormatException
    */
    function getConcessions($id = null, $type = null, $page = null, $idOrganismo = null, $tipoOrganismo = null){

    if($type !== null && $type == "COMPANY_ID"){
        $cache = 'concessions_'.$id.'_'.$type.'_'.$page;
    }else{
        $cache = 'concessions_'.$idOrganismo.'_'.$tipoOrganismo.'_'.$type.'_'.$page;
    }

    $concesiones = Cache::remember(mb_strtolower($cache), now()->addMinutes(120), function () use($id, $type, $idOrganismo, $tipoOrganismo, $page) {

        $elasticEnvironment = config('services.elastic_ayudas.environment');
        $urlEndpoint = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.endpoint');
        $apikey = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.api_key');

        $client = new \GuzzleHttp\Client([
            'base_uri' => $urlEndpoint,
            'headers' => [
                'x-api-key' => $apikey
            ]
        ]);

        if($type == "COMPANY_ID"){

            $dataSend = array(
                "type" => $type,
                "params" => [
                    "companyId" => $id
                ]
            );

        }else{
            $dataSend = array(
                "type" => $type,
                "params" => [
                    "idOrganismo" => (int)$idOrganismo,
                    "tipoOrganismo" => (string)$tipoOrganismo
                ]
            );

        }
        
        try{
            $response = $client->post('concession/search?numItemsPage=20&numPage='.$page,[
                \GuzzleHttp\RequestOptions::JSON => $dataSend
            ]);

        }catch (\GuzzleHttp\Exception\ServerException $e){
            //dd($e->getMessage());
            Log::error(Request::url());
            Log::error($e->getMessage());
            return;
        }

        $concesiones = json_decode((string)$response->getBody());
        
        if(isset($concesiones->data) && !empty($concesiones->data)){
            $organismos = collect();
            foreach($concesiones->data as $concesion){    
                if($organismos->where('id', $concesion->IdOrganismo)->count() > 0){
                    if($organismos->where('id', $concesion->IdOrganismo)->first()->url !== null){
                        $concesion->dpto = $organismos->where('id', $concesion->IdOrganismo)->first()->url;
                    }
                }else{                                                
                    $organo = \App\Models\Organos::find($concesion->IdOrganismo);                         
                    if($organo === null){                    
                        $departamento = \App\Models\Departamentos::find($concesion->IdOrganismo);
                        if($departamento){
                            $organismos->push($departamento);
                            $concesion->dpto = $departamento->url;
                        }                        
                    }else{
                        $organismos->push($organo);
                        $concesion->dpto = $organo->url;
                    }
                }
            }
        }

        return $concesiones;

    });

    return $concesiones;

    }

    function checkEnableMenuOption($option, $cif){

    $entity = \App\Models\Entidad::where('CIF', $cif)->select('opcionesMenu')->first();

    if(!$entity){
        return false;
    }

    $companyOptions = json_decode($entity->opcionesMenu);

    if($companyOptions->{$option} == 0){
        return false;
    }

    return true;

    }

    function getEmpresasTargetizadas($email, $page, $score){

    if($email === true){
        $cache = "empresas_targetizadas_con_email_".$score."_".$page;
    }else{
        $cache = "empresas_targetizadas_sin_email_".$score."_".$page;
    }

    $empresas = Cache::remember($cache, now()->addMinutes(600), function () use($email, $page, $score) {

        $elasticEnvironment = config('services.elastic_ayudas.environment');
        $urlEndpoint = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.endpoint');
        $apikey = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.api_key');

        $client = new \GuzzleHttp\Client([
            'base_uri' => $urlEndpoint,
            'headers' => [
                'x-api-key' => $apikey
            ]
        ]);

        $dataSend = array(
            "type" => "TIPO_ENCAJE",
            "params" => [
                'tipoEncaje' => "proyecto",
                'minScore' => $score,
                'statusEmail' => $email
            ]
        );            
        

        try{
            $response = $client->post('company/search?numItemsPage=200&numPage='.$page,[
                \GuzzleHttp\RequestOptions::JSON => $dataSend

            ]);

            $result = json_decode((string)$response->getBody());

            ##ONLY FOR DEV DEBUGGING
            if(\App::environment() != "prod"){
                if(request()->get('debug') == "1"){
                    dump($result);
                }
            }

        }catch (\GuzzleHttp\Exception\ServerException $e){
            //dd("error");
            Log::error($e->getMessage());
            return array();
        }

        $data = json_decode((string)$response->getBody());

        if(isset($data->debug) && isset($data->debug->hits)){

            foreach($data->debug->hits->hits as $info){
                $encaje = \App\Models\Encaje::find($info->_index);
                if($encaje->proyecto !== null){                
                    $info->hits->ayudaNombre = $encaje->proyecto->Acronimo.":".$encaje->proyecto->Titulo;
                    $info->hits->ayudaUrl = $encaje->proyecto->uri;
                    $info->hits->esProyecto = 1;
                    $info->hits->esAyuda = 0;
                    $info->hits->proyecto_id = $encaje->proyecto->id;
                    $info->hits->encaje_id = $encaje->id;
                    $info->hits->encaje_titulo = $encaje->Titulo;
                }elseif($encaje->ayuda !== null){
                    $info->hits->ayudaNombre = $encaje->ayuda->Acronimo.":".$encaje->ayuda->Titulo;
                    $info->hits->ayudaUrl = $encaje->ayuda->Uri;
                    $info->hits->esProyecto = 0;
                    $info->hits->esAyuda = 1;
                    $info->hits->proyecto_id = $encaje->ayuda->id;
                    $info->hits->encaje_id = $encaje->id;
                    $info->hits->encaje_titulo = $encaje->Titulo;
                    if($encaje->ayuda->organo !== null){
                        $info->hits->ayudaDpto = $encaje->ayuda->organo->url;
                    }
                    if($encaje->ayuda->departamento !== null){
                        $info->hits->ayudaDpto = $encaje->ayuda->departamento->url;
                    }
                }
            }

            $empresas['data'] = $data->debug->hits->hits;
            $empresas['pagination'] = $data->pagination;
        }else{
            $empresas['data'] = null;
            $empresas['pagination'] = null;
        }

        return $empresas;
    });


    return $empresas;

    }

    function groupTargetCompaniesByCIF($empresas, $email, $score, $page){

    if($email === true){
        $cache = "empresas_targetizadas_con_email_bycif_".$score."_".$page;
    }else{
        $cache = "empresas_targetizadas_sin_email_bycif_".$score."_".$page;
    }

    $empresasbyCif = Cache::remember($cache, now()->addMinutes(600), function () use($empresas, $email) {

        $empresasbyCif = array();
        $cifs = array();

        foreach($empresas['data'] as $empresa){

            foreach($empresa->hits->hits as $key => $data){

                if(!in_array($data->fields->NIF[0], $cifs)){
                    $cifs[] = $data->fields->NIF[0];
                    $empresasbyCif[$data->fields->NIF[0]]['Nombre'] = $data->fields->Nombre[0];
                    $empresasbyCif[$data->fields->NIF[0]]['NIF'] = $data->fields->NIF[0];
                    $empresasbyCif[$data->fields->NIF[0]]['LinkInterno'] = $data->fields->LinkInterno[0];
                    $empresasbyCif[$data->fields->NIF[0]]['ListadoEmails'] = ($email === true) ? $data->fields->ListadoEmails : [];
                    $empresasbyCif[$data->fields->NIF[0]]['proyectos'][$key]['score'] = $empresa->hits->hits[$key]->_score;
                    $empresasbyCif[$data->fields->NIF[0]]['proyectos'][$key]['ayudaNombre'] = $empresa->hits->ayudaNombre;
                    $empresasbyCif[$data->fields->NIF[0]]['proyectos'][$key]['ayudaUrl'] = $empresa->hits->ayudaUrl;
                    $empresasbyCif[$data->fields->NIF[0]]['proyectos'][$key]['esProyecto'] = $empresa->hits->esProyecto;
                    $empresasbyCif[$data->fields->NIF[0]]['proyectos'][$key]['esAyuda'] = $empresa->hits->esAyuda;
                    $empresasbyCif[$data->fields->NIF[0]]['proyectos'][$key]['encaje_id'] = $empresa->hits->encaje_id;
                    $empresasbyCif[$data->fields->NIF[0]]['proyectos'][$key]['encaje_titulo'] = $empresa->hits->encaje_titulo;
                    $empresasbyCif[$data->fields->NIF[0]]['proyectos'][$key]['proyecto_id'] = $empresa->hits->proyecto_id;
                    if($empresa->hits->esAyuda == 1){
                        $empresasbyCif[$data->fields->NIF[0]]['proyectos'][$key]['ayudaDpto'] = $empresa->hits->ayudaDpto;
                    }                    
                    
                }else{
                    $empresasbyCif[$data->fields->NIF[0]]['proyectos'][$key]['score'] = $empresa->hits->hits[$key]->_score;
                    $empresasbyCif[$data->fields->NIF[0]]['proyectos'][$key]['ayudaNombre'] = $empresa->hits->ayudaNombre;
                    $empresasbyCif[$data->fields->NIF[0]]['proyectos'][$key]['ayudaUrl'] = $empresa->hits->ayudaUrl;
                    $empresasbyCif[$data->fields->NIF[0]]['proyectos'][$key]['esProyecto'] = $empresa->hits->esProyecto;
                    $empresasbyCif[$data->fields->NIF[0]]['proyectos'][$key]['esAyuda'] = $empresa->hits->esAyuda;
                    $empresasbyCif[$data->fields->NIF[0]]['proyectos'][$key]['encaje_id'] = $empresa->hits->encaje_id;
                    $empresasbyCif[$data->fields->NIF[0]]['proyectos'][$key]['encaje_titulo'] = $empresa->hits->encaje_titulo;
                    $empresasbyCif[$data->fields->NIF[0]]['proyectos'][$key]['proyecto_id'] = $empresa->hits->proyecto_id;
                    if($empresa->hits->esAyuda == 1){
                        $empresasbyCif[$data->fields->NIF[0]]['proyectos'][$key]['ayudaDpto'] = $empresa->hits->ayudaDpto;
                    }
                    $empresasbyCif[$data->fields->NIF[0]]['proyectostotal']++;
                }    

                $empresasbyCif[$data->fields->NIF[0]]['proyectostotal'] = count($empresasbyCif[$data->fields->NIF[0]]['proyectos']);
                        
            }
        }

        usort($empresasbyCif, function($a, $b) {
            return $b['proyectostotal'] <=> $a['proyectostotal'];
        });

        return $empresasbyCif;

    });

    return $empresasbyCif;
    }

    function getAyudasCalendario($ayudas){

        $ayudas = collect($ayudas)->groupBy('id_ayuda')->sortByDesc('Estado')->sortByDesc('');
        $data = collect(null);

        $currentdate = Carbon::now()->addMonths(9);

        foreach($ayudas as $ayuda){

            $convocatoria = \App\Models\Convocatorias::find($ayuda->first()->id_ayuda);

            if($convocatoria){

                if($ayuda->first()->Estado == "Próximamente"){
                    if($convocatoria->mes_apertura_1 !== null){
                        $date = Carbon::createFromDate(Carbon::now()->format('Y'), $convocatoria->mes_apertura_1)->endOfMonth()->addDay();
                        if(Carbon::now()->diffInMonths($date) >= 10){                       
                            continue;
                        }
                    }
                }
    
                if((int)$convocatoria->mes_apertura_1 >= (int)Carbon::now()->subMonths(2)->format('m') && 
                (int)$convocatoria->mes_apertura_1 < (int)Carbon::now()->format('m') &&
                $convocatoria->duracion_convocatorias <= 2){
                    continue;
                }
                if((int)$convocatoria->mes_apertura_2 >= (int)Carbon::now()->subMonths(2)->format('m') && 
                (int)$convocatoria->mes_apertura_2 < (int)Carbon::now()->format('m') &&
                $convocatoria->duracion_convocatorias <= 2){
                    continue;
                }
                if((int)$convocatoria->mes_apertura_3 >= (int)Carbon::now()->subMonths(2)->format('m') && 
                (int)$convocatoria->mes_apertura_3 < (int)Carbon::now()->format('m') &&
                $convocatoria->duracion_convocatorias <= 2){
                    continue;
                }

                //$idayuda = $ayuda->first()->id_ayuda;
                $estado = $ayuda->first()->Estado;
                $ambito = $ayuda->first()->Ambito;
                $intensidad = $ayuda->first()->Intensidad;
                $presentacion = $ayuda->first()->Presentacion;
                
                //if($convocatoria && $estado != "Cerrada"){
                $dpto = $ayuda->first()->organo()->first();
                if(!$dpto){
                    $dpto = $ayuda->first()->departamento()->first();
                }
                $uri = $ayuda->first()->Uri;
                $convocatoria->estadoConvocatorias = $estado;
                $convocatoria->Intensidad = $intensidad;
                $convocatoria->Presentacion = $presentacion;
                $convocatoria->convocatoriaUrl = null;
                $convocatoria->pasarExtinguida = $ayuda->first()->update_extinguida_ayuda;
                if($uri && $dpto){
                    if($estado == "Cerrada"){
                        $checkproximaconvocatoria = \App\Models\Ayudas::where('id_ayuda', $ayuda->first()->id_ayuda)->where('Estado', '!=', 'Cerrada')->first();
                        if($checkproximaconvocatoria){
                            $convocatoria->convocatoriaUrl = route('ayuda.convocatorias', [$dpto->url, $checkproximaconvocatoria->Uri]);
                        }else{
                            $convocatoria->convocatoriaUrl = route('ayuda.convocatorias', [$dpto->url, $uri]);    
                        }
                    }else{
                        $convocatoria->convocatoriaUrl = route('ayudasimple', [$dpto->url, $uri]);
                    }
                }
                if($ambito == "Comunidad Autónoma"){
                    $convocatoria->ambito = $ambito;
                    $convocatoria->ccaas = $ambito;
                }else{
                    $convocatoria->ambito = $ambito;
                    $convocatoria->ccaas = null;
                }
                $data[] = $convocatoria;
            }
        }

        $data = $data->sortBy('ambito');

        return $data;
    }