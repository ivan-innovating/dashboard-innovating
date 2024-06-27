<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\RedisController;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\ValidateCompany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use stdClass;

class EmpresaController extends Controller
{
    //
    const OCCURENCES = array("s.a.", "s.l.", "S.A.", "S.L.", "SA", "SL", "SAU", "S.A.U.", "s.a.u", "sa.", "sl.", "sau.", "S.A.L.", "S.L.L", "S L");

    public $rediscontroller;
    public $expiration;

    public function __construct()
    {
        $this->rediscontroller = new RedisController;
        $this->expiration = config('app.cache.expiration');
    }

    public function empresa(Request $request)
    {

        $uri = $request->route('uri');
        if($uri === null){
            return abort(404);
        }

        $uri = $request->route('uri');

        $entity = \App\Models\Entidad::where('uri', $uri)->first();
        if($entity){
            $entity->eszoho = 0;
        }
        if(!$entity){
            $entity = \App\Models\CifsNoZoho::where('uri', $uri)->where('movidoEntidad', 0)->first();
            if($entity){
                $entity->eszoho = 1;
            }
        }

        if(!$entity){
            return abort(404);
        }

        if($entity->organo !== null || $entity->departamento !== null){
            $url = ($entity->organo !== null) ? $entity->organo->url : $entity->departamento->url;
            return redirect()->route('ayudas', $url);
        }

        if(\App::environment() == "prod"){
            if(Auth::check()){
                if(!isSuperAdmin()){
                    if($this->rediscontroller->checkRedisCache('single:empresa:uri:'.$uri)){
                        return $this->rediscontroller->getRedisCache('single:empresa:uri:'.$uri, 'empresa');
                    }
                }
            }else{
                if($this->rediscontroller->checkRedisCache('single:empresa:uri:'.$uri)){
                    return $this->rediscontroller->getRedisCache('single:empresa:uri:'.$uri, 'empresa');
                }
            }
        }

        $data = null;
        $companynews = \App\Models\CompanyNews::where('company_id', $entity->CIF)->first();

        if($entity){
            $data = $entity->einforma;            
        }

        if(!$data){
            if(!$entity){

                $totalproyectos = 0;

                return view('empresa-noeinforma',[
                    'empresa' => null,
                    'partners' => null,
                    'data' => null,
                    'sellopyme' => null,
                    'sellostartup' => null,
                    'nodata' => 1,
                    'ayudas' => null,
                    'patentes' => null,
                    'prioriza' => null,
                    'totalproyectos' => $totalproyectos,
                    'companynews' => null,
                    'totalparticipantes' => 0,
                    'totalequipo' => 0,
                    'showsubheader' => 0,
                    'formclass' => 'col-sm-10 pl-0',
                    'title' => "Empresa no encontrada"
                ]);
            }

            if($entity){

                $totalayudas = 0;
                $concesiones = getConcessions($entity->CIF, "COMPANY_ID", 1);
                if($concesiones !== null && $concesiones != "ups" && isset($concesiones->pagination)){
                    $totalayudas = $concesiones->pagination->totalItems;
                }
        
                $patentes = new StdClass();
                $patentes->total = 0;
                $patentes->data = collect(null);
                $patentes->esorgano = false;
        
                $sellopyme = \App\Models\Pymes::where('CIF', $entity->CIF)->where('es_enisa', 0)->orderBy('validez', 'desc')->first();
                $sellostartup = \App\Models\Pymes::where('CIF', $entity->CIF)->where('es_enisa', 1)->orderBy('validez', 'desc')->first();

                $patentes->data = \App\Models\Patentes::where('CIF', $entity->CIF)->orderByDesc('Fecha_publicacion')->take(20)->get();
                if(empty($patentes->data) || $patentes->data->empty()){
                    $patentes->total = \App\Models\Concessions::where('custom_field_cif', $entity->CIF)->where('id_organo', 650)->count();
                    if($patentes->total > 0){
                        $patentes->esorgano = true;
                    }
                }
        
                $pais = \App\Models\Paises::where('iso2', mb_strtolower($entity->pais))->first();

                if($pais){
                    $entity->paisdata = $pais;
                }else{
                    $entity->paisdata = null;
                }

                $solicitud = null;

                if(Auth::check()){
                    $solicitud = \App\Models\PriorizaEmpresas::where('solicitante', Auth::user()->email)->where('cifPrioritario', $entity->CIF)->get();
                }

                $proyectos = \App\Models\Proyectos::where('proyectos.empresaPrincipal', $entity->CIF)->orderByDesc('Fecha')->orderByDesc('inicio')->get();
                $misproyectosabiertos = collect($proyectos)->where('Estado', 'Abierto')->where('importado', 0)->where('esEuropeo', 0);   
                $totalproyectos = $misproyectosabiertos->count();

                return view('empresa-noeinforma',[
                    'empresa' => $entity,
                    'data' => null,
                    'sellopyme' => null,
                    'sellostartup' => null,
                    'nodata' => 1,
                    'totalayudas' => $totalayudas,
                    'companynews' => null,
                    'totalproyectos' => $totalproyectos,
                    'patentes' => $patentes,
                    'prioriza_empresa' => $solicitud,
                    'showsubheader' => 0,
                    'totalparticipantes' => 0,
                    'totalequipo' => 0,
                    'formclass' => 'col-sm-10 pl-0',
                    'title' => "Empresa - ".$entity->Nombre
                ]);
            }

        }
        
        $patentes = new StdClass();
        $patentes->total = 0;
        $patentes->data = collect(null);
        $patentes->esorgano = false;        

        if($data){
            $data->trl_warning = 0;
            $data->format_primaemision = number_shorten($data->PrimaEmision,0);
        }

        $sellopyme = \App\Models\Pymes::where('CIF', $entity->CIF)->where('es_enisa', 0)->orderBy('validez', 'desc')->first();
        $sellostartup = \App\Models\Pymes::where('CIF', $entity->CIF)->where('es_enisa', 1)->orderBy('validez', 'desc')->first();
        $patentes->data = \App\Models\Patentes::where('CIF', $entity->CIF)->orderByDesc('Fecha_publicacion')->take(20)->get();
        if(empty($patentes->data) || $patentes->data->empty()){
            $patentes->total = \App\Models\Concessions::where('custom_field_cif', $entity->CIF)->where('id_organo', 650)->count();
            if($patentes->total > 0){
                $patentes->esorgano = true;
            }
        }

        if(isset($entity->cantidadImasD)){
            $entity->format_imasd = number_shorten(round($entity->cantidadImasD, -3), 0);
        }

        if(isset($entity->naturalezaEmpresa)){
            $naturalezas = json_decode($entity->naturalezaEmpresa, true);
            if(count($naturalezas) == 1 && $naturalezas[0] == "6668837"){
                $entity->naturalezas = null;
            }else{
                $entity->naturalezasicons = "";
                $entity->naturalezas = "";
                foreach($naturalezas as $natur){
                    if($natur == "6668837"){
                        $entity->naturalezasicons .= '<i class="fa-solid fa-building fa-xl"></i>';
                        $entity->naturalezas .= "Empresa privada, ";
                    }
                    if($natur == "6668838"){
                        $entity->naturalezasicons .= '<i class="fa-solid fa-microscope fa-xl"></i>';
                        $entity->naturalezas .= "Centro Tecnológico, ";
                    }
                    if($natur == "6668839"){
                        $entity->naturalezasicons .= '<i class="fa-solid fa-user-tie fa-xl"></i>';
                        $entity->naturalezas .= "Consultora I+D, ";
                    }
                    if($natur == "6668840"){
                        $entity->naturalezasicons .= '<i class="fa-solid fa-graduation-cap fa-xl"></i>';
                        $entity->naturalezas .= "Universidad, ";
                    }
                    if($natur == "6668841"){
                        $entity->naturalezasicons .= '<i class="fa-solid fa-certificate fa-xl"></i>';
                        $entity->naturalezas .= "Certificadora, ";
                    }
                    if($natur == "6668842"){
                        $entity->naturalezasicons .= '<i class="fa-solid fa-city fa-xl"></i>';
                        $entity->naturalezas .= "Asociación, ";
                    }
                    if($natur == "6668843"){
                        $entity->naturalezasicons .= '<i class="fa-solid fa-landmark fa-xl"></i>';
                        $entity->naturalezas .= "Organismo público, ";
                    }
                }
            }
        }

        $pais = \App\Models\Paises::where('iso2', mb_strtolower($entity->pais))->first();

        if($pais){
            $entity->paisdata = $pais;
        }else{
            $entity->paisdata = null;
        }


        $partenariados = array();
  
        if(userEntidadSelected()){
            if(isset(userEntidadSelected()->simulada) && userEntidadSelected()->simulada == 1 && userEntidadSelected()->simulada == 1 && request()->session()->get('simulando') === true){
                if($entity->CIF != userEntidadSelected()->cif_original){
                    $partenariados = getGraphData($entity->CIF, "ENTITY_WITH_PROJECTS_IN_COMMON", userEntidadSelected()->cif_original, "ALL");
                }else{
                    $partenariados = getGraphData($entity->CIF, "ENTITY_BY_NIF", null, "ALL");  
                }
            }else{
                if($entity->CIF != userEntidadSelected()->CIF){
                    $partenariados = getGraphData($entity->CIF, "ENTITY_WITH_PROJECTS_IN_COMMON", userEntidadSelected()->CIF, "ALL");
                }else{
                    $partenariados = getGraphData($entity->CIF, "ENTITY_BY_NIF", null, "ALL");
                }
            }
        }else{
            $partenariados = getGraphData($entity->CIF, "ENTITY_BY_NIF", null, "ALL");
        }

        $partneriadosnacionales = count(array_filter($partenariados, function($partner) {
            return ($partner->pais == 'ES') ? true : false;
        }, ARRAY_FILTER_USE_BOTH));

        $partneriadosinternacionales = count(array_filter($partenariados, function($partner) {
            return ($partner->pais != 'ES') ? true : false;
        }, ARRAY_FILTER_USE_BOTH));


        $totalinvestigadores = 0;
        $investigadores = getResearchers($entity->CIF, "COMPANY_ID", 1);
        if($investigadores !== null && $investigadores != "ups"){
            if(isset($investigadores->pagination)){
                $totalinvestigadores = $investigadores->pagination->totalItems;
            }
        }

        $totalayudas = 0;
        $totalamount = 0;
        $concesiones = getConcessions($entity->CIF, "COMPANY_ID", 1);
        if($concesiones !== null && $concesiones != "ups"){
            if(isset($concesiones->pagination)){
                $totalayudas = $concesiones->pagination->totalItems;
                foreach($concesiones->data as $concesion){
                    $totalamount += $concesion->Amount;
                }
            }
        }

        $proyectosparticipante = \App\Models\Participantes::where('cif_participante', $entity->CIF)->get();
        $totales = $this->getTotalProyectosByCIF($entity->CIF, $proyectosparticipante);    

        $s3_keywords = null;
        if(Storage::disk('s3_files')->exists('company_keywords/'.$entity->CIF.'.json')){
            $s3_file = Storage::disk('s3_files')->get('company_keywords/'.$entity->CIF.'.json');
            $s3_keywords = json_decode($s3_file, true);
        }


        if(\App::environment() == "prod"){
            Redis::set('single:empresa:uri:'.$uri, $uri, 'EX',  $this->expiration);
            Redis::set('single:empresa:uri:'.$uri.':empresa', json_encode($entity), 'EX',  $this->expiration);
            Redis::set('single:empresa:uri:'.$uri.':data', json_encode($data), 'EX',  $this->expiration);
            Redis::set('single:empresa:uri:'.$uri.':sellopyme', json_encode($sellopyme), 'EX',  $this->expiration);
            Redis::set('single:empresa:uri:'.$uri.':sellostartup', json_encode($sellostartup), 'EX',  $this->expiration);
            Redis::set('single:empresa:uri:'.$uri.':patentes', json_encode($patentes), 'EX',  $this->expiration);
            Redis::set('single:empresa:uri:'.$uri.':totalayudas', $totalayudas, 'EX',  $this->expiration);
            Redis::set('single:empresa:uri:'.$uri.':totalamount', $totalamount, 'EX',  $this->expiration);
            Redis::set('single:empresa:uri:'.$uri.':title', 'Empresa - '.$entity->Nombre, 'EX',  $this->expiration);
            Redis::set('single:empresa:uri:'.$uri.':companynews', json_encode($companynews), 'EX',  $this->expiration);
            Redis::set('single:empresa:uri:'.$uri.':totalproyectos', $totales['totalproyectos'], 'EX',  $this->expiration);
            Redis::set('single:empresa:uri:'.$uri.':totalnacionales', $totales['totalnacionales'], 'EX',  $this->expiration);
            Redis::set('single:empresa:uri:'.$uri.':totaleuropeos', $totales['totaleuropeos'], 'EX',  $this->expiration);
            Redis::set('single:empresa:uri:'.$uri.':totalproyectosrechazados', $totales['totalproyectosrechazados'], 'EX',  $this->expiration);
            Redis::set('single:empresa:uri:'.$uri.':totalinvestigadores', $totalinvestigadores, 'EX',  $this->expiration);
            Redis::set('single:empresa:uri:'.$uri.':partners', json_encode($partenariados), 'EX',  $this->expiration);
            Redis::set('single:empresa:uri:'.$uri.':partneriadosnacionales', $partneriadosnacionales, 'EX',  $this->expiration);
            Redis::set('single:empresa:uri:'.$uri.':partneriadosinternacionales', $partneriadosinternacionales, 'EX',  $this->expiration);
            Redis::set('single:empresa:uri:'.$uri.':s3_keywords', json_encode($s3_keywords), 'EX',  $this->expiration);
        }

        return view('empresa-new',[
            'empresa' => $entity,
            'data' => $data,
            'partners' => $partenariados,
            'sellopyme' => $sellopyme,
            'sellostartup' => $sellostartup,
            'patentes' => $patentes,
            'totalayudas' => $totalayudas,
            'totalamount' => $totalamount,
            'title' => 'Empresa - '.$entity->Nombre,
            'companynews' => $companynews,
            'totalproyectos' => $totales['totalproyectos'],
            'totalnacionales' => $totales['totalnacionales'],
            'totaleuropeos' => $totales['totaleuropeos'],
            'totalproyectosrechazados' => $totales['totalproyectosrechazados'],
            'totalinvestigadores' => $totalinvestigadores,
            'partneriadosnacionales' => $partneriadosnacionales,
            'partneriadosinternacionales' => $partneriadosinternacionales,
            's3_keywords' => $s3_keywords
        ]);
    }

    public function miEmpresa(Request $request)
    {
        // return abort(404);
        if(Auth::guest()){
            return view('auth.login',[]);
        }

        if(!$request->route('uri')){
            return abort(404);
        }

        if(isSuperAdmin()){
            $this->setEmpresa($request);
        }

        if(isAdmin() || isSuperAdmin() || isManager() || isTecnico()){

            $entity = \App\Models\Entidad::where('uri', $request->route('uri'))->first();

            if(!$entity){
                return abort(404);
            }

            if(userEntidadSelected()){

                if(userEntidadSelected()->id != $entity->id){
                    return abort(404);
                }

                if($entity->organo !== null || $entity->departamento !== null){
                    $url = ($entity->organo !== null) ? $entity->organo->url : $entity->departamento->url;
                    return redirect()->route('miorganismo', $url);
                }

                $data = userEntidadSelected()->einforma;
                if(!$data){
                    $data = \App\Models\Einforma::select('einforma.identificativo as cif_ok','einforma.*')
                    ->where('einforma.identificativo', userEntidadSelected()->CIF)->where('lastEditor', 'einforma')->first();
                }

            }

            $entity->paisdata = \App\Models\Paises::where('iso2', $entity->pais)->first();

            if(isset($entity->cantidadImasD)){
                $entity->format_imasd = number_shorten(round($entity->cantidadImasD, -3), 0);
            }

            if(userEntidadSelected()){
                $busquedasguardadas = collect(null);
                $solicitud = \App\Models\PriorizaEmpresas::where('solicitante', userEntidadSelected()->CIF)->where('cifPrioritario', $entity->CIF)->first();
                if(userEntidadSelected()->crearBusquedas == 1){
                    $busquedasguardadas = \App\Models\adHocSearch::where('entidad_id', userEntidadSelected()->id)->orderBy('updated_at', 'DESC')->get();
                }
            }
            
            $ccaas = \App\Models\Ccaa::orderBy('Nombre', 'asc')->get();
            $naturalezas = \App\Models\Naturalezas::where('Activo', 1)->get();
            $cnaes = collect(null);
            if($entity->einforma !== null && $entity->einforma->cnaeEditado != null && $entity->einforma->cnaeEditado != ""){                
                $cnaes = \App\Models\Cnaes::where('Nombre',$entity->einforma->cnae)->get();
            }elseif($entity->einforma !== null && $entity->einforma->cnae != null && $entity->einforma->cnae != ""){
                $cnaes = \App\Models\Cnaes::where('Nombre',$entity->einforma->cnaeEditado)->get();
            }

            $totalinvestigadores = 0;
            $investigadores = getResearchers($entity->CIF, "COMPANY_ID", 1);
            if($investigadores !== null && $investigadores != "ups"){
                if(isset($investigadores->pagination)){
                    $totalinvestigadores = $investigadores->pagination->totalItems;
                }
            }
            $totalayudas = 0;
            $concesiones = getConcessions($entity->CIF, "COMPANY_ID", 1);
            if($concesiones !== null && $concesiones != "ups"){
                if(isset($concesiones->pagination)){
                    $totalayudas = $concesiones->pagination->totalItems;
                }
            }
    
            $organismos = collect(null);
            if(isSuperAdmin() && is_array(json_decode($entity->naturalezaEmpresa, true)) && in_array("6668843",json_decode($entity->naturalezaEmpresa, true))){
                $organos = \App\Models\Organos::all();
                $departamentos = \App\Models\Departamentos::all();
                $organismos = $organos->merge($departamentos);
            }

            $simulaciones = collect(null);
            $pdfscomerciales = collect(null);       

            if($data){
             
                $intereses = \App\Models\Intereses::where('defecto', 'true')->get();
                $textos = array();

                if(Auth::check()){
                    if(isSuperAdmin()){
                        $textos =  \App\Models\TextosElastic::where('CIF', $data->identificativo)->first();
                    }
                }

                if($textos){                
                    if($entity->TextosTecnologia !== null && $entity->TextosTecnologia != ""){
                        $textos->Textos_Tecnologia = $textos->Textos_Tecnologia.",".strip_tags($entity->TextosTecnologia);                    
                    }
                    if($entity->TextosDocumentos !== null && $entity->TexTextosDocumentososTecnologia != ""){
                        $textos->Textos_Documentos = $textos->Textos_Documentos.",".strip_tags($entity->TextosDocumentos);
                    }
                    if($entity->TextosTramitaciones !== null && $entity->TextosTramitaciones != ""){
                        $textos->Textos_Tramitaciones = $textos->Textos_Tramitaciones.",".strip_tags($entity->TextosTramitaciones);
                    }
                    if($entity->TextosProyectos !== null && $entity->TextosProyectos != ""){
                        $textos->Textos_Proyectos = $textos->Textos_Proyectos.",".strip_tags($entity->TextosProyectos);                    
                    }
                }

                $equipo = array();
                $entidad = \App\Models\Entidad::find(userEntidadSelected()->id);
                $equipo = $entidad->users;

                foreach($equipo as $member){
                    $member->role = getRoleById($member->id, userEntidadSelected()->id);
                    $member->departs = $member->userdepartamentos->where('id_entidad', userEntidadSelected()->id);

                }

                $equipo = collect($equipo)->sortBy('role');
               
                $solicitudes = \App\Models\Invitation::where('entidad_id',$entity->id)->get();
                $solicitudesacceso = \App\Models\Notification::where('entity_id',$entity->id)->where('data', '!=', "null")
                ->where('type','access_request')->whereJsonDoesntContain('data', null)
                ->leftJoin('users', 'users.id', '=', 'notifications.data->user_id')->get();
                $totallineas = 0;

                if($entity->TextosLineasTec){
                    if(!empty(array_filter(json_decode($entity->TextosLineasTec,true)))){
                        foreach(json_decode($entity->TextosLineasTec) as $lineas){
                            $texto = explode(",", $lineas ?? '');
                            if($texto[0] != "" && $texto[0] !== null){
                                $totallineas += count($texto);
                            }
                        }
                    }
                }

                if($solicitudesacceso){
                    foreach($solicitudesacceso as $key => $acceso){
                        $datos = json_decode($acceso->data,true);
                        if($datos){
                            if($datos['aceptadorechazado'] > 0){
                                unset($solicitudesacceso[$key]);
                                continue;
                            }
                        }
                    }
                }

                $einformas = \App\Models\Einforma::where('identificativo', $entity->CIF)->orderByDesc('anioBalance')->get();
                $isEinformaManual = \App\Models\Einforma::where('identificativo', $entity->CIF)->where('lastEditor', '!=', 'einforma')->where('anioBalance', Carbon::now()->format('Y'))->count();
                $lasteinformadate = null;
                if($einformas){
                    $lasteinformadate = collect($einformas)->sortByDesc('ultimaActualizacion')->pluck('ultimaActualizacion')->first();
                }

                $totalparticipantes = 0;
                $partners = getGraphData($entity->CIF, "ENTITY_BY_NIF", null, "ALL");
                if(!empty($partners) && is_array($partners)){
                    $totalparticipantes = count($partners);
                }       

                $simulaciones = \App\Models\EntidadesSimuladas::where('creator_id', userEntidadSelected()->id)->where('created_at', '>=', Carbon::now()->subDays(60))->orderByDesc('created_at')->paginate(50);

                foreach($simulaciones as $simulacion){
                    $simulacion->FechaGroupBy = Carbon::parse($simulacion->created_at)->format('d-m-Y');
                }

                $companynews = \App\Models\CompanyNews::where('company_id', $entity->CIF)->first();
                $entity->mostrarUltimasNoticias = 0;
                if($companynews !== null){
                    $entity->mostrarUltimasNoticias = $companynews->mostrar;
                }

                $proyectosparticipante = \App\Models\Participantes::where('cif_participante', $entity->CIF)->get();
                $totales = $this->getTotalProyectosByCIF($entity->CIF, $proyectosparticipante);

                $patentes = new stdClass;
                $patentes->data = collect(null); 
                $patentes->data = \App\Models\Patentes::where('CIF', $entity->CIF)->orderByDesc('Fecha_publicacion')->take(20)->get();
                if(empty($patentes->data) || $patentes->data->empty()){
                    $patentes->total = \App\Models\Concessions::where('custom_field_cif', $entity->CIF)->where('id_organo', 650)->count();
                    if($patentes->total > 0){
                        $patentes->esorgano = true;
                    }
                }

                $pdfscomerciales = collect(null);
                if($entity->upload_pdfs == 1){
                    $pdfscomerciales = \App\Models\EntidadesPdfComerciales::where('entidad_id', $entity->id)->orderBy('updated_at', 'DESC')->get();
                }

                $analisis360 = \App\Models\Analisis360::where('entidad_creator_id', $entity->id)->orderByDesc('updated_at')->paginate(20);
                $entitydepartamentos = \App\Models\EntidadesDepartamentos::where('id_entidad', $entity->id)->get();
                $chatgptareas = \App\Models\Areas::get(['id','Nombre'])->sortBy('Nombre');
            
                $tags = null;                         
                if($entity->TextosLineasTec !== null && $entity->TextosLineasTec !== null){
                    foreach(json_decode($entity->TextosLineasTec, true) as $tag){                       
                        $tags .= $tag.",";                            
                    }
                }
                if($entity->keywords !== null && !empty($entity->keywords->keywords)){
                    $chatgptkeywords =  json_decode($entity->keywords->keywords,true);            
                    if(isset($chatgptkeywords['keywords']) && !empty($chatgptkeywords['keywords'])){
                        foreach($chatgptkeywords['keywords'] as $key => $keyword){
                                $tags .= $keyword.",";    
                        }
                    }
                }

                $s3_file = null;
                if(Storage::disk('s3_files')->exists('company_keywords/'.$entity->CIF.'.json')){
                    $s3_file = Storage::disk('s3_files')->get('company_keywords/'.$entity->CIF.'.json');
                }
                
                $tagsareas = array(); 
                if($s3_file !== null && $s3_file != ""){
                    $s3_keywords = json_decode($s3_file, true);
                    if(!empty($s3_keywords)){                                                                      
                        $s3_areas = array_filter($s3_keywords, function ($var) {
                            return ($var['area'] == 1);
                        });
                        $tagsareas = array_column($s3_areas, 'keyword');
                    }
                }

                return view('mycompany-new',[
                    'empresa' => $entity,
                    'data' => $data,
                    'totalayudas' => $totalayudas,
                    'intereses' => $intereses,
                    'textos' => $textos,
                    'patentes' => $patentes,
                    'ccaas' => $ccaas,
                    'cnaes' => $cnaes,
                    'totalproyectos' => $totales['totalproyectos'],
                    'totalnacionales' => $totales['totalnacionales'],
                    'totaleuropeos' => $totales['totaleuropeos'],
                    'totalproyectosrechazados' => $totales['totalproyectosrechazados'],
                    'totalparticipantes' => $totalparticipantes,
                    'equipo' => $equipo,
                    'naturalezas' => $naturalezas,
                    'solicitudes' => $solicitudes,
                    'solicitudesacceso' => $solicitudesacceso,
                    'totallineas' => $totallineas,
                    'einformas' => $einformas,
                    'lasteinforma' => $lasteinformadate,
                    'currentmanual' => $isEinformaManual,
                    'totalinvestigadores' => $totalinvestigadores,
                    'organismos' => $organismos,
                    'busquedasguardadas' => $busquedasguardadas,
                    'simulaciones' => $simulaciones,
                    'pdfscomerciales' => $pdfscomerciales,
                    'analisis360' => $analisis360,
                    'entitydepartamentos' => $entitydepartamentos,
                    'chatgptareas' => $chatgptareas,
                    'tags' => $tags,
                    'tagsareas' => $tagsareas,
                ]);
            }else{

                $naturalezas = \App\Models\Naturalezas::where('Activo', 1)->get();
                $ccaas = \App\Models\Ccaa::get();
                $cnaes = \App\Models\Cnaes::get();
                $intereses = \App\Models\Intereses::where('defecto', 'true')->get();

                $solicitudes = \App\Models\Invitation::where('entidad_id',$entity->id)->get();
                $solicitudesacceso = \App\Models\Notification::where('entity_id',$entity->id)->where('data', '!=', "null")
                ->where('type','access_request')->whereJsonDoesntContain('data', null)
                ->leftJoin('users', 'users.id', '=', 'notifications.data->user_id')->get();

                $equipo = array();
                if(Auth::check()){
                    $entidad = \App\Models\Entidad::find($entity->id);
                    $equipo = $entidad->users;
                }

                foreach($equipo as $member){
                    $member->role = getRoleById($member->id, userEntidadSelected()->id);
                }

                $equipo = collect($equipo)->sortBy('role');

                $totallineas = 0;

                if($entity->TextosLineasTec){
                    if(!empty(array_filter(json_decode($entity->TextosLineasTec,true)))){
                        foreach(json_decode($entity->TextosLineasTec) as $lineas){
                            $texto = explode(",", $lineas);
                            if($texto[0] != "" && $texto[0] !== null){
                                $totallineas += count($texto);
                            }
                        }
                    }
                }

                $einformas = \App\Models\Einforma::where('identificativo', $entity->CIF)->orderByDesc('anioBalance')->get();
                $isEinformaManual = \App\Models\Einforma::where('identificativo', $entity->CIF)->where('lastEditor', '!=', 'einforma')->where('anioBalance', Carbon::now()->format('Y'))->count();
                $lasteinformadate = null;
                if($einformas){
                    $lasteinformadate = collect($einformas)->sortByDesc('ultimaActualizacion')->pluck('ultimaActualizacion')->first();
                }

                $totalparticipantes = 0;
                $partners = getGraphData($entity->CIF, "ENTITY_BY_NIF", null, "ALL");
                if(!empty($partners) && is_array($partners)){
                    $totalparticipantes = count($partners);
                }       

                $companynews = \App\Models\CompanyNews::where('company_id', $entity->CIF)->first();
                $entity->mostrarUltimasNoticias = 0;
                if($companynews !== null){
                    $entity->mostrarUltimasNoticias = $companynews->mostrar;
                }

                $proyectosparticipante = \App\Models\Participantes::where('cif_participante', $entity->CIF)->get();
                $totales = $this->getTotalProyectosByCIF($entity->CIF, $proyectosparticipante);

                $patentes = new stdClass;
                $patentes->data = collect(null); 
                $patentes->data = \App\Models\Patentes::where('CIF', $entity->CIF)->orderByDesc('Fecha_publicacion')->take(20)->get();
                if(empty($patentes->data) || $patentes->data->empty()){
                    $patentes->total = \App\Models\Concessions::where('custom_field_cif', $entity->CIF)->where('id_organo', 650)->count();
                    if($patentes->total > 0){
                        $patentes->esorgano = true;
                    }
                }

                $pdfscomerciales = collect(null);        
                if($entity->upload_pdfs == 1){
                    $pdfscomerciales = \App\Models\EntidadesPdfComerciales::where('entidad_id', $entity->id)->orderBy('updated_at', 'DESC')->get();
                }

                $analisis360 = \App\Models\Analisis360::where('entidad_creator_id', $entity->id)->orderByDesc('updated_at')->paginate(20);

                $entitydepartamentos = \App\Models\EntidadesDepartamentos::where('id_entidad', $entity->id)->get();

                $chatgptareas = \App\Models\Areas::get(['id','Nombre'])->sortBy('Nombre');

                $tags = null;                                      
                if($entity->TextosLineasTec !== null && $entity->TextosLineasTec !== null){
                    foreach(json_decode($entity->TextosLineasTec, true) as $tag){                       
                        $tags .= $tag.",";                            
                    }
                }
                if($entity->keywords !== null && !empty($entity->keywords->keywords)){
                    $chatgptkeywords =  json_decode($entity->keywords->keywords,true);            
                    if(isset($chatgptkeywords['keywords']) && !empty($chatgptkeywords['keywords'])){
                        foreach($chatgptkeywords['keywords'] as $key => $keyword){
                                $tags .= $keyword.",";    
                        }
                    }
                }

                $tagsareas = array();  
                if($entity->keywords !== null && !empty($entity->keywords->keywords)){               
                    $chatgptkeywords =  json_decode($entity->keywords->keywords,true);                                                           
                    if(isset($chatgptkeywords['areas']) && !empty($chatgptkeywords['areas'])){
                        foreach($chatgptkeywords['areas'] as $area){                            
                            $tagsareas[] = $area;                                     
                        }
                    }
                }

                return view('mycompany',[
                    'empresa' => $entity,
                    'data' => null,
                    'intereses' => $intereses,
                    'textos' => null,
                    'ccaas' => $ccaas,
                    'patentes' => $patentes,
                    'cnaes' => $cnaes,
                    'totalproyectos' => $totales['totalproyectos'],
                    'totalnacionales' => $totales['totalnacionales'],
                    'totaleuropeos' => $totales['totaleuropeos'],
                    'totalproyectosrechazados' => $totales['totalproyectosrechazados'],
                    'totalparticipantes' => $totalparticipantes,
                    'equipo' => $equipo,
                    'naturalezas' => $naturalezas,
                    'solicitudes' => $solicitudes,
                    'solicitudesacceso' => $solicitudesacceso,
                    'prioriza_empresa' => $solicitud,
                    'totallineas' => $totallineas,
                    'einformas' => $einformas,
                    'lasteinforma' => $lasteinformadate,
                    'currentmanual' => $isEinformaManual,
                    'totalinvestigadores' => $totalinvestigadores,
                    'organismos' => $organismos,
                    'busquedasguardadas' => $busquedasguardadas,
                    'simulaciones' => $simulaciones,
                    'pdfscomerciales' => $pdfscomerciales,
                    'analisis360' => $analisis360,
                    'entitydepartamentos' => $entitydepartamentos,
                    'chatgptareas' => $chatgptareas,
                    'tags' => $tags,
                    'tagsareas' => $tagsareas,
                ]);
            }
        }
    }

    public function addInvitation(Request $request)
    {
        $entidadInfo = \App\Models\Entidad::where('uri',$request->uri)->first();

        if($entidadInfo){

            if($request->get('resend') !== null && $request->get('resend') == 1){
                $entityName = $entidadInfo->Nombre;
                $registerEmail = $request->email;         
                $existInvitation = \App\Models\Invitation::where('email',$request->email)->where('entidad_id',$entidadInfo->id)->first();
                $existInvitation->max_resends = $existInvitation->max_resends +1;
                $existInvitation->save();
                $roleName = getRoleNameByKey($request->rol);       
                $message = '<b>'.Auth::user()->email.'</b> te ha invitado a <b>'.$entidadInfo->Nombre.'</b> como <b>'.$roleName.'</b>';
                $mail = new \App\Mail\InvitationRegisterMail($message, $entityName, $registerEmail, Auth::user()->name);
                Mail::to($request->email)->queue($mail);
                return redirect()->route('miempresa',['uri'=> $entidadInfo->uri])->withSuccess('Invitación reenviada correctamente.');
            }
            //miro si existe el usuario
            $existUser = \App\Models\User::where('email',$request->email)->first();
            if($existUser){
                //miro si el usuario ya esta en esta empresa....
                $existUserEntidad = \App\Models\UsersEntidad::where('users_id',$existUser->id)->where('entidad_id',$entidadInfo->id)->first();
                if($existUserEntidad === NULL){

                    $totalempresas = \App\Models\UsersEntidad::where('users_id', $existUser->id)->count();

                    if($totalempresas == 0){
                        $mail = new \App\Mail\MyFirstCompany($request->uri);
                        Mail::to($existUser->email)->queue($mail);
                    }

                    $userEntidad = new \App\Models\UsersEntidad();
                    $userEntidad->users_id = $existUser->id;
                    $userEntidad->entidad_id = $entidadInfo->id;
                    $userEntidad->role = $request->rol;
                    $userEntidad->save();

                    //Enviar notificaciones a los admins de la empresa
                    $roleName = getRoleNameByKey($request->rol);

                    $message = '<b>'.$request->email.'</b> forma parte del equipo de <b>'.$entidadInfo->Nombre.'</b> como <b>'.$roleName.'</b>. Invitado por <b>'.Auth::user()->email.'</b>';
                    $usersAdmin = \App\Models\UsersEntidad::where('entidad_id',$entidadInfo->id)->where('role','admin')->get();
                    if($usersAdmin->isNotEmpty()){
                        $subject = "Nuevo miembro en el equipo";
                        foreach ($usersAdmin as $userAdm) {
                            $notification = new \App\Models\Notification();
                            $notification->sendNotification('invitation_accepted',$message,$userAdm->users_id, $entidadInfo->id);
                            $admin = \App\Models\User::find($userAdm->users_id);
                            $mail = new \App\Mail\NewTeamMember($message, $subject);
                            Mail::to($admin->email)->queue($mail);
                        }
                    }

                    //por último envio notificacion al usuario receptor
                    $message = '<b>'.Auth::user()->email.'</b> te ha invitado a <b>'.$entidadInfo->Nombre.'</b> como <b>'.$roleName.'</b>';
                    $notification = new \App\Models\Notification();
                    $notification->sendNotification('invitation_receptor',$message,$existUser->id);

                    //TODO: enviar Mail
                    $entityName = $entidadInfo->Nombre;
                    $registerEmail = $request->email;
                    $mail = new \App\Mail\InvitationRegisterMail($message, $entityName, $registerEmail, Auth::user()->name);
                    Mail::to($request->email)->queue($mail);

                    if(!isSuperAdmin()){
                        $messageTg = Auth::user()->email.' ha invitado a '.$entidadInfo->Nombre.' como '.$roleName.' al usuario: '.$registerEmail;
                        try{
                            Artisan::call('send:telegram_notification', [
                                'message' =>  $messageTg
                            ]);
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                        }
                    }
                }

            }else{
                //TODO: Enviar invitación
                //Usuario no registrado. Primero miro si ya existe una invitacion
                $existInvitation = \App\Models\Invitation::where('email',$request->email)->where('entidad_id',$entidadInfo->id)->first();
                if($existInvitation){
                    //ya existe, la actualizo el role
                    $existInvitation->role = $request->rol;
                    $existInvitation->save();
                }else{

                    $invitation = new  \App\Models\Invitation;
                    $invitation->email = $request->email;
                    $invitation->entidad_id = $entidadInfo->id;
                    $invitation->role = $request->rol;
                    $invitation->save();

                    $roleName = getRoleNameByKey($request->rol);

                    //Enviamos notificacion a todos los admins de la empresa para informar de que se ha invitado
                    $message = '<b>'.Auth::user()->email.'</b> ha invitado a <b>'.$request->email.'</b> a <b>'.$entidadInfo->Nombre.'</b> como <b>'.$roleName.'</b>';
                    $usersAdmin = \App\Models\UsersEntidad::where('entidad_id',$entidadInfo->id)->where('role','admin')->get();
                    if($usersAdmin->isNotEmpty()){
                        $subject = "Invitación al equipo enviada";
                        foreach ($usersAdmin as $userAdm) {
                            $notification = new \App\Models\Notification();
                            $admin = \App\Models\User::find($userAdm->users_id);
                            $notification->sendNotification('invitation_team',$message,$userAdm->users_id,$entidadInfo->id);
                            $mail = new \App\Mail\NewTeamMember($message, $subject);
                            Mail::to($admin->email)->queue($mail);
                        }
                    }
                    //TODO: enviar Mail
                    $entityName = $entidadInfo->Nombre;
                    $registerEmail = $request->email;
                    $message = '<b>'.Auth::user()->email.'</b> te ha invitado a <b>'.$entidadInfo->Nombre.'</b> como <b>'.$roleName.'</b>';
                    $mail = new \App\Mail\InvitationRegisterMail($message, $entityName, $registerEmail, Auth::user()->name);
                    Mail::to($request->email)->queue($mail);

                    if(!isSuperAdmin()){
                        $messageTg = Auth::user()->email.' ha invitado a '.$entidadInfo->Nombre.' como '.$roleName.' al usuario: '.$registerEmail;
                        try{
                            Artisan::call('send:telegram_notification', [
                                'message' =>  strip_tags($messageTg)
                            ]);
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                        }
                    }

                }
            }

        }
        return redirect()->route('miempresa',['uri'=> $entidadInfo->uri])->withSuccess('Invitación enviada correctamente.');
    }

    public function setEmpresa(Request $request)
    {

        if(!$request->route('uri')){
            return redirect("/");
        }

        $emp = \App\Models\Entidad::where('uri', $request->route('uri'))->first();

        if($emp){

            $entidad = \App\Models\Entidad::find($emp->id);

            if(Auth::check()){
                if(isSuperAdmin()){
                    request()->session()->put('user_entidad_id', $emp->id);
                }
                if(isTecnico() || isManager() || isAdmin()){
                    foreach($entidad->users as $user){
                        if(Auth::user()->id == $user->id){
                            $userEntidadInfo = \App\Models\UsersEntidad::where('users_id', Auth::user()->id)->where('entidad_id',$emp->id)->first();
                            if($userEntidadInfo){
                                request()->session()->put('user_entidad_id', $userEntidadInfo->id);
                            }
                        }
                    }
                }
            }

            if(strpos(url()->previous(), "empresa/") || strpos(url()->previous(), "my-company/")
                || strpos(url()->previous(), "notificaciones/") || strpos(url()->previous(), "mensajes/") || strpos(url()->previous(), "oportunidades/")){
                return redirect()->route('empresa', [$emp->uri]);
            }else{
                return redirect(url()->previous());
            }
        }

        return redirect('/');

    }

    public function removeEmpresa(Request $request)
    {

        $request->session()->forget('user_entidad_id');

        $route = app('router')->getRoutes(URL::previous())->match(app('request')->create(URL::previous()))->getName();

        if($route == "empresa" || $route == "ayudasimple" || $route == "ayudas"){
            return redirect()->back();
        }

        return redirect()->route('index');
    }

    public function calculaImasD(Request $request)
    {

        $cif = $request->get('cif');

        if(!$cif){
            return abort(419);
        }

        try{
            Artisan::call('calcula:I+D', [
                'cif' =>  $cif
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se podido calcular el i+d de la empresa');
        }

        try{
            Artisan::call('calcula:company_news', [
                'cif' =>  $cif
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());            
        }

        try{
            Artisan::call('check:companies_spi', [
                'cif' =>  $cif
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
        }

        try{
            Artisan::call('elastic:companies', [
                'cif' =>  $cif
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('Error en el envío de los nuevos datos a elastic');
        }

        return redirect()->back()->withSuccess('Actualizado el TRL y el I+D de la empresa, mandados los datos a elastic');
    }

    public function calculaNivelCooperacion(Request $request)
    {

        $cif = $request->get('cif');

        if(!$cif){
            return abort(419);
        }

        try{
            Artisan::call('calcula:nivel_cooperacion', [
                'cif' =>  $cif
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se podido calcular el nivel de cooperación de la empresa');
        }

        try{
            Artisan::call('calcula:total_participantes', [
                'cif' =>  $cif
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se podido calcular el nivel de cooperación de la empresa');
        }

        return redirect()->back()->withSuccess('Actualizado el nivel de cooperación de la empresa');
    }

    public function createPrioridad(Request $request)
    {

        if(userEntidadSelected()){
            $check = \App\Models\PriorizaEmpresas::where('solicitante', userEntidadSelected()->CIF)->where('cifPrioritario', $request->get('prioriza'))->first();
        }else{
            $check = \App\Models\PriorizaEmpresas::where('cifPrioritario', $request->get('prioriza'))->first();
        }
        //$cif = \App\Models\CifsNoZoho::where('CIF', $request->get('prioriza'))->first();

        if(!$check){

            if($request->get('esorgano') == 1){
                try{
                    \App\Models\PriorizaEmpresas::insert([
                        'solicitante' => Auth::user()->email,
                        'sacadoEinforma' => 0,
                        'esOrgano' => 1,
                        'idOrgano' => $request->get('prioriza'),
                        'created_at' => Carbon::now()
                    ]);
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return false;
                }

                $message = "El usuario ".Auth::user()->email." de la empresa ".userEntidadSelected()->Nombre." ha solicitado priorizar el siguiente Id de organo/departamento:".$request->get('prioriza');

                try{
                    Artisan::call('send:telegram_notification', [
                        'message' =>  $message
                    ]);
                }catch(Exception $e){
                    Log::error($e->getMessage());
                }

            }else{

                try{
                    \App\Models\PriorizaEmpresas::insert([
                        'solicitante' => Auth::user()->email,
                        'cifPrioritario' => $request->get('prioriza'),
                        'sacadoEinforma' => 0,
                        'created_at' => Carbon::now()
                    ]);
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return false;
                }

                $message = "El usuario ".Auth::user()->email." de la empresa ".userEntidadSelected()->Nombre." ha solicitado priorizar el siguiente CIF: ".$request->get('prioriza')." como usuario superadmin en gestion de empresas -> priorizar empresas puedes ver esta acción";

                try{
                    Artisan::call('send:telegram_notification', [
                        'message' =>  $message
                    ]);
                }catch(Exception $e){
                    Log::error($e->getMessage());
                }

            }

        }

        return true;
    }

    public function solicitudAcceso(Request $request)
    {

        try{
            $file = $request->file('documento');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('uploadfiles'), $filename);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(['msg' => 'Error 1 al intentar subir el archivo, intentelo de nuevo pasado unos minutos.']);
        }

        $checkvalidte = ValidateCompany::where('user_id', Auth::user()->id)->where('cif', $request->get('cif'))->first();

        if(!$checkvalidte){

            try{

                $validate = new ValidateCompany;
                $validate->user_id = Auth::user()->id;
                $validate->cif = $request->get('cif');
                $validate->doc = $filename;
                $validate->esEntidad = $request->get('esempresa');
                $validate->save();

                $notification = new \App\Models\Notification();
                $message = 'Hemos registrado tu petición de validar la empresa con CIF: <b>'.$request->get('cif').'</b>';
                $notification->sendNotification('invitation_accepted',$message,Auth::user()->id);
                $subject = 'Solicitud de validación: '.$request->get('cif');
                $mail = new \App\Mail\NewTeamMember($message, $subject);
                Mail::to(Auth::user()->email)->queue($mail);
                $subject = 'Solicitud de validación: '.$request->get('cif');
                $mail = new \App\Mail\NewTeamMember($message, $subject);
                Mail::to('info@innovating.works')->queue($mail);

            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors(['msg' => 'Error 2 al intentar subir el archivo, intentelo de nuevo pasado unos minutos.']);
            }

            try{
                $message = "El usuario: ".Auth::user()->email." ha solicitado la validación de una compañía con el cif: ".$request->get('cif')." como usuario superadmin en gestion de empresas -> validar empresas puedes ver esta acción";
                Artisan::call('send:telegram_notification', [
                    'message' =>  $message
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
            }

        }else{
            return redirect()->back()->with('message', 'Ya tienes una solicitud pendiente para ese CIF. Pronto tendrás noticias :)');
        }

        return redirect()->back()->with('message', 'Solicitud añadida con exito. Pronto tendrás noticias :)');
    }

    public function updateDocumentacion(Request $request)
    {
        try{
            $file = $request->file('documento');
            if($file !== null){
                $validator = Validator::make($request->all(), [
                    'documento' => 'required|max:5120',
                ]);
        
                if($validator->fails()){                    
                    return redirect()->back()->withErrors('Error al intentar subir el archivo, no puede ser de un tamaño mayor a 5MB y con formato distinto a .pdf, .jpg o .png');
                }
                $file->storeAs('cuentas', $file->getClientOriginalName(), ['disk' => 'documentoscuentas']);                                

                $validarcuentas = new \App\Models\ValidacionCuentas();
                $validarcuentas->user_id = Auth::user()->id;
                $validarcuentas->entidad_id = userEntidadSelected()->id;
                $validarcuentas->filename = $file->getClientOriginalName();
                $validarcuentas->aceptado = 0;
                $validarcuentas->upload_at = Carbon::now();
                $validarcuentas->save();

                $message = "El usuario: ".Auth::user()->email." ha solicitado la validación de sus cuentas por archivo adjunto: ".$file->getClientOriginalName()." para la empresa: ".userEntidadSelected()->Nombre;
                Artisan::call('send:telegram_notification', [
                    'message' =>  $message
                ]);

            }
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('Error al intentar subir el archivo, intentelo de nuevo pasado unos minutos.');
        }

        return redirect()->back()->withSuccess('El archivo se ha subido correctamente. Pronto tendrás noticias :)');
    }

    public function empresaConcesiones(Request $request)
    {
        $uri = $request->route('uri');
        if($uri === null){
            return abort(404);
        }

        $uri = $request->route('uri');

        if($request->get('page') === null || $request->get('page') < 1){
            $page = 1;
        }else{
            $page = $request->get('page');
        }

        if($page > 1 && Auth::guest()){
            return redirect()->route('register')->withErrors('Necesitas registrarte para poder acceder a esta información. Es gratis!');
        }

        $entity = \App\Models\Entidad::where('uri', $uri)->first();
        if($entity){
            $entity->eszoho = 0;
        }
        if(!$entity){
            $entity = \App\Models\CifsNoZoho::where('uri', $uri)->where('movidoEntidad', 0)->first();
            if($entity){
                $entity->eszoho = 1;
            }
        }

        if(!$entity){
            return abort(404);
        }

        if($entity->organo !== null || $entity->departamento !== null){
            $url = ($entity->organo !== null) ? $entity->organo->url : $entity->departamento->url;
            return redirect()->route('miorganismo', $url);
        }

        $data = null;

        if($entity){
            $data = $entity->einforma;
        }

        if(!$data){
            if(!$entity){

                return view('empresa-noeinforma',[
                    'empresa' => null,
                    'data' => null,
                    'nodata' => 1,
                    'ayudas' => null,
                    'totalayudas' => 0
                ]);
            }
        }

        $ayudas = collect(null);
        $totalayudas = 0;
        $concesiones = getConcessions($entity->CIF, "COMPANY_ID", $page);
        if($concesiones !== null && $concesiones != "ups"){
            if(isset($concesiones->pagination)){
                $totalayudas = $concesiones->pagination->totalItems;
            }
            if(isset($concesiones->data) && isset($concesiones->pagination)){
                $url = request()->fullUrl();
                if(strripos(request()->fullUrl(),"?page=") !== false){
                    $url = substr(request()->fullUrl(),0, strripos(request()->fullUrl(),"?page="));
                }
                $ayudas = custompaginate($concesiones->data, 20, $page, $url, [], $concesiones->pagination->totalItems);
            }
        }

        return view('empresa.concesiones-new',[
            'empresa' => $entity,
            'data' => $data,
            'ayudas' => $ayudas,
            'totalayudas' => $totalayudas,
        ]);

    }

    public function empresaProyectos(Request $request)
    {
        $uri = $request->route('uri');
        if($uri === null){
            return abort(404);
        }

        $uri = $request->route('uri');

        if($request->get('page') === null || $request->get('page') < 1){
            $page = 1;
        }else{
            $page = $request->get('page');
        }

        if($page > 1 && Auth::guest()){
            return redirect()->route('register')->withErrors('Necesitas registrarte para poder acceder a esta información. Es gratis!');
        }

        $entity = \App\Models\Entidad::where('uri', $uri)->first();
        if($entity){
            $entity->eszoho = 0;
        }
        if(!$entity){
            $entity = \App\Models\CifsNoZoho::where('uri', $uri)->where('movidoEntidad', 0)->first();
            if($entity){
                $entity->eszoho = 1;
            }
        }

        if(!$entity){
            return abort(404);
        }

        if($entity->organo !== null || $entity->departamento !== null){
            $url = ($entity->organo !== null) ? $entity->organo->url : $entity->departamento->url;
            return redirect()->route('miorganismo', $url);
        }

        $data = null;

        if($entity){
            $data = $entity->einforma;
        }

        if(!$data){
            if(!$entity){

                $totalproyectos = 0;

                return view('empresa-noeinforma',[
                    'empresa' => null,
                    'data' => null,
                    'nodata' => 1,
                    'totalproyectos' => $totalproyectos,
                    'totalnacionales' => 0,
                    'totaleuropeos' => 0,
                    'totalproyectosrechazados' => 0,
                    'title' => "Empresa no encontrada"
                ]);
            }
        }

        $status = $request->get('status');

        $proyectosdata = Cache::remember('empresa_proyectos_'.$entity->CIF.'_'.$page.'_'.$status, now()->addHours(12), function () use($entity, $page, $status) {

            $proyectos = \App\Models\Proyectos::where('empresaPrincipal', $entity->CIF)->orderByDesc('Fecha')->orderByDesc('inicio')->get();        
            $participantes = \App\Models\Participantes::where('cif_participante', $entity->CIF)->get();            
            foreach($participantes as $participante){
                if($participante->proyecto !== null){
                    $proyectos->push($participante->proyecto);    
                }
            } 
            
            $proyectos = $proyectos->sortBy('inicio', SORT_DESC, true)->sortBy('Fecha', SORT_DESC, true);
        
            $proyectosreturn = collect(null);

            if($status === null || $status === "nacional"){
                $proyectosreturn = collect($proyectos)->where('esEuropeo', 0);    
            }elseif($status !== null && $status === "europeo"){
                $proyectosreturn = collect($proyectos)->where('esEuropeo', 1);    
            }elseif($status !== null && $status === "declined"){
                $proyectosreturn = collect($proyectos)->where('Estado', 'Desestimado');    
            }

            if($status === null || $status === "nacional"){
                $projects = null;

                if(userEntidadSelected()){
                    if(userEntidadSelected()->CIF != $entity->CIF){
                        $projects = getElasticAyudas(userEntidadSelected()->CIF, 'proyecto');
                    }
                }

                foreach($proyectosreturn as $key => $proyecto){

                    if($proyecto->esAnonimo == 1){
                        unset($proyectosreturn[$key]);
                        continue;
                    }

                    $encajes = \App\Models\Encaje::where('Proyecto_id', $proyecto->id)->get();

                    $proyecto->cooperacion = 0;
                    $proyecto->subcontrata = 0;
                    $proyecto->consultoria = 0;

                    foreach($encajes as $encaje){

                        if($encaje->tipoPartner == "Cooperación"){
                            $proyecto->cooperacion = 1;
                        }
                        if($encaje->tipoPartner == "Subcontratación"){
                            $proyecto->subcontrata = 1;
                        }
                        if($encaje->tipoPartner == "Consultoría"){
                            $proyecto->consultoria = 1;
                        }
                    }

                    $proyecto->AyudaAcronimo = null;
                    if($proyecto->IdAyuda){
                        $ayuda =  \App\Models\Ayudas::where('id', $proyecto->IdAyuda)->select('Acronimo', 'Titulo')->first();
                        if($ayuda->Acronimo){
                            $proyecto->AyudaAcronimo = $ayuda->Acronimo;
                        }else{
                            $proyecto->AyudaAcronimo = $ayuda->Titulo;
                        }
                    }

                    $proyecto->empresaNombre = $entity->Nombre;
                    $proyecto->empresaUri = $entity->uri;
                    $proyecto->score = -10;

                    if($projects && $projects != "ups"){

                        if(isset($encaje)){
                            if($encaje->tipoPartner != "Consultoría"){
                                $key = array_search($encaje->id, array_column($projects, 'Encaje_id'));
                                if($key !== false){
                                    if($key == 0){
                                        $key = 1;
                                    }
                                    if($key > count($projects)){
                                        $key = count($projects);
                                    }

                                    if(isset($projects[$key])){
                                        $proyecto->score = $projects[$key]->score;
                                    }
                                }else{
                                    foreach($projects as $key => $project){
                                        if($key == "totalpages"){
                                            continue;
                                        }
                                        if($proyecto->Proyecto_id == $project->id){
                                            $proyecto->score = $project->score;
                                        }
                                    }
                                }
                            }else{
                                if(isset($consultorias)){
                                    foreach($consultorias as $el){
                                        if(isset($el->Encaje_id)){
                                            if($encaje->id == $el->Encaje_id){
                                                $proyecto->score = $el->score;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if(Auth::check() && userEntidadSelected()){
                        if(userEntidadSelected()->CIF != $proyecto->empresaPrincipal){
                            if($proyecto->Tipo == "privado" && isset($proyecto->score) && $proyecto->score < 0){
                                unset($proyectosreturn[$key]);
                                continue;
                            }
                        }
                    }                    
                }
            }

            $url = request()->fullUrl();
            if(strripos(request()->fullUrl(),"?page=") !== false){
                $url = substr(request()->fullUrl(),0, strripos(request()->fullUrl(),"?page="));
            }

            if($status !== null && ($status === "closed" || $status == "declined")){
                $proyectosparticipante = collect(null);
                $participante = \App\Models\Participantes::where('cif_participante', $entity->CIF)->get();

                if($participante->isNotEmpty()){
                    $idsproyectos = array();
                    if($status === "closed"){
                        if($proyectosreturn->isNotEmpty()){
                            $idsproyectos = $proyectosreturn->pluck('id')->toArray();
                        }
                    }
                    if($status == "declined"){
                        if($proyectosreturn->isNotEmpty()){
                            $idsproyectos = $proyectosreturn->pluck('id')->toArray();
                        }
                    }                    
                    foreach($participante as $proyectoparticipante){
                        if(!in_array($proyectoparticipante->proyecto->id, $idsproyectos)){
                            $proyectosparticipante->push($proyectoparticipante->proyecto);
                            if($proyectoparticipante->proyecto->Estado == "Cerrado" && $status === "closed"){
                                $proyectosreturn->push($proyectoparticipante->proyecto);
                            }
                            if($proyectoparticipante->proyecto->Estado == "Desestimado" && $status === "declined"){
                                $proyectosreturn->push($proyectoparticipante->proyecto);
                            }
                        }
                    }
                    $proyectosreturn = $proyectosreturn->sortByDesc('inicio')->sortByDesc('Fecha')->values();
                }

            }

            if($proyectosreturn === null){
                $proyectosreturn = collect(null);
            }

            return [
                'proyectos' => custompaginate($proyectosreturn->sortByDesc('inicio')->sortByDesc('Fecha'), 20, $page, $url),
                //'proyectosparticipante' => custompaginate($proyectosparticipante, 20, $page, $url)
            ];
        });

        
        $proyectosparticipante = \App\Models\Participantes::where('cif_participante', $entity->CIF)->get();
        $totales = $this->getTotalProyectosByCIF($entity->CIF, $proyectosparticipante);

        return view('empresa.proyectos-new',[
            'empresa' => $entity,
            'data' => $data,
            'totalproyectos' => $totales['totalproyectos'],
            'totalnacionales' => $totales['totalnacionales'],
            'totaleuropeos' => $totales['totaleuropeos'],
            'totalproyectosrechazados' => $totales['totalproyectosrechazados'],
            'proyectos' => $proyectosdata['proyectos'],
            //'proyectosparticipante' => $proyectosdata['proyectosparticipante'],
        ]);

    }

    public function empresaPartenariados(Request $request)
    {

        $uri = $request->route('uri');
        if($uri === null){
            return abort(404);
        }

        $uri = $request->route('uri');

        if($request->get('page') === null || $request->get('page') < 1){
            $page = 1;
        }else{
            $page = $request->get('page');
        }

        if($page > 1 && Auth::guest()){
            return redirect()->route('register')->withErrors('Necesitas registrarte para poder acceder a esta información. Es gratis!');
        }

        $entity = \App\Models\Entidad::where('uri', $uri)->first();
        if($entity){
            $entity->eszoho = 0;
        }
        if(!$entity){
            $entity = \App\Models\CifsNoZoho::where('uri', $uri)->where('movidoEntidad', 0)->first();
            if($entity){
                $entity->eszoho = 1;
            }
        }

        if(!$entity){
            return abort(404);
        }

        if($entity->organo !== null || $entity->departamento !== null){
            $url = ($entity->organo !== null) ? $entity->organo->url : $entity->departamento->url;
            return redirect()->route('miorganismo', $url);
        }

        $data = null;

        if($entity){
            $data = \App\Models\Einforma::leftJoin('pymes', 'einforma.identificativo', '=', 'pymes.CIF')
            ->select('einforma.*', 'pymes.*')
            ->where('einforma.identificativo', $entity->CIF)->where('lastEditor', '!=', 'einforma')->first();
            if(!$data){
                $data = \App\Models\Einforma::leftJoin('pymes', 'einforma.identificativo', '=', 'pymes.CIF')
                ->select('einforma.*', 'pymes.*')
                ->where('einforma.identificativo', $entity->CIF)->where('lastEditor',  'einforma')->first();
            }
        }

        if(!$data){
            if(!$entity){

                $totalproyectos = 0;

                return view('empresa-noeinforma',[
                    'empresa' => null,
                    'data' => null,
                    'sellopyme' => null,
                    'sellostartup' => null,
                    'nodata' => 1,
                    'ayudas' => null,
                    'patentes' => null,
                    'prioriza' => null,
                    'totalproyectos' => $totalproyectos,
                    'totalparticipantes' => 0,
                    'totalequipo' => 0,
                    'showsubheader' => 0,
                    'formclass' => 'col-sm-10 pl-0',
                    'title' => "Empresa no encontrada"
                ]);
            }
        }

        $pais = \App\Models\Paises::where('iso2', mb_strtolower($entity->pais))->first();

        if($pais){
            $entity->paisdata = $pais;
        }else{
            $entity->paisdata = null;
        }

        $partenariados = array();
  
        if(userEntidadSelected()){
            if(isset(userEntidadSelected()->simulada) && userEntidadSelected()->simulada == 1 && userEntidadSelected()->simulada == 1 && request()->session()->get('simulando') === true){
                if($entity->CIF != userEntidadSelected()->cif_original){
                    $partenariados = getGraphData($entity->CIF, "ENTITY_WITH_PROJECTS_IN_COMMON", userEntidadSelected()->cif_original, "ALL");
                }else{
                    $partenariados = getGraphData($entity->CIF, "ENTITY_BY_NIF", null, "ALL");  
                }
            }else{
                if($entity->CIF != userEntidadSelected()->CIF){
                    $partenariados = getGraphData($entity->CIF, "ENTITY_WITH_PROJECTS_IN_COMMON", userEntidadSelected()->CIF, "ALL");
                }else{
                    $partenariados = getGraphData($entity->CIF, "ENTITY_BY_NIF", null, "ALL");
                }
            }
        }else{
            $partenariados = getGraphData($entity->CIF, "ENTITY_BY_NIF", null, "ALL");
        }

        $partneriadosnacionales = array_filter($partenariados, function($partner) {
            return ($partner->pais == 'ES') ? true : false;
        }, ARRAY_FILTER_USE_BOTH);

        $partneriadosinternacionales = array_filter($partenariados, function($partner) {
            return ($partner->pais != 'ES') ? true : false;
        }, ARRAY_FILTER_USE_BOTH);

        return view('empresa.partenariados-new',[
            'empresa' => $entity,
            'data' => $data,
            'partneriadosnacionales' => $partneriadosnacionales,
            'partneriadosinternacionales' => $partneriadosinternacionales,
   
        ]);
    }

    public function empresaInvestigadores(Request $request)
    {
        $uri = $request->route('uri');
        if($uri === null){
            return abort(404);
        }

        $uri = $request->route('uri');

        if($request->get('page') === null || $request->get('page') < 1){
            $page = 1;
        }else{
            $page = $request->get('page');
        }

        if($page > 1 && Auth::guest()){
            return redirect()->route('register')->withErrors('Necesitas registrarte para poder acceder a esta información. Es gratis!');
        }

        $entity = \App\Models\Entidad::where('uri', $uri)->first();
        if($entity){
            $entity->eszoho = 0;
        }
        if(!$entity){
            $entity = \App\Models\CifsNoZoho::where('uri', $uri)->where('movidoEntidad', 0)->first();
            if($entity){
                $entity->eszoho = 1;
            }
        }

        if(!$entity){
            return abort(404);
        }

        if($entity->organo !== null || $entity->departamento !== null){
            $url = ($entity->organo !== null) ? $entity->organo->url : $entity->departamento->url;
            return redirect()->route('miorganismo', $url);
        }

        $data = null;

        if($entity){
            $data = \App\Models\Einforma::leftJoin('pymes', 'einforma.identificativo', '=', 'pymes.CIF')
            ->select('einforma.*', 'pymes.*')
            ->where('einforma.identificativo', $entity->CIF)->where('lastEditor', '!=', 'einforma')->first();
            if(!$data){
                $data = \App\Models\Einforma::leftJoin('pymes', 'einforma.identificativo', '=', 'pymes.CIF')
                ->select('einforma.*', 'pymes.*')
                ->where('einforma.identificativo', $entity->CIF)->where('lastEditor',  'einforma')->first();
            }
        }

        if(!$data){
            if(!$entity){
                return view('empresa-noeinforma',[
                    'empresa' => null,
                    'data' => null,
                    'nodata' => 1,
                ]);
            }
        }

        $patentes = new stdClass;
        $totalpatentes = 0;
        $patentes->data = collect(null); 
        $patentes->data = \App\Models\Patentes::where('CIF', $entity->CIF)->orderByDesc('Fecha_publicacion')->take(20)->get();
        if(empty($patentes->data) || $patentes->data->empty()){
            $patentes->total = \App\Models\Concessions::where('custom_field_cif', $entity->CIF)->where('id_organo', 650)->count();
            if($patentes->total > 0){
                $patentes->esorgano = true;
            }
            $totalpatentes = count($patentes->data);
        }
        
        $totalinvestigadores = 0;
        $investigadoreselastic = getResearchers($entity->CIF, "COMPANY_ID", $page);
        $investigadores = collect(null);
        if($investigadoreselastic !== null && $investigadoreselastic != "ups"){           
            if(isset($investigadoreselastic->pagination)){
                $totalinvestigadores = $investigadoreselastic->pagination->totalItems;
            }
            if(isset($investigadoreselastic->data) && isset($investigadoreselastic->pagination)){
                $url = request()->fullUrl();
                if(strripos(request()->fullUrl(),"?page=") !== false){
                    $url = substr(request()->fullUrl(),0, strripos(request()->fullUrl(),"?page="));
                }
                $investigadores = custompaginate($investigadoreselastic->data, 20, $page, $url, [], $investigadoreselastic->pagination->totalItems);
            }
        }

        return view('empresa.investigadores-new',[
            'empresa' => $entity,
            'data' => $data,
            'patentes' => $patentes,
            'totalpatentes' => $totalpatentes,
            'totalinvestigadores' => $totalinvestigadores,
            'investigadores' => $investigadores,
        ]);
    }

    public function updateSolicitudesDominio(Request $request){

        try{
            \App\Models\Entidad::where('cif', $request->get('cif'))->update(
                [
                    'solicitudesDominio' => ($request->get('solicitudesdominio') === null) ? 0 : 1
                ]
            );
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido actualizar las solicitudes por dominio, intentelo de nuevo pasado unos minutos');
        }

        return redirect()->back()->withSuccess('Actualización de las solicitudes por dominio, realizada correctamente');

    }

    public function empresaEquipo(Request $request){

        $uri = $request->route('uri');
        if($uri === null || !userEntidadSelected()){
            return abort(404);
        }

        $uri = $request->route('uri');
        $entity = \App\Models\Entidad::where('uri', $uri)->first();
        if($entity){
            $entity->eszoho = 0;
        }
        if(!$entity){
            $entity = \App\Models\CifsNoZoho::where('uri', $uri)->where('movidoEntidad', 0)->first();
            if($entity){
                $entity->eszoho = 1;
            }
        }

        if(userEntidadSelected() && isset(userEntidadSelected()->simulada) && userEntidadSelected()->simulada == 1 && userEntidadSelected()->simulada == 1 && request()->session()->get('simulando') === true
        && userEntidadSelected()->cif_original == $entity->CIF){
            if(userEntidadSelected()->creador !== null && userEntidadSelected()->creador->ver_equipo == 0){
                return abort(404);
            }
        }else{
            if(!$entity || userEntidadSelected()->ver_equipo == 0){
                return abort(404);
            }
        }

        if($entity->organo !== null || $entity->departamento !== null){
            $url = ($entity->organo !== null) ? $entity->organo->url : $entity->departamento->url;
            return redirect()->route('ayudas', $url);
        }

        $data = null;

        if($entity){
            $data = \App\Models\Einforma::leftJoin('pymes', 'einforma.identificativo', '=', 'pymes.CIF')
            ->select('einforma.*', 'pymes.*')
            ->where('einforma.identificativo', $entity->CIF)->orderBy('anioBalance','desc')->where('lastEditor', '!=', 'einforma')->first();
            if(!$data){
                $data = \App\Models\Einforma::leftJoin('pymes', 'einforma.identificativo', '=', 'pymes.CIF')
                ->select('einforma.*', 'pymes.*')
                ->where('einforma.identificativo', $entity->CIF)->orderBy('anioBalance','desc')->where('lastEditor',  'einforma')->first();
            }
        }

        if(!$data){
            if(!$entity){

                $totalproyectos = 0;

                return view('empresa-noeinforma',[
                    'empresa' => null,
                    'data' => null,
                    'sellopyme' => null,
                    'sellostartup' => null,
                    'nodata' => 1,
                    'ayudas' => null,
                    'patentes' => null,
                    'prioriza' => null,
                    'totalproyectos' => $totalproyectos,
                    'totalnacionales' => 0,
                    'totaleuropeos' => 0,
                    'totalproyectosrechazados' => 0,
                    'companynews' => null,
                    'totalparticipantes' => 0,
                    'totalequipo' => 0,
                    'showsubheader' => 0,
                    'formclass' => 'col-sm-10 pl-0',
                    'title' => "Empresa no encontrada"
                ]);
            }
            if($entity){

                $patentes = new StdClass();
                $patentes->total = 0;
                $patentes->data = collect(null);
                $patentes->esorgano = false;
        
                $sellopyme = \App\Models\Pymes::where('CIF', $entity->CIF)->where('es_enisa', 0)->orderBy('validez', 'desc')->first();
                $sellostartup = \App\Models\Pymes::where('CIF', $entity->CIF)->where('es_enisa', 1)->orderBy('validez', 'desc')->first();
                $patentes->data = \App\Models\Patentes::where('CIF', $entity->CIF)->orderByDesc('Fecha_publicacion')->take(20)->get();
                if(empty($patentes->data) || $patentes->data->empty()){
                    $patentes->total = \App\Models\Concessions::where('custom_field_cif', $entity->CIF)->where('id_organo', 650)->count();
                    if($patentes->total > 0){
                        $patentes->esorgano = true;
                    }
                }
        
                $pais = \App\Models\Paises::where('iso2', mb_strtolower($entity->pais))->first();

                if($pais){
                    $entity->paisdata = $pais;
                }else{
                    $entity->paisdata = null;
                }

                $solicitud = null;

                if(userEntidadSelected()){
                    $solicitud = \App\Models\PriorizaEmpresas::where('solicitante', userEntidadSelected()->CIF)->where('cifPrioritario', $entity->CIF)->first();
                }

                $proyectosparticipante = \App\Models\Participantes::where('cif_participante', $entity->CIF)->get();
                $totales = $this->getTotalProyectosByCIF($entity->CIF, $proyectosparticipante);       

                $totalayudas = 0;
                $concesiones = getConcessions($entity->CIF, "COMPANY_ID", 1);
                if($concesiones !== null && $concesiones != "ups"){
                    if(isset($concesiones->pagination)){
                        $totalayudas = $concesiones->pagination->totalItems;
                    }
                }

                return view('empresa-noeinforma',[
                    'empresa' => $entity,
                    'data' => null,
                    'sellopyme' => null,
                    'sellostartup' => null,
                    'nodata' => 1,
                    'totalayudas' => $totalayudas,
                    'companynews' => null,
                    'totalproyectos' => $totales['totalproyectos'],
                    'totalnacionales' => $totales['totalnacionales'],
                    'totaleuropeos' => $totales['totaleuropeos'],
                    'totalproyectosrechazados' => $totales['totalproyectosrechazados'],
                    'patentes' => $patentes,
                    'prioriza_empresa' => $solicitud,
                    'showsubheader' => 0,
                    'totalparticipantes' => 0,
                    'totalequipo' => 0,
                    'formclass' => 'col-sm-10 pl-0',
                    'title' => "Empresa - ".$entity->Nombre
                ]);
            }

        }

        $patentes = new StdClass();
        $patentes->total = 0;
        $patentes->data = collect(null);
        $patentes->esorgano = false;
        $ayudas = array();

        if($data){
            $data->trl_warning = 0;
            $data->format_primaemision = number_shorten($data->PrimaEmision,0);
        }

        $sellopyme = \App\Models\Pymes::where('CIF', $entity->CIF)->where('es_enisa', 0)->orderBy('validez', 'desc')->first();
        $sellostartup = \App\Models\Pymes::where('CIF', $entity->CIF)->where('es_enisa', 1)->orderBy('validez', 'desc')->first();
        $ayudas = \App\Models\Concessions::where('custom_field_cif', $entity->CIF)->orderByDesc('fecha')->groupBy('amount', 'fecha', 'id_convocatoria')->get();
        $totalayudas = $ayudas->count();
        $patentes->data = \App\Models\Patentes::where('CIF', $entity->CIF)->orderByDesc('Fecha_publicacion')->take(20)->get();
        if(empty($patentes->data) || $patentes->data->empty()){
            $patentes->total = \App\Models\Concessions::where('custom_field_cif', $entity->CIF)->where('id_organo', 650)->count();
            if($patentes->total > 0){
                $patentes->esorgano = true;
            }
        }

        if($data){
            $misproyectos = \App\Models\Proyectos::where('proyectos.esAnonimo', '0')->where('importado',0)->where('esEuropeo', 0)->where('proyectos.empresaPrincipal', $data->identificativo)
            ->select('proyectos.Titulo as proyecto_titulo','proyectos.Acronimo as proyecto_acronimo','proyectos.id as Proyecto_id','proyectos.*')->orderByDesc('Fecha')->orderByDesc('inicio')->get();
        }


        $pais = \App\Models\Paises::where('iso2', mb_strtolower($entity->pais))->first();

        if($pais){
            $entity->paisdata = $pais;
        }else{
            $entity->paisdata = null;
        }

        $totalparticipantes = 0;
        $partners = getGraphData($entity->CIF, "ENTITY_BY_NIF", null, "ALL");
        if(!empty($partners) && is_array($partners)){
            $totalparticipantes = count($partners);
        }

        $totalinvestigadores = 0;
        $investigadores = getResearchers($entity->CIF, "COMPANY_ID", 1);
        if($investigadores !== null && $investigadores != "ups"){
            if(isset($investigadores->pagination)){
                $totalinvestigadores = $investigadores->pagination->totalItems;
            }
        }
        $totalayudas = 0;
        $concesiones = getConcessions($entity->CIF, "COMPANY_ID", 1);
        if($concesiones !== null && $concesiones != "ups"){
            if(isset($concesiones->pagination)){
                $totalayudas = $concesiones->pagination->totalItems;
            }
        }

        $equipo = \App\Models\UsersEntidad::where('entidad_id', $entity->id)->get();
        $mailsapollo = \App\Models\ZohoMails::where('Cif', $entity->CIF)->where('source', 'apollo')->get();
        $mailsbeagle = \App\Models\ZohoMails::where('Cif', $entity->CIF)->where('source', 'beagle')->get();

        $proyectosparticipante = \App\Models\Participantes::where('cif_participante', $entity->CIF)->get();
        $totales = $this->getTotalProyectosByCIF($entity->CIF, $proyectosparticipante);    
        
        $totalequipo = 0;
        $totalinnovating = \App\Models\UsersEntidad::where('entidad_id', $entity->id)->count();
        $totalapollo = \App\Models\ZohoMails::where('Cif', $entity->CIF)->where('source', 'apollo')->count();
        $totalequipo = $totalinnovating + $totalapollo;

        if(userEntidadSelected() && isset(userEntidadSelected()->simulada) && userEntidadSelected()->simulada == 1 && userEntidadSelected()->simulada == 1 && request()->session()->get('simulando') === true
            && userEntidadSelected()->cif_original == $entity->CIF){
                return view('simular.equipo',[
                    'empresa' => $entity,
                    'data' => $data,
                    'sellopyme' => $sellopyme,
                    'sellostartup' => $sellostartup,
                    'patentes' => $patentes,
                    'equipo' => $equipo,
                    'totalequipo' => $totalequipo,
                    'mailsapollo' => $mailsapollo,
                    'mailsbeagle' => $mailsbeagle,
                    'totalayudas' => $totalayudas,
                    'proyectos' => $misproyectos,
                    'totalproyectos' => $totales['totalproyectos'],
                    'totalnacionales' => $totales['totalnacionales'],
                    'totaleuropeos' => $totales['totaleuropeos'],
                    'totalproyectosrechazados' => $totales['totalproyectosrechazados'],
                    'totalparticipantes' => $totalparticipantes,
                    'totalinvestigadores' => $totalinvestigadores,
                    'showsubheader' => 0,
                    'formclass' => 'col-sm-10 pl-0'
                ]);
        }

        return view('empresa.equipo',[
            'empresa' => $entity,
            'data' => $data,
            'sellopyme' => $sellopyme,
            'sellostartup' => $sellostartup,
            'patentes' => $patentes,
            'equipo' => $equipo,
            'mailsapollo' => $mailsapollo,
            'mailsbeagle' => $mailsbeagle,
            'totalayudas' => $totalayudas,
            'totalequipo' => $totalequipo,
            'proyectos' => $misproyectos,
            'totalproyectos' => $totales['totalproyectos'],
            'totalnacionales' => $totales['totalnacionales'],
            'totaleuropeos' => $totales['totaleuropeos'],
            'totalproyectosrechazados' => $totales['totalproyectosrechazados'],
            'totalparticipantes' => $totalparticipantes,
            'totalinvestigadores' => $totalinvestigadores
        ]);
    }

    public function getApolloData(Request $request){

        if(!$request->get('id')){
            return redirect()->back()->withErrors('No se ha podido obtener informacion desde apollo intentalo de nuevo en unos minutos.');
        }        

        try{
            Artisan::call('get:apollo', [
                'id' => $request->get('id'),
                'domain' => $request->get('domain'),
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());            
            return redirect()->back()->withErrors('No se ha podido obtener informacion desde apollo intentalo de nuevo en unos minutos.');
        }

        $entity = \App\Models\Entidad::where('id', $request->get('id'))->first();
        if($entity){
            $totalmailsapollo = \App\Models\ZohoMails::where('Cif', $entity->CIF)->where('source', 'apollo')->where('created_at', '>=', Carbon::now()->format('Y-m-d')." 00:00:00")->count();
        }

        if($request->get('domain') !== null && $request->get('domain') != ""){
            if($entity->Web != $request->get('domain')){
                try{
                    $entity->Web = $request->get('domain');
                    $entity->save();
                }catch(Exception $e){
                    Log::error("Error guardado dominio popup solicitar apollo:". $e->getMessage());
                }
            }
        }

        return redirect()->back()->withSuccess('Se ha obtenido información desde apollo, actualmente hay '.$totalmailsapollo.' contactos de apollo en esta empresa, puede darse el caso de uso de que no hubiera contactos.');
    }

    public function getTotalProyectosByCIF($cif, $proyectosparticipante){
        
        $cache = 'get_total_proyectos_'.$cif.'_'.$proyectosparticipante->count();

        $totales = Cache::remember(mb_strtolower($cache), now()->addMinutes(240), function () use($cif, $proyectosparticipante) {

            $totales = ['totalproyectos' => 0, 'totalproyectosrechazados' => 0, 'totaleuropeos' => 0, 'totalnacionales' => 0];
                    
            $totales['totalproyectos'] = \App\Models\Proyectos::where('importado', 0)->where('esEuropeo', 0)->where('proyectos.empresaPrincipal', $cif)->where('esAnonimo', 0)->count();                       
            $totales['totalproyectosrechazados'] = \App\Models\Proyectos::where('Estado', 'Desestimado')->where('proyectos.empresaPrincipal', $cif)->where('esAnonimo', 0)->count();                       
            $totales['totaleuropeos'] = \App\Models\Proyectos::where('esEuropeo', 1)->where('proyectos.empresaPrincipal', $cif)->where('esAnonimo', 0)->count();
            $totales['totalnacionales'] = \App\Models\Proyectos::where('esEuropeo', 0)->where('proyectos.empresaPrincipal', $cif)->where('esAnonimo', 0)->count();

            $proyectos = \App\Models\Proyectos::where('proyectos.empresaPrincipal', $cif)->pluck('id','id')->toArray();                       

            foreach($proyectosparticipante as $key => $participante){
                if($participante->proyecto !== null && in_array($participante->proyecto->id, $proyectos)){
                    unset($proyectosparticipante[$key]);
                    continue;
                }
                if($participante->proyecto->esEuropeo == 1){
                    $totales['totaleuropeos']++;
                }else{
                    $totales['totalnacionales']++;
                }
            }


            return $totales;
        });

        return $totales;

    }

    public function empresaTecnologias(Request $request){

        $uri = $request->route('uri');
        if($uri === null){
            return abort(404);
        }

        $uri = $request->route('uri');
        $entity = \App\Models\Entidad::where('uri', $uri)->first();
      
        if(!$entity || !userEntidadSelected()){
            return abort(404);
        }

        if($entity->einforma === null){           
            return view('empresa-noeinforma',[
                'empresa' => null,
                'data' => null,
                'nodata' => 1,
            ]);           
        }

        $s3_keywords = null;
        if(Storage::disk('s3_files')->exists('company_keywords/'.$entity->CIF.'.json')){
            $s3_file = Storage::disk('s3_files')->get('company_keywords/'.$entity->CIF.'.json');
            $s3_keywords = json_decode($s3_file, true);
        }

        return view('empresa.tecnologias',[
            'empresa' => $entity,
            'data' => $entity->einforma,
            's3_keywords' => $s3_keywords
   
        ]);
    }

}
