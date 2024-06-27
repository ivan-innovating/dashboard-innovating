<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\RedisController;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;

class FondosController extends Controller
{
    //
    private $rediscontroller;
    private $expiration;

    public function __construct()
    {
        $this->rediscontroller = new RedisController;
        $this->expiration = config('app.cache.expiration');
    }

    public function index(Request $request){

        if($request->route('uri') === null || $request->route('uri') == ""){
            return abort(404);
        }

        $fondo = \App\Models\Fondos::where('url', $request->route('uri'))->where('status', 1)->first();

        if(!$fondo){
            return abort(404);
        }

        $name = $fondo->url;

        if(\App::environment() == "prod"){
            if($this->rediscontroller->checkRedisCache('single:fondos:uri:'.$name.':name')){
                return $this->rediscontroller->getRedisCache('single:fondos:uri:'.$name, 'fondos.graficos', null);
            }
        }

    
        $graficos = collect(null);
        if($fondo->mostrar_graficos == 1){
            $graficos = \App\Models\GraficosFondos::where('id_fondo', $fondo->id)->where('activo', 1)->get();         
        }

        $jsondata = array();
        $totalgraficos = $graficos->count();

        if($totalgraficos > 0){
            foreach($graficos as $grafico){                      
                if($grafico->type == "grafico-totales"){
                    $jsondata['totales'] = json_decode($grafico->datos, true);
                }
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
        if(!isset($jsondata['totales'])){
            $jsondata['totales'] = "";
        }
       
        if(\App::environment() == "prod"){
            Redis::set('single:fondos:uri:'.$name.':name', $name, 'EX', $this->expiration);
            Redis::set('single:fondos:uri:'.$name.':fondo', json_encode($fondo), 'EX',  $this->expiration);
            Redis::set('single:fondos:uri:'.$name.':totales', json_encode($jsondata['totales']), 'EX',  $this->expiration);
            Redis::set('single:fondos:uri:'.$name.':graficos', json_encode($graficos), 'EX',  $this->expiration);
            Redis::set('single:fondos:uri:'.$name.':totalgraficos', $totalgraficos, 'EX',  $this->expiration);
            Redis::set('single:fondos:uri:'.$name.':jsondata', json_encode($jsondata), 'EX',  $this->expiration);
        }
        
        return view('fondos.graficos',[  
            'name' => $name,
            'fondo' => $fondo,      
            'totales' => $jsondata['totales'],
            'graficos' => $graficos,
            'totalgraficos' => $totalgraficos,
            'jsondata' => $jsondata
        ]);

    }

    public function actualizarGraficos(Request $request){

        $fondo = \App\Models\Fondos::where('id', $request->get('id'))->first();

        if(!$fondo){
            return abort(404);
        }

        try{
            \Artisan::call('create:fondos_graficos',[
                'id' => $fondo->id
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido actualizar los gráficos en este momento, intentelo de nuevo en unos minutos.');
        }

        return redirect()->back()->withSuccess('Gráficos de fondo actualizados correctamente.');

    }

    public function concesiones(Request $request){

        if($request->route('uri') === null || $request->route('uri') == ""){
            return abort(404);
        }

        $fondo = \App\Models\Fondos::where('url', $request->route('uri'))->where('status', 1)->first();

        if(!$fondo){
            return abort(404);
        }

        $name = $fondo->url;

        $graficos = collect(null);
        if($fondo->mostrar_graficos == 1){
            $graficos = \App\Models\GraficosFondos::where('id_fondo', $fondo->id)->where('activo', 1)->get();         
        }

        $totalgraficos = $graficos->count();
        if($totalgraficos > 0){
            foreach($graficos as $grafico){                      
                if($grafico->type == "grafico-totales"){
                    $jsondata['totales'] = json_decode($grafico->datos, true);
                }                      
            } 
        }
        if(!isset($jsondata['totales'])){
            $jsondata['totales'] = "";
        }

        $tosearch = "";
        $condition = "";
        if($fondo->matches_budget_application !== null){
            foreach(json_decode($fondo->matches_budget_application, true) as $text){
                if($text !== null){                    
                    $tosearch .= $text."|";
                }                    
            }
            $tosearch = rtrim($tosearch,"|");
            $condition = "REGEXP";
        }else{
            $tosearch = $this->getFondoSearchName($fondo->id);
            $condition = "LIKE";
        }

        $concesiones = Cache::remember($fondo->url, now()->addMinutes(120), function () use($condition, $tosearch) {
           $concesiones = \App\Models\Concessions::where('budget_application', $condition, $tosearch)->orderBy('amount','DESC')->get();
           return $concesiones;
        });

        return view('fondos.concesiones',[  
            'name' => $name,
            'fondo' => $fondo,      
            'totales' => $jsondata['totales'],
            'totalgraficos' => $totalgraficos,
            'concesiones' => $concesiones
        ]);

    }

    private function getFondoSearchName($id){

        $tosearch = "";

        switch($id){
            case 1:
                $tosearch = "%Fondos Feder%";#ok
            break;
            case 2:
                $tosearch = "%Nextgen%";#ok
            break;
            case 3:
                $tosearch = "%MRR%";#ok
            break;
            case 4:
                $tosearch = "%Justa%";#ok
            break;
            case 5:
                $tosearch = "%FEMP%";#ok
            break;
            case 6:
                $tosearch = "%Rural%";#ok
            break;
            case 7:
                $tosearch = "%CDTI-EUROSTARS%";#ok
            break;
            case 8:
                $tosearch = "%EEA GRANTS%";#ok
            break; 
            case 9:
                $tosearch = "%CRIN%";#ok
            break;   
        }         
    }       
    
    public function saveFondo(Request $request){

        $fondo = \App\Models\Fondos::where('nombre', $request->get('nombre'))->first();

        if($fondo){
            return redirect()->back()->withErrors('Ya existe un fondo con ese nombre');
        }

        try{
            $fondo = new \App\Models\Fondos();            
            $fondo->nombre = $request->get('nombre');
            $fondo->status = ($request->get('estado') === null) ? 0 : 1;
            $fondo->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido guardar el fondo en la base de datos');
        }

        return redirect()->back()->withSuccess('Nuevo fondo creado correctamente');
    }

    public function saveSubfondo(Request $request){

        $subfondo = \App\Models\Subfondos::where('nombre', $request->get('nombre'))->first();

        if($subfondo){
            return redirect()->back()->withErrors('Ya existe un subfondo con ese nombre');
        }

        $subfondo = \App\Models\Subfondos::where('acronimo', $request->get('acronimo'))->first();

        if($subfondo){
            return redirect()->back()->withErrors('Ya existe un subfondo con ese acronimo');
        }

        try{
            $subfondo = new \App\Models\Subfondos();            
            $subfondo->nombre = $request->get('nombre');
            $subfondo->acronimo = $request->get('acronimo');         
            $subfondo->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido guardar el subfondo en la base de datos');
        }

        try{
            $newfondosubfondo = new \App\Models\FondosSubfondos();
            $newfondosubfondo->fondo_id = 10;
            $newfondosubfondo->subfondo_id = $subfondo->id;
            $newfondosubfondo->nivel = $request->get('nivel');
            if($request->get('padre_subfondo_id') !== null){
                $newfondosubfondo->padre_subfondo_id = $request->get('padre_subfondo_id');
            }
            $newfondosubfondo->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido guardar el subfondo en la base de datos');
        }

        return redirect()->back()->withSuccess('Nuevo subfondo creado correctamente');
    }

    public function saveAction(Request $request){

        $action = \App\Models\TypeOfActions::where('nombre', $request->get('nombre'))->first();

        if($action){
            return redirect()->back()->withErrors('Ya existe un Type Of Action con ese nombre');
        }

        if($request->get('acronimo') !== null){
            $action = \App\Models\TypeOfActions::where('acronimo', $request->get('acronimo'))->first();

            if($action){
                return redirect()->back()->withErrors('Ya existe un Type Of Action con ese acronimo');
            }
        }

        try{
            $action->nombre = $request->get('nombre');
            $action->acronimo = $request->get('acronimo');
            $action->presentacion = json_encode($request->get('presentacion'), JSON_UNESCAPED_UNICODE);
            $action->naturaleza = json_encode($request->get('naturaleza'), JSON_UNESCAPED_UNICODE);
            $action->categoria = json_encode($request->get('categoria'), JSON_UNESCAPED_UNICODE);
            $action->trl = $request->get('trl');
            $action->perfil_financiacion = json_encode($request->get('perfil_financiacion'), JSON_UNESCAPED_UNICODE);
            $action->tipo_financiacion = json_encode($request->get('tipo_financiacion'), JSON_UNESCAPED_UNICODE);
            $action->objetivo_financiacion = json_encode($request->get('objetivo_financiacion'), JSON_UNESCAPED_UNICODE);
            $action->fondo_perdido_minimo = (int)$request->get('fondo_perdido_minimo');
            $action->fondo_perdido_maximo = (int)$request->get('fondo_perdido_maximo');
            $action->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido guardar el Type Of Action en la base de datos');
        }

        return redirect()->back()->withSuccess('Nuevo Type Of Action creado correctamente');
    }

    public function saveBudget(Request $request){

        $budget = \App\Models\BudgetYearMap::where('anio', $request->get('anio'))->where('presupuesto', $request->get('presupuesto'))
        ->where('convocatoria_id', $request->get('convocatoria_id'))->first();

        if($budget){
            return redirect()->back()->withErrors('Ya existe un Budget Year Map para esa convocatoria año y presupuesto');
        }

        try{
            $budget->convocatoria_id = $request->get('convocatoria_id');
            $budget->anio = $request->get('anio');
            $budget->presupuesto = $request->get('presupuesto');
            $budget->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido guardar el Budget Year Map en la base de datos');
        }

        return redirect()->back()->withSuccess('Nuevo Budget Year Map creado correctamente');
    }

    public function viewSubfondo($id){

        if(!$id){
            return abort(419);
        }

        $subfondo = \App\Models\Subfondos::find($id);

        if(!$subfondo){
            return abort(419);
        }

        $subfondos = \App\Models\Subfondos::all();

        return view('dashboard.editsubfondo',[  
            'subfondo' => $subfondo,
            'subfondos' => $subfondos,                  
        ]);

    }

    public function viewTypeOfAction($id){

        if(!$id){
            return abort(419);
        }

        $actions = \App\Models\TypeOfActions::find($id);

        if(!$actions){
            return abort(419);
        }

        $categorias = getAllCategories();
        $naturalezas = \App\Models\Naturalezas::where('Activo', 1)->get();
        $trls = \App\Models\Trl::all();
        $subfondos = \App\Models\Subfondos::all();
        $intereses = \App\Models\Intereses::where('defecto', 'true')->where('Nombre', '!=', 'Cooperación')->where('Nombre', '!=', 'Subcontratación')->get();

        return view('dashboard.edittypeofaction',[  
            'actions' => $actions,
            'categorias' => $categorias,                  
            'naturalezas' => $naturalezas->pluck('NombreNaturaleza','id')->toArray(),                  
            'trls' => $trls->pluck('nivel','id')->toArray(),                  
            'intereses' => $intereses->pluck('Nombre','Id_zoho'),
            'subfondos' => $subfondos->pluck('nombre','external_id')->toArray()
        ]);

    }

    public function viewBudgetYearMap($id){

        if(!$id){
            return abort(419);
        }

        $budget = \App\Models\BudgetYearMap::find($id);

        if(!$budget){
            return abort(419);
        }

        return view('dashboard.editbudgetyearmap',[  
            'budget' => $budget,            
        ]);

    }

    public function editSubFondo(Request $request){

        if($request->get('id') === null || !isSuperAdmin()){
            return abort(419);
        }

        $subfondo = \App\Models\Subfondos::find($request->get('id'));

        if(!$subfondo){
            return abort(419);
        }

        try{
            $subfondo->nombre = $request->get('nombre');
            $subfondo->acronimo = $request->get('acronimo');
            $subfondo->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withError('No se ha podido actualizar el subfondo en este momento intentelo de nuevo en unos minutos');
        }

        return redirect()->back()->withSuccess('Actualizado correctamente el subfondo');
    }

    public function editAction(Request $request){

        if($request->get('id') === null || !isSuperAdmin()){
            return abort(419);
        }

        $action = \App\Models\TypeOfActions::find($request->get('id'));

        if(!$action){
            return abort(419);
        }

        try{
            $action->nombre = $request->get('nombre');
            $action->acronimo = $request->get('acronimo');
            $action->presentacion = json_encode($request->get('presentacion'), JSON_UNESCAPED_UNICODE);
            $action->naturaleza = json_encode($request->get('naturaleza'), JSON_UNESCAPED_UNICODE);
            $action->categoria = json_encode($request->get('categoria'), JSON_UNESCAPED_UNICODE);
            $action->trl = $request->get('trl');
            $action->perfil_financiacion = json_encode($request->get('perfil_financiacion'), JSON_UNESCAPED_UNICODE);
            $action->tipo_financiacion = json_encode($request->get('tipo_financiacion'), JSON_UNESCAPED_UNICODE);
            $action->objetivo_financiacion = json_encode($request->get('objetivo_financiacion'), JSON_UNESCAPED_UNICODE);
            $action->capitulos_financiacion = json_encode($request->get('capitulos_financiacion'), JSON_UNESCAPED_UNICODE);
            $action->fondo_perdido_minimo = (int)$request->get('fondo_perdido_minimo');
            $action->fondo_perdido_maximo = (int)$request->get('fondo_perdido_maximo');
            $action->condiciones_financiacion = $request->get('condiciones_financiacion');
            $action->texto_consorcio = $request->get('texto_consorcio');
            $action->publicar_ayudas = ($request->get('publicar_ayudas') !== null) ? 1 : 0;
            $action->updated_at = Carbon::now();
            $action->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withError('No se ha podido actualizar el Type Of Action en este momento intentelo de nuevo en unos minutos');
        }

        $convocatorias = \App\Models\Ayudas::where('type_of_action_id', $action->id)->get();

        if($convocatorias->count() > 0){
            foreach($convocatorias as $convocatoria){
                try{
                    $convocatoria->Trl = $action->trl;
                    $convocatoria->objetivoFinanciacion = $action->objetivo_financiacion;
                    $convocatoria->CapitulosFinanciacion = $action->capitulos_financiacion;
                    if($action->fondo_perdido_maximo != $action->fondo_perdido_minimo){
                        $convocatoria->PorcentajeFondoPerdido = $action->fondo_perdido_maximo;
                        $convocatoria->FondoPerdidoMinimo = $action->fondo_perdido_minimo;
                    }else{
                        $convocatoria->PorcentajeFondoPerdido = $action->fondo_perdido_minimo;
                    }
                    $convocatoria->TipoFinanciacion = $action->tipo_financiacion;  
                    $convocatoria->Categoria = $action->categoria;
                    $convocatoria->naturalezaConvocatoria = $action->naturaleza;
                    $convocatoria->PerfilFinanciacion = ($action->perfil_financiacion === null) ? '["231435000088214861"]' : $action->perfil_financiacion; 
                    $convocatoria->Presentacion = json_decode($action->presentacion)[0]; 

                    $convocatoria->TextoConsorcio = ($action->texto_consorcio === null) ? null : $action->texto_consorcio;
                    $convocatoria->CondicionesFinanciacion = ($action->condiciones_financiacion === null) ? null : $action->condiciones_financiacion;
                    if($convocatoria->FondoPerdidoMaximoNominal !== null){
                        $convocatoria->PresupuestoMax = $convocatoria->FondoPerdidoMaximoNominal*($action->fondo_perdido_maximo/100);
                    }
                    if($convocatoria->FondoPerdidoMinimoNominal !== null){
                        $convocatoria->PresupuestoMin = $convocatoria->FondoPerdidoMinimoNominal*($action->fondo_perdido_minimo/100);
                        $convocatoria->FondoPerdidoMinimoNominal = $convocatoria->FondoPerdidoMinimoNominal*($action->fondo_perdido_minimo/100);
                    }
                    $convocatoria->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return redirect()->back()->withError('No se han podido actualizar las convocatorias asociadas a este Type Of Action intentelo de nuevo en unos minutos');
                }
            }
        }

        return redirect()->back()->withSuccess('Actualizado correctamente el Type Of Action');
    }

    public function editBudget(Request $request){

        if($request->get('id') === null || !isSuperAdmin()){
            return abort(419);
        }

        $budget = \App\Models\BudgetYearMap::find($request->get('id'));

        if(!$budget){
            return abort(419);
        }

        try{
            $budget->anio = $request->get('anio');
            $budget->presupuesto = $request->get('presupuesto'); 
            $budget->save();           
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withError('No se ha podido actualizar el Budget Year Map en este momento intentelo de nuevo en unos minutos');
        }

        return redirect()->back()->withSuccess('Actualizado correctamente el Budget Year Map');
    }


    public function checkIfSubfondos(Request $request){

        if($request->get('id') !== null && !empty($request->get('id')) && is_array($request->get('id'))){
            $response = array();
            foreach($request->get('id') as $id){
                $fondossubfondos = \App\Models\FondosSubfondos::where('fondo_id', $id)->get();
                if($fondossubfondos->count() > 0){
                    foreach($fondossubfondos as $fondosubfondo){
                        if($fondosubfondo->fondo !== null){
                            $response[$fondosubfondo->fondo->external_id] = $fondosubfondo->fondo->nombre;
                        }
                    }
                }
            }
            if(!empty($response)){
                return response()->json(json_encode($response, JSON_UNESCAPED_UNICODE, JSON_UNESCAPED_SLASHES), 200);
            }
        }
        
        return response()->json(array(
            'code'      =>  403,
            'message'   =>  'No hay subfondos'
        ), 403);
    }
}
