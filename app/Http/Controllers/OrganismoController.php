<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\RedisController;
use PhpParser\Node\Expr\Isset_;
use stdClass;

class OrganismoController extends Controller
{
    //
    private $rediscontroller;
    private $expiration;

    public function __construct()
    {
        $this->rediscontroller = new RedisController;
        $this->expiration = config('app.cache.expiration');
    }

    public function miOrganismo(Request $request){
        // return abort(404);
        if(Auth::guest()){
            return view('auth.login',[]);
        }

        if(!$request->route('uri')){
            return abort(404);
        }

        if(!userEntidadSelected()){
            return abort(404);
        }

        if(isAdmin() || isSuperAdmin() || isManager() || isTecnico()){

            $entity = \App\Models\Entidad::where('uri', $request->route('uri'))->first();

            if(!$entity){
                return abort(404);
            }

            $name = $request->route('uri');
            $dpto = \App\Models\Organos::where('url', $name)->first();
            $esorgano = false;
            if(!$dpto){
                $dpto = \App\Models\Departamentos::where('url', $name)->first();
                $esorgano = true;
            }
            if(!$dpto){
                return abort(404);
            }

            if(userEntidadSelected()){

                if(userEntidadSelected()->id != $entity->id){
                    //return abort(404);
                    dump("revisar id entidad en sesion = entidad en bbdd");
                }

                $busquedasguardadas = collect(null);
                if(userEntidadSelected()->crearBusquedas == 1){
                    $busquedasguardadas = \App\Models\adHocSearch::where('entidad_id', userEntidadSelected()->id)->where('Activo', 1)->orderBy('updated_at', 'DESC')->get();
                }

                $naturalezas = \App\Models\Naturalezas::where('Activo', 1)->get();

                $solicitudes = \App\Models\Invitation::where('entidad_id',$entity->id)->get();
                $solicitudesacceso = \App\Models\Notification::where('entity_id',$entity->id)->where('data', '!=', "null")
                ->where('type','access_request')->whereJsonDoesntContain('data', null)
                ->leftJoin('users', 'users.id', '=', 'notifications.data->user_id')->get();

                $equipo = array();
                if(Auth::check()){
                    $entidad = \App\Models\Entidad::find($entity->id);
                    $equipo = $entidad->users->sortByDesc('created_at');
                }

                foreach($equipo as $member){
                    $member->role = getRoleById($member->id, userEntidadSelected()->id);
                    $member->departs = $member->userdepartamentos->where('id_entidad', userEntidadSelected()->id);
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

                $totalproyectos = \App\Models\Proyectos::where('proyectos.empresaPrincipal', $entity->CIF)->orWhere('proyectos.empresasParticipantes', 'LIKE', '%'.$entity->CIF.'%')
                ->select('proyectos.Titulo as proyecto_titulo','proyectos.Acronimo as proyecto_acronimo','proyectos.id as Proyecto_id','proyectos.*')->count();

                $totalparticipantes = $entity->totalPartenariados;
                $companynews = \App\Models\CompanyNews::where('company_id', $entity->CIF)->first();
                $entity->mostrarUltimasNoticias = 0;
                if($companynews !== null){
                    $entity->mostrarUltimasNoticias = $companynews->mostrar;
                }

                $simulaciones = \App\Models\EntidadesSimuladas::where('creator_id', userEntidadSelected()->id)->orderByDesc('created_at')->paginate(50);

                foreach($simulaciones as $simulacion){
                    $simulacion->FechaGroupBy = Carbon::parse($simulacion->created_at)->format('d-m-Y');
                }

                $totalinvestigadores = \App\Models\Investigadores::where('id_ultima_experiencia', $entity->id)->count();
                $textos = array();

                if(Auth::check()){
                    if(isSuperAdmin()){
                        $textos =  \App\Models\TextosElastic::where('CIF', $entity->CIF)->first();
                    }
                }

                if($textos){
                    if(empty($textos->Textos_Tecnologia)){
                        $textos->Textos_Tecnologia = strip_tags($entity->TextosTecnologia);
                    }
                    if(empty($textos->Textos_Documentos)){
                        $textos->Textos_Documentos = strip_tags($entity->TextosTramitaciones);
                    }
                    if(empty($textos->Textos_Proyectos)){
                        $textos->Textos_Proyectos = strip_tags($entity->TextosProyectos);
                    }
                }

                $data = new stdClass();
                if($esorgano){
        
                    $ccaa = \App\Models\Ccaa::where('id', $dpto->id_ccaa)->first();
                    $data->search_organo = $dpto->Nombre;
                    $data->check_acronimo = ($dpto->Acronimo === null) ? false : $dpto->Acronimo;
                    $data->Web = ($dpto->Web === null) ? false : $dpto->Web;
                    if($ccaa){
                        $data->search_ccaa = $ccaa->Nombre;
                    }
                }else{
                    $ministerio = \App\Models\Ministerios::where('id', $dpto->id_ministerio)->first();
                    $data->search_departamento = $dpto->Nombre;
                    $data->check_acronimo = ($dpto->Acronimo === null) ? false : $dpto->Acronimo;
                    $data->Web = ($dpto->Web === null) ? false : $dpto->Web;
                    if($ministerio){
                        $data->search_ministerio = $ministerio->Nombre;
                    }        
                }

                $totalayudas = \App\Models\Ayudas::where('Organismo', $dpto->id)->where('Publicada', 1)->count();

                $totalconcesiones = 0;
                if($esorgano){
                    $concesiones = getConcessions(null, "ORGANISMO", 1, $dpto->id, "departamento");
                }else{
                    $concesiones = getConcessions(null, "ORGANISMO", 1, $dpto->id, "organo");
                }    
                if($concesiones !== null && $concesiones != "ups"){
                    if(isset($concesiones->pagination)){
                        $totalconcesiones = $concesiones->pagination->totalItems;
                    }
                }

                if($dpto->proyectosImportados == 1){
                    $totalproyectos = \App\Models\Proyectos::where('organismo', $dpto->id)->where('Estado', 'Cerrado')->where('importado', 1)->count();
                    $totalproyectosrechazados = \App\Models\Proyectos::where('organismo', $dpto->id)->where('Estado', 'Desestimado')->where('importado', 1)->count();
                }else{
                    $totalproyectos = \App\Models\Proyectos::where('organismo', $dpto->id)->where('Estado', 'Cerrado')->where('esEuropeo', 1)->count();
                    $totalproyectosrechazados = \App\Models\Proyectos::where('organismo', $dpto->id)->where('Estado', 'Desestimado')->where('esEuropeo', 1)->count();
                }

                $graficos = collect(null);
                if($entity->ver_graficos_organismo == 1){
                    $graficos = \App\Models\GraficosOrganismos::where('id_organo', $entity->idOrganismo)->get();
                    if(!$graficos || $graficos->isEmpty()){
                        $graficos = \App\Models\GraficosOrganismos::where('id_departamento', $entity->idOrganismo)->get();
                    }
                }

                $pdfscomerciales = collect(null);
                if($entity->upload_pdfs == 1){
                    $pdfscomerciales = \App\Models\EntidadesPdfComerciales::where('entidad_id', $entity->id)->orderBy('updated_at', 'DESC')->get();
                }

                $analisis360 = \App\Models\Analisis360::where('entidad_creator_id', $entity->id)->orderByDesc('updated_at')->paginate(20);

                $entitydepartamentos = \App\Models\EntidadesDepartamentos::where('id_entidad', $entity->id)->get();

                return view('myorganization',[
                    'empresa' => $entity,
                    'data' => null,
                    'totalproyectos' => $totalproyectos,
                    'totalproyectosrechazados' => $totalproyectosrechazados,
                    'totalparticipantes' => $totalparticipantes,
                    'equipo' => $equipo,
                    'naturalezas' => $naturalezas,
                    'solicitudes' => $solicitudes,
                    'solicitudesacceso' => $solicitudesacceso,
                    'totallineas' => $totallineas,
                    'totalinvestigadores' => $totalinvestigadores,
                    'textos' => $textos,
                    'busquedasguardadas' => $busquedasguardadas,
                    'simulaciones' => $simulaciones,
                    'convocatoria' => $data,
                    'dpto' => $dpto,
                    'totalayudas' => $totalayudas,
                    'totalconcesiones' => $totalconcesiones,
                    'graficos' => $graficos,
                    'pdfscomerciales' => $pdfscomerciales,
                    'analisis360' => $analisis360,
                    'entitydepartamentos' => $entitydepartamentos
                ]);
            }
        }

        return redirect()->route('index');
    
    }

    public function graficos(Request $request){

        if($request->route('dpto') === null || $request->route('dpto') == ""){
            return abort(404);
        }

        $name = $request->route('dpto');
        $dpto = \App\Models\Organos::where('url', $name)->first();
        $esorgano = false;
        if(!$dpto){
            $dpto = \App\Models\Departamentos::where('url', $name)->first();
            $esorgano = true;
        }
        if(!$dpto){
            return abort(404);
        }

        if(\App::environment() == "prod"){
            if($this->rediscontroller->checkRedisCache('single:organismo:uri:'.$name.':name')){
                return $this->rediscontroller->getRedisCache('single:organismo:uri:'.$name, 'organismo.graficos', null);
            }
        }

        $entity = \App\Models\Entidad::where('idOrganismo', $dpto->id)->first();

        if(!$entity){
            return abort(404);
        }

        $totalproyectos = \App\Models\Proyectos::where('proyectos.empresaPrincipal', $entity->CIF)->orWhere('proyectos.empresasParticipantes', 'LIKE', '%'.$entity->CIF.'%')
        ->select('proyectos.Titulo as proyecto_titulo','proyectos.Acronimo as proyecto_acronimo','proyectos.id as Proyecto_id','proyectos.*')->count();

        $totalparticipantes = $entity->totalPartenariados;
        $companynews = \App\Models\CompanyNews::where('company_id', $entity->CIF)->first();
        $entity->mostrarUltimasNoticias = 0;
        if($companynews !== null){
            $entity->mostrarUltimasNoticias = $companynews->mostrar;
        }

        $totalinvestigadores = \App\Models\Investigadores::where('id_ultima_experiencia', $entity->id)->count();

        $data = new stdClass();
        if($esorgano){
            $ccaa = \App\Models\Ccaa::where('id', $dpto->id_ccaa)->first();
            $data->search_organo = $dpto->Nombre;
            $data->check_acronimo = ($dpto->Acronimo === null) ? false : $dpto->Acronimo;
            $data->Web = ($dpto->Web === null) ? false : $dpto->Web;
            if($ccaa){
                $data->search_ccaa = $ccaa->Nombre;
            }
        }else{
            $ministerio = \App\Models\Ministerios::where('id', $dpto->id_ministerio)->first();
            $data->search_departamento = $dpto->Nombre;
            $data->check_acronimo = ($dpto->Acronimo === null) ? false : $dpto->Acronimo;
            $data->Web = ($dpto->Web === null) ? false : $dpto->Web;
            if($ministerio){
                $data->search_ministerio = $ministerio->Nombre;
            }        
        }

        $totalayudas = \App\Models\Ayudas::where('Organismo', $dpto->id)->where('Publicada', 1)->count();

        $totalconcesiones = 0;
        if($esorgano){
            $concesiones = getConcessions(null, "ORGANISMO", 1, $dpto->id, "departamento");
        }else{
            $concesiones = getConcessions(null, "ORGANISMO", 1, $dpto->id, "organo");
        }    
        if($concesiones !== null && $concesiones != "ups"){
            if(isset($concesiones->pagination)){
                $totalconcesiones = $concesiones->pagination->totalItems;
            }
        }

        if($dpto->proyectosImportados == 1){
            $totalproyectos = \App\Models\Proyectos::where('organismo', $dpto->id)->where('Estado', 'Cerrado')->where('importado', 1)->count();
            $totalproyectosrechazados = \App\Models\Proyectos::where('organismo', $dpto->id)->where('Estado', 'Desestimado')->where('importado', 1)->count();
        }else{
            $totalproyectos = \App\Models\Proyectos::where('organismo', $dpto->id)->where('Estado', 'Cerrado')->where('esEuropeo', 1)->count();
            $totalproyectosrechazados = \App\Models\Proyectos::where('organismo', $dpto->id)->where('Estado', 'Desestimado')->where('esEuropeo', 1)->count();
        }

        $graficos = collect(null);
        if($entity->ver_graficos_organismo == 1){
            $graficos = \App\Models\GraficosOrganismos::where('id_organo', $entity->idOrganismo)->where('activo', 1)->get();
            if(!$graficos || $graficos->isEmpty()){
                $graficos = \App\Models\GraficosOrganismos::where('id_departamento', $entity->idOrganismo)->where('activo', 1)->get();
            }
        }

        $jsondata = array();
        $totalgraficos = $graficos->count();

        if($totalgraficos > 0){
            foreach($graficos as $grafico){                      
                $jsondata[$grafico->type] = json_decode($grafico->datos, true);                        
            } 
        }
        if(!isset($jsondata['grafico-1'])){
            $jsondata['grafico-1'] = "";
        }
        if(!isset($jsondata['grafico-2'])){
            $jsondata['grafico-2'] = "";
        }
        if(!isset($jsondata['grafico-3'])){
            $jsondata['grafico-3'] = "";
        }

        if(\App::environment() == "prod"){
            Redis::set('single:organismo:uri:'.$name.':name', $name, 'EX', $this->expiration);
            Redis::set('single:organismo:uri:'.$name.':empresa', json_encode($entity), 'EX', $this->expiration);
            Redis::set('single:organismo:uri:'.$name.':data', null, 'EX',   $this->expiration);
            Redis::set('single:organismo:uri:'.$name.':convocatoria', json_encode($data), 'EX',  $this->expiration);
            Redis::set('single:organismo:uri:'.$name.':totalproyectos', $totalproyectos, 'EX',  $this->expiration);
            Redis::set('single:organismo:uri:'.$name.':totalproyectosrechazados', $totalproyectosrechazados, 'EX',  $this->expiration);
            Redis::set('single:organismo:uri:'.$name.':totalparticipantes', $totalparticipantes, 'EX',  $this->expiration);
            Redis::set('single:organismo:uri:'.$name.':totalproyectos', $totalproyectos, 'EX',  $this->expiration);
            Redis::set('single:organismo:uri:'.$name.':totalinvestigadores', $totalinvestigadores, 'EX',  $this->expiration);
            Redis::set('single:organismo:uri:'.$name.':dpto', json_encode($dpto), 'EX',  $this->expiration);   
            Redis::set('single:organismo:uri:'.$name.':totalayudas', $totalayudas, 'EX',  $this->expiration);
            Redis::set('single:organismo:uri:'.$name.':totalconcesiones', $totalconcesiones, 'EX',  $this->expiration);
            Redis::set('single:organismo:uri:'.$name.':graficos', json_encode($graficos), 'EX',  $this->expiration);
            Redis::set('single:organismo:uri:'.$name.':totalgraficos', $totalgraficos, 'EX',  $this->expiration);
            Redis::set('single:organismo:uri:'.$name.':jsondata', json_encode($jsondata), 'EX',  $this->expiration);
        }
        
        return view('organismos.graficos',[
            'name' => $name,
            'empresa' => $entity,
            'data' => null,
            'convocatoria' => $data,
            'totalproyectos' => $totalproyectos,
            'totalproyectosrechazados' => $totalproyectosrechazados,
            'totalparticipantes' => $totalparticipantes,
            'totalinvestigadores' => $totalinvestigadores,
            'dpto' => $dpto,
            'totalayudas' => $totalayudas,
            'totalconcesiones' => $totalconcesiones,
            'graficos' => $graficos,
            'totalgraficos' => $totalgraficos,
            'jsondata' => $jsondata
        ]);
        
    }

    public function actualizarGraficosOrganismo(Request $request){

        if($request->get('cif') === null){
            return redirect()->back()->withErrors('No se han podido generar los gráficos 1');
        }

        $empresa = \App\Models\Entidad::where('CIF', $request->get('cif'))->first();

        if(!$empresa || $empresa->idOrganismo === null){
            return redirect()->back()->withErrors('No se han podido generar los gráficos 2');
        }
        
        $esorgano = false;
        if($empresa->departamento !== null){
            $esorgano = true;
        }
        
        if($esorgano === true){
            $concesiones = \App\Models\Concessions::where('id_departamento', $empresa->idOrganismo)->where('fecha', '>=', now()->subMonths(6)->format('Y-m-01'))->get();
        }else{
            $concesiones = \App\Models\Concessions::where('id_organo', $empresa->idOrganismo)->where('fecha', '>=', now()->subMonths(6)->format('Y-m-01'))->get();
        }

        if($concesiones->count() > 0){
            
            ##DATOS GRAFICO SUMA TOTAL DE CONCESIONES POR MES
            $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(6)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(6)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(5)->endOfMonth()->format('Y-m-d'))->count()];
            $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(5)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(5)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(4)->endOfMonth()->format('Y-m-d'))->count()];
            $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(4)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(4)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(3)->endOfMonth()->format('Y-m-d'))->count()];
            $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(3)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(3)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(2)->endOfMonth()->format('Y-m-d'))->count()];
            $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(2)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(2)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(1)->endOfMonth()->format('Y-m-d'))->count()];
            $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(1)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(1)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->endOfMonth()->format('Y-m-d'))->count()];

            $check = 0;
            if(!empty($totalconcesiones)){
                $check = max(array_column($totalconcesiones, "total"));
            }

            try{
                if($esorgano === true){
                    $grafico = \App\Models\GraficosOrganismos::where('type', 'grafico-1')->where('id_departamento', $empresa->idOrganismo)->first();                
                }else{
                    $grafico = \App\Models\GraficosOrganismos::where('type', 'grafico-1')->where('id_organo', $empresa->idOrganismo)->first();
                }
                $grafico->datos = json_encode($totalconcesiones, JSON_UNESCAPED_UNICODE);
                $grafico->activo = ($check > 0) ? 1 : 0;
                $grafico->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('No se ha podido generar el gráfico 1 correctamente');
            }
            
            ##DATOS GRAFICO SUMA TOTAL DE AMOUNTS DE CONCESIONES POR MES
            $totaldinero[] = ["mes" => ucfirst(now()->subMonths(6)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(6)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(5)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
            $totaldinero[] = ["mes" => ucfirst(now()->subMonths(5)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(5)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(4)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
            $totaldinero[] = ["mes" => ucfirst(now()->subMonths(4)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(4)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(3)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
            $totaldinero[] = ["mes" => ucfirst(now()->subMonths(3)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(3)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(2)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
            $totaldinero[] = ["mes" => ucfirst(now()->subMonths(2)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(2)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(1)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
            $totaldinero[] = ["mes" => ucfirst(now()->subMonths(1)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(1)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->endOfMonth()->format('Y-m-d'))->sum->amount,0)];

            $check = 0;
            if(!empty($totaldinero)){
                $check = max(array_column($totaldinero, "total"));
            }

            try{
                if($esorgano === true){
                    $grafico = \App\Models\GraficosOrganismos::where('type', 'grafico-2')->where('id_departamento', $empresa->idOrganismo)->first();                
                }else{
                    $grafico = \App\Models\GraficosOrganismos::where('type', 'grafico-2')->where('id_organo', $empresa->idOrganismo)->first();
                }
                $grafico->datos = json_encode($totaldinero, JSON_UNESCAPED_UNICODE  );
                $grafico->activo = ($check > 0) ? 1 : 0;
                $grafico->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('No se ha podido generar el gráfico 2 correctamente');
            }

            ##DATOS GRAFICO TAMAÑOS DE EMPRESA CON CONCESIONES
            $empresatipo[] = ['tipo' => 'Micro', "total" => $concesiones->filter(function($concesion){
                if($concesion->entidad !== null && $concesion->entidad->einforma !== null){
                    if($concesion->entidad->einforma->categoriaEmpresa == "Micro"){
                        return true;
                    }
                }
                return false;
            })->count()];
            $empresatipo[] = ['tipo' => 'Pequeña', "total" => $concesiones->filter(function($concesion){
                if($concesion->entidad !== null && $concesion->entidad->einforma !== null){
                    if($concesion->entidad->einforma->categoriaEmpresa == "Pequeña"){
                        return true;
                    }
                }
                return false;
            })->count()];
            $empresatipo[] = ['tipo' => 'Mediana', "total" => $concesiones->filter(function($concesion){
                if($concesion->entidad !== null && $concesion->entidad->einforma !== null){
                    if($concesion->entidad->einforma->categoriaEmpresa == "Mediana"){
                        return true;
                    }
                }
                return false;
            })->count()];
            $empresatipo[] = ['tipo' => 'Grande', "total" => $concesiones->filter(function($concesion){
                if($concesion->entidad !== null && $concesion->entidad->einforma !== null){
                    if($concesion->entidad->einforma->categoriaEmpresa == "Grande"){
                        return true;
                    }
                }
                return false;
            })->count()];
            $empresatipo[] = ['tipo' => 'Desconocido', "total" => $concesiones->filter(function($concesion){
                if($concesion->entidad !== null && $concesion->entidad->einforma !== null){
                    if($concesion->entidad->einforma->categoriaEmpresa == "Desconocido" || $concesion->entidad->einforma->categoriaEmpresa == "Error"){
                        return true;
                    }
                }
                return false;
            })->count()];

            $check = 0;
            if(!empty($empresatipo)){
                $check = max(array_column($empresatipo, "total"));
            }

            try{
                if($esorgano === true){
                    $grafico = \App\Models\GraficosOrganismos::where('type', 'grafico-3')->where('id_departamento', $empresa->idOrganismo)->first();                
                }else{
                    $grafico = \App\Models\GraficosOrganismos::where('type', 'grafico-3')->where('id_organo', $empresa->idOrganismo)->first();
                }
                $grafico->datos = json_encode($empresatipo, JSON_UNESCAPED_UNICODE);
                $grafico->activo = ($check > 0) ? 1 : 0;
                $grafico->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('No se ha podido generar el gráfico 3 correctamente');
            }

            ##DATOS TABLA CNAES EMPRESAS CON CONCESIONES
            $empresacnaes = array();       
            $cifs = array();     
            foreach ($concesiones as $key => $concesion) {            
                if($concesion->entidad !== null && $concesion->entidad->einforma !== null){                    
                    $cnae = substr($concesion->entidad->einforma->cnae, 0, strripos($concesion->entidad->einforma->cnae,"-") -3);
                    $cnae = str_replace('"', '', $cnae);
                    if(array_search($cnae, array_column($empresacnaes, 'cnaeid')) === false && !in_array($concesion->entidad->CIF,$cifs)){                        
                        $empresacnaes[$cnae]['cnaeid'] = $cnae;
                        $empresacnaes[$cnae]['cnae'] = $concesion->entidad->einforma->cnae;
                        $empresacnaes[$cnae]['amount'] = $concesion->amount;
                        $empresacnaes[$cnae]['total'] = 1;
                        $cifs[] = $concesion->entidad->CIF;
                    }else{
                        if(isset($empresacnaes[$cnae])){
                            $empresacnaes[$cnae]['amount'] += $concesion->amount;
                            $empresacnaes[$cnae]['total'] += 1;
                        }
                    }
                }
            }

            $key_values = array_column($empresacnaes, 'total'); 
            array_multisort($key_values, SORT_DESC, $empresacnaes);
            $empresacnaes = array_values($empresacnaes);

            $check = 0;
            if(!empty($empresacnaes)){
                $check = max(array_column($empresacnaes, "total"));
            }

            try{
                if($esorgano === true){
                    $grafico = \App\Models\GraficosOrganismos::where('type', 'grafico-4')->where('id_departamento', $empresa->idOrganismo)->first();                
                }else{
                    $grafico = \App\Models\GraficosOrganismos::where('type', 'grafico-4')->where('id_organo', $empresa->idOrganismo)->first();
                }
                $grafico->datos = json_encode($empresacnaes, JSON_UNESCAPED_UNICODE);
                $grafico->activo = ($check > 0) ? 1 : 0;
                $grafico->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('No se ha podido generar el gráfico 4 correctamente');
            }

            ##DATOS TABLA CCAAS EMPRESAS CON CONCESIONES
            $empresaccaas = array();       
            $ccaas = array();     
            foreach ($concesiones as $key => $concesion) {            
                if($concesion->entidad !== null && $concesion->entidad->einforma !== null && $concesion->entidad->einforma->ccaa !== null){        
                    $ccaa = mb_strtolower(str_replace(" ", "", $concesion->entidad->einforma->ccaa));
                    if(array_search($concesion->entidad->einforma->ccaa, array_column($empresaccaas, 'ccaa')) === false && !in_array($concesion->entidad->einforma->ccaa,$ccaas)){                        
                        $empresaccaas[$ccaa]['ccaa'] = $concesion->entidad->einforma->ccaa;
                        $empresaccaas[$ccaa]['amount'] = $concesion->amount;
                        $empresaccaas[$ccaa]['total'] = 1;
                        $ccaas[] = $concesion->entidad->einforma->ccaa;
                    }else{
                        if(isset($empresaccaas[$ccaa])){
                            $empresaccaas[$ccaa]['amount'] += $concesion->amount;
                            $empresaccaas[$ccaa]['total'] += 1;
                        }
                    }
                }
            }

            $key_values = array_column($empresaccaas, 'total'); 
            array_multisort($key_values, SORT_DESC, $empresaccaas);
            $empresaccaas = array_values($empresaccaas);

            $check = 0;
            if(!empty($empresaccaas)){
                $check = max(array_column($empresaccaas, "total"));
            }

            try{
                if($esorgano === true){
                    $grafico = \App\Models\GraficosOrganismos::where('type', 'grafico-5')->where('id_departamento', $empresa->idOrganismo)->first();                
                }else{
                    $grafico = \App\Models\GraficosOrganismos::where('type', 'grafico-5')->where('id_organo', $empresa->idOrganismo)->first();
                }
                $grafico->datos = json_encode($empresaccaas, JSON_UNESCAPED_UNICODE, ENT_QUOTES);
                $grafico->activo = ($check > 0) ? 1 : 0;
                $grafico->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('No se ha podido generar el gráfico 5 correctamente');
            }

            return redirect()->back()->withSuccess('los gráficos se han actualizado con la información más reciente de la base de datos');

        }else{
            return redirect()->back()->withErrors('No se han encontrado concesiones para este organismo de los ultimos 6 meses');
        }

    }

}
