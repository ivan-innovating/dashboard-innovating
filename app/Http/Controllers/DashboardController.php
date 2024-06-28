<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Settings\GeneralSettings;
use App\Models\ValidateCompany;
use App\Models\OldEinforma;
use App\Models\Einforma;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\Ministerios;
use App\Models\Departamentos;
use App\Models\Organos;
use stdClass;

class DashboardController extends Controller
{

    public function index(){
        return redirect()->route('home');
    }

    public function organos(Request $request){

        $ministerios = Ministerios::get();
        $departamentos = Departamentos::get();
        $organos = Organos::get();

        if($request->query('type') !== null && $request->query('value') !== null){

            $departamentos = $departamentos->whereIn('Nombre', $request->query('value'));
            $organos = $organos->whereIn('Nombre', $request->query('value'));

            foreach($departamentos as $dpto){
                $total = \App\Models\Ayudas::where('Organismo', $dpto->id)->count();
                $totalconcesiones = DB::table('concessions')->where('id_departamento', $dpto->id)->count();
                $dpto->totalayudas = 0;
                $dpto->totalconcesiones = 0;
                if($total > 0 && $totalconcesiones > 0){
                    $dpto->totalayudas = $total;
                    $dpto->totalconcesiones = $totalconcesiones;
                }
            }

            foreach($organos as $organo){
                $total = \App\Models\Ayudas::where('Organismo', $organo->id)->count();
                $totalconcesiones = DB::table('concessions')->where('id_organo', $organo->id)->count();
                $organo->totalayudas = 0;
                $organo->totalconcesiones = 0;
                if($total > 0 && $totalconcesiones > 0){
                    $organo->totalayudas = $total;
                    $organo->totalconcesiones = $totalconcesiones;
                }
            }

        }else{

            foreach($departamentos as $dpto){
                $total = \App\Models\Ayudas::where('Organismo', $dpto->id)->count();
                $totalconcesiones = DB::table('concessions')->where('id_departamento', $dpto->id)->count();
                $dpto->totalayudas = $total;
                $dpto->totalconcesiones = $totalconcesiones;
            }

            foreach($organos as $organo){
                $total = \App\Models\Ayudas::where('Organismo', $organo->id)->count();
                $totalconcesiones = DB::table('concessions')->where('id_organo', $organo->id)->count();
                $organo->totalayudas = $total;
                $organo->totalconcesiones = $totalconcesiones;
            }

        }

        $ccaa = DB::table('ccaa')->get();

        $grandesempresas = null;

        $priorizar = DB::table('prioriza_empresas')->where('esOrgano',1)->orderBy('created_at', 'desc')->get();

        return view('dashboard/organos', [
            'grandesempresas' => $grandesempresas,
            'ministerios' => $ministerios,
            'departamentos' => $departamentos,
            'organos' => $organos,
            'priorizar' => $priorizar,
            'ccaa' => $ccaa,
        ]);
    }

    public function convocatorias(Request $request){

        $totalbbdd = DB::table('convocatorias')->count('IDConvocatoria');
        $totalconvocatorias = DB::table('convocatorias')->where('Analisis', 'Estudio')->count('IDConvocatoria');
        $rechazadas = DB::table('convocatorias')->where('Analisis', 'Rechazada')->count('IDConvocatoria');
        $aceptadas = DB::table('convocatorias')->where('Analisis', 'Aceptada')->count('IDConvocatoria');

        $rechazadasAbierta = DB::table('convocatorias')->where('Analisis', 'Rechazada')->where('Estado','Abierta')->count('IDConvocatoria');
        $rechazadasCerrada = DB::table('convocatorias')->where('Analisis', 'Rechazada')->where('Estado','Cerrada')->count('IDConvocatoria');
        $rechazadasnose = DB::table('convocatorias')->where('Analisis', 'Rechazada')->where('Estado', NULL)->count('IDConvocatoria');

        $aceptadasAbierta = DB::table('convocatorias')->where('Analisis', 'Aceptada')->where('Estado','Abierta')->count('IDConvocatoria');
        $aceptadasCerrada = DB::table('convocatorias')->where('Analisis', 'Aceptada')->where('Estado','Cerrada')->count('IDConvocatoria');
        $aceptadasnose = DB::table('convocatorias')->where('Analisis', 'Aceptada')->where('Estado', NULL)->count('IDConvocatoria');

        $revisarAbierta = DB::table('convocatorias')->where('Analisis', 'Estudio')->where('Estado', 'Abierta')->count('IDConvocatoria');
        $revisarCerrada = DB::table('convocatorias')->where('Analisis', 'Estudio')->where('Estado', 'Cerrada')->count('IDConvocatoria');
        $revisarnose = DB::table('convocatorias')->where('Analisis', 'Estudio')->where('Estado', NULL)->count('IDConvocatoria');

        $total = $totalconvocatorias + $rechazadas + $aceptadas;
        $convocatorias = array();
        $page = $request->get('page');

        if($page > 1){
            $skip = ($page -1) * 200;
        }else{
            $skip = 0;
        }

        if($request->get('Estado') != "" && $request->get('Analisis') != ""){
            $estado = ($request->get('Estado') == 'NULL') ? NULL : $request->get('Estado');
            $convocatorias = DB::table('convocatorias')->where('Analisis', $request->get('Analisis'))->where('Estado', $estado)->orderBy('FechaRegistro','desc')->offset($skip)->limit(200)->get();
        }else if($request->get('Analisis') && $request->get('Estado') == ""){
            $convocatorias = DB::table('convocatorias')->where('Analisis', $request->get('Analisis'))->orderBy('FechaRegistro','desc')->offset($skip)->limit(200)->get();
        }

        if(!empty($convocatorias)){
            foreach($convocatorias as $convocatoria){
                if(isset($convocatoria->id_organo)){
                    $org = DB::table('organos')->select('Nombre')->where('id',$convocatoria->id_organo)->first();
                    $convocatoria->check_org = $org->Nombre;
                }
                if(isset($convocatoria->id_departamento)){
                    $dpto = DB::table('departamentos')->select('Nombre')->where('id',$convocatoria->id_departamento)->first();
                    $convocatoria->check_org = $dpto->Nombre;
                }

                if($convocatoria->id_ayudas){
                    $convocatoria->ayuda_nombre = "";
                    foreach(json_decode($convocatoria->id_ayudas) as $ayuda_id){
                        $ayu =$ayuda = \App\Models\Ayudas::select(['Titulo', 'Acronimo'])->where('id', $ayuda_id)->first();
                        if(isset($ayu->Acronimo)){
                            $convocatoria->ayuda_nombre .= $ayu->Acronimo." ";
                        }
                        $convocatoria->ayuda_nombre .= $ayu->Titulo.",";
                    }
                }
                $convocatoria->format_amount = number_shorten($convocatoria->Presupuesto,0);
            }
        }

        if($request->get('Analisis') == "Aceptada"){
            $pages = ceil($aceptadas / 200);
            if($request->get('Estado') == "Abierta"){
                $pages = ceil($aceptadasAbierta / 200);
            }
            if($request->get('Estado') == "Cerrrada"){
                $pages = ceil($rechazadasCerrada / 200);
            }
            if($request->get('Estado') == "NULL"){
                $pages = ceil($aceptadasnose / 200);
            }
        }

        if($request->get('Analisis') == "Rechazada"){
            $pages = ceil($rechazadas / 200);
            if($request->get('Estado') == "Abierta"){
                $pages = ceil($rechazadasAbierta / 200);
            }
            if($request->get('Estado') == "Cerrrada"){
                $pages = ceil($aceptadasCerrada / 200);
            }
            if($request->get('Estado') == "NULL"){
                $pages = ceil($rechazadasnose / 200);
            }
        }

        if($request->get('Analisis') == "Estudio"){
            $pages = ceil($totalconvocatorias / 200);
            if($request->get('Estado') == "Abierta"){
                $pages = ceil($revisarAbierta / 200);
            }
            if($request->get('Estado') == "Cerrrada"){
                $pages = ceil($revisarCerrada / 200);
            }
            if($request->get('Estado') == "NULL"){
                $pages = ceil($revisarnose / 200);
            }
        }

        if($request->get('Analisis') == "" && $request->get('Estado') == ""){
            $pages = 0;
        }

        return view('dashboard/convocatorias', [
            'convocatorias' => $convocatorias,
            'total' => $total,
            'totalbbdd' => $totalbbdd,
            'revisar' => $totalconvocatorias,
            'rechazadas' => $rechazadas,
            'aceptadas' => $aceptadas,
            'aceptadasabiertas' => $aceptadasAbierta,
            'aceptadascerradas' => $aceptadasCerrada,
            'aceptadasnose' => $aceptadasnose,
            'rechazadasabiertas' => $rechazadasAbierta,
            'rechazadascerradas' => $rechazadasCerrada,
            'rechazadasnose' => $rechazadasnose,
            'revisarabiertas' => $revisarAbierta,
            'revisarcerradas' => $revisarCerrada,
            'revisarnose' => $revisarnose,
            'pages' => $pages
        ]);

    }

    public function ayudas(Request $request){

        $ayudas = DB::table('ayuda')->get();

        return view('dashboard/ayudas', [
            'ayudas' => $ayudas
        ]);

    }

    public function ayudasFondos(){

        $fondos = \App\Models\Fondos::get();
        $subfondos = \App\Models\Subfondos::get();
        $actions = \App\Models\TypeOfActions::get();
        $budgets = \App\Models\BudgetYearMap::get();
        $convocatorias = \App\Models\Ayudas::all();
        $categorias = getAllCategories();
        $naturalezas = \App\Models\Naturalezas::where('Activo', 1)->get();
        $trls = \App\Models\Trl::all();
        $intereses = \App\Models\Intereses::where('defecto', 'true')->where('Nombre', '!=', 'Cooperación')->where('Nombre', '!=', 'Subcontratación')->get();

        return view('dashboard/fondos', [
            'fondos' => $fondos,
            'subfondos' => $subfondos,
            'actions' => $actions,
            'budgets' => $budgets,
            'convocatorias' => $convocatorias,
            'categorias' => $categorias,
            'naturalezas' => $naturalezas,
            'trls' => $trls,
            'intereses' => $intereses->pluck('Nombre','Id_zoho'),
        ]);

    }

    public function editFondo(Request $request){

        if($request->route('id') === null || empty($request->route('id'))){
            return abort(404);
        }

        $fondo = \App\Models\Fondos::where('id', $request->route('id'))->first();
        $graficos = \App\Models\GraficosFondos::where('id_fondo', $request->route('id'))->orderBy('updated_at','DESC')->first();

        return view('dashboard/editfondo', [
            'fondo' => $fondo,
            'graficos' => $graficos
        ]);

    }

    public function editarFondo(Request $request){

        if($request->get('old_name') != $request->get('nombre')){
            $fondo = \App\Models\Fondos::where('nombre', $request->get('nombre'))->first();
            if($fondo){
                return redirect()->back()->withErrors('Ya existe un fondo con ese nombre');
            }
        }

        $tags = array();
        if($request->get('tags') !== null && $request->get('tags') != ""){
            foreach(explode(",", $request->get('tags')) as $tag){
                array_push($tags, $tag);
            }
        }

        try{
            $fondo = \App\Models\Fondos::where('id', $request->get('id'))->first();            
            $fondo->nombre = $request->get('nombre');
            $fondo->descripcion = ($request->get('descripcion') === null) ? null : $request->get('descripcion');
            $fondo->matches_budget_application = (empty($tags)) ? null : json_encode($tags, JSON_UNESCAPED_UNICODE);
            $fondo->status = ($request->get('estado') === null) ? 0 : 1;
            $fondo->mostrar_graficos = ($request->get('mostrar_graficos') === null) ? 0 : 1;
            $fondo->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido actualizar el fondo en la base de datos');
        }

        return redirect()->back()->withSuccess('Fondo actualizado correctamente');

    }

    public function saveAyudaConvocatoria(Request $request){

        $ayuda = DB::table('ayuda')->where('acronimo', $request->get('acronimo'))->orWhere('titulo', $request->get('acronimo'))->first();

        if($ayuda){
            return redirect()->back()->withErrors('Ya existe una ayuda con ese acronimo o título');
        }

        try{
            DB::table('ayuda')->insert([
                'acronimo' => $request->get('acronimo'),
                'titulo' => $request->get('titulo'),
                'descripcion_corta' => $request->get('descripcion'),
                'mes_apertura_1' => $request->get('mes_1'),
                'mes_apertura_2' => ($request->get('mes_2') !== null) ? $request->get('mes_2') : null,
                'mes_apertura_3' => ($request->get('mes_3') !== null) ? $request->get('mes_3') : null,
                'duracion_convocatorias' => $request->get('duracion'),
                'es_indefinida' => ($request->get('esindefinida') !== null) ? 1 : 0,
                'created_at' => Carbon::now()
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido guardar la ayuda en la base de datos');
        }

        return redirect()->back()->withSuccess('Nueva ayuda creada correctamente');
    }

    public function updateAyudaConvocatoria(Request $request){

        if($request->get('esindefinida') !== null){
            $mes = ($request->get('mes_1') === null) ? null : $request->get('mes_1');
            $duracion = null;
        }else{
            $mes = $request->get('mes_1');
            $duracion = $request->get('duracion');
        }

        try{
            DB::table('ayuda')->where('id', $request->get('id'))->update([
                'acronimo' => $request->get('acronimo'),
                'titulo' => $request->get('titulo'),
                'descripcion_corta' => $request->get('descripcion'),
                'mes_apertura_1' => $mes,
                'mes_apertura_2' => ($request->get('mes_2') !== null) ? $request->get('mes_2') : null,
                'mes_apertura_3' => ($request->get('mes_3') !== null) ? $request->get('mes_3') : null,
                'duracion_convocatorias' => $duracion,
                'es_indefinida' => ($request->get('esindefinida') === null) ? 0 : 1,
                'extinguida' => ($request->get('extinguida') === null) ? 0 : 1,
                'updated_at' => Carbon::now()
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido actualizar la ayuda en la base de datos');
        }

        return redirect()->back()->withSuccess('Ayuda actualizada correctamente');
    }

    public function viewAyudaConvocatoria(Request $request){


        $ayuda = DB::table('ayuda')->where('id', $request->route('id'))->first();

        return view('dashboard/editayudaconvocatoria', [
            'ayuda' => $ayuda
        ]);

    }

    public function crearAyudaDesdeConvocatoria(Request $request){

        try{
            $id = DB::table('ayuda')->insertGetId([
                'acronimo' => $request->get('acronimo'),
                'titulo' => $request->get('titulo'),
                'descripcion_corta' => ($request->get('descripcion') !== null) ? $request->get('descripcion') : null,
                'created_at' => Carbon::now()
            ]);

            $ayuda = \App\Models\Ayudas::find($request->get('id_ayuda'));
            $ayuda->id_ayuda = $id;
            $ayuda->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('Error al crear la ayuda desde la convocatoria');
        }

        return redirect()->back()->withSuccess('Se ha creado correctamente la ayuda desde la convocatoria');

    }

    public function ayudasConvocatorias(Request $request){

        if($request->query('Estado')){
            if($request->query('Estado') == "publicadas"){
                $ayudas =\App\Models\Ayudas::where('Publicada', 1)->orderBy('updated_at','desc')->orderBy('created_at','desc')->paginate(50);
            }
            elseif($request->query('Estado') == "nopublicadas"){
                $ayudas =\App\Models\Ayudas::where('Publicada', 0)->orderBy('updated_at','desc')->orderBy('created_at','desc')->paginate(50);
            }elseif($request->query('Estado') == "nuevas"){
                $ayudas =\App\Models\Ayudas::where('created_at', '>=', Carbon::now()->subDays(15))->orderBy('updated_at','desc')->orderBy('created_at','desc')->paginate(50);
            }else{
                $ayudas = \App\Models\Ayudas::orderBy('updated_at','desc')->orderBy('created_at','desc')->paginate(50);
            }
        }else{
            $ayudas = \App\Models\Ayudas::orderBy('updated_at','desc')->orderBy('created_at','desc')->paginate(50);
        }

        foreach($ayudas as $ayuda){
            if($ayuda->organo !== null){                                
                $ayuda->dpto = $ayuda->organo->url;
                $ayuda->dptoNombre = $ayuda->organo->Nombre;                
            }
            if($ayuda->departamento !== null){                                
                $ayuda->dpto = $ayuda->departamento->url;
                $ayuda->dptoNombre = $ayuda->departamento->Nombre;            
            }
            $ayuda->totalencajes = \App\Models\Encaje::where('Ayuda_id', $ayuda->id)->count();
        }

        $naturalezas = \App\Models\Naturalezas::where('Activo', 1)->get();

        return view('dashboard/ayudasconvocatorias', [
            'ayudas' => $ayudas,
            'naturalezas' => $naturalezas
        ]);
    }

    public function empresas(Request $request){

        if($request->get('tipo') !== null){
            switch($request->get('tipo')){
                case "perfilcompleto":
                    $empresas = \App\Models\Entidad::where('esCentroTecnologico',0)
                    ->where('perfilCompleto', 1)
                    ->orWhereJsonContains('efectoWow->perfilcompleto', 1)
                    ->orderBy('updated_at', 'DESC')
                    ->paginate(20);
                break;
                case "efectowow":
                    $empresas = \App\Models\Entidad::where('esCentroTecnologico',0)
                    ->whereJsonContains('efectoWow->perfilcompleto', 1)
                    ->whereJsonContains('efectoWow->perfilentrayuda', 1)
                    ->whereJsonContains('efectoWow->enviarelastic', 1)
                    ->orderBy('updated_at', 'DESC')
                    ->paginate(20);
                break;
                case "envioelastic":
                    $empresas = \App\Models\Entidad::where('esCentroTecnologico',0)
                    ->where('firstElastic', '>', 0)
                    ->orWhereJsonContains('efectoWow->enviarelastic', 1)
                    ->orderBy('updated_at', 'DESC')
                    ->paginate(20);
                break;
                default:
                    $empresas = \App\Models\Entidad::where('esCentroTecnologico',0)->orderBy('updated_at', 'DESC')->paginate(20);
                break;
            }

        }else{
            $empresas = \App\Models\Entidad::where('esCentroTecnologico',0)->orderBy('updated_at', 'DESC')->paginate(20);
        }

        $totalempresas = \App\Models\Entidad::where('esCentroTecnologico',0)->count();
        $centros = \App\Models\Entidad::where('esCentroTecnologico',1)->get();
        $naturalezas = \App\Models\Naturalezas::where('Activo', 1)->get();
        $priorizar = \App\Models\PriorizaEmpresas::where('esOrgano',0)->orderBy('created_at', 'DESC')->paginate(20);
        $validar = ValidateCompany::orderBy('updated_at', 'DESC')->paginate(20);
        $ccaas = getAllCcaas();
        $paises = \App\Models\Paises::all();
        $cnaes = \App\Models\Cnaes::all();

        return view('admin.empresas.empresas', [
            'totalempresas' => $totalempresas+$centros->count(),
            'empresas' => $empresas,
            'centros' => $centros,
            'naturalezas' => $naturalezas,
            'ccaas' => $ccaas,
            'paises' => $paises,
            'cnaes' => $cnaes,
            'priorizar' => $priorizar,
            'validaciones' => $validar,
        ]);
    }

    public function statsGenerales(){

        $entidades =  \App\Models\Entidad::where('pais', 'ES')->get();
        $empresassintrl = $entidades->whereNull('valorTrl')->count();
        $empresastrlmenor4 = $entidades->where('valorTrl', '<', 4)->whereNotNull('valorTrl')->count();
        $empresastrl4 = $entidades->where('valorTrl', '=', 4)->count();
        $empresastrl5 = $entidades->where('valorTrl', '=', 5)->count();
        $empresastrl6 = $entidades->where('valorTrl', '=', 6)->count();
        $empresastrl7 = $entidades->where('valorTrl', '=', 7)->count();
        $empresastrlmayor7 = $entidades->where('valorTrl', '>', 7)->count();
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
        $empresas = \App\Models\Entidad::where('esCentroTecnologico',0)->count();
        $centros = \App\Models\Entidad::where('esCentroTecnologico',1)->count();


        return view('dashboard/statsgenerales', [
            'empresas' => $empresas,
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

    public function viewPriorizar($id){

        $priorizar = DB::table('prioriza_empresas')->where('id', $id)->first();

        if(!$priorizar){
            return abort(404);
        }

        $solicitante = DB::table('entidades')->where('CIF', $priorizar->solicitante)->first();

        if($priorizar->esOrgano == 1){
            $organodpto = DB::table('organos')->where('id', $priorizar->idOrgano)->first();
            if(!$organodpto){
                $organodpto = DB::table('departamentos')->where('id', $priorizar->idOrgano)->first();
            }
            $priorizar->NombreOrgano = $organodpto->Nombre;
        }

        if(!$solicitante){
            $solicitante = \App\Models\User::where('email', $priorizar->solicitante)->first();
        }

        if(!$solicitante){
            return abort(404);
        }

        $ccaas = DB::table('ccaa')->orderBy('Nombre', 'asc')->get();
        $cnaes = DB::table('Cnaes')->orderBy('Nombre', 'asc')->get();

        return view('dashboard/editpriorizar', [
            'priorizar' => $priorizar,
            'solicitante' => $solicitante,
            'ccaas' => $ccaas,
            'cnaes' => $cnaes
        ]);
    }

    public function rechazaPriorizar(Request $request){

        $priorizar = DB::table('prioriza_empresas')->where('id', $request->get('id'))->first();

        if($priorizar->esOrgano == 1){
            $organodpto = DB::table('organos')->where('id', $priorizar->idOrgano)->first();
            if(!$organodpto){
                try{
                    DB::table('departamentos')->where('id', $priorizar->idOrgano)->update([
                        'scrapper' => 0,
                        'updated_at' => Carbon::now(),
                    ]);
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return response()->json(['error' => 'Error al actualizar el departamento']);
                }
            }else{
                try{
                    DB::table('organos')->where('id', $priorizar->idOrgano)->update([
                        'scrapper' => 1,
                        'updated_at' => Carbon::now(),
                    ]);
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return response()->json(['error' => 'Error al actualizar el organo']);
                }
            }

            $empresa = DB::table('entidades')->where('CIF', $priorizar->solicitante)->first();

            $userentidades = \App\Models\UsersEntidad::where('entidad_id', $empresa->id)->where('role','admin')->Join('users','users_entidades.users_id', '=', 'users.id')
                ->get();

            if($userentidades){

                $dpto = DB::table('organos')->where('id', $priorizar->idOrgano)->first();

                if(!$dpto){
                    $dpto = DB::table('departamentos')->where('id', $priorizar->idOrgano)->first();
                }

                foreach($userentidades  as $user){

                    $mail = new \App\Mail\RechazaPriorizar($priorizar, $dpto, $request->get('message'));

                    try{
                        Mail::to($user->email)->queue($mail);
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        return response()->json(['error' => 'Error al enviar el correo al usuario']);
                    }

                }

            }
        }else{

            try{
                DB::table('prioriza_empresas')->where('id', $request->get('id'))->update([
                    'updated_at' => Carbon::now(),
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
                return response()->json(['error' => 'Error al actualizar la solicitud']);
            }

            $empresa = DB::table('entidades')->where('CIF', $priorizar->solicitante)->first();

            if($empresa){

                $empresa = DB::table('entidades')->where('CIF', $priorizar->solicitante)->first();

                $userentidades = \App\Models\UsersEntidad::where('entidad_id', $empresa->id)->where('role','admin')->Join('users','users_entidades.users_id', '=', 'users.id')
                    ->get();

                if($userentidades){

                    if($priorizar->esOrgano == 0){

                        $empresapriorizar = DB::table('entidades')->where('CIF', $priorizar->cifPrioritario)->first();

                        if(!$empresapriorizar){
                            $empresapriorizar = DB::table('CifsnoZoho')->where('CIF', $priorizar->cifPrioritario)->first();
                        }

                        foreach($userentidades  as $user){

                            $mail = new \App\Mail\RechazaPriorizar($priorizar, $empresapriorizar, $request->get('message'));

                            try{
                                Mail::to($user->email)->queue($mail);
                            }catch(Exception $e){
                                Log::error($e->getMessage());
                                return response()->json(['error' => 'Error al enviar el correo al usuario']);
                            }

                        }

                    }

                }
            }

        }

        return response()->json(['success'=> 'Solicitud rechazada.']);
    }

    public function aceptaPriorizar(Request $request){

        try{
            $priorizar = \App\Models\PriorizaEmpresas::find($request->get('id'));
            if(!$priorizar){
                return response()->json(['error' => 'No se ha encontrado la solicitud']);
            }
            $priorizar->sacadoEinforma = 1;
            $priorizar->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return response()->json(['error' => 'No se ha procesado correctamente la petición']);
        }

        return response()->json(['success'=> 'Actualizada solicitud.']);
        
    }

    public function viewValidacion($id){

        $validar = ValidateCompany::find($id);

        if(!$validar){
            return abort(404);
        }

        $ccaas = DB::table('ccaa')->get();
        $paises = DB::table('paises')->get();
        $cnaes = DB::table('Cnaes')->get();

        return view('dashboard/editvalidacion', [
            'validacion' => $validar,
            'ccaas' => $ccaas,
            'paises' => $paises,
            'cnaes' => $cnaes,
        ]);
    }

    public function rechazaValidacion(Request $request){

        $validar = ValidateCompany::find($request->get('id'));
        try{

            ValidateCompany::where('id', $request->get('id'))->update(
                [
                    'updated_at' => Carbon::now(),
                    'aceptado' => 1,
                ]
            );
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Error 1, no se ha podido actualizar la petición']);
        }

        $mail = new \App\Mail\RechazaValidacion($validar, $request->get('mensaje'));

        try{
            #Mail::to($validar->solicitante->email)->cc('info@deducible.es')->queue($mail);
            Mail::to($validar->solicitante->email)->queue($mail);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Error 2, no se ha podido enviar el correo']);
        }

        return redirect()->back()->with('success', 'Rechazada la solicitud de acceso');

    }
    public function aceptaValidacion(Request $request){

        $validar = ValidateCompany::find($request->get('id'));

        if($validar->esEntidad == 1){

            $company = DB::table('entidades')->where('CIF', $validar->cif)->first();

            try{

                $checkuserentidad = \App\Models\UsersEntidad::where('users_id', $validar->user_id)->where('entidad_id', $company->id)->first();
                if(!$checkuserentidad){
                    $userEntidad = new \App\Models\UsersEntidad;
                    $userEntidad->users_id = $validar->user_id;
                    $userEntidad->entidad_id = $company->id;
                    $userEntidad->role = 'admin';
                    $userEntidad->save();
                }

                ValidateCompany::where('id', $request->get('id'))->update(
                    [
                        'updated_at' => Carbon::now(),
                        'aceptado' => 1
                    ]
                );
            }catch(Exception $e){
                Log::error($e->getMessage());
                return response()->json(['error' => 'Error al actualizar la información de la solicitud']);
            }

            $mail = new \App\Mail\AceptaValidacion($validar->solicitante, $company);

            try{
                #Mail::to($validar->solicitante->email)->cc('info@deducible.es')->queue($mail);
                Mail::to($validar->solicitante->email)->queue($mail);
            }catch(Exception $e){
                Log::error($e->getMessage());
                return response()->json(['error' => 'Error al enviar el correo al usuario']);
            }

            $messageTg = "El usuario: ".Auth::user()->email." ha acepatado la validación de la empresa: ". $company->Nombre." ahora el usuario:".$validar->solicitante->email." tiene acceso admin.";
            try{
                Artisan::call('send:telegram_notification', [
                    'message' => $messageTg
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
            }

            return response()->json(['success' => 'Información actualizada y dado acceso al usuario']);

        }else{
            if($this->createCifData($validar->cif) === true){

                $newcompany = Einforma::where('identificativo', $validar->cif)->first();

                if($newcompany){

                    try{

                        $checkuserentidad = \App\Models\UsersEntidad::where('users_id', $validar->user_id)->where('entidad_id', $newcompany->id)->first();
                        if(!$checkuserentidad){
                            $userEntidad = new \App\Models\UsersEntidad;
                            $userEntidad->users_id = $validar->user_id;
                            $userEntidad->entidad_id = $newcompany->id;
                            $userEntidad->role = 'admin';
                            $userEntidad->save();
                        }

                        ValidateCompany::where('id', $request->get('id'))->update(
                            [
                                'updated_at' => Carbon::now(),
                                'aceptado' => 1
                            ]
                        );
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        return response()->json(['error' => 'Error al asociar el uaurio con la empresa']);
                    }

                    $messageTg = "El usuario: ".Auth::user()->email." ha acepatado la validación de la empresa: ". $newcompany->Nombre." ahora el usuario:".$validar->solicitante->email." tiene acceso admin.";
                    try{
                        Artisan::call('send:telegram_notification', [
                            'message' => $messageTg
                        ]);
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                    }

                return response()->json(['success' =>  'Información actualizada y dado acceso al usuario']);

                }else{
                    return response()->json(['error' => 'El cif solicitado no existe en einforma']);
                }
            }else{
                return response()->json(['error' => 'Error al obtener los datos de la empresa']);
            }
        }

        return response()->json(['error' => 'Error al actualizar la información']);
    }

    public function editconvocatoria(Request $request){  

        if($request->route('id') === null){
            return abort(419);
        }

        $id = $request->route('id');
        $convocatoria = \App\Models\Ayudas::where('id', $id)->first();

        if(!$convocatoria){
            return abort(419);
        }

        $intereses = \App\Models\Intereses::where('defecto', 'true')->get();
        
        $ccaas = \App\Models\Ccaa::orderBy('Nombre')->get();
        $cnaes = Cache::rememberForever('cnaes_superadmin', function () {
            return \App\Models\Cnaes::all();
        });
        $encajes = \App\Models\Encaje::where('Ayuda_id', $id)->get();

        $org = null;
        $org = \App\Models\Organos::where('id', $convocatoria->Organismo)->first();
        if(!$org){
            $org = \App\Models\Departamentos::where('id', $convocatoria->Organismo)->first();
        }

        $convocatoria->dpto = null;
        if($org){
            $convocatoria->dpto = $org->url;
        }

        #$convocatorias = DB::table('convocatorias')->whereJsonContains('id_ayudas', $id)->get();

        foreach($encajes as $encaje){
            if(is_array($encaje->TagsTec)){
                $encaje->TagsTec = json_decode($encaje->TagsTec, true);
            }
        }

        $trls = \App\Models\Trl::all();
        $naturalezas = \App\Models\Naturalezas::where('Activo', 1)->get();
        $ayuda = \App\Models\Convocatorias::where('id', $convocatoria->id_ayuda)->first();
        $ayudas = \App\Models\Convocatorias::where('extinguida', 0)->get();
        $fondos = \App\Models\Fondos::where('status', 1)->get();

        $condiciones = \App\Models\CondicionesFinancieras::where('idsconvocatorias', 'LIKE', '%'.$convocatoria->id.'%')->get();
        $variables = [
            'Gastos Anuales' => 'Gastos Anuales',
            'Presupuesto del proyecto' => 'Presupuesto del proyecto',
            'Fondos propios' => 'Fondos propios',
            'Circulante' => 'Circulante',
            'Beneficios reales' => 'Beneficios reales',
            'Margen de endeudamiento' => 'Margen de endeudamiento'
        ];
        $variables2 = [
            'Fijo' => 'Fijo',
            'Presupuesto Total del proyecto' => 'Presupuesto Total del proyecto',
            'Presupuesto Mínimo de la ayuda' => 'Presupuesto mínimo de la ayuda',
            'Presupuesto Máximo de la ayuda' => 'Presupuesto Máximo de la ayuda',
        ];
      
        $solicitudespriorizarcashflow = \App\Models\PriorizaAnalisisTesoreria::where('convocatoria_id', $convocatoria->id)->count();

        $subfondos = \App\Models\Subfondos::all();
        $actions = \App\Models\TypeOfActions::all();
        $checksubfondos = false;
        if(isset($convocatoria->FondosEuropeos) && $convocatoria->FondosEuropeos !== null){
            foreach(json_decode($convocatoria->FondosEuropeos, true) as $fondo_id){
                $fondossubfondos = \App\Models\FondosSubfondos::where('fondo_id', $fondo_id)->get();
                if($fondossubfondos->count() > 0){
                    $checksubfondos = true;
                }
            }
            
        }
        
        $capitulosFinanciacion = \App\Models\CapitulosPaises::where('pais', 'ES')->where('activo', 1)->get();

        return view('dashboard/editconvocatoria', [
            'ayuda_convocatoria' => $ayuda,
            'ayuda' => $convocatoria,
            'org' => $org,
            'condiciones' => $condiciones,
            'variables' => $variables,
            'variables2' => $variables2,
            'intereses' => $intereses,
            'ayudasconv' => $ayudas,
            'cnaes' => $cnaes,
            'ccaas' => $ccaas,
            'trls' => $trls,
            'fondos' => $fondos,
            'subfondos' => $subfondos,
            'checksubfondos' => $checksubfondos,
            'actions' => $actions,
            'categorias' => json_decode($convocatoria->Categoria, true),
            'naturalezas' => $naturalezas,
            'encajes' => $encajes,
            'solicitudespriorizarcashflow' => $solicitudespriorizarcashflow,
            'capitulosFinanciacion' => $capitulosFinanciacion
        ]);

    }

    public function viewConvocatoria($id){
        
        if($id === null){
            return abort(419);
        }

        $convocatoria = DB::table('convocatorias')->where('IDConvocatoria', $id)->first();
        if(!$convocatoria){
            return abort(419);
        }
        
        $departamentos = DB::table('departamentos')->get();
        $organos = DB::table('organos')->get();
        $ayudas = \App\Models\Ayudas::get();

        $organo = DB::table('organos')->where('id', $convocatoria->id_organo)->first();

        if(!$organo){
            $organo =  DB::table('departamentos')->where('id', $convocatoria->id_departamento)->first();
        }

        return view('dashboard/convocatoria', [
            'convocatoria' => $convocatoria,
            'organo' => $organo,
            'organos' => $organos,
            'departamentos' => $departamentos,
            'ayudas' => $ayudas
        ]);
    }

    public function concesiones(Request $request){

        $page = $request->query('page');

        $skip = 0;
        $total = 2000;

        if($page > 1){
            $skip = (2000 * $page) - 2000;
            $total = 2000 * $page;
        }

        $concesiones = DB::table('concessions')->orderBy('fecha', 'desc')->skip($skip)->take($total)->get();
        $totalconcesiones = DB::table('concessions')->count();

        foreach($concesiones as $concesion){
            $concesion->format_amount = number_shorten($concesion->amount,0);
            $concesion->format_equivalent_aid = number_shorten($concesion->equivalent_aid,0);
            $concesion->dpto = null;
            if($concesion->id_organo){
                $dpto = DB::table('organos')->where('id',$concesion->id_organo)->select(['Nombre'])->first();
                $concesion->dpto = $dpto->Nombre;
            }
            if($concesion->id_departamento){
                $dpto = DB::table('departamentos')->where('id',$concesion->id_departamento)->select(['Nombre'])->first();
                $concesion->dpto = $dpto->Nombre;
            }
        }

        return view('dashboard/concesiones', [
            'concesiones' => $concesiones,
            'totalconcesiones' => $totalconcesiones,
            'skip' => $skip,
            'total' => $total,
        ]);
    }

    public function viewEmpresa($id, $cif){

        $empresa = DB::table('entidades')->where('entidades.id', $id)->where('CIF', $cif)->first();
        $nozoho = 0;
        if(!$empresa){
            $empresa = DB::table('CifsnoZoho')->where('id', $id)->where('CIF', $cif)->first();
            $nozoho = 1;
            if(!$empresa){
                return abort(404);
            }
        }

        $textos =  DB::table('TextosElastic')->where('CIF', $empresa->CIF)->first();
        $data = DB::table('einforma')->where('identificativo', $empresa->CIF)->first();

        $ccaa = DB::table('ccaa')->orderBy('Nombre')->get();
        $intereses = DB::table('Intereses')->where('defecto', 'true')->get();
        $naturalezas = DB::table('naturalezas')->get();

        $patentes = DB::table('patentes')->where('CIF', $empresa->CIF)->get();
        $concesiones = DB::table('concessions')->where('custom_field_cif', $empresa->CIF)->get();
        $sellopyme = DB::table('pymes')->where('CIF', $empresa->CIF)->get();
        $einforma = DB::table('einforma')->where('identificativo', $empresa->CIF)->get();

        if($nozoho == 1){
            $empresa->Marca = null;
            $empresa->Web = null;
            $empresa->Ccaa = null;
            $empresa->Intereses = json_encode(array());
            $empresa->naturalezaEmpresa = json_encode(array());
            $empresa->esConsultoria = 0;
            $empresa->maxProyectos = 10;
            $empresa->TextosLineasTec = json_encode(array());
            $empresa->NumeroLineasTec = 2;
        }

        if($empresa->esCentroTecnologico == 1){

            $organos = \App\Models\Organos::all()->toArray();
            $departamentos = \App\Models\Departamentos::all()->toArray();
            $organismos = array_merge($organos, $departamentos);
            usort($organismos, function($a, $b) {
                return $a['Nombre'] <=> $b['Nombre'];
            });
        }else{
            $organismos = array();
        }

        $solicitudes = \App\Models\PriorizaEmpresas::where('cifPrioritario', $cif)->get();

        return view('dashboard/editempresa', [
            'empresa' => $empresa,
            'textos' => $textos,
            'data' => $data,
            'ccaa' => $ccaa,
            'intereses' => $intereses,
            'naturalezas' => $naturalezas,
            'patentes' => $patentes,
            'concesiones' => $concesiones,
            'sellopyme' => $sellopyme,
            'einforma' => $einforma,
            'nozoho' => $nozoho,
            'organismos' => $organismos,
            'solicitudes' => $solicitudes
        ]);
    }

    public function proyectos(){

        $proyectos =\App\Models\Proyectos::leftJoin('convocatorias_ayudas', 'convocatorias_ayudas.id', '=', 'proyectos.IdAyuda')
        ->select('proyectos.id as idproyecto', 'proyectos.*','convocatorias_ayudas.Titulo as ayudanombre')
        ->where('proyectos.esEuropeo', 0)->where('proyectos.importado', 0)->orderByDesc('Fecha')
        ->paginate(200);

        return view('dashboard/proyectos', [
            'proyectos' => $proyectos,
        ]);
    }

    public function asignador(Request $request){

        $organos = \App\Models\Organos::all();
        $departamentos = \App\Models\Departamentos::all();
        $convocatorias = \App\Models\Convocatorias::orderBy('titulo')->get();
        $organismo = ($request->get('organo') !== null) ? $request->get('organo') : $request->get('departamento');
        
        if($request->get('ayuda') !== null){
            $ayudas = \App\Models\Ayudas::where('id_ayuda', $request->get('ayuda'))->orderBy('Titulo')->get();
        }else{
            $ayudas = \App\Models\Ayudas::orderBy('Titulo')->get();
        }

        $departamentos = collect($departamentos)->pluck('Nombre', 'id');
        $organos = collect($organos)->pluck('Nombre', 'id');
        $ayudas = collect($ayudas)->pluck('Titulo', 'id');
        $convocatorias = collect($convocatorias)->pluck('titulo', 'id');

        $proyectos = null;
        $concesiones = null;
        $totalproyectos = 0;
        $organismo = ($request->get('organo') !== null) ? $request->get('organo') : $request->get('departamento');

        if($request->get('tipo') !== null && $request->get('tipo') == "proyectos"){
            if($request->get('texto_convocatoria') !== null){
                $proyectos = \App\Models\Proyectos::where('organismo', $organismo)->where('id_europeo', $request->get('texto_convocatoria'))->take(100)->get();
                $totalproyectos = \App\Models\Proyectos::where('organismo', $organismo)->where('id_europeo', $request->get('texto_convocatoria'))->count();
            }
            if($request->get('texto_referencia') !== null){
                $proyectos = \App\Models\Proyectos::where('organismo', $organismo)->where('Acronimo', 'LIKE', $request->get('texto_referencia').'%')->take(100)->get();
                $totalproyectos = \App\Models\Proyectos::where('organismo', $organismo)->where('Acronimo', 'LIKE', $request->get('texto_referencia').'%')->count();
            }
            //dump($request->all());
        }

        return view('dashboard/asignador', [
            'organos' => $organos,
            'departamentos' => $departamentos,
            'convocatorias' => $ayudas,
            'ayudas' => $convocatorias,
            'proyectos' => $proyectos,
            'totalproyectos' => $totalproyectos,
            'concesiones' => $concesiones

        ]);
    }

    public function asignarDatosProyectos(Request $request){

        $organismo = ($request->get('organo') !== null) ? $request->get('organo') : $request->get('departamento');

        if($request->get('tipo') !== null && $request->get('tipo') == "proyectos"){

            if($request->get('convocatoria') !== null){
                $ayuda = \App\Models\Ayudas::where('id', $request->get('convocatoria'))->where('id_ayuda', $request->get('ayuda'))->first();
            }else{
                $ayuda = \App\Models\Ayudas::where('id_ayuda', $request->get('ayuda'))->first();
            }

            if(!$ayuda){
                $ayuda = \App\Models\Convocatorias::find($request->get('ayuda'));
                if(!$ayuda){
                    log::error("No se ha encontrado la ayuda para los datos: ".$request->get('convocatoria')." - ".$request->get('ayuda'));
                    return redirect()->back()->withErrors('Error al asignar proyectos por texto convocatoria, 001');
                }
            }

            if($request->get('texto_convocatoria') !== null){
                try{
                    if($request->get('ayuda') !== null){
                        \App\Models\Proyectos::where('organismo', $organismo)->where('Acronimo', 'LIKE', $request->get('texto_convocatoria').'%')->update(
                            [
                                'idAyudaAcronimo' => ($request->get('convocatoria') !== null) ? $ayuda->IdConvocatoriaStr: null,
                                'idConvocatoriaAcronimo' => ($request->get('ayuda') !== null) ? $request->get('ayuda') : null,
                            ]
                        );
                    }else{
                        \App\Models\Proyectos::where('organismo', $organismo)->where('Acronimo', 'LIKE', $request->get('texto_convocatoria').'%')->update(
                            [
                                'idAyudaAcronimo' => $ayuda->IdConvocatoriaStr,
                            ]
                        );
                    }
                }catch(Exception $e){
                    log::error($e->getMessage());
                    return redirect()->back()->withErrors('Error al asignar proyectos por texto convocatoria, 002');
                }
            }
            if($request->get('texto_referencia') !== null){
                try{
                    if($request->get('ayuda') !== null){
                        \App\Models\Proyectos::where('organismo', $organismo)->where('Acronimo', 'LIKE', $request->get('texto_referencia').'%')->update(
                            [
                                'idAyudaAcronimo' => ($request->get('convocatoria') !== null) ? $ayuda->IdConvocatoriaStr: null,
                                'idConvocatoriaAcronimo' => ($request->get('ayuda') !== null) ? $request->get('ayuda') : null,
                            ]
                        );
                    }else{
                        \App\Models\Proyectos::where('organismo', $organismo)->where('Acronimo', 'LIKE', $request->get('texto_referencia').'%')->update(
                            [
                                'idAyudaAcronimo' => $ayuda->IdConvocatoriaStr,
                            ]
                        );
                    }
                }catch(Exception $e){
                    log::error($e->getMessage());
                    return redirect()->back()->withErrors('Error al asignar proyectos por texto referencia empieza por, 003');
                }
            }
            //dump($request->all());
        }

        $total = 0;
        if($request->get('texto_convocatoria') !== null){
            $total = \App\Models\Proyectos::where('organismo', $organismo)->where('Acronimo', 'LIKE', $request->get('texto_convocatoria').'%')->count();
        }else{
            $total = \App\Models\Proyectos::where('organismo', $organismo)->where('Acronimo', 'LIKE', $request->get('texto_referencia').'%')->count();
        }

        return redirect()->back()->withSuccess($total.': proyectos asignados correctamente.');
    }

    public function proyectosImportados(){

        $proyectosImportados = \App\Models\Proyectos::where('esEuropeo', 0)->where('importado', 1)->where('movidoEntidad', 0)->where('matchConcesion', 0)->where('Estado', 'Cerrado')->orderByDesc('Fecha')->paginate(200);
        $selectayudas = \App\Models\Ayudas::select('Id', 'Titulo')->get();
        $ccaas = \App\Models\Ccaa::get();

        $archivos = \App\Models\Proyectos::whereNotNull('fromFile')->where('created_at', '>=', Carbon::now()->subDays(7))->groupBy('fromFile')->pluck('fromFile','fromFile')->toArray();
        $archivospendientes = false;
        $listaarchivos = array();
        $files = Storage::disk('s3_files')->files('proyectos/import');

        foreach($files as $file){
            $filedata =  Storage::disk('s3_files')->lastModified($file);
            $date = Carbon::createFromTimestamp($filedata)->toDateTimeString();
            if($date > Carbon::now()->subDays(7)){
                $archivospendientes = false;
                $listaarchivos[$file] = $file;
            }
        }

        $organos = \App\Models\Organos::all();
        $departamentos = \App\Models\Departamentos::all();
        $organismos = $organos->merge($departamentos);
        $organismosarray = $organismos->sortBy('Nombre')->pluck('Nombre', 'id')->toArray();

        return view('dashboard/proyectosimportados', [
            'proyectosImportados' => $proyectosImportados,
            'selectayudas' => $selectayudas,
            'ccaas' => $ccaas,
            'archivosimportacion' => $archivos,
            'archivospendientes' => $archivospendientes,
            'listaarchivos' => $listaarchivos,
            'organismosarray' => $organismosarray
        ]);

    }

    public function proyectosDesestimados(){

        $proyectosImportadosDesestimados = \App\Models\Proyectos::where('esEuropeo', 0)->where('importado', 1)->where('Estado', 'Desestimado')->orderByDesc('Fecha')->paginate(200);
        $selectayudas = \App\Models\Ayudas::select('Id', 'Titulo')->get();
        $ccaas = \App\Models\Ccaa::get();

        $archivos = \App\Models\Proyectos::whereNotNull('fromFile')->groupBy('fromFile')->pluck('fromFile','fromFile')->toArray();
        $archivospendientes = false;
        $listaarchivos = array();
        $files = Storage::disk('s3_files')->files('proyectos/import');

        foreach($files as $file){
            $filedata =  Storage::disk('s3_files')->lastModified($file);
            $date = Carbon::createFromTimestamp($filedata)->toDateTimeString();
            if($date > Carbon::now()->subDays(7)){
                $archivospendientes = false;
                $listaarchivos[$file] = $file;
            }
        }


        return view('dashboard/proyectosdesestimados', [
            'proyectosDesestimados' => $proyectosImportadosDesestimados,
            'selectayudas' => $selectayudas,
            'ccaas' => $ccaas,
            'archivosimportacion' => $archivos,
            'archivospendientes' => $archivospendientes,
            'listaarchivos' => $listaarchivos
        ]);

    }

    public function proyectosImportadosMatchs(Request $request){

        $proyectosMatch = null;
        $proyectosNoMatch = null;
        $proyectosNoCif = null;

        if($request->get('filtroarchivo') !== null && $request->get('filtroarchivo') != ""){

            if($request->get('tipo') == "empresas"){
                $proyectosNoCif = \App\Models\Proyectos::where('esEuropeo', 0)->where('importado', 1)->where('movidoEntidad', 0)->where('empresaPrincipal', 'XXXXXXXXX')->where('fromFile', $request->get('filtroarchivo'))
                ->orderByDesc('Fecha')->paginate(200);
                $proyectosMatch = \App\Models\Proyectos::where('esEuropeo', 0)->where('importado', 1)->where('movidoEntidad', '>', 0)->where('empresaPrincipal', '!=', 'XXXXXXXXX')->where('fromFile', $request->get('filtroarchivo'))
                ->orderByDesc('Fecha')->paginate(200);
                $proyectosNoMatch = \App\Models\Proyectos::where('esEuropeo', 0)->where('importado', 1)->where('movidoEntidad', 0)->where('empresaPrincipal', '!=', 'XXXXXXXXX')->where('fromFile', $request->get('filtroarchivo'))
                ->orderByDesc('Fecha')->paginate(200);
            }

            if($request->get('tipo') == "concesiones"){
                $proyectosMatch = \App\Models\Proyectos::where('esEuropeo', 0)->where('importado', 1)->where('matchConcesion', 1)
                ->where('empresaPrincipal', '!=', 'XXXXXXXXX')->whereNull('idConcesion')->where('Estado', 'Cerrado')->where('fromFile', $request->get('filtroarchivo'))
                ->orderByDesc('Fecha')->paginate(200);
                $proyectosNoMatch = \App\Models\Proyectos::where('esEuropeo', 0)->where('importado', 1)->where('matchConcesion', 0)
                ->where('empresaPrincipal', '!=', 'XXXXXXXXX')->whereNull('idConcesion')->where('Estado', 'Cerrado')->where('fromFile', $request->get('filtroarchivo'))
                ->orderByDesc('Fecha')->paginate(200);
            }

            if($request->get('tipo') == "ayudas"){
                $proyectosMatch = \App\Models\Proyectos::where('esEuropeo', 0)->where('importado', 1)->whereNotNull('idAyudaAcronimo')->where('fromFile', $request->get('filtroarchivo'))
                ->orderByDesc('Fecha')->paginate(200);
                $proyectosNoMatch = \App\Models\Proyectos::where('esEuropeo', 0)->where('importado', 1)->whereNull('idAyudaAcronimo')->where('fromFile', $request->get('filtroarchivo'))
                ->orderByDesc('Fecha')->paginate(200);
            }

        }else{

            if($request->query('tipo') == "empresas"){
                $proyectosNoCif = \App\Models\Proyectos::where('esEuropeo', 0)->where('importado', 1)->where('movidoEntidad', 0)->where('empresaPrincipal', 'XXXXXXXXX')->orderByDesc('Fecha')->paginate(200);
                $proyectosMatch = \App\Models\Proyectos::where('esEuropeo', 0)->where('importado', 1)->where('movidoEntidad', '>', 0)->where('empresaPrincipal', '!=', 'XXXXXXXXX')->orderByDesc('Fecha')->paginate(200);
                $proyectosNoMatch = \App\Models\Proyectos::where('esEuropeo', 0)->where('importado', 1)->where('movidoEntidad', 0)->where('empresaPrincipal', '!=', 'XXXXXXXXX')->orderByDesc('Fecha')->paginate(200);
            }

            if($request->query('tipo') == "concesiones"){
                $proyectosMatch = \App\Models\Proyectos::where('esEuropeo', 0)->where('importado', 1)->where('matchConcesion', 1)->where('empresaPrincipal', '!=', 'XXXXXXXXX')
                ->whereNull('idConcesion')->where('Estado', 'Cerrado')->orderByDesc('Fecha')->paginate(200);
                $proyectosNoMatch = \App\Models\Proyectos::where('esEuropeo', 0)->where('importado', 1)->where('matchConcesion', 0)->where('empresaPrincipal', '!=', 'XXXXXXXXX')
                ->whereNull('idConcesion')->where('Estado', 'Cerrado')->orderByDesc('Fecha')->paginate(200);
                
            }

            if($request->query('tipo') == "ayudas"){
                $proyectosMatch = \App\Models\Proyectos::where('esEuropeo', 0)->where('importado', 1)->whereNotNull('idAyudaAcronimo')->orderByDesc('Fecha')->paginate(200);
                $proyectosNoMatch = \App\Models\Proyectos::where('esEuropeo', 0)->where('importado', 1)->whereNull('idAyudaAcronimo')->orderByDesc('Fecha')->paginate(200);
            }

        }

        $selectayudas = \App\Models\Ayudas::select('Id', 'Titulo')->get();
        $ccaas = \App\Models\Ccaa::get();
        $archivos = \App\Models\Proyectos::whereNotNull('fromFile')->groupBy('fromFile')->pluck('fromFile','fromFile')->toArray();
        $archivospendientes = false;
        $listaarchivos = array();
        $files = Storage::disk('s3_files')->files('proyectos/import');

        foreach($files as $file){                    
            $filedata =  Storage::disk('s3_files')->lastModified($file);
            $date = Carbon::createFromTimestamp($filedata)->toDateTimeString();
            if($date > Carbon::now()->subDays(7)){
                $archivospendientes = false;
                $listaarchivos[$file] = $file;
            }
        }

        return view('dashboard/proyectosimportadosmatchs', [
                'proyectosNoCif' => $proyectosNoCif,
                'proyectosMatch' => $proyectosMatch,
                'proyectosNoMatch' => $proyectosNoMatch,
                'selectayudas' => $selectayudas,
                'ccaas' => $ccaas,
                'archivosimportacion' => $archivos,
                'archivospendientes' => $archivospendientes,
                'listaarchivos' => $listaarchivos
            ]
        );

    }

    public function editarProyecto(Request $request){

        $id = $request->route('id');
        $proyecto = DB::table('proyectos')->where('id', $id)->first();
        $intereses = DB::table('Intereses')->where('defecto', 'true')->get();
        $ccaas = DB::table('ccaa')->orderBy('Nombre')->get();
        $encajes = DB::table('Encajes_zoho')->where('Proyecto_id', $id)->get();
        $ayuda =  \App\Models\Ayudas::where('id', $proyecto->IdAyuda)->first();

        if($ayuda){
            if($ayuda->Organismo){
                $dpto = DB::table('departamentos')->where('id', $ayuda->Organismo)->first();
                if(!$dpto){
                    $dpto = DB::table('organos')->where('id', $ayuda->Organismo)->first();
                }
                $ayuda->dpto = null;
                if($dpto){
                    $ayuda->dpto = $dpto->url;
                }

            }
        }

        $ayudas = \App\Models\Ayudas::select('Id', 'Titulo', 'IdConvocatoriaStr')->get();
        $empresa = DB::table('entidades')->where('CIF', $proyecto->empresaPrincipal)->first();
        $naturalezas = DB::table('naturalezas')->where('Activo', 1)->get();

        return view('dashboard/editproyecto', [
            'proyecto' => $proyecto,
            'empresa' => $empresa,
            'ayuda' => $ayuda,
            'encajes' => $encajes,
            'ayudas' => $ayudas,
            'intereses' => $intereses,
            'ccaas' => $ccaas,
            'naturalezas' => $naturalezas,
        ]);

    }

    public function config(Request $request){

        $config = DB::table('Config')->get();
        $umbral_ayudas = app(GeneralSettings::class)->umbral_ayudas;
        $umbral_proyectos = app(GeneralSettings::class)->umbral_proyectos;
        $allow_register = app(GeneralSettings::class)->allow_register;
        $enlace_evento = app(GeneralSettings::class)->enlace_evento;
        $texto_evento = app(GeneralSettings::class)->texto_evento;
        $enable_axesor = app(GeneralSettings::class)->enable_axesor;
        $enable_einforma = app(GeneralSettings::class)->enable_einforma;
        $master_featured = app(GeneralSettings::class)->master_featured;
        $cnaes = DB::table('Cnaes')->get();
        $recompensas = \App\Models\RecompensasTecnologicas::paginate(1000);
        $condicionesrecompensas = \App\Models\CondicionesRecompensas::all();
        $scrappersdata = DB::table('settings')->where('group', 'scrapper')->get();

        foreach($scrappersdata as $data){

            $datos = json_decode($data->payload,true);
            $data->datos = $datos;
            $search = explode("-",$data->name);
            if($search[0] == "organo"){
                $orgdpto = DB::table('organos')->where('id', $search[1])->select(['Nombre'])->first();
            }
            if($search[0] == "departamento"){
                $orgdpto = DB::table('departamentos')->where('id', $search[1])->select(['Nombre'])->first();
            }

            $data->orgdpto = $orgdpto;
        }

        $alarmas = DB::table('alarms')->get();

        return view('dashboard/configuration', [
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

    public function scrappers(){

        $patentes = DB::table('buffer_patentes')->get();
        $concesiones = array();
        $einforma = DB::table('einforma')->whereJsonLength('web', 0)->take(2000)->get();

        return view('dashboard/scrappers', [
            'patentes' => $patentes,
            'concesiones' => $concesiones,
            'einforma' => $einforma,
            'option' => 0,
        ]);
    }

    public function viewScrapper(Request $request){

        $scrapper = DB::table('settings')->where('group', 'scrapper')->where('id', $request->route('id'))->first();

        if(!$scrapper){
            return abort(404);
        }

        $datos = json_decode($scrapper->payload,true);
        $scrapper->datos = $datos;

        $search = explode("-",$scrapper->name);
        if($search[0] == "organo"){
            $orgdpto = DB::table('organos')->where('id', $search[1])->select(['Nombre'])->first();
        }
        if($search[0] == "departamento"){
            $orgdpto = DB::table('departamentos')->where('id', $search[1])->select(['Nombre'])->first();
        }

        $scrapper->orgdpto = $orgdpto;

        return view('dashboard/editscrapper', [
            'scrapper' => $scrapper,
        ]);
    }

    public function editarScrapper(Request $request){

        if($request->get('setnull')){
            $update = null;
        }else{
            $update = Carbon::createFromFormat('d/m/Y H:i:s', $request->get('inicio'))->format('Y-m-d H:i:s');
        }

        $jsonddbb =  DB::table('settings')->where('id', $request->get('id'))->select(['payload'])->first();

        $jsondata = json_decode($jsonddbb->payload, true);

        $jsondata['current'] = $request->get('ultima');

        try{
            DB::table('settings')->where('id', $request->get('id'))->update([
                'payload' => json_encode($jsondata),
                'updated_at' => $update,
            ]);
        }catch(Exception $e){
            return redirect()->back()->withErrors('Error al actualizar los datos');
        }

        return redirect()->back()->withSuccess('Scrapper actualizado correctamente');

    }

    public function patentes(){

        $patentes = \App\Models\Patentes::all();
        $patentessinmatch = collect($patentes)->where('MatchCif', '')->where('CIF', '');
        $patentes2 = collect($patentes)->where('MatchCif', null)->where('CIF', null);
        $patentessinmatch->merge($patentes2);
        $patentesconmatch = collect($patentes)->where('MatchCif', '!=', '')->where('CIF', '!=', '');

        return view('dashboard/patentes', [
            'patentessinmatch' => $patentessinmatch,
            'patentesconmatch' => $patentesconmatch,
        ]);
    }

    public function viewPatente(Request $request){

        $patente = \App\Models\Patentes::where('id', $request->route('id'))->first();

        if(!$patente){
            return abort(404);
        }

        return view('dashboard/editpatente', [
            'patente' => $patente,
        ]);
    }

    public function editarPatente(Request $request){

        $patente = \App\Models\Patentes::find($request->get('id'));

        if(!$patente){
            return redirect()->back()->withErrors('No se ha podido guardar la patente');    
        }

        try{
            $patente->MatchCif = "Manual";
            $patente->CIF = $request->get('cif');
            $patente->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido guardar la patente');    
        }

        $textos = \App\Models\TextosElastic::where('CIF', $request->get('cif'))->first();

        try{
            if($textos){
                if(strrpos($textos->Textos_Tecnologia, $request->get('titulo')) !== false){
                    $textos->Textos_Tecnologia = $textos->Textos_Tecnologia.", ".$request->get('titulo');
                    $textos->save();
                }
            }else{
                $textos = new \App\Models\TextosElastic();
                $textos->CIF = $request->get('cif');
                $textos->Textos_Tecnologia = $request->get('titulo');
                $textos->Last_Update = Carbon::now();
                $textos->save();
            }
        }catch(Exception $e){
            Log::error($e->getMessage());
        }

        if($request->get('iguales') !== null){
            $find = [
                "~SLNE(?!.*SLNE)~", "~SCoop(?!.*SCoop)~", "~SLL(?!.*SLL)~", "~SLU(?!.*SLU)~", "~SAU(?!.*SAU)~", "~SPA(?!.*SPA)~", "~SLA(?!.*SLA)~", "~SL(?!.*SL)~", "~SA(?!.*SA)~", "~S L(?!.*S L)~", 
                "~S A(?!.*S A)~", "~S L U(?!.*S A U)~", "~S L U(?!.*S A U)~", "~S P A(?!.*S P A)~", "~S L L(?!.*S L L)~"
            ];
            $replace = [''];
            $name = trim(str_replace(['[ES]','[IT]'], ['',''], $patente->Solicitantes));
            $name = preg_replace($find, $replace, $name, 1, $count);
            if($count > 1){
                
            }
            $name = preg_replace('/[^ \w-]/u', '', $name);
            $name = str_replace(' ','%', $name);
            try{
                \App\Models\Patentes::where('Solicitantes', 'LIKE', $name.'%')->update([
                    'MatchCif' => "Manual",
                    'CIF' => $request->get('cif')
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
            }
        }

        return redirect()->back()->withSuccess('Patente actualizada correctamente');

    }

    public function viewEinforma(Request $request){

        $einforma = DB::table('einforma')->where('id', $request->route('id'))->first();

        if(!$einforma){
            return abort(404);
        }

        return view('dashboard/editeinforma', [
            'einforma' => $einforma,
        ]);
    }

    public function editarEinforma(Request $request){

        $webs = json_encode(explode(",", $request->get('webs')), JSON_UNESCAPED_SLASHES);

        try{
            DB::table('einforma')->where('id', $request->get('id'))->update([
                'web' => $webs,
                'updated_at' => Carbon::now()
            ]);
        }catch(Exception $e){
            return redirect()->back()->withErrors('Error al actualizar los datos');
        }

        return redirect()->back()->withSuccess('Einforma actualizado correctamente');

    }

    public function viewConcesion(Request $request){

        $concesion = DB::table('buffer_concesiones')->where('id', $request->route('id'))->first();

        if(!$concesion){

            $concesion = \App\Models\Concessions::find($request->route('id'));
            if(!$concesion){
                return abort(404);
            }
        }

        return view('dashboard/editconcesion', [
            'concesion' => $concesion,
        ]);
    }

    public function editarConcesion(Request $request){

        #1. Actualizamos el buffer de concesiones y la concesion en su tabla
        try{
            DB::table('buffer_concesiones')->where('id', $request->get('id'))->update([
                'custom_field_cif' => $request->get('custom_field_cif'),
                'updated_at' => Carbon::now()
            ]);
        }catch(Exception $e){
            return redirect()->back()->withErrors('Error al actualizar la concesión');
        }

        try{
            DB::table('concessions')->where('id', $request->get('id'))->update([
                'custom_field_cif' => $request->get('custom_field_cif'),
                'updated_at' => Carbon::now()
            ]);
        }catch(Exception $e){
            return redirect()->back()->withErrors('Error al actualizar la concesión');
        }

        #2. revisamos y actualizamos los matchinternos
        $matchinterno = DB::table('MatchInterno')->where('CIF', $request->get('custom_field_cif'))->first();

        if($matchinterno){
            $concesiones = json_decode($matchinterno->id_concessions , true);

            if(!in_array($request->get('id'), $concesiones)){
                array_push($concesiones, (int)$request->get('id'));
            }

            try{
                DB::table('MatchInterno')->where('CIF', $request->get('custom_field_cif'))->update([
                    'id_concessions' => json_encode($concesiones),
                    'MatchUpdate' => Carbon::now(),
                ]);
            }catch(Exception $e){
                return redirect()->back()->withErrors('2.1 Error al actualizar la patente');
            }
        }else{
            $concesiones = array($request->get('id'));

            try{
                DB::table('MatchInterno')->insert([
                    'id_concessions' => json_encode($concesiones),
                    'id_patentes' => json_encode(array()),
                    'id_pymes' => json_encode(array($request->get('custom_field_cif'))),
                    'id_convocatoria' => json_encode(array()),
                    'CIF' => $request->get('custom_field_cif'),
                    'PageRank' => 0,
                    'MatchUpdate' => Carbon::now(),
                ]);
            }catch(Exception $e){
                return redirect()->back()->withErrors('2.1 Error al actualizar la patente');
            }
        }

        #3. si existe datos einforma, actualiza cantidad y calidad I+D
        $einforma = DB::table('einforma')->where('identificativo', $request->get('custom_field_cif'))->first();
        if($einforma){
            try{
                Artisan::call('calcula:I+D', [
                    'cif' => $request->get('custom_field_cif')
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('3. No se podido calcular el i+d de la empresa');
            }
        #3.1 si no existe en einforma calculamos el viejo pageRank
        }else{
            try{
                Artisan::call('create:pagerank', [
                    'cif' => $request->get('custom_field_cif')
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('3. No se podido calcular el i+d de la empresa');
            }
        }

        #4 solo si ejecuta el paso anterior envido de datos a elastic
        if($einforma){
            try{
                Artisan::call('elastic:companies', [
                    'cif' => $request->get('custom_field_cif')
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('4. No se podido enviar la empresa a elastic');
            }
        }

        return redirect()->back()->withSuccess('Concesión actualizada correctamente');
    }

    public function lastnews(){

        $lastnews = DB::table('noticias')
        ->leftJoin('convocatorias_ayudas', 'convocatorias_ayudas.id', '=', 'noticias.id_ayuda')->orderBy('fecha', 'desc')
        ->select('noticias.id as noticiaid', 'noticias.*', 'convocatorias_ayudas.*')->get();

        if($lastnews->isNotEmpty()){
            foreach($lastnews as $noticia){
                $noticia->dpto = null;
                $noticiadpto = DB::table('organos')->where('id', $noticia->id_organo)->first();
                if(!$noticiadpto){
                    $noticiadpto = DB::table('departamentos')->where('id', $noticia->id_organo)->first();
                }
                if($noticiadpto){
                    $noticia->dpto = ($noticiadpto->Acronimo)
                    ? mb_strtolower(str_replace(" ","-", $noticiadpto->Acronimo))
                    : mb_strtolower(str_replace(" ","-", $noticiadpto->Nombre));
                }
            }
        }

        return view('dashboard/lastnews', [
            'lastnews' => $lastnews,
        ]);
    }

    public function viewLastnew(Request $request){

        $noticia = DB::table('noticias')->where('noticias.id', $request->route('id'))->leftJoin('convocatorias_ayudas', 'convocatorias_ayudas.id', '=', 'noticias.id_ayuda')
        ->first();

        if(!$noticia){
            return abort(404);
        }

        $noticia->dpto = null;
        $noticiadpto = DB::table('organos')->where('id', $noticia->id_organo)->first();
        if(!$noticiadpto){
            $noticiadpto = DB::table('departamentos')->where('id', $noticia->id_organo)->first();
        }
        if($noticiadpto){
            $noticia->dpto = ($noticiadpto->Acronimo)
            ? $noticiadpto->Acronimo
            : $noticiadpto->Nombre;
        }

        return view('dashboard/viewlastnew', [
            'noticia' => $noticia,
        ]);
    }

    private function createCifData($cif){

        try{
            $response = Artisan::call('get:axesor', [
                'cif' => $cif
            ]);
            /*$response = Artisan::call('sync:einforma', [
                'cif' => $cif,
                'tipo' => 'manual'
            ]);*/
        }catch(Exception $e){
            Log::error($e->getMessage());
            return false;
        }

        if($response == 0){
            return "noeinforma";
        }

        try{
            Artisan::call('calcula:I+D', [
                'cif' => $cif
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return false;
        }

        try{
            Artisan::call('create:pagerank', [
                'cif' => $cif
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return false;
        }

        try{
            Artisan::call('elastic:companies', [
                'cif' => $cif
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return false;
        }

        return true;

    }

    public function createEinformaManual(Request $request){

        $pasivoNoCorriente = ($request->get('pasivonocorriente') !== null) ? $request->get('pasivonocorriente') : 0;
        $pasivoCorriente = ($request->get('pasivocorriente') !== null) ? $request->get('pasivocorriente') : 0;
        $activoCorriente = ($request->get('activocorriente') !== null) ? $request->get('activocorriente') : 0;
        $ebitda = ($request->get('ebitda') !== null) ? $request->get('ebitda') : 0;
        $patrimonioNeto = ($request->get('patrimonioneto') !== null) ? $request->get('patrimonioneto') : 0;
        $importeNetoCifraNegocios = ($request->get('importenetocifra') !== null) ? $request->get('importenetocifra') : 0;
        $trabajosInmovilizado = $request->get('trabajosinmovilizado');

        $balanceTotal = $patrimonioNeto+$pasivoCorriente+$pasivoNoCorriente;
        $activoNoCorriente = $balanceTotal - $activoCorriente;
        $gastoAnual = $importeNetoCifraNegocios-$ebitda;

        $categoriaEmpresa = 'error';

        $encrisis = 3;

        if($request->get('primaemision') === null){
            $primaEmision = 0;
        }else{
            $primaEmision = $request->get('primaemision');
        }


        $dbDate = \Carbon\Carbon::parse($request->get('fecha'));
        $diffYears = \Carbon\Carbon::now()->diffInYears($dbDate);

        if($patrimonioNeto && $diffYears > 3){

            if($request->get('capitalsocial') !== null && $request->get('capitalsocial') > 0){
                $diff = ($patrimonioNeto/2) - ($patrimonioNeto + $primaEmision)/2;
                if($diff > 10000){
                    $encrisis = 0; #No en crisis
                }elseif($diff >= -10000 && $diff <= 10000){
                    $encrisis = 1; #posible en crisis
                }elseif($diff < -10000){
                    $encrisis = 2; #en crisis
                }
            }
        }

        if($request->get('empleados') < 10 || $balanceTotal <= 2000000 || $importeNetoCifraNegocios <= 2000000){
            $categoriaEmpresa = 'Micro';
        }

        if(($request->get('empleados') > 10 && $request->get('empleados') < 50) || ($balanceTotal > 2000000 && $balanceTotal <= 10000000) || ($importeNetoCifraNegocios > 2000000 && $importeNetoCifraNegocios < 10000000)){
            $categoriaEmpresa = 'Pequeña';
        }

        if(($request->get('empleados') >= 50 && $request->get('empleados') < 250) || ($balanceTotal > 10000000 && $balanceTotal  <= 43000000) || ($importeNetoCifraNegocios > 10000000 && $importeNetoCifraNegocios <= 50000000)){
            $categoriaEmpresa = 'Mediana';
        }

        if($request->get('empleados') >= 250 || $balanceTotal > 43000000 || $importeNetoCifraNegocios > 50000000){
            $categoriaEmpresa = 'Grande';
        }

        try{
            DB::table('einforma')
            ->updateOrInsert(
                [
                    'identificativo' => $request->get('cif'),
                ],
                [
                    'anioBalance' => $request->get('balance'),
                    'ultimaActualizacion' => Carbon::now(),
                    'denominacion' => $request->get('denominacion'),
                    'domicilioSocial' => $request->get('domicilio'),
                    'localidad' => $request->get('localidad'),
                    'cnae' => $request->get('cnaes'),
                    'situacion' => "Activa",
                    'web' => $request->get('web'),
                    'capitalSocial' => ($request->get('capitalsocial') !== null) ? round($request->get('capitalsocial'),0,0): null,
                    'empleados' => $request->get('empleados'),
                    'fechaConstitucion' => Carbon::parse($request->get('fecha'))->format('Y-m-d'),
                    'objetoSocial' => $request->get('objetoSocial'),
                    'importeNetoCifraNegocios' => ($request->get('importenetocifra') !== null) ? round($request->get('importenetocifra'),0,0): null,
                    'patrimonioNeto' => ($request->get('patrimonioneto') !== null) ? round($request->get('patrimonioneto'),0,0): null,
                    'pasivoNoCorriente' => ($request->get('pasivonocorriente') !== null) ? round($request->get('pasivonocorriente'),0,0): null,
                    'pasivoCorriente' => ($request->get('pasivocorriente') !== null) ? round($request->get('pasivocorriente'),0,0): null,
                    'activoCorriente' => ($request->get('activocorriente') !== null) ? round($request->get('activocorriente'),0,0): null,
                    'ebitda' => ($request->get('ebitda') !== null) ? round($request->get('ebitda'),0,0): null,
                    'balanceTotal' => $balanceTotal,
                    'gastoAnual' => $gastoAnual,
                    'categoriaEmpresa' => $categoriaEmpresa,
                    'activoNoCorriente' => $activoNoCorriente,
                    'trabajosInmovilizado' => $trabajosInmovilizado,
                    'ccaa' => $request->get('ccaa'),
                    'PrimaEmision' => $primaEmision,
                    'Cuenta74' => ($request->get('cuenta74') !== null) ? round($request->get('cuenta74'),0,0): null,
                    'EmpresaCrisis' => $encrisis,
                    'lastEditor' => 'manual',
                    'created_at' => Carbon::now()
                ]
            );

        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('Error al crear einforma manual. 1');
        }

        try{
            Artisan::call('calcula:I+D', [
                'cif' => $request->get('cif')
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('Error al crear einforma manual. 2');
        }

        try{
            Artisan::call('create:pagerank', [
                'cif' => $request->get('cif')
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('Error al crear einforma manual. 3');
        }

        try{
            Artisan::call('elastic:companies', [
                'cif' => $request->get('cif')
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('Error al crear einforma manual. 4');
        }

        return redirect()->back()->with('success', 'Einforma manual creado.');

    }

    public function mandarEmpresasBeagle(Request $request){

        if($request->get('esproyectos') !== null && $request->get('esproyectos') == "1"){

            $name = $request->get('organismo');
            $linea = $request->get('linea');
            $queryconvocatoria = $request->get('queryconvocatoria');
            $fechadesde = $request->get('desde');
            $fechahasta = $request->get('hasta');
            $rechazadas = $request->get('rechazadas');

            $dpto = \App\Models\Organos::where('url', $name)->first();
            if(!$dpto){
                $dpto = \App\Models\Departamentos::where('url', $name)->first();
            }
            if(!$dpto){
                return abort(404);
            }

            $proyectos = null;
            $proyectosrechazados = null;

            if($fechadesde !== null){
                $fechadesde = Carbon::createFromFormat('d/m/Y', $fechadesde);
            }

            if($fechahasta !== null){
                $fechahasta = Carbon::createFromFormat('d/m/Y', $fechahasta);
            }

            if(isset($linea) && $linea !== null && isset($queryconvocatoria) && $queryconvocatoria !== null){

                if($dpto->proyectosImportados == 1){
                    $proyectos = \App\Models\Proyectos::where('proyectos.organismo', $dpto->id)
                    ->where('proyectos.Estado', 'Cerrado')->where('convocatorias_ayudas.id_ayuda', $linea)->where('convocatorias_ayudas.IdConvocatoriaStr', $queryconvocatoria)
                    ->where('proyectos.importado', 1);

                    if($fechadesde !== null && $fechahasta !== null){
                        $proyectos->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'))->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                    }

                    if($fechadesde !== null && $fechahasta === null){
                        $proyectos->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'));
                    }
        
                    if($fechadesde === null && $fechahasta !== null){
                        $proyectos->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                    }

                    $proyectos->orderBy('proyectos.inicio', 'desc')->orderBy('proyectos.Fecha', 'desc')
                    ->leftJoin('entidades', 'entidades.CIF','=','proyectos.empresaPrincipal')
                    ->leftJoin('convocatorias_ayudas', 'convocatorias_ayudas.IdConvocatoriaStr', '=', 'proyectos.idAyudaAcronimo')
                    ->select('proyectos.uri as urlproyecto','entidades.uri as urlempresa','entidades.Nombre as nombreempresa','proyectos.*');

                }else{
                    $proyectos = \App\Models\Proyectos::where('proyectos.organismo', $dpto->id)
                    ->where('proyectos.Estado', 'Cerrado')->where('convocatorias_ayudas.id_ayuda', $linea)->where('convocatorias_ayudas.IdConvocatoriaStr', $queryconvocatoria)
                    ->where('proyectos.esEuropeo', 1);

                    if($fechadesde !== null && $fechahasta !== null){
                        $proyectos->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'))->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                    }

                    if($fechadesde !== null && $fechahasta === null){
                        $proyectos->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'));
                    }
        
                    if($fechadesde === null && $fechahasta !== null){
                        $proyectos->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                    }

                    $proyectos->orderBy('proyectos.inicio', 'desc')->orderBy('proyectos.Fecha', 'desc')
                    ->leftJoin('entidades', 'entidades.CIF','=','proyectos.empresaPrincipal')
                    ->leftJoin('convocatorias_ayudas', 'convocatorias_ayudas.IdConvocatoriaStr', '=', 'proyectos.idAyudaAcronimo')
                    ->select('proyectos.uri as urlproyecto','entidades.uri as urlempresa','entidades.Nombre as nombreempresa','proyectos.*');
                }

                $proyectos->get();

                if(isset($rechazadas) && $rechazadas !== null){
                    if($dpto->proyectosImportados == 1){
                        $proyectosrechazados = \App\Models\Proyectos::where('proyectos.organismo', $dpto->id)
                        ->where('proyectos.Estado', 'Desestimado')->where('convocatorias_ayudas.id_ayuda', $linea)->where('convocatorias_ayudas.IdConvocatoriaStr', $queryconvocatoria)
                        ->where('proyectos.importado', 1);
    
                        if($fechadesde !== null && $fechahasta !== null){
                            $proyectosrechazados->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'))->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                        }
    
                        if($fechadesde !== null && $fechahasta === null){
                            $proyectosrechazados->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'));
                        }
            
                        if($fechadesde === null && $fechahasta !== null){
                            $proyectosrechazados->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                        }
    
                        $proyectosrechazados->orderBy('proyectos.inicio', 'desc')->orderBy('proyectos.Fecha', 'desc')
                        ->leftJoin('entidades', 'entidades.CIF','=','proyectos.empresaPrincipal')
                        ->leftJoin('convocatorias_ayudas', 'convocatorias_ayudas.IdConvocatoriaStr', '=', 'proyectos.idAyudaAcronimo')
                        ->select('proyectos.uri as urlproyecto','entidades.uri as urlempresa','entidades.Nombre as nombreempresa','proyectos.*');
    
                    }else{
                        $proyectosrechazados = \App\Models\Proyectos::where('proyectos.organismo', $dpto->id)
                        ->where('proyectos.Estado', 'Desestimado')->where('convocatorias_ayudas.id_ayuda', $linea)->where('convocatorias_ayudas.IdConvocatoriaStr', $queryconvocatoria)
                        ->where('proyectos.esEuropeo', 1);
    
                        if($fechadesde !== null && $fechahasta !== null){
                            $proyectosrechazados->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'))->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                        }
    
                        if($fechadesde !== null && $fechahasta === null){
                            $proyectosrechazados->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'));
                        }
            
                        if($fechadesde === null && $fechahasta !== null){
                            $proyectosrechazados->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                        }
    
                        $proyectosrechazados->orderBy('proyectos.inicio', 'desc')->orderBy('proyectos.Fecha', 'desc')
                        ->leftJoin('entidades', 'entidades.CIF','=','proyectos.empresaPrincipal')
                        ->leftJoin('convocatorias_ayudas', 'convocatorias_ayudas.IdConvocatoriaStr', '=', 'proyectos.idAyudaAcronimo')
                        ->select('proyectos.uri as urlproyecto','entidades.uri as urlempresa','entidades.Nombre as nombreempresa','proyectos.*');
                    }

                    $proyectosrechazados->get();
                }

            }elseif(isset($linea) && $linea !== null){

                if($dpto->proyectosImportados == 1){
                    $proyectos = \App\Models\Proyectos::where('proyectos.organismo', $dpto->id)
                    ->where('proyectos.Estado', 'Cerrado')->where('convocatorias_ayudas.id_ayuda', $linea)
                    ->where('proyectos.importado', 1);

                    if($fechadesde !== null && $fechahasta !== null){
                        $proyectos->where('proyectos.Fecha', '>=', $fechadesde)->where('proyectos.Fecha', '<=', $fechahasta);
                    }

                    if($fechadesde !== null && $fechahasta === null){
                        $proyectos->where('proyectos.Fecha', '>=', $fechadesde);
                    }
        
                    if($fechadesde === null && $fechahasta !== null){
                        $proyectos->where('proyectos.Fecha', '<=', $fechahasta);
                    }

                    $proyectos->orderBy('proyectos.inicio', 'desc')->orderBy('proyectos.Fecha', 'desc')
                    ->leftJoin('entidades', 'entidades.CIF','=','proyectos.empresaPrincipal')
                    ->leftJoin('convocatorias_ayudas', 'convocatorias_ayudas.IdConvocatoriaStr', '=', 'proyectos.idAyudaAcronimo')
                    ->select('proyectos.uri as urlproyecto','entidades.uri as urlempresa','entidades.Nombre as nombreempresa','proyectos.*');

                }else{
                    $proyectos = \App\Models\Proyectos::where('proyectos.organismo', $dpto->id)
                    ->where('proyectos.Estado', 'Desestimado')->where('convocatorias_ayudas.id_ayuda', $linea)
                    ->where('proyectos.esEuropeo', 1);

                
                    if($fechadesde !== null && $fechahasta !== null){
                        $proyectos->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'))->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                    }

                    if($fechadesde !== null && $fechahasta === null){
                        $proyectos->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'));
                    }
        
                    if($fechadesde === null && $fechahasta !== null){
                        $proyectos->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                    }

                    $proyectos->orderBy('proyectos.inicio', 'desc')->orderBy('proyectos.Fecha', 'desc')
                    ->leftJoin('entidades', 'entidades.CIF','=','proyectos.empresaPrincipal')
                    ->leftJoin('convocatorias_ayudas', 'convocatorias_ayudas.IdConvocatoriaStr', '=', 'proyectos.idAyudaAcronimo')
                    ->select('proyectos.uri as urlproyecto','entidades.uri as urlempresa','entidades.Nombre as nombreempresa','proyectos.*');
                }

                $proyectos->get();

                if(isset($rechazadas) && $rechazadas !== null){
                 
                    if($dpto->proyectosImportados == 1){
                        $proyectosrechazados = \App\Models\Proyectos::where('proyectos.organismo', $dpto->id)
                        ->where('proyectos.Estado', 'Desestimado')->where('convocatorias_ayudas.id_ayuda', $linea)
                        ->where('proyectos.importado', 1);
    
                        if($fechadesde !== null && $fechahasta !== null){
                            $proyectosrechazados->where('proyectos.Fecha', '>=', $fechadesde)->where('proyectos.Fecha', '<=', $fechahasta);
                        }
    
                        if($fechadesde !== null && $fechahasta === null){
                            $proyectosrechazados->where('proyectos.Fecha', '>=', $fechadesde);
                        }
            
                        if($fechadesde === null && $fechahasta !== null){
                            $proyectosrechazados->where('proyectos.Fecha', '<=', $fechahasta);
                        }
    
                        $proyectosrechazados->orderBy('proyectos.inicio', 'desc')->orderBy('proyectos.Fecha', 'desc')
                        ->leftJoin('entidades', 'entidades.CIF','=','proyectos.empresaPrincipal')
                        ->leftJoin('convocatorias_ayudas', 'convocatorias_ayudas.IdConvocatoriaStr', '=', 'proyectos.idAyudaAcronimo')
                        ->select('proyectos.uri as urlproyecto','entidades.uri as urlempresa','entidades.Nombre as nombreempresa','proyectos.*');
    
                    }else{
                        $proyectosrechazados = \App\Models\Proyectos::where('proyectos.organismo', $dpto->id)
                        ->where('proyectos.Estado', 'Desestimado')->where('convocatorias_ayudas.id_ayuda', $linea)
                        ->where('proyectos.esEuropeo', 1);
    
                    
                        if($fechadesde !== null && $fechahasta !== null){
                            $proyectosrechazados->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'))->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                        }
    
                        if($fechadesde !== null && $fechahasta === null){
                            $proyectosrechazados->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'));
                        }
            
                        if($fechadesde === null && $fechahasta !== null){
                            $proyectosrechazados->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                        }
    
                        $proyectosrechazados->orderBy('proyectos.inicio', 'desc')->orderBy('proyectos.Fecha', 'desc')
                        ->leftJoin('entidades', 'entidades.CIF','=','proyectos.empresaPrincipal')
                        ->leftJoin('convocatorias_ayudas', 'convocatorias_ayudas.IdConvocatoriaStr', '=', 'proyectos.idAyudaAcronimo')
                        ->select('proyectos.uri as urlproyecto','entidades.uri as urlempresa','entidades.Nombre as nombreempresa','proyectos.*');
                    }

                    $proyectosrechazados->get();
    
                }
             
            }else{

                if($dpto->proyectosImportados == 1){
                    $proyectos = \App\Models\Proyectos::where('proyectos.organismo', $dpto->id)
                    ->where('proyectos.Estado', 'Cerrado')->orderBy('proyectos.inicio', 'desc')->orderBy('proyectos.Fecha', 'desc')
                    ->where('proyectos.importado', 1);

                    if($fechadesde !== null && $fechahasta !== null){
                        $proyectos->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'))->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                    }

                    if($fechadesde !== null && $fechahasta === null){
                        $proyectos->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'));
                    }
        
                    if($fechadesde === null && $fechahasta !== null){
                        $proyectos->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                    }
                    
                }else{
                    $proyectos = \App\Models\Proyectos::where('proyectos.organismo', $dpto->id)
                    ->where('proyectos.Estado', 'Cerrado')->orderBy('proyectos.inicio', 'desc')->orderBy('proyectos.Fecha', 'desc')
                    ->where('proyectos.esEuropeo', 1);

                    if($fechadesde !== null && $fechahasta !== null){
                        $proyectos->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'))->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                    }

                    if($fechadesde !== null && $fechahasta === null){
                        $proyectos->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'));
                    }
        
                    if($fechadesde === null && $fechahasta !== null){
                        $proyectos->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                    }
                    
                }

                $proyectos->get();


                if(isset($rechazadas) && $rechazadas !== null){
                    
                    if($dpto->proyectosImportados == 1){
                        $proyectosrechazados = \App\Models\Proyectos::where('proyectos.organismo', $dpto->id)
                        ->where('proyectos.Estado', 'Cerrado')->orderBy('proyectos.inicio', 'desc')->orderBy('proyectos.Fecha', 'desc')
                        ->where('proyectos.importado', 1);
    
                        if($fechadesde !== null && $fechahasta !== null){
                            $proyectosrechazados->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'))->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                        }
    
                        if($fechadesde !== null && $fechahasta === null){
                            $proyectosrechazados->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'));
                        }
            
                        if($fechadesde === null && $fechahasta !== null){
                            $proyectosrechazados->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                        }
                        
                    }else{
                        $proyectosrechazados = \App\Models\Proyectos::where('proyectos.organismo', $dpto->id)
                        ->where('proyectos.Estado', 'Cerrado')->orderBy('proyectos.inicio', 'desc')->orderBy('proyectos.Fecha', 'desc')
                        ->where('proyectos.esEuropeo', 1);
    
                        if($fechadesde !== null && $fechahasta !== null){
                            $proyectosrechazados->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'))->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                        }
    
                        if($fechadesde !== null && $fechahasta === null){
                            $proyectosrechazados->where('proyectos.Fecha', '>=', $fechadesde->format('Y-m-d'));
                        }
            
                        if($fechadesde === null && $fechahasta !== null){
                            $proyectosrechazados->where('proyectos.Fecha', '<=', $fechahasta->format('Y-m-d'));
                        }
                        
                    }

                    $proyectosrechazados->get();
                }                

            }
            if(isset($proyectos) || isset($proyectosrechazados)){
                $zoho = new \App\Libs\ZohoCreatorV2();

                $ayuda = null;
                if($request->get('ayuda') !== null && $request->get('ayuda') != ""){
                    $ayuda = \App\Models\Ayudas::find($request->get('ayuda'));
                }

                $proyectos->chunk(3000, function($proyectosbeagle) use($zoho, $request, $ayuda) {
                    $chunk = array();
                    foreach ($proyectosbeagle as $databeagle) {
                        array_push($chunk, $databeagle->empresaPrincipal);
                        if(json_decode($databeagle->empresasParticipantes, true) !== null){
                            foreach(json_decode($databeagle->empresasParticipantes, true) as $cifParticipante){
                                array_push($chunk, $cifParticipante);                                
                            }
                        }
                    }

                    $chunk = array_values(array_unique($chunk));
                    $data = new stdClass();
                    $data->Acronimo = $request->get('titulo');
                    $data->Descripcion = $request->get('mensaje');
                    $data->Pitch = ($request->get('speech') === null) ? $request->get('mensaje') : $request->get('speech');
                    $data->NIFSPendientes = json_encode($chunk);
                    $data->LinkAyuda = ($request->get('link') === null) ? '' : $request->get('link');
                    $data->ResponsableAlCrear = "Auto";
                    $data->ResponsableReAbrir = "Auto";
                    $data->FechaMaxima = ($request->get('fechamax') === null) ? Carbon::now()->addDays(7)->format('d-m-Y') : Carbon::createFromFormat('d/m/Y', $request->get('fechamax'))->format('d-m-Y');
                    $data->Prioridad = ($request->get('prioridad') === null) ? "Baja" : $request->get('prioridad');
                    $data->UnicosInnovating = ($request->get('totalnifs') === null) ? 0 : $request->get('totalnifs');
                    $data->RepetidosInnovating = ($request->get('totalnifsrepetidos') === null) ? 0 : $request->get('totalnifsrepetidos');
                    $data->DiasSeguimiento = 100;

                    if($ayuda){
                        if($ayuda->Inicio !== null){
                            $data->FechaAberturaAyuda = Carbon::parse($ayuda->Inicio)->format('d-m-Y');
                        }
                        if($ayuda->Fin !== null){
                            $data->FechaCierreAyuda =  Carbon::parse($ayuda->Fin)->format('d-m-Y');
                        }
                    }

                    try{
                        $response = $zoho->addRecords('PropuestasInnovating',$data);           
                        //dd($response);
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        return redirect()->back()->withErrors("Error en el envio o la conexion con beagle");
                    }


                });   
                
                if(isset($proyectosrechazados) && !empty($proyectosrechazados)){
                    $proyectosrechazados->chunk(3000, function($proyectosbeagle) use($zoho, $request, $ayuda) {
                        $chunk = array();
                        foreach ($proyectosbeagle as $databeagle) {
                            array_push($chunk, $databeagle->empresaPrincipal);
                            if(json_decode($databeagle->empresasParticipantes, true) !== null){
                                foreach(json_decode($databeagle->empresasParticipantes, true) as $cifParticipante){
                                    array_push($chunk, $cifParticipante);                                
                                }
                            }
                        }

                        $chunk = array_values(array_unique($chunk));
                        $data = new stdClass();
                        $data->Acronimo = "RECHAZADOS: ".$request->get('titulo');
                        $data->Descripcion = $request->get('mensaje');
                        $data->Pitch = ($request->get('speech') === null) ? $request->get('mensaje') : $request->get('speech');
                        $data->NIFSPendientes = json_encode($chunk);
                        $data->LinkAyuda = ($request->get('link') === null) ? '' : $request->get('link');
                        $data->ResponsableAlCrear = "Auto";
                        $data->ResponsableReAbrir = "Auto";
                        $data->FechaMaxima = ($request->get('fechamax') === null) ? Carbon::now()->addDays(7)->format('d-m-Y') : Carbon::createFromFormat('d/m/Y', $request->get('fechamax'))->format('d-m-Y');
                        $data->Prioridad = ($request->get('prioridad') === null) ? "Baja" : $request->get('prioridad');
                        $data->UnicosInnovating = ($request->get('totalnifs') === null) ? 0 : $request->get('totalnifs');
                        $data->RepetidosInnovating = ($request->get('totalnifsrepetidos') === null) ? 0 : $request->get('totalnifsrepetidos');
                        $data->DiasSeguimiento = 100;

                        if($ayuda){
                            if($ayuda->Inicio !== null){
                                $data->FechaAberturaAyuda = Carbon::parse($ayuda->Inicio)->format('d-m-Y');
                            }
                            if($ayuda->Fin !== null){
                                $data->FechaCierreAyuda =  Carbon::parse($ayuda->Fin)->format('d-m-Y');
                            }
                        }

                        try{
                            $response = $zoho->addRecords('PropuestasInnovating',$data);           
                            //dd($response);
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                            return redirect()->back()->withErrors("Error en el envio o la conexion con beagle");
                        }


                    });   
                }
            
            }else{
                return redirect()->back()->withErrors('No hay empresas para mandar a beagle que coincidan con los filtros seleccionados');        
            }

            if(isset($rechazadas) && $rechazadas !== null){
                return redirect()->back()->withSuccess('Enviados '.$proyectos->count().' proyectos aceptados y '.$proyectosrechazados->count().' rechazados con los filtros seleccionados a beagle en grupos de 200');
            }else{
                return redirect()->back()->withSuccess('Enviados '.$proyectos->count().' proyectos con los filtros seleccionados a beagle en grupos de 200');
            }
            
        }else{

            $ayuda = null;
            if($request->get('ayuda') !== null && $request->get('ayuda') != ""){
                $ayuda = \App\Models\Ayudas::find($request->get('ayuda'));
            }

            $data = array();
            $cifs = array();
            $validador = 0;
            for($i = 1; $i < 11; $i++){

                $empresas = getElasticCompanies($request->get('search'), $request, $i, $request->get('filter'), 200);

                if(isset($empresas->data) && !empty($empresas->data) && $empresas != "ups"){
                    $zoho = new \App\Libs\ZohoCreatorV2();                    
                    array_push($data, array_column($empresas->data, 'NIF'));
                }elseif(isset($empresas->data) && !empty($empresas->data)){
                    $validador++;
                    Log::info("Respuesta elastic mandar a beagle:". json_encode($empresas));
                    //return redirect()->back()->withErrors('Ha ocurrido un error en la consulta a elastic, borra la cache e intentalo de nuevo dentro de unos minutos');        
                    if($validador > 2){
                        break;
                    }
                }else{
                    Log::error("Respuesta elastic mandar a beagle:". json_encode($empresas));
                }
            }
                
            foreach($data as $items){
                foreach($items as $cif){
                    $cifs[] = $cif;
                }
            }

            if(!empty($data) && ! empty($cifs)){
                $cifs = array_values(array_unique($cifs));
                $data = new stdClass();
                $data->Acronimo = $request->get('titulo');
                $data->Descripcion = $request->get('mensaje');
                $data->Pitch = ($request->get('speech') === null) ? $request->get('mensaje') : $request->get('speech');
                $data->NIFSPendientes = json_encode($cifs);
                $data->LinkAyuda = ($request->get('link') === null) ? '' : $request->get('link');
                $data->ResponsableAlCrear = "Auto";
                $data->ResponsableReAbrir = "Auto";
                $data->FechaMaxima = ($request->get('fechamax') === null) ? Carbon::now()->addDays(7)->format('d-m-Y') : Carbon::createFromFormat('d/m/Y', $request->get('fechamax'))->format('d-m-Y');
                $data->Prioridad = ($request->get('prioridad') === null) ? "Baja" : $request->get('prioridad');
                $data->DiasSeguimiento = 100;

                if($ayuda){
                    if($ayuda->Inicio !== null){
                        $data->FechaAberturaAyuda = Carbon::parse($ayuda->Inicio)->format('d-m-Y');
                    }
                    if($ayuda->Fin !== null){
                        $data->FechaCierreAyuda =  Carbon::parse($ayuda->Fin)->format('d-m-Y');
                    }
                }

                try{
                    $response = $zoho->addRecords('PropuestasInnovating',$data);  
                    //dd($response);        
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return redirect()->back()->withErrors("Error en el envio o la conexion con beagle");
                }
            }


            return redirect()->back()->withSuccess('Enviados '.count($cifs).' cifs de la busqueda a beagle');
        }
    }

    public function GeneraProcesoVenta(Request $request){

        if(!Auth::check() || !isSuperAdmin()){
            return abort(404);
        }

        $esbusqueda = 0;
        if($request->get('organo') === null && $request->get('ayuda') === null){
            $esbusqueda = 1;
        }

        #guardar consulta en nuestra bbdd
        try{
            $proceso = new \App\Models\ProcesosTeleventa();
            $proceso->titulo = $request->get('titulo');
            $proceso->descripcion = $request->get('mensaje');
            $proceso->nivel = $request->get('tipo');
            $proceso->speech = ($request->get('speech') === null) ? '' : $request->get('speech');
            $proceso->idorgano = ($request->get('organo') === null) ? null : $request->get('organo');
            $proceso->idayuda = ($request->get('ayuda') === null) ? null : $request->get('ayuda');
            $proceso->fecha = ($request->get('datepicker') === null) ? null : Carbon::parse($request->get('datepicker'))->format('Y-m-d');
            $proceso->esbusqueda = $esbusqueda;
            $proceso->user = Auth::user()->email;
            $proceso->link = ($request->get('link') === null) ? null : $request->get('link');
            
            $proceso->save();

        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('Error al guardar el proceso de televenta');
        }

        $companies = array();

        if($request->get('tipo') !== null && $request->get('tipo') == "organismo"){

            $proyectos = \App\Models\Proyectos::where('organismo', $request->get('organo'))->where('Fecha', '>=', Carbon::createFromFormat("d/m/Y", $request->get('datepicker'))->format('Y-m-d'))->get();
            foreach($proyectos as $proyecto){
                foreach($proyecto->participantes as $participante){
                    if(!in_array($participante->cif_participante, $companies)){
                        $companies[] = $participante->cif_participante;
                    }
                }
            }

        }else{
        #obtener cifs de empresas desde elastic filtrando por campos de formulario tipo(Recomendada, etc) y idorgano, idayuda
            if($esbusqueda == 0){
                #dd($request->get('ayuda'));
                if($request->get('ayuda') !== null){
                    for($i = 1; $i < 5; $i++){
                        sleep(1);
                        $data = getElasticEmpresasByIdAyuda($request->get('ayuda'), 200, $i, 1);

                        if(!is_array($data) || empty($data)){
                            break;
                        }

                        if(is_array($data) && $data['data']->isNotEmpty()){
                            $companies = array_merge($companies, $data['data']->pluck('ID')->toArray());
                        }else{
                            continue;
                            #return redirect()->back()->withErrors('Error en la respuesta de elastic, intentalo de nuevo pasados unos minutos');
                        }
                    }

                    #filtramos empresas por la seleccion del campo tipo del modal 0 = todas(no se ejecuta nada), 1 = recomendadas, 2 = encajes y exigen
                    if($request->get('tipo') == 1)
                        foreach($companies as $key => $cif){
                            $company = \App\Models\Entidad::where('CIF', $cif)->first();
                            $ayuda = \App\Models\Ayudas::where('id', $request->get('ayuda'))->first();
                            $score = getElasticScore($ayuda, $company);
                            if(isset($score) && (isset($score['Recomendar']) && $score['Recomendar']->valor == 1) && $score['score'] !== null && $score['score'] > 0){
                                continue;
                            }else{
                                unset($companies[$key]);
                                continue;
                            }
                        }
                    if($request->get('tipo') == 2){
                        foreach($companies as $key => $cif){
                            $company = \App\Models\Entidad::where('CIF', $cif)->first();
                            $ayuda = \App\Models\Ayudas::where('id', $request->get('ayuda'))->first();
                            $score = getElasticScore($ayuda, $company);
                            if(isset($score) && ($score['score'] !== null && $score['score'] == 0) && (isset($score['Recomendar']) && $score['Recomendar']->valor == -1)){
                                if($score['Recomendar']->tematica == 1 || $score['Recomendar']->innovacion == 1 || $score['Recomendar']->presupuesto == 1){
                                    continue;
                                }else{
                                    unset($companies[$key]);
                                    continue;
                                }
                            }else{
                                unset($companies[$key]);
                                continue;
                            }
                        }
                    }
                }

            }
        }

        if(!empty($companies)){

            $companies_chunk = array_chunk($companies, 300);

            foreach($companies_chunk as $chunk){

                #endpoint zoho al que atacar: "PropuestasInnovating"
                try{

                    $data = new stdClass();
                    $data->Acronimo = $request->get('titulo');
                    $data->Descripcion = $request->get('mensaje');
                    $data->Pitch = ($request->get('speech') === null) ? '' : $request->get('speech');
                    $data->LinkAyuda = ($request->get('link') === null) ? '' : $request->get('link');
                    $data->NIFS = json_encode($chunk);
                    $data->ResponsableAlCrear = "Auto";
                    $data->ResponsableReAbrir = "Auto";
                    $data->DiasSeguimiento = 100;
                    $zoho = new \App\Libs\ZohoCreatorV2();
                    $response = $zoho->addRecords('PropuestasInnovating',$data);

                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return redirect()->back()->withErrors('Error al guardar el proceso de televenta');
                }
            }

        }

        if(!empty($companies)){
            return redirect()->back()->withSuccess('Proceso de televenta, creado correctamente');
        }else{
            return redirect()->back()->withErrors('No se han encontrado empresas para esos filtros');
        }

    }

    public function CrearNoticia(Request $request){

        try{
            $noticia = new \App\Models\Noticias();
            $noticia->id_ayuda = ($request->get('ayuda') === null) ? null : $request->get('ayuda');
            $noticia->id_organo = ($request->get('organo') === null) ? null : $request->get('organo');
            $noticia->link = ($request->get('link') === null) ? null : $request->get('link');
            $noticia->texto = $request->get('texto');
            $noticia->fecha = Carbon::createFromFormat('d/m/Y', $request->get('fecha'))->format('Y-m-d');
            $noticia->user = Auth::user()->email;
            $noticia->created_at = Carbon::now();
            $noticia->save();

        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('Error al crear la noticia');
        }

        return redirect()->back()->withSuccess('Noticia creada correctamente');
    }

    public function EditNoticia(Request $request){

        if(!$request->get('id')){
            return abort(419);
        }

        try{
            $noticia = \App\Models\Noticias::find($request->get('id'));

            if($noticia){

                if(str_contains($noticia->user, 'system_')){
                }else{
                    $noticia->user = Auth::user()->email;
                }

                $noticia->link = ($request->get('link') === null) ? null : $request->get('link');
                $noticia->texto = $request->get('texto');
                $noticia->fecha = Carbon::createFromFormat('d/m/Y', $request->get('fecha'))->format('Y-m-d');
                $noticia->status = ($request->get('status') === null) ? 0 : 1;
                $noticia->updated_at = Carbon::now();
                $noticia->save();
            }

        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('Error al actualizar la noticia');
        }

        return redirect()->back()->withSuccess('Noticia actualizada correctamente');
    }

    public function createCondicionRecompensa(Request $request){

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

    public function viewCondicionRecompensa($id){

        $condicion = \App\Models\CondicionesRecompensas::find($id);

        if(!$condicion){
            return abort(404);
        }

        return view('dashboard/condicionrecompensa', [
            'condicion' => $condicion
        ]);
    }

    public function updateCondicionRecompensa(Request $request){

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

    public function aceptarValidacion(Request $request){

        if(Auth::check() && isSuperAdmin()){

            try{
                \App\Models\ValidateCompany::where('id', $request->get('id'))->update([
                    'aceptado' => 1,
                    'usuariovalidacion' => Auth::user()->email
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('No se ha podido actualizar la solicitud de validación en estos momentos, intentalo de nuevo más tarde');
            }


        }

        return redirect()->back()->withSuccess('Actualizada la solicitud de validación a "ACEPTADA".');
    }

    public function proyectosUsuario(){

        $proyectos = \App\Models\Proyectos::where('esEuropeo', 0)->where('importado', 0)->orderByDesc('Fecha')->where('Estado', 'Abierto')->paginate(200);
        $busquedas = \App\Models\Encaje::whereNotNull('Proyecto_id')->paginate(200);

        return view('dashboard/proyectosuser', [
            'proyectos' => $proyectos,
            'busquedas' => $busquedas
        ]);
    
    }

    public function empresasTargetizadas($opt, Request $request){

        $page = $request->get('page');

        if(!$page){
            $page = 1;
        }

        if(!$opt || ($opt != "con" && $opt != "sin")){
            return abort(404);
        }

        $umbral = app(GeneralSettings::class)->umbral_proyectos;

        if($opt == "con"){
            $cache = "empresas_targetizadas_con_email_".$umbral."_".$page;
            $cache2 = "empresas_targetizadas_con_email_bycif_".$umbral."_".$page;
            $empresas = getEmpresasTargetizadas(true, $page, $umbral);
            $empresasporcif = collect(null);
            if($empresas['data'] !== null){
                $empresasporcif = groupTargetCompaniesByCIF($empresas, true, $umbral, $page);
            }else{
                $empresas = collect(null);
            }
            return view('dashboard/empresastargetizadas', [
                'empresas' => $empresas,
                'empresasporcif' => $empresasporcif,
                'umbral' => $umbral,
                'cache' => $cache,
                'cache2' => $cache2
            ]);
        }

        if($opt == "sin"){
            $cache = "empresas_targetizadas_sin_email_".$umbral."_".$page;
            $cache2 = "empresas_targetizadas_sin_email_bycif_".$umbral."_".$page;
            $empresas = getEmpresasTargetizadas(false, $page, $umbral);        
            $empresasporcif = collect(null);
            if($empresas['data'] !== null){
                $empresasporcif = groupTargetCompaniesByCIF($empresas, false, $umbral, $page);
            }else{
                $empresas = collect(null);
            }
            return view('dashboard/empresastargetizadas', [
                'empresas' => $empresas,
                'empresasporcif' => $empresasporcif,
                'umbral' => $umbral,
                'cache' => $cache,
                'cache2' => $cache2
            ]);
            
        }

        return abort(404);

    }

    public function borrarCacheporId(Request $request){

        try{
            Cache::forget($request->get('cache'));
            Cache::forget($request->get('cache2'));
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('Error en el borrado de los resultados almacenados en cache');
        }

        return redirect()->back()->withSuccess('Borrados los resultados almacenados en cache');
    }


    public function getAdminHelp(){

        $paginasayuda = \App\Models\Help::all();
        $carpetas = \App\Models\FolderHelp::all();

        $carpetasArray = $carpetas->pluck('nombre_carpeta', 'id')->toArray();

        return view('dashboard.adminhelp', [
            'paginasayuda' => $paginasayuda,
            'carpetas' => $carpetas,
            'carpetasarray' => $carpetasArray 
        ]);

    }

    public function saveAdminHelp(Request $request){

        if($request->get('type') == "nueva" && $request->get('id') === null){

            try{
                $nuevapagina = new \App\Models\Help;
                $nuevapagina->titulo = $request->get('titulo');
                $nuevapagina->descripcion = $request->get('descripcion');
                $nuevapagina->link = $request->get('link');
                $nuevapagina->position = $request->get('posicion');
                $nuevapagina->creator_id = Auth::user()->id;
                $nuevapagina->editor_id = Auth::user()->id;
                $nuevapagina->activa = ($request->get('activa') === null) ? 0 : 1;
                $nuevapagina->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('No se ha podido crear la pagina de ayuda');
            }
        }

        if($request->get('type') == "edit" && $request->get('id') !== null){

            $editpagina = \App\Models\Help::find($request->get('id'));

            if(!$editpagina){
                Log::error("pagina de ayuda al editar como superadmin no encontrada");
                return redirect()->back()->withErrors('No se ha podido editar la pagina de ayuda');
            }

            if($request->get('carpetas') === null){
                $activa = 0;
            }else{
                $activa = ($request->get('activa') === null) ? 0 : 1;
            }

            try{
                $editpagina->titulo = $request->get('titulo');
                $editpagina->descripcion = $request->get('descripcion');
                $editpagina->link = $request->get('link');
                $editpagina->position = $request->get('posicion');
                $editpagina->id_carpeta = ($request->get('carpetas') === null) ? null : json_encode($request->get('carpetas'));
                $editpagina->editor_id = Auth::user()->id;
                $editpagina->activa = $activa;
                $editpagina->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('No se ha podido editar la pagina de ayuda');
            }

        }

        return redirect()->back()->withSuccess('Página de ayuda actualizada correctamente');
    }

    public function getPaginaHelp(Request $request){

        if($request->get('id') === null){
            return Response::json(array(
                'code'      =>  404,
                'message'   =>  'No se ha podido editar la pagina de ayuda'
            ), 404);   
        }

        $editpagina = \App\Models\Help::find($request->get('id'));

        if(!$editpagina){
            return Response::json(array(
                'code'      =>  404,
                'message'   =>  'No se ha podido editar la pagina de ayuda'
            ), 404);     
        }

        return Response::json(array(
            'code'      =>  200,
            'data'   =>  json_encode($editpagina)
        ), 200);     

    }

    public function saveFolderHelp(Request $request){

        if($request->get('type') == "nueva" && $request->get('id') === null){

            try{
                $nuevacarpeta = new \App\Models\FolderHelp;
                $nuevacarpeta->nombre_carpeta = $request->get('nombre');
                $nuevacarpeta->orden = $request->get('orden');
                $nuevacarpeta->creator_id = Auth::user()->id;
                $nuevacarpeta->editor_id = Auth::user()->id;
                $nuevacarpeta->activa = ($request->get('activa') === null) ? 0 : 1;
                $nuevacarpeta->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('No se ha podido crear la carpeta de ayudas');
            }
        }

        if($request->get('type') == "edit" && $request->get('id') !== null){

            $editcarpeta = \App\Models\FolderHelp::find($request->get('id'));

            if(!$editcarpeta){
                Log::error("pagina de ayuda al editar como superadmin no encontrada");
                return redirect()->back()->withErrors('No se ha podido editar la carpeta de ayudas');
            }

            try{
                $editcarpeta->nombre_carpeta = $request->get('nombre');
                $editcarpeta->orden = $request->get('orden');
                $editcarpeta->editor_id = Auth::user()->id;
                $editcarpeta->activa = ($request->get('activa') === null) ? 0 : 1;
                $editcarpeta->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('No se ha podido editar la carpeta de ayudas');
            }

        }

        return redirect()->back()->withSuccess('Carpeta de ayudas creada/actualizada correctamente');

    }

    public function getFolderHelp(Request $request){

        if($request->get('id') === null){
            return Response::json(array(
                'code'      =>  404,
                'message'   =>  'No se ha podido editar la carpeta de ayudas'
            ), 404);   
        }

        $editcarpeta = \App\Models\FolderHelp::find($request->get('id'));

        if(!$editcarpeta){
            return Response::json(array(
                'code'      =>  404,
                'message'   =>  'No se ha podido editar la carpeta de ayudas'
            ), 404);     
        }

        return Response::json(array(
            'code'      =>  200,
            'data'   =>  json_encode($editcarpeta)
        ), 200);     

    }
   
    public function convocatoriasEU($id){

        if(!$id){
            return abort(419);
        }

        $convocatoriaRawdata = \App\Models\ConvocatoriasEURawData::find($id);

        if(!$convocatoriaRawdata){
            return abort(419);
        }

        $currentconvocatoria = null;
        $indice = null;
        if($convocatoriaRawdata->budgetTopicActionMap !== null){
            foreach(json_decode($convocatoriaRawdata->budgetTopicActionMap, true) as $key => $value){
                if(strripos($value[0]['action'],$convocatoriaRawdata->identifier) !== false){
                    $currentconvocatoria[$key] = [];
                    $indice = $key;
                    break;
                }
            }

            if(is_array($currentconvocatoria)){
                if($convocatoriaRawdata->topicAction !== null){
                    $array = json_decode($convocatoriaRawdata->topicAction, true);
                    $currentconvocatoria[$indice]['topicAction'] = (isset($array[$indice][0])) ? $array[$indice][0] : null;
                }
                if($convocatoriaRawdata->topicActionMap !== null){
                    $array = json_decode($convocatoriaRawdata->topicActionMap, true);
                    $currentconvocatoria[$indice]['topicActionMap'] = (isset($array[$indice][0])) ? $array[$indice][0] : null;
                }
                if($convocatoriaRawdata->deadlineModel !== null){
                    $array = json_decode($convocatoriaRawdata->deadlineModel, true);
                    $currentconvocatoria[$indice]['deadlineModel'] = (isset($array[$indice][0])) ? $array[$indice][0] : null;
                }
                if($convocatoriaRawdata->deadlineDates !== null){
                    $array = json_decode($convocatoriaRawdata->deadlineDates, true);
                    $currentconvocatoria[$indice]['deadlineDates'] = (isset($array[$indice][0])) ? $array[$indice][0] : null;
                }
                if($convocatoriaRawdata->budgetYearMap !== null){
                    $array = json_decode($convocatoriaRawdata->budgetYearMap, true);
                    $currentconvocatoria[$indice]['budgetYearMap'] = (isset($array[$indice][0])) ? $array[$indice][0] : null;
                }
                if($convocatoriaRawdata->expectedGrants !== null){
                    $array = json_decode($convocatoriaRawdata->expectedGrants, true);
                    $currentconvocatoria[$indice]['expectedGrants'] = (isset($array[$indice][0])) ? $array[$indice][0] : null;
                }
                if($convocatoriaRawdata->minContribution !== null){
                    $array = json_decode($convocatoriaRawdata->minContribution, true);
                    $currentconvocatoria[$indice]['minContribution'] = (isset($array[$indice][0])) ? $array[$indice][0] : null;
                }
                if($convocatoriaRawdata->maxContribution !== null){
                    $array = json_decode($convocatoriaRawdata->maxContribution, true);
                    $currentconvocatoria[$indice]['maxContribution'] = (isset($array[$indice][0])) ? $array[$indice][0] : null;
                }
            }
        }
        
        return view('dashboard/convocatoriaseu', [
            'convocatoriarawdata' => $convocatoriaRawdata,
            'currentconvocatoria' => $currentconvocatoria,
            'indice' => $indice
        ]);
    }

    public function chatGptDdata($id){

        if(!$id){
            return abort(419);
        }

        $chatgptdata = \App\Models\ChatGPTData::where('convocatoria_id', $id)->get();

        if($chatgptdata->count() == 0){
            return abort(419);
        }

        $convocatoria = \App\Models\Ayudas::find($id);

        $description_html = "";

        if($convocatoria && $convocatoria->rawdataEU){
            $description_html = strip_tags($convocatoria->rawdataEU->description_html);
        }

        $checktotalemptyresponses = \App\Models\ChatGPTData::where('run_id', '!=', '')->where('run', 1)->whereNull('response')->count();

        return view('dashboard/chatgptdata', [
            'chatgptdata' => $chatgptdata,
            'description_html' => $description_html,
            'convocatoria' => $convocatoria,
            'emptyresponses' => $checktotalemptyresponses
        ]);

    }

    public function createChatGptData(Request $request){

        $types = ['descripcion_corta', 'descripcion_larga', 'requisitos_tecnicos', 'palabras_tematica'];

        if($request->get('update') !== null && $request->get('update') == "1"){

            $convocatoria = \App\Models\Ayudas::find($request->get('id'));

            if(!$convocatoria){
                return redirect()->back()->withErrors('No se ha podido encontrar datos');
            }

            if($request->get('campo_innovating') !== null && $request->get('prompt') !== null){

                Artisan::call('get:convocatorias_eu_data',[
                    'type' => $request->get('campo_innovating'),
                    'id' => $convocatoria->id,
                    'update' => 1,
                    'prompt' => $request->get('prompt')
                ]);

            }else{
                foreach($types as $type){

                    Artisan::call('get:convocatorias_eu_data',[
                        'type' => $type,
                        'id' => $convocatoria->id,
                        'update' => 1
                    ]);
                    sleep(30);         
                }                
            }

            return redirect()->back()->withSuccess('Solicitud de actualización respuestas a chat gpt, estarán disponibles próximamente.');

        }else{

            $convocatoria = \App\Models\ChatGPTData::where('convocatoria_id', $request->get('id'))->get();

            if(!$convocatoria){
                return redirect()->back()->withErrors('No se ha podido encontrar datos');
            }

            foreach($types as $type){

                $checkchatgptdata = \App\Models\ChatGPTData::where('convocatoria_id', $convocatoria->id)->where('type', $type)->first();

                if($checkchatgptdata){                                        
                    continue;
                }

                Artisan::call('get:convocatorias_eu_data',[
                    'type' => $type,
                    'id' => $convocatoria->id,
                ]);
                sleep(5);         

            }                

            return redirect()->back()->withSuccess('Solicitud de respuestas a chat gpt, estarán disponibles próximamente.');
        }

        return redirect()->back()->withErrors('No se ha podido encontrar datos');

    }

    public function getChatGptResponse(){

        try{
            Artisan::call('get:chatgpt_responses');
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withError('No se han podido obtener respuestas nuevas desde Chat GPT');
        }

        return redirect()->back()->withSuccess('Se han solicitado nuevas respuestas a Chat GPT');

    }

    public function duplicarConvocatoria(){

        $ayudasselect = \App\Models\Ayudas::select('Titulo','Acronimo','id')->get();

        return view('dashboard.duplicarconvocatoria',[
            'ayudas' => $ayudasselect
        ]);

    }

    public function addConvocatoria(){

        $organos = \App\Models\Organos::get();
        $departamentos = \App\Models\Departamentos::get();

        return view('dashboard.addconvocatoria',[
            'organos' => $organos,
            'departamentos' => $departamentos
        ]);

    }

}