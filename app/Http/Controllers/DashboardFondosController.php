<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardFondosController extends Controller
{
    //
    public function fondos(){

        $fondos = \App\Models\Fondos::orderByDesc('updated_at')->get();
        $convocatorias = \App\Models\Ayudas::all();
      
        return view('admin.fondos.fondos', [
            'fondos' => $fondos,
            'convocatorias' => $convocatorias,
        ]);

    }

    public function crearFondo(){

        return view('admin.fondos.crear', [
        ]);

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

    public function editarFondo(Request $request){

        if($request->route('id') === null || empty($request->route('id'))){
            return abort(404);
        }

        $fondo = \App\Models\Fondos::where('id', $request->route('id'))->first();
        $graficos = \App\Models\GraficosFondos::where('id_fondo', $request->route('id'))->orderBy('updated_at','DESC')->first();

        return view('admin.fondos.editar', [
            'fondo' => $fondo,
            'graficos' => $graficos
        ]);
    }

    public function editFondo(Request $request){

        if($request->get('old_name') != $request->get('nombre')){
            $fondo = \App\Models\Fondos::where('nombre', $request->get('nombre'))->first();
            if($fondo){
                return redirect()->back()->withErrors('Ya existe un fondo con ese nombre');
            }
        }

        $tags = array();
        if($request->get('tags') !== null && $request->get('tags') != ""){
            if(is_array($request->get('tags'))){
                $tags = $request->get('tags');
            }else{
                foreach(explode(",", $request->get('tags')) as $tag){
                    array_push($tags, $tag);
                }
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

    public function subfondos(){

        $subfondos = \App\Models\Subfondos::orderByDesc('updated_at')->get();

        return view('admin.subfondos.subfondos', [
            'subfondos' => $subfondos,
        ]);
    }

    public function crearSubfondo(){

        $subfondos = \App\Models\Subfondos::orderByDesc('updated_at')->get();

        return view('admin.subfondos.crear', [
            'subfondos' => $subfondos,
        ]);

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

    public function editarSubfondo($id){
        if(!$id){
            return abort(419);
        }

        $subfondo = \App\Models\Subfondos::find($id);

        if(!$subfondo){
            return abort(419);
        }

        $subfondos = \App\Models\Subfondos::all();

        return view('admin.subfondos.editar',[  
            'subfondo' => $subfondo,
            'subfondos' => $subfondos,                  
        ]);
    }

    public function editSubfondo(Request $request){

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

    public function typeofactions(){

        $typeofactions = \App\Models\TypeOfActions::get();

        return view('admin.typeofactions.typeofactions',[  
            'actions' => $typeofactions,
                  
        ]);
    }

    public function crearTypeofaction(){

        $categorias = getAllCategories();
        $naturalezas = \App\Models\Naturalezas::where('Activo', 1)->get();
        $trls = \App\Models\Trl::all();
        $subfondos = \App\Models\Subfondos::all();
        $intereses = getIntereses();

        return view('admin.typeofactions.crear',[  
            'categorias' => $categorias,                  
            'naturalezas' => $naturalezas->pluck('NombreNaturaleza','id')->toArray(),                  
            'trls' => $trls->pluck('nivel','id')->toArray(),                  
            'intereses' => $intereses->pluck('Nombre','Id_zoho'),
            'subfondos' => $subfondos->pluck('nombre','external_id')->toArray()    
        ]);
    }

    public function saveTypeofaction(Request $request){

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

    public function editarTypeofaction($id){
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

        return view('admin.typeofactions.editar',[  
            'actions' => $actions,
            'categorias' => $categorias,                  
            'naturalezas' => $naturalezas->pluck('NombreNaturaleza','id')->toArray(),                  
            'trls' => $trls->pluck('nivel','id')->toArray(),                  
            'intereses' => $intereses->pluck('Nombre','Id_zoho'),
            'subfondos' => $subfondos->pluck('nombre','external_id')->toArray()
        ]);

    }

    public function editTypeofaction(Request $request){

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

    public function budgetyearmap(){

        $budgets = \App\Models\BudgetYearMap::get();

        return view('admin.budgetyearmaps.budgetyearmaps',[  
            'budgets' => $budgets,
                  
        ]);
    }

    public function crearBudgetyearmap(){

        $convocatorias = \App\Models\Ayudas::all();

        return view('admin.budgetyearmaps.crear',[  
            'convocatorias' => $convocatorias          
        ]);
    }

    public function saveBudgetyearmap(Request $request){

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

    public function editarBudgetyearmap($id){

        if(!$id){
            return abort(419);
        }

        $budget = \App\Models\BudgetYearMap::find($id);

        if(!$budget){
            return abort(419);
        }

        return view('admin.budgetyearmaps.editar',[  
            'budget' => $budget,            
        ]);

    }

    public function editBudgetyearmap(Request $request){

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

}
