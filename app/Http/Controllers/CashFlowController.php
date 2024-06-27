<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CashFlowController extends Controller
{
    //

    public function editCashflow(Request $request){

        $id = $request->route('id2');
        if(!$id){
            return abort(404);
        }
        
        $ayuda = \App\Models\Convocatorias::where('id', $id)->first();

        if(!$ayuda){
            return abort(404);
        }

        $convocatoria = \App\Models\Ayudas::find($request->route('id'));
        $cashflow = \App\Models\AyudasCashFlow::where('id_ayuda', $id)->first();

        $tipo_financiacion = null;
        $tipo_proyectos = null;
        $categorias = ['Micro' => 'Micro','Pequeña' => 'Pequeña','Mediana' => 'Mediana','Grande' => 'Grande'];

        if($cashflow){
            $tipo_proyectos = \App\Models\AyudasTipoProyectoCashFlow::where('id_cashflow', $cashflow->id)->get();
            $tipo_financiacion = \App\Models\AyudasFinanciacionCashFlow::where('id_cashflow', $cashflow->id)->get();

            /*$categorias_usadas = $tipo_proyectos->pluck('categoria_empresas')->toArray();
            
            foreach($categorias_usadas as $usadas){
                foreach(json_decode($usadas, true) as $usada){
                    if(in_array($usada, $categorias)){
                        unset($categorias[$usada]);
                    }
                }                
            }*/

        }

        $solicitudespriorizacion = \App\Models\PriorizaAnalisisTesoreria::where('convocatoria_id',  $convocatoria->id)->where('completado', 0)->get();

        return view('dashboard.editcashflow',[
            'ayuda' => $ayuda,
            'convocatoria' => $convocatoria,
            'cashflow' => $cashflow,
            'tipo_financiacion' => $tipo_financiacion,
            'tipo_proyectos' => $tipo_proyectos,
            'categorias' => $categorias,
            'solicitudespriorizacion' => $solicitudespriorizacion
        ]);
    }

    public function saveCashflow(Request $request){

        $minhitos = $request->get('num_min_hitos');
        if($request->get('num_min_hitos') > $request->get('num_max_hitos')){
           $minhitos = 1; 
        }

        $duracionminhitos = $request->get('duracion_min_hitos');
        if($request->get('duracion_min_hitos') !== null && $request->get('duracion_min_hitos') > $request->get('duracion_max_hitos')){
           $duracionminhitos = $request->get('duracion_max_hitos'); 
        }

        if($request->get('id') !== null){

            $cashflow = \App\Models\AyudasCashFlow::find($request->get('id'));
            if(!$cashflow){
                return abort(419);
            }

            try{
                $cashflow->id_editor = Auth::user()->id;
                $cashflow->meses_preparacion_documentacion = $request->get('meses_preparacion_documentacion');
                $cashflow->meses_resolucion_organismo = $request->get('meses_resolucion_organismo');
                $cashflow->meses_dnsh = $request->get('meses_dnsh');
                $cashflow->tipos_hitos = $request->get('tipos_hitos');
                $cashflow->fecha_inicio_proyecto = ($request->get('fecha_inicio_proyecto') !== null) ? Carbon::createFromFormat('d/m/Y', $request->get('fecha_inicio_proyecto')) : null;
                $cashflow->num_min_hitos = $minhitos;
                $cashflow->num_max_hitos = $request->get('num_max_hitos');
                $cashflow->duracion_min_hitos = $duracionminhitos;
                $cashflow->duracion_max_hitos = $request->get('duracion_max_hitos');
                $cashflow->meses_analisis_organismo = $request->get('meses_analisis_organismo');
                $cashflow->meses_justificacion_organismo = ($request->get('meses_justificacion_organismo') === null) ? 0 : $request->get('meses_justificacion_organismo');
                $cashflow->visible_ayuda = ($request->get('visible_ayuda') !== null) ? 1 : 0;
                $cashflow->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('No se ha podido guardar los datos del cashflow intentelo pasados unos minutos');
            }
            
        }else{

            $cashflow = new \App\Models\AyudasCashFlow;

            try{
                $cashflow->id_ayuda = $request->get('id_ayuda');
                $cashflow->id_convocatoria = $request->get('id_convocatoria');
                $cashflow->id_editor = Auth::user()->id;
                $cashflow->meses_preparacion_documentacion = $request->get('meses_preparacion_documentacion');
                $cashflow->meses_resolucion_organismo = $request->get('meses_resolucion_organismo');
                $cashflow->meses_dnsh = $request->get('meses_dnsh');
                $cashflow->fecha_inicio_proyecto = ($request->get('fecha_inicio_proyecto') !== null) ? Carbon::createFromFormat('d/m/Y', $request->get('fecha_inicio_proyecto')) : null;
                $cashflow->tipos_hitos = $request->get('tipos_hitos');
                $cashflow->num_min_hitos = $minhitos;
                $cashflow->num_max_hitos = $request->get('num_max_hitos');
                $cashflow->duracion_min_hitos = $duracionminhitos;
                $cashflow->duracion_max_hitos = $request->get('duracion_max_hitos');
                $cashflow->meses_analisis_organismo = $request->get('meses_analisis_organismo');
                $cashflow->meses_justificacion_organismo = ($request->get('meses_justificacion_organismo') === null) ? 0 : $request->get('meses_justificacion_organismo');
                $cashflow->visible_ayuda = ($request->get('visible_ayuda') !== null) ? 1 : 0;
                $cashflow->save();

            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('No se ha podido guardar los datos del cashflow intentelo pasados unos minutos');
            }
        }

        return redirect()->back()->withSuccess('Se han guardado los datos del cashflow correctamente.');

    }

    public function saveTipoProyectoCashflow(Request $request){

        $cashflow = \App\Models\AyudasCashFlow::find($request->get('id_cashflow'));
        if(!$cashflow){
            return abort(419);
        }

        $tipoproyectocashflow = new \App\Models\AyudasTipoProyectoCashFlow();

        try{
            $tipoproyectocashflow->id_cashflow = $cashflow->id;
            $tipoproyectocashflow->nombre_tipo_proyecto = $request->get('nombre_tipo_proyecto');
            $tipoproyectocashflow->categoria_empresas = json_encode($request->get('categoria_empresas'), JSON_UNESCAPED_UNICODE);
            $tipoproyectocashflow->id_tipo_financiacion = $request->get('tipo_financiacion');
            $tipoproyectocashflow->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido guardar el tipo de proyecto para este cashflow intentelo pasados unos minutos');
        
        }

        return redirect()->back()->withSuccess('Se han guardado el tipo de proyecto del cashflow correctamente.');
    }

    public function saveFinanciacionCashflow(Request $request){

        $mesesentre = $request->get('meses_entre_cuotas');
        if($request->get('meses_entre_cuotas') > $request->get('meses_amortizacion')){
           $mesesentre = $request->get('meses_amortizacion'); 
        }

        $financiacion = new \App\Models\AyudasFinanciacionCashFlow;

        try{
            $financiacion->id_cashflow = $request->get('id_cashflow');
            $financiacion->nombre_financiacion = $request->get('nombre_financiacion');
            $financiacion->meses_carencia = $request->get('meses_carencia');
            $financiacion->carencia_desde = $request->get('carencia_desde');
            $financiacion->meses_amortizacion = $request->get('meses_amortizacion');
            $financiacion->meses_entre_cuotas = $mesesentre;
            $financiacion->tipo_interes = $request->get('tipo_interes');
            $financiacion->porcentaje_interes = $request->get('porcentaje_interes');
            $financiacion->tipo_amortizacion = $request->get('tipo_amortizacion');
            $financiacion->intensidad_convocatoria = 0;
            $financiacion->tipo_fondo_perdido = $request->get('tipo_fondo_perdido');
            $financiacion->fondo_perdido = $request->get('fondo_perdido');
            $financiacion->porcentaje_sobre_presupuesto = $request->get('porcentaje_sobre_presupuesto');
            $financiacion->ambito_anticipo = $request->get('ambito_anticipo');
            $financiacion->tipo_anticipo = $request->get('tipo_anticipo');
            $financiacion->anticipo_nominal = ($request->get('anticipo_nominal') === null) ? 0 : $request->get('anticipo_nominal');
            $financiacion->anticipo_porcentual = $request->get('anticipo_porcentual');
            $financiacion->maximo_anticipo_nominal = $request->get('maximo_anticipo_nominal');
            $financiacion->momento_pagos = $request->get('momento_pagos');
            $financiacion->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido guardar los datos de la financiación, intentelo pasados unos minutos');
        }

        return redirect()->back()->withSuccess('Se ha creado un nuevo tipo de financiación para este cashflow.');
    }

    public function updateTipoProyectoCashflow(Request $request){

        if($request->get('id_cashflow') === null || $request->get('id') === null){
            return abort(419);
        }

        $cashflow = \App\Models\AyudasCashFlow::find($request->get('id_cashflow'));
        if(!$cashflow){
            return abort(419);
        }

        $tipoproyectocashflow = \App\Models\AyudasTipoProyectoCashFlow::find($request->get('id'));
        if(!$tipoproyectocashflow){
            return abort(419);
        }

        try{
            $tipoproyectocashflow->nombre_tipo_proyecto = $request->get('nombre_tipo_proyecto');
            $tipoproyectocashflow->categoria_empresas = json_encode($request->get('categoria_empresas'), JSON_UNESCAPED_UNICODE);
            $tipoproyectocashflow->id_tipo_financiacion = $request->get('tipo_financiacion');
            $tipoproyectocashflow->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido actualizar el tipo de proyecto para este cashflow intentelo pasados unos minutos');
        
        }

        return redirect()->back()->withSuccess('Se ha actualizado el tipo de proyecto del cashflow correctamente.');
    }

    public function updateFinanciacionCashflow(Request $request){

        if($request->get('id') === null){
            return abort(419);
        }
        
        $financiacion = \App\Models\AyudasFinanciacionCashFlow::find($request->get('id'));

        if(!$financiacion){
            return abort(419);
        }

        if($financiacion->id_cashflow != $request->get('id_cashflow')){
            return abort(419);
        }

        $mesesentre = $request->get('meses_entre_cuotas');
        if($request->get('meses_entre_cuotas') > $request->get('meses_amortizacion')){
           $mesesentre = $request->get('meses_amortizacion'); 
        }

        try{
            $financiacion->nombre_financiacion = $request->get('nombre_financiacion');
            $financiacion->meses_carencia = $request->get('meses_carencia');
            $financiacion->carencia_desde = $request->get('carencia_desde');
            $financiacion->meses_amortizacion = $request->get('meses_amortizacion');
            $financiacion->meses_entre_cuotas = $mesesentre;
            $financiacion->tipo_interes = $request->get('tipo_interes');
            $financiacion->porcentaje_interes = $request->get('porcentaje_interes');
            $financiacion->tipo_amortizacion = $request->get('tipo_amortizacion');
            $financiacion->intensidad_convocatoria = 0;
            $financiacion->tipo_fondo_perdido = $request->get('tipo_fondo_perdido');
            $financiacion->fondo_perdido = $request->get('fondo_perdido');
            $financiacion->porcentaje_sobre_presupuesto = $request->get('porcentaje_sobre_presupuesto');
            $financiacion->ambito_anticipo = $request->get('ambito_anticipo');
            $financiacion->tipo_anticipo = $request->get('tipo_anticipo');
            $financiacion->anticipo_nominal = ($request->get('anticipo_nominal') === null) ? 0 : $request->get('anticipo_nominal');
            $financiacion->anticipo_porcentual = $request->get('anticipo_porcentual');
            $financiacion->maximo_anticipo_nominal = $request->get('maximo_anticipo_nominal');
            $financiacion->momento_pagos = $request->get('momento_pagos');
            $financiacion->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido actualizar los datos de la financiación, intentelo pasados unos minutos');
        }

        return redirect()->back()->withSuccess('Se ha actualizado el nuevo tipo de financiación para este cashflow.');
    }

    public function getAjaxTipoProyectoCashflow(Request $request){

        $id = $request->get('id');

        if(!$id){
           return response()->json(['error' => 'Error'], 404);
        }

        $intensidad = \App\Models\AyudasTipoProyectoCashFlow::find($id);

        if(!$intensidad){
            return  response()->json(['error' => 'Error'], 404);
        }

        return response()->json(json_encode($intensidad->toArray()));
    }

    public function getAjaxFinanciacionCashflow(Request $request){

        $id = $request->get('id');

        if(!$id){
            return response()->json(['error' => 'Error'], 404);
        }
   
        $financiacion = \App\Models\AyudasFinanciacionCashFlow::find($id);

        if(!$financiacion){
            return response()->json(['error' => 'Error'], 404);
        }

        return response()->json(json_encode($financiacion->toArray()));
    }

    public function deleteTipoProyectoCashflow(Request $request){

        if($request->get('id') === null){
            return abort(419);
        }

        $tipoproyecto = \App\Models\AyudasTipoProyectoCashFlow::find($request->get('id'));

        if(!$tipoproyecto){
            return abort(419);
        }

        if($tipoproyecto->id_cashflow != $request->get('id_cashflow')){
            return abort(419);
        }

        try{
            $tipoproyecto->delete();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido borrar los datos del tipo de proyecto, intentelo pasados unos minutos');
        }

        return redirect()->back()->withSuccess('Se ha borrado el tipo de proyecto para este cashflow.');
    }

    public function deleteFinanciacionCashflow(Request $request){

        if($request->get('id') === null){
            return abort(419);
        }
        
        $financiacion = \App\Models\AyudasFinanciacionCashFlow::find($request->get('id'));

        if(!$financiacion){
            return abort(419);
        }

        if($financiacion->id_cashflow != $request->get('id_cashflow')){
            return abort(419);
        }

        try{
            $financiacion->delete();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido borrar los datos de la financiación, intentelo pasados unos minutos');
        }

        try{
            \App\Models\AyudasTipoProyectoCashFlow::where('id_tipo_financiacion', $request->get('id'))->delete();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido borrar los datos de la financiación, intentelo pasados unos minutos');
        }

        return redirect()->back()->withSuccess('Se ha borrado el tipo de financiación para este cashflow.');
    }

    public function deleteCashflow(Request $request){
        
        if($request->get('id') === null){
            return abort(419);
        }

        $cashflow = \App\Models\AyudasCashFlow::find($request->get('id'));

        $nombre = $cashflow->ayuda->titulo;
        
        if($cashflow->ayuda->acronimo !== null){
            $nombre = $cashflow->ayuda->acronimo."/".$cashflow->ayuda->titulo;
        }

        $message = "El usuario: ".Auth::user()->email." ha borrado el cashflow de la ayuda: ".$nombre;

        try{
            Artisan::call('send:telegram_notification', [
                'message' =>  $message
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
        }

        try{
            \App\Models\AyudasFinanciacionCashFlow::where('id_cashflow', $cashflow->id)->delete();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido borrar el cashflow, intentelo pasados unos minutos');
        }

        try{
            \App\Models\AyudasIntensidadCashFlow::where('id_cashflow', $cashflow->id)->delete();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido borrar el cashflow, intentelo pasados unos minutos');
        }

        $id = $cashflow->id_convocatoria;

        try{
            $cashflow->delete();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido borrar el cashflow, intentelo pasados unos minutos');
        }

        return redirect()->route('editconvocatoria', [$id])->withSuccess('Se ha borrado el cashflow correctamente.');
    }

    public function createAnalisisCashflow(Request $request){

        if($request->get('id_cashflow') === null || $request->get('id_ayuda') === null || !userEntidadSelected()){
            return abort(419);
        }

        $analisiscashflow = new \App\Models\AnalisisCashflow();

        $cashflow = \App\Models\AyudasCashFlow::find($request->get('id_cashflow'));
        if(!$cashflow){
            return abort(419);
        }

        $convocatoria = \App\Models\Ayudas::find($request->get('id_ayuda'));
        if(!$convocatoria){
            return abort(419);
        }

        if($request->get('tipo_financiacion') !== null){            
            $financiacion = \App\Models\AyudasFinanciacionCashFlow::find($request->get('tipo_financiacion'));
        }else{
            return abort(419);
        }

        if(!$financiacion){
            return abort(419);
        }

        try{
              
            $analisiscashflow->tipo_financiacion = $financiacion->id;                      
            $analisiscashflow->id_cashflow = $request->get('id_cashflow');
            $analisiscashflow->id_ayuda = $convocatoria->id_ayuda;
            $analisiscashflow->id_convocatoria = $convocatoria->id;
            $analisiscashflow->id_editor = Auth::user()->id;
            $analisiscashflow->id_entidad = userEntidadSelected()->id;
            $analisiscashflow->titulo_proyecto = $request->get('titulo_proyecto');
            $analisiscashflow->presupuesto_proyecto = $request->get('presupuesto_proyecto');
            $analisiscashflow->inicio_proyecto = Carbon::createFromFormat('d/m/Y', $request->get('fecha_inicio_proyecto'))->format('Y-m-d');            
            ###CAMPOS FINANCIACION CASHFLOW            
            $analisiscashflow->tipo_fondo_perdido = $financiacion->tipo_fondo_perdido;
            $analisiscashflow->fondo_perdido = $financiacion->fondo_perdido;
            $analisiscashflow->meses_carencia = $financiacion->meses_carencia;
            $analisiscashflow->carencia_desde = $financiacion->carencia_desde;
            $analisiscashflow->meses_amortizacion = $financiacion->meses_amortizacion;
            $analisiscashflow->meses_entre_cuotas = $financiacion->meses_entre_cuotas;
            $analisiscashflow->tipo_interes = $financiacion->tipo_interes;
            $analisiscashflow->porcentaje_interes = $financiacion->porcentaje_interes;
            $analisiscashflow->tipo_amortizacion = $financiacion->tipo_amortizacion;
            $analisiscashflow->intensidad_convocatoria = 0;
            $analisiscashflow->porcentaje_sobre_presupuesto = $financiacion->porcentaje_sobre_presupuesto;
            $analisiscashflow->ambito_anticipo = $financiacion->ambito_anticipo;
            $analisiscashflow->tipo_anticipo = $financiacion->tipo_anticipo;
            $analisiscashflow->anticipo_nominal = $financiacion->anticipo_nominal;
            $analisiscashflow->anticipo_porcentual = $financiacion->anticipo_porcentual;
            $analisiscashflow->maximo_anticipo_nominal = $financiacion->maximo_anticipo_nominal;
            $analisiscashflow->momento_pagos = $financiacion->momento_pagos;
            ###FIN CAMPOS FINANCIACION CASHFLOW            
            ###CAMPOS CASHFLOW
            $analisiscashflow->meses_preparacion_documentacion = $cashflow->meses_preparacion_documentacion;
            $analisiscashflow->meses_resolucion_organismo = $cashflow->meses_resolucion_organismo;
            $analisiscashflow->meses_dnsh = $cashflow->meses_dnsh;
            $analisiscashflow->tipos_hitos = $cashflow->tipos_hitos;
            $analisiscashflow->num_min_hitos = ($cashflow->num_min_hitos === null) ? 1 : $cashflow->num_min_hitos;
            $analisiscashflow->num_max_hitos = ($cashflow->num_max_hitos === null) ? 1 : $cashflow->num_max_hitos;
            $analisiscashflow->duracion_min_hitos = $cashflow->duracion_min_hitos;
            $analisiscashflow->duracion_max_hitos = $cashflow->duracion_max_hitos;
            $analisiscashflow->meses_analisis_organismo = $cashflow->meses_analisis_organismo;
            ###FIN DE CAMPOS CASHFLOW 

            $euribor = \App\Models\Euribor::where('fecha', Carbon::now()->subDay()->format('Y-m-d'))->where('meses', 12)->first();
            if(!$euribor){
                try{
                    Artisan::call('get:euribor');                    
                }catch(Exception $e){
                    Log::error($e->getMessage());                 
                }
                try{
                    Artisan::call('get:ninja_euribor');                    
                }catch(Exception $e){
                    Log::error($e->getMessage());                    
                }
            }

            $euribor = \App\Models\Euribor::where('fecha', Carbon::now()->subDay()->format('Y-m-d'))->where('meses', 12)->first();
            if(!$euribor){             
                $latesteuribor = \App\Models\Euribor::where('meses', 12)->latest('updated_at');
                if(!$latesteuribor){
                    Log::error("No hay euribor en la bbdd para la fecha de ayer: ".Carbon::now()->subDay()->format('Y-m-d'));
                    return redirect()->back()->withErrors('231, no se ha podido crear el análisis de cashflow en este momento');             
                }
                $euribor = $latesteuribor->first();                
            }
            $analisiscashflow->euribor = $euribor->euribor;

            if($request->get('inicio') !== null){
                $analisiscashflow->inicio_expediente = Carbon::createFromFormat('d/m/Y', $request->get('inicio'))->format('Y-m-d');
                $analisiscashflow->tiempo_estimado_preparacion = $request->get('tiempo_estimado_preparacion');                
                $analisiscashflow->presentacion_expediente = Carbon::createFromFormat('d/m/Y', $request->get('presentacion'))->format('Y-m-d');
            }

            $analisiscashflow->tipos_hitos_proyecto = $cashflow->tipos_hitos;

            if($cashflow->tipos_hitos == "Personalizados"){
                $analisiscashflow->hito_1_inicio = Carbon::parse($request->get('inicio_1'))->format('Y-m-d');                
                $analisiscashflow->hito_1_fin = Carbon::createFromFormat('d/m/Y', $request->get('hito_1_fin'))->format('Y-m-d');
                $numero_hitos = 1;
                if($request->get('hito_2_fin') !== null){
                    $analisiscashflow->hito_2_inicio = Carbon::parse($request->get('inicio_2'))->format('Y-m-d');
                    $analisiscashflow->hito_2_fin = Carbon::createFromFormat('d/m/Y', $request->get('hito_2_fin'))->format('Y-m-d');
                    $numero_hitos++;
                }
                if($request->get('hito_3_fin') !== null){
                    $analisiscashflow->hito_3_inicio = Carbon::parse($request->get('inicio_3'))->format('Y-m-d');
                    $analisiscashflow->hito_3_fin = Carbon::createFromFormat('d/m/Y', $request->get('hito_3_fin'))->format('Y-m-d');
                    $numero_hitos++;
                }
                if($request->get('hito_4_fin') !== null){
                    $analisiscashflow->hito_4_inicio = Carbon::parse($request->get('inicio_4'))->format('Y-m-d');
                    $analisiscashflow->hito_4_fin = Carbon::createFromFormat('d/m/Y', $request->get('hito_4_fin'))->format('Y-m-d');
                    $numero_hitos++;
                }
                if($request->get('hito_5_fin') !== null){
                    $analisiscashflow->hito_5_inicio = Carbon::parse($request->get('inicio_5'))->format('Y-m-d');
                    $analisiscashflow->hito_5_fin = Carbon::createFromFormat('d/m/Y', $request->get('hito_5_fin'))->format('Y-m-d');
                    $numero_hitos++;
                }                

                $analisiscashflow->numero_hitos = $numero_hitos;

            }else{
                $analisiscashflow->numero_anualidades = $request->get('numero_anualidades');
                if($request->get('fecha_inicio_proyecto') !== null){
                    try{
                        $inicio = Carbon::createFromFormat('d/m/Y', $request->get('fecha_inicio_proyecto'));
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        return redirect()->back()->withErrors('232, no se ha podido crear el análisis de cashflow en este momento');
                    }
                }else{
                    $inicio = Carbon::parse($cashflow->fecha_inicio_proyecto);
                }

                if($cashflow->tipos_hitos == "Anualidades"){
                    $analisiscashflow->hito_1_inicio = $inicio->format('Y-m-d');
                    $analisiscashflow->hito_1_fin = $inicio->addYears(1)->startOfYear()->format('Y-m-d');
                    if($request->get('numero_anualidades') == 2){
                        $analisiscashflow->hito_2_inicio = $inicio->startOfYear()->format('Y-m-d');                        
                        $analisiscashflow->hito_2_fin =  $inicio->addYears(1)->startOfYear()->format('Y-m-d');
                    }elseif($request->get('numero_anualidades') == 3){
                        $analisiscashflow->hito_2_inicio = $inicio->startOfYear()->format('Y-m-d');
                        $analisiscashflow->hito_2_fin = $inicio->addYears(1)->startOfYear()->format('Y-m-d');
                        $analisiscashflow->hito_3_inicio = $inicio->startOfYear()->format('Y-m-d');
                        $analisiscashflow->hito_3_fin = $inicio->addYears(1)->startOfYear()->format('Y-m-d');
                    }elseif($request->get('numero_anualidades') == 4){
                        $analisiscashflow->hito_2_inicio = $inicio->startOfYear()->format('Y-m-d');
                        $analisiscashflow->hito_2_fin = $inicio->addYears(1)->startOfYear()->format('Y-m-d');
                        $analisiscashflow->hito_3_inicio = $inicio->startOfYear()->format('Y-m-d');
                        $analisiscashflow->hito_3_fin = $inicio->addYears(1)->startOfYear()->format('Y-m-d');
                        $analisiscashflow->hito_4_inicio = $inicio->startOfYear()->format('Y-m-d');
                        $analisiscashflow->hito_4_fin = $inicio->addYears(1)->startOfYear()->format('Y-m-d');
                    }elseif($request->get('numero_anualidades') == 5){
                        $analisiscashflow->hito_2_inicio = $inicio->startOfYear()->format('Y-m-d');
                        $analisiscashflow->hito_2_fin = $inicio->addYears(1)->startOfYear()->format('Y-m-d');
                        $analisiscashflow->hito_3_inicio = $inicio->startOfYear()->format('Y-m-d');
                        $analisiscashflow->hito_3_fin = $inicio->addYears(1)->startOfYear()->format('Y-m-d');
                        $analisiscashflow->hito_4_inicio = $inicio->startOfYear()->format('Y-m-d');
                        $analisiscashflow->hito_4_fin = $inicio->addYears(1)->startOfYear()->format('Y-m-d');
                        $analisiscashflow->hito_5_inicio = $inicio->startOfYear()->format('Y-m-d');
                        $analisiscashflow->hito_5_fin = $inicio->addYears(1)->startOfYear()->format('Y-m-d');
                    }
                }else{ ## caso de uso cuando tipos_hitos == "Anualidades fecha inicio"
                    $analisiscashflow->hito_1_inicio = $inicio->format('Y-m-d');
                    $analisiscashflow->hito_1_fin = $inicio->addYears(1)->format('Y-m-d');
                    if($request->get('numero_anualidades') == 2){
                        $analisiscashflow->hito_2_inicio = $inicio->format('Y-m-d');
                        $analisiscashflow->hito_2_fin =  $inicio->addYears(1)->format('Y-m-d');
                    }elseif($request->get('numero_anualidades') == 3){
                        $analisiscashflow->hito_2_inicio = $inicio->format('Y-m-d');
                        $analisiscashflow->hito_2_fin = $inicio->addYears(1)->format('Y-m-d');
                        $analisiscashflow->hito_3_inicio = $inicio->format('Y-m-d');
                        $analisiscashflow->hito_3_fin = $inicio->addYears(1)->format('Y-m-d');
                    }elseif($request->get('numero_anualidades') == 4){
                        $analisiscashflow->hito_2_inicio = $inicio->format('Y-m-d');
                        $analisiscashflow->hito_2_fin = $inicio->addYears(1)->format('Y-m-d');
                        $analisiscashflow->hito_3_inicio = $inicio->format('Y-m-d');
                        $analisiscashflow->hito_3_fin = $inicio->addYears(1)->format('Y-m-d');
                        $analisiscashflow->hito_4_inicio = $inicio->format('Y-m-d');
                        $analisiscashflow->hito_4_fin = $inicio->addYears(1)->format('Y-m-d');
                    }elseif($request->get('numero_anualidades') == 5){
                        $analisiscashflow->hito_2_inicio = $inicio->format('Y-m-d');
                        $analisiscashflow->hito_2_fin = $inicio->addYears(1)->format('Y-m-d');
                        $analisiscashflow->hito_3_inicio = $inicio->format('Y-m-d');
                        $analisiscashflow->hito_3_fin = $inicio->addYears(1)->format('Y-m-d');
                        $analisiscashflow->hito_4_inicio = $inicio->format('Y-m-d');
                        $analisiscashflow->hito_4_fin = $inicio->addYears(1)->format('Y-m-d');
                        $analisiscashflow->hito_5_inicio = $inicio->format('Y-m-d');
                        $analisiscashflow->hito_5_fin = $inicio->addYears(1)->format('Y-m-d');
                    }
                }
                              
            }

            $analisiscashflow->es_simulacion = 0;
            $analisiscashflow->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido crear el analisis, intentelo pasados unos minutos');
        }

        return redirect()->route('viewanalisiscashflow', $analisiscashflow->id);

    }

    public function viewAnalisisCashflow(Request $request){

        if(Auth::guest() || !userEntidadSelected()){
            return abort(404);
        }

        if($request->route('id') === null){
            return abort(404);
        }

        $analisiscashflow = \App\Models\AnalisisCashflow::find($request->route('id'));
        if(!$analisiscashflow){
            return abort(404);
        }

        ###CALCULO PREVIO DEL TIMELINE
        $timeline = $this->createTimeline($analisiscashflow, $analisiscashflow->cashflow);

        $fechas = array();
        if($analisiscashflow->momento_pagos == "fin_paquete"){
            if($analisiscashflow->hito_1_fin !== null){
                array_push($fechas, $analisiscashflow->hito_1_fin);
            }
            if($analisiscashflow->hito_2_fin !== null){
                array_push($fechas, $analisiscashflow->hito_2_fin);
            }
            if($analisiscashflow->hito_3_fin !== null){
                array_push($fechas, $analisiscashflow->hito_3_fin);
            }
            if($analisiscashflow->hito_4_fin !== null){
                array_push($fechas, $analisiscashflow->hito_4_fin);
            }
            if($analisiscashflow->hito_5_fin !== null){
                array_push($fechas, $analisiscashflow->hito_5_fin);
            }
        }elseif($analisiscashflow->momento_pagos == "fin_proyecto" && $analisiscashflow->ambito_anticipo == "paquete"){
            if($analisiscashflow->hito_1_fin !== null){
                array_push($fechas, $analisiscashflow->hito_1_fin);
            }
            if($analisiscashflow->hito_2_fin !== null){
                array_push($fechas, $analisiscashflow->hito_2_fin);
            }
            if($analisiscashflow->hito_3_fin !== null){
                array_push($fechas, $analisiscashflow->hito_3_fin);
            }
            if($analisiscashflow->hito_4_fin !== null){
                array_push($fechas, $analisiscashflow->hito_4_fin);
            }
            if($analisiscashflow->hito_5_fin !== null){
                array_push($fechas, $analisiscashflow->hito_5_fin);
            }
        }

        $inicio = $analisiscashflow->inicio_expediente;
        $key = array_search('Fecha firma contrato', array_column($timeline, 'title'));
        if($key !== false){
            $inicio = $timeline[$key]['date'];
        }

        $momentos = array();
        $tipo_paquete = ($analisiscashflow->tipos_hitos == "Personalizados") ? "Hito" : "Anualidad";

        if($analisiscashflow->momento_pagos == "fin_paquete"){            
            $key = array_search('Fin análisis justificación '.$tipo_paquete.' 1', array_column($timeline, 'title'));
            if($key !== false){
                $momentos[] = $timeline[$key]['date']->format('d/m/Y');
            }
            $key = array_search('Fin análisis justificación '.$tipo_paquete.' 2', array_column($timeline, 'title'));
            if($key !== false){
                $momentos[] = $timeline[$key]['date']->format('d/m/Y');
            }
            $key = array_search('Fin análisis justificación '.$tipo_paquete.' 3', array_column($timeline, 'title'));
            if($key !== false){
                $momentos[] = $timeline[$key]['date']->format('d/m/Y');
            }
            $key = array_search('Fin análisis justificación '.$tipo_paquete.' 4', array_column($timeline, 'title'));
            if($key !== false){
                $momentos[] = $timeline[$key]['date']->format('d/m/Y');
            }
            $key = array_search('Fin análisis justificación '.$tipo_paquete.' 5', array_column($timeline, 'title'));
            if($key !== false){
                $momentos[] = $timeline[$key]['date']->format('d/m/Y');
            }
        }      
        if($analisiscashflow->momento_pagos == "fin_proyecto"){
            $total = ($analisiscashflow->numero_hitos > 0) ? $analisiscashflow->numero_hitos :  $analisiscashflow->numero_anualidades;
            $key = array_search('Fin análisis justificación '.$tipo_paquete.' '.$total, array_column($timeline, 'title'));
            if($key !== false){
                $momentos[] = $timeline[$key]['date']->format('d/m/Y');
            }
        }
        
        ###CALCULO VARIABLES DE FINANCIACION
        $credito = ($analisiscashflow->presupuesto_proyecto * $analisiscashflow->porcentaje_sobre_presupuesto)/100;
        $financiaciontnr = 0;
        $financiaciontotal = 0;
        $subvencion = 0;
        if($analisiscashflow->tipo_fondo_perdido == "tramo_no_reembolsable"){
            $financiaciontnr = ($analisiscashflow->presupuesto_proyecto/100)* ($analisiscashflow->porcentaje_sobre_presupuesto/100) *$analisiscashflow->fondo_perdido;            
            $financiaciontotal = $credito;        
        }elseif($analisiscashflow->tipo_fondo_perdido == "subvencion"){
            $subvencion = ($analisiscashflow->fondo_perdido*$analisiscashflow->presupuesto_proyecto)/100;
            $financiaciontotal = $credito + $subvencion;
        }

        $anticipo = 0;
        if($analisiscashflow->tipo_anticipo == "porcentual"){
            $anticipo = ($analisiscashflow->anticipo_porcentual/100) * $financiaciontotal;            
        }elseif($analisiscashflow->tipo_anticipo == "nominal"){
            $anticipo = $analisiscashflow->anticipo_nominal;
        }

        $numpaquetes = 0;
        if($analisiscashflow->momento_pagos == "fin_paquete" || $analisiscashflow->ambito_anticipo == "paquete"){
            $numpaquetes = ($analisiscashflow->numero_hitos > 0) ? $analisiscashflow->numero_hitos :  $analisiscashflow->numero_anualidades;
        }

        if($anticipo > 0 && $numpaquetes > 0){
            $anticipo = $anticipo/$numpaquetes;    
        }

        if($anticipo > $analisiscashflow->maximo_anticipo_nominal){
            $anticipo = $analisiscashflow->maximo_anticipo_nominal;
        }

        $paquetes = 0;
        if($analisiscashflow->tipo_anticipo == "porcentual"){
            if($numpaquetes > 0){
                $dto = $anticipo;
                if($analisiscashflow->ambito_anticipo == "paquete"){
                    $dto = $anticipo*$numpaquetes;  
                }
                $paquetes = ($financiaciontotal - $dto)/ $numpaquetes;
            }
        }

        $timeline = $this->updateTimelineAndSetOrder($timeline, $fechas, $momentos, $analisiscashflow);

        if($analisiscashflow->momento_pagos == "fin_proyecto" && $analisiscashflow->ambito_anticipo == "firma"){
            $paquetes = $financiaciontotal - $anticipo;
        }elseif($analisiscashflow->momento_pagos == "fin_proyecto" && $analisiscashflow->ambito_anticipo == "paquete"){
            if($numpaquetes > 0){
                $dto = $anticipo*$numpaquetes;                  
                $paquetes = $financiaciontotal - $dto;
            }else{
                $paquetes = $financiaciontotal-$anticipo;
            }
        }
            
        return view('analisiscashflow.analisiscashflow', [
            'analisiscashflow' => $analisiscashflow,
            'cashflow' => $analisiscashflow->cashflow,
            'convocatoria' => $analisiscashflow->convocatoria,
            'inicio' => Carbon::parse($analisiscashflow->inicio_expediente),
            'timeline' => $timeline,
            'fechas' => $fechas,
            'inicio' => $inicio,
            'momentos' => $momentos,
            'credito' => $credito,
            'subvencion' => $subvencion,
            'financiaciontotal' => $financiaciontotal,
            'financiaciontnr' => $financiaciontnr,
            'anticipo' => $anticipo,
            'numpaquetes' => $numpaquetes,
            'paquetes' => $paquetes

        ]);
    }

    public function downloadPdfCashflow(Request $request){

        if($request->get('id') === null){
            return abort(404);
        }

        $analisiscashflow = \App\Models\AnalisisCashflow::find($request->get('id'));
        if(!$analisiscashflow){
            return abort(404);
        }

        try{

            $pdf = PDF::loadView('analisiscashflow.pdf.analisiscashflow', [
                'analisiscashflow' => $analisiscashflow,
                'cashflow' => $analisiscashflow->cashflow,
                'convocatoria' => $analisiscashflow->convocatoria,
                'inicio' => Carbon::parse($analisiscashflow->inicio_expediente)
            ]);

            $pdf->setPaper('A4', 'portrait');  
            $pdf->setOption(['dpi' => 250, 'defaultFont' => 'Source Sans Pro', 'enable_php' => true, 'isJavascriptEnabled' => true, 'isHtml5ParserEnabled' => true]);
            $nombre = str_replace(' ','-', mb_strtolower(userEntidadSelected()->Nombre)).str_replace(' ','-', Auth::user()->name);
            $name = "analisis-cashflow-".$nombre.'-'.Carbon::now()->format('Y-m-d-H-i-s');
            $pdf->save(public_path()."/pdfs/cashflow/".$name.'.pdf');
        }catch(Exception $e){
            Log::error($e->getMessage());
            return throw ValidationException::withMessages(['Error en la generación del pdf con datos ayudas y empresa']);                
        }

        return $pdf->download(public_path()."/pdfs/cashflow/".$name.'.pdf');
    }

    function createTimeline($analisiscashflow, $cashflow){

        $timeline = array();

        $tipo_paquete = ($analisiscashflow->tipos_hitos == "Personalizados") ? "Hito" : "Anualidad";

        if($analisiscashflow->tiempo_estimado_preparacion > 0){
            $timeline[] = [
                'direction' => 'l',                
                'title' => 'Inicio preparación expediente',
                'date' => Carbon::parse($analisiscashflow->inicio_expediente),
                'desc' => 'La duración estamidada de la preparación del expediente para esta ayuda es de '.$analisiscashflow->tiempo_estimado_preparacion.' meses'
            ];
            $timeline[] = [
                'direction' => 'l',                
                'title' => 'Fecha presentación expediente',
                'date' => Carbon::parse($analisiscashflow->presentacion_expediente)->subDay(),
                'desc' => 'Fecha de presentación de expediente'
            ];
        }else{
            $timeline[] = [
                'direction' => 'l',                
                'title' => 'Inicio preparación expediente',
                'date' => Carbon::parse($analisiscashflow->inicio_expediente),
                'desc' => 'No hay establecida una duración estimdada para la preparación del expediente para esta ayuda'
            ];
            $timeline[] = [
                'direction' => 'l',                
                'title' => 'Fecha presentación expediente',
                'date' => Carbon::parse($analisiscashflow->presentacion_expediente)->subDay(),
                'desc' => 'Fecha de presentación de expediente'
            ];
        }

        if($cashflow->meses_resolucion_organismo > 0){
            $timeline[] = [
                'direction' => 'l',            
                'title' => 'Inicio análisis organismo',
                'date' => Carbon::parse($analisiscashflow->presentacion_expediente),
                'desc' => 'La duración estimada de la preparación del análisis del organismo para esta ayuda es de '.$cashflow->meses_resolucion_organismo.' meses'
            ];
        }else{
            $timeline[] = [
                'direction' => 'l',            
                'title' => 'Inicio análisis organismo',
                'date' => Carbon::parse($analisiscashflow->presentacion_expediente)->addMonths($cashflow->meses_resolucion_organismo),
                'desc' => 'No hay un número de meses determinados de análisis del proyecto por parte del organismo'
            ];
        }

        if($cashflow->meses_dnsh > 0){

            $addmonths = $cashflow->meses_resolucion_organismo;
            $finanalisis = Carbon::parse($analisiscashflow->presentacion_expediente)->addMonths($addmonths);
            $timeline[] = [
                'direction' => 'l',                    
                'title' => 'Fin de análisis organismo',
                'date' => $finanalisis,
                'desc' => 'Fin de análisis por parte del organismo de esta ayuda'
            ];

            $iniciodnsh = Carbon::parse($analisiscashflow->presentacion_expediente)->addMonths($addmonths)->addDays(2);
            $timeline[] = [
                'direction' => 'l',                    
                'title' => 'Inicio proceso DNSH',
                'date' => $iniciodnsh,
                'desc' => 'La duración estimada del proceso DNSH es de '.$cashflow->meses_dnsh.' meses'
            ];

            $addmonths = $cashflow->meses_resolucion_organismo + $cashflow->meses_dnsh;
            
            $fechafirma = Carbon::parse($analisiscashflow->presentacion_expediente)->addMonths($addmonths);

            $timeline[] = [
                'direction' => 'l',                    
                'title' => 'Fecha firma contrato',
                'date' => $fechafirma,
                'desc' => 'Fecha de firma del contrato y desembolso del anticipo'
            ];
            $timeline[] = [
                'direction' => 'r',                    
                'title' => 'Fecha inicio Proyecto - '.$tipo_paquete.' 1',
                'date' => Carbon::parse($analisiscashflow->inicio_proyecto),
                'desc' => 'Incio del proyecto marcado por '.$tipo_paquete.' 1'
            ];

        }else{

            $timeline[] = [
                'direction' => 'l',                    
                'title' => 'Fin de análisis organismo',
                'date' => Carbon::parse($analisiscashflow->presentacion_expediente)->addMonths($cashflow->meses_resolucion_organismo)->addDay(),
                'desc' => 'Fin de análisis por parte del organismo de esta ayuda'
            ];

            $timeline[] = [
                'direction' => 'r',                    
                'title' => 'Fecha inicio Proyecto - '.$tipo_paquete.' 1',
                'date' => Carbon::parse($analisiscashflow->inicio_proyecto),
                'desc' => 'Inicio del proyecto marcado por '.$tipo_paquete.' 1'
            ];
        }

        if($analisiscashflow->hito_2_fin !== null){

            $title = ($tipo_paquete == "Anualidad") ? 'Fin '.$tipo_paquete.' 1 - inicio '.$tipo_paquete.' 2' : 'Fecha fin '.$tipo_paquete.' 1 - inicio '.$tipo_paquete.' 2';

            $timeline[] = [
                'direction' => 'r',                    
                'title' => $title,
                'date' => Carbon::parse($analisiscashflow->hito_2_inicio),
                'desc' => 'Fin de '.$tipo_paquete.' 2 del proyecto e inicio de '.$tipo_paquete.' 3'
            ];
            if($analisiscashflow->hito_3_fin === null){
                $timeline[] = [
                    'direction' => 'r',                    
                    'title' => 'Fecha fin '.$tipo_paquete.' 2',
                    'date' => Carbon::parse($analisiscashflow->hito_2_fin),
                    'desc' => 'Fin de '.$tipo_paquete.' 2 del proyecto'
                ];
            }
            
        }else{
            $timeline[] = [
                'direction' => 'r',                    
                'title' => 'Fecha fin '.$tipo_paquete.' 1',
                'date' => Carbon::parse($analisiscashflow->hito_1_fin)  ,
                'desc' => 'Fin de '.$tipo_paquete.' 1 del proyecto'
            ];            
        }

        if($analisiscashflow->hito_3_fin !== null){

            $title = ($tipo_paquete == "Anualidad") ? 'Fin '.$tipo_paquete.' 2 - inicio '.$tipo_paquete.' 3' : 'Fecha fin '.$tipo_paquete.' 2 - inicio '.$tipo_paquete.' 3';
           
            $timeline[] = [
                'direction' => 'r',                    
                'title' => $title,
                'date' => Carbon::parse($analisiscashflow->hito_3_inicio),
                'desc' => 'Fin de '.$tipo_paquete.' 2 del proyecto e inicio de '.$tipo_paquete.' 3'
            ];
        
            if($analisiscashflow->hito_4_fin === null){
                $timeline[] = [
                    'direction' => 'r',                    
                    'title' => 'Fecha fin '.$tipo_paquete.' 3',
                    'date' => Carbon::parse($analisiscashflow->hito_3_fin),
                    'desc' => 'Fin de '.$tipo_paquete.' 3 del proyecto'
                ];
            }             
            
        }

        if($analisiscashflow->hito_4_fin !== null){

            $title = ($tipo_paquete == "Anualidad") ? 'Fin '.$tipo_paquete.' 3 - inicio '.$tipo_paquete.' 4' : 'Fecha fin '.$tipo_paquete.' 3 - inicio '.$tipo_paquete.' 4';

            $timeline[] = [
                'direction' => 'r',                    
                'title' => $title,
                'date' => Carbon::parse($analisiscashflow->hito_4_inicio),
                'desc' => 'Fin de '.$tipo_paquete.' 2 del proyecto e inicio de '.$tipo_paquete.' 3'
            ];
        
            if($analisiscashflow->hito_5_fin === null){
                $timeline[] = [
                    'direction' => 'r',                    
                    'title' => 'Fecha fin '.$tipo_paquete.' 4',
                    'date' => Carbon::parse($analisiscashflow->hito_4_fin),
                    'desc' => 'Fin de '.$tipo_paquete.' 4 del proyecto'
                ];
            }
        }

        if($analisiscashflow->hito_5_fin !== null){

            $title = ($tipo_paquete == "Anualidad") ? 'Fin '.$tipo_paquete.' 4 - inicio '.$tipo_paquete.' 5' : 'Fecha fin '.$tipo_paquete.' 4 - inicio '.$tipo_paquete.' 5';

            $timeline[] = [
                'direction' => 'r',                    
                'title' => $title,
                'date' => Carbon::parse($analisiscashflow->hito_4_inicio)   ,
                'desc' => 'Fin de '.$tipo_paquete.' 4 del proyecto e inicio de '.$tipo_paquete.' 5'
            ];         
            $timeline[] = [
                'direction' => 'r',                    
                'title' => 'Fecha fin '.$tipo_paquete.' 5',
                'date' => Carbon::parse($analisiscashflow->hito_4_fin)   ,
                'desc' => 'Fin de proyecto y fin del '.$tipo_paquete.' 5'
            ];         
        }

        if($cashflow->meses_justificacion_organismo > 0){
            $timeline[] = [
                'direction' => 'l',                    
                'title' => 'Inicio justificación '.$tipo_paquete.' 1',
                'date' => Carbon::parse($analisiscashflow->hito_1_fin),
                'desc' => 'Fecha de inicio de justificación del '.$tipo_paquete.' 1'
            ];
            $timeline[] = [
                'direction' => 'l',                    
                'title' => 'Fin justificación '.$tipo_paquete.' 1',
                'date' => Carbon::parse($analisiscashflow->hito_1_fin)->addMonths($cashflow->meses_justificacion_organismo),
                'desc' => 'Fecha de fin de justificación del '.$tipo_paquete.' 1'
            ];
        }
        if($cashflow->meses_analisis_organismo > 0){
            $timeline[] = [
                'direction' => 'l',                    
                'title' => 'Inicio análisis justificación '.$tipo_paquete.' 1',
                'date' => Carbon::parse($analisiscashflow->hito_1_fin)->addMonths($cashflow->meses_justificacion_organismo)->addDay(),
                'desc' => 'Fecha de inicio de análisis de la justificación '.$tipo_paquete.' 1'
            ];
            $timeline[] = [
                'direction' => 'l',                    
                'title' => 'Fin análisis justificación '.$tipo_paquete.' 1',
                'date' => Carbon::parse($analisiscashflow->hito_1_fin)->addMonths($cashflow->meses_justificacion_organismo+$cashflow->meses_analisis_organismo)->addDays(2),
                'desc' => 'Fecha de fin de análisis de la justificación '.$tipo_paquete.' 1'
            ];
        }

        if($analisiscashflow->hito_2_fin !== null){
            if($cashflow->meses_justificacion_organismo > 0){
              $timeline[] = [
                    'direction' => 'l',                    
                    'title' => 'Inicio justificación '.$tipo_paquete.' 2',
                    'date' => Carbon::parse($analisiscashflow->hito_2_fin)  ,
                    'desc' => 'Fecha de inicio de justificación del '.$tipo_paquete.' 2'
                ];
                $timeline[] = [
                    'direction' => 'l',                    
                    'title' => 'Fin justificación '.$tipo_paquete.' 2',
                    'date' => Carbon::parse($analisiscashflow->hito_2_fin)->addMonths($cashflow->meses_justificacion_organismo),
                    'desc' => 'Fecha de fin de justificación del '.$tipo_paquete.' 2'
                ];
            }
            if($cashflow->meses_analisis_organismo > 0){
                $timeline[] = [
                    'direction' => 'l',                    
                    'title' => 'Inicio análisis justificación '.$tipo_paquete.' 2',
                    'date' => Carbon::parse($analisiscashflow->hito_2_fin)->addMonths($cashflow->meses_justificacion_organismo)->addDay(),
                    'desc' => 'Fecha de inicio de análisis de la justificación '.$tipo_paquete.' 2'
                ];
                $timeline[] = [
                    'direction' => 'l',                    
                    'title' => 'Fin análisis justificación '.$tipo_paquete.' 2',
                    'date' => Carbon::parse($analisiscashflow->hito_2_fin)->addMonths($cashflow->meses_justificacion_organismo+$cashflow->meses_analisis_organismo)->addDays(2),
                    'desc' => 'Fecha de fin de análisis de la justificación '.$tipo_paquete.' 2'
                ];
            }
        }
        if($analisiscashflow->hito_3_fin !== null){
            if($cashflow->meses_justificacion_organismo > 0){
                $timeline[] = [
                    'direction' => 'l',                    
                    'title' => 'Inicio justificación '.$tipo_paquete.' 3',
                    'date' => Carbon::parse($analisiscashflow->hito_3_fin)  ,
                    'desc' => 'Fecha de inicio de justificación del '.$tipo_paquete.' 3'
                ];
                $timeline[] = [
                    'direction' => 'l',                    
                    'title' => 'Fin justificación '.$tipo_paquete.' 3',
                    'date' => Carbon::parse($analisiscashflow->hito_3_fin)->addMonths($cashflow->meses_justificacion_organismo),
                    'desc' => 'Fecha de fin de justificación del '.$tipo_paquete.' 3'
                ];
            }
            if($cashflow->meses_analisis_organismo > 0){
                $timeline[] = [
                    'direction' => 'l',                    
                    'title' => 'Inicio análisis justificación '.$tipo_paquete.' 3',
                    'date' => Carbon::parse($analisiscashflow->hito_3_fin)->addMonths($cashflow->meses_justificacion_organismo)->addDay(),
                    'desc' => 'Fecha de inicio de análisis de la justificación '.$tipo_paquete.' 3'
                ];
                $timeline[] = [
                    'direction' => 'l',                    
                    'title' => 'Fin análisis justificación '.$tipo_paquete.' 3',
                    'date' => Carbon::parse($analisiscashflow->hito_3_fin)->addMonths($cashflow->meses_justificacion_organismo+$cashflow->meses_analisis_organismo)->addDays(2),
                    'desc' => 'Fecha de fin de análisis de la justificación '.$tipo_paquete.' 3'
                ];
            }
        }
        if($analisiscashflow->hito_4_fin !== null){
            if($cashflow->meses_justificacion_organismo > 0){
                $timeline[] = [
                    'direction' => 'l',                    
                    'title' => 'Inicio justificación '.$tipo_paquete.' 4',
                    'date' => Carbon::parse($analisiscashflow->hito_4_fin)  ,
                    'desc' => 'Fecha de inicio de justificación del '.$tipo_paquete.' 4'
                ];
                $timeline[] = [
                    'direction' => 'l',                    
                    'title' => 'Fin justificación '.$tipo_paquete.' 4',
                    'date' => Carbon::parse($analisiscashflow->hito_4_fin)->addMonths($cashflow->meses_justificacion_organismo),
                    'desc' => 'Fecha de fin de justificación del '.$tipo_paquete.' 4'
                ];
            }
            if($cashflow->meses_analisis_organismo > 0){
                $timeline[] = [
                    'direction' => 'l',                    
                    'title' => 'Inicio análisis justificación '.$tipo_paquete.' 4',
                    'date' => Carbon::parse($analisiscashflow->hito_4_fin)->addMonths($cashflow->meses_justificacion_organismo)->addDay(),
                    'desc' => 'Fecha de inicio de análisis de la justificación '.$tipo_paquete.' 4'
                ];
                $timeline[] = [
                    'direction' => 'l',                    
                    'title' => 'Fin análisis justificación '.$tipo_paquete.' 4',
                    'date' => Carbon::parse($analisiscashflow->hito_4_fin)->addMonths($cashflow->meses_justificacion_organismo+$cashflow->meses_analisis_organismo)->addDays(2),
                    'desc' => 'Fecha de fin de análisis de la justificación '.$tipo_paquete.' 4'
                ];
            }
        }
        if($analisiscashflow->hito_5_fin !== null){
            if($cashflow->meses_justificacion_organismo > 0){
                $timeline[] = [
                    'direction' => 'l',                    
                    'title' => 'Inicio justificación '.$tipo_paquete.' 5',
                    'date' => Carbon::parse($analisiscashflow->hito_5_fin)  ,
                    'desc' => 'Fecha de inicio de justificación del '.$tipo_paquete.' 5'
                ];
                $timeline[] = [
                    'direction' => 'l',                    
                    'title' => 'Fin justificación '.$tipo_paquete.' 5',
                    'date' => Carbon::parse($analisiscashflow->hito_5_fin)->addMonths($cashflow->meses_justificacion_organismo),
                    'desc' => 'Fecha de fin de justificación del '.$tipo_paquete.' 5'
                ];
            }
            if($cashflow->meses_analisis_organismo > 0){
                $timeline[] = [
                    'direction' => 'l',                    
                    'title' => 'Inicio análisis justificación '.$tipo_paquete.' 5',
                    'date' => Carbon::parse($analisiscashflow->hito_5_fin)->addMonths($cashflow->meses_justificacion_organismo)->addDay(),
                    'desc' => 'Fecha de inicio de análisis de la justificación '.$tipo_paquete.' 5'
                ];
                $timeline[] = [
                    'direction' => 'l',                    
                    'title' => 'Fin análisis justificación '.$tipo_paquete.' 5',
                    'date' => Carbon::parse($analisiscashflow->hito_5_fin)->addMonths($cashflow->meses_justificacion_organismo+$cashflow->meses_analisis_organismo)->addDays(2),
                    'desc' => 'Fecha de fin de análisis de la justificación '.$tipo_paquete.' 5'
                ];
            }
        }

        return $timeline;
    }

    function updateTimelineAndSetOrder($timeline, $fechas, $momentos, $analisiscashflow){

        $addmonths = $analisiscashflow->meses_resolucion_organismo + $analisiscashflow->meses_dnsh;
        $fechaanticipo = Carbon::parse($analisiscashflow->presentacion_expediente)->addMonths($addmonths)->addDays(1);

        if($analisiscashflow->ambito_anticipo == "paquete"){
            $i = 1;
            foreach($fechas as $key => $fecha){
                if($key == 0){
                    $timeline[] = [
                        'direction' => 'r or',                    
                        'title' => 'Fecha entrega anticipo '.$i,
                        'date' => $fechaanticipo,
                        'desc' => 'Fecha de entrega del anticipo '.$i
                    ];
                }else{
                    if(strrpos($fecha,"-") !== false){
                        $timeline[] = [
                            'direction' => 'r or',                    
                            'title' => 'Fecha entrega anticipo '.$i,
                            'date' => Carbon::createFromFormat('Y-m-d', $fecha)->subDay(),
                            'desc' => 'Fecha de entrega del anticipo '.$i
                        ];
                    }else{
                        $timeline[] = [
                            'direction' => 'r or',                    
                            'title' => 'Fecha entrega anticipo '.$i,
                            'date' => Carbon::createFromFormat('d/m/Y', $fecha)->subDay(),
                            'desc' => 'Fecha de entrega del anticipo '.$i
                        ];
                    }
                }
                $i++;
            }

        }else{
            if($analisiscashflow->ambito_anticipo == "firma"){
                $timeline[] = [
                    'direction' => 'r or',                    
                    'title' => 'Fecha entrega anticipo',
                    'date' => $fechaanticipo,
                    'desc' => 'Solo hay una fecha de anticipo el dia de la firma del contrato'
                ];
            }
        }    

        if($analisiscashflow->momento_pagos == "fin_proyecto"){            
            $timeline[] = [
                'direction' => 'r or',                    
                'title' => 'Pagos restantes al finalizar el proyecto',
                'date' => Carbon::createFromFormat('d/m/Y',$momentos[count($momentos)-1])->addMonth(),
                'desc' => 'Los pagos restantes se realizaran en la fecha de finalización del proyecto'
            ];
                    
        }elseif($analisiscashflow->momento_pagos == "fin_paquete"){
            foreach($momentos as $key => $momento){
                $i = $key+1;
                if(($i > $analisiscashflow->numero_hitos && $analisiscashflow->numero_hitos > 0) || 
                ($i > $analisiscashflow->numero_anualidades && $analisiscashflow->numero_anualidades > 0)){
                    continue;
                }
                $timeline[] = [
                    'direction' => 'r or',                    
                    'title' => 'Fecha pago fin justificación '.$key+1,
                    'date' => Carbon::createFromFormat('d/m/Y',$momento),
                    'desc' => 'Fecha de pago al finalizar la justificación '.$key+1
                ];
            }
        }

        usort($timeline, function ($a, $b) {
            $dateA = $a['date'];
            $dateB = $b['date'];
            // ascending ordering, use `<=` for descending
            return ($dateA >= $dateB) ? true : false;
        });

        foreach($timeline as $key => $line){
            if(isset($timeline[$key+1])){
                $months = $line['date']->diffInMonths($timeline[$key+1]['date']);
                if($months == 0){
                    $timeline[$key]['months'] = $line['date']->diffInMonths($timeline[$key+1]['date']);
                    if($timeline[$key+1]['direction'] == $line['direction']){
                        $timeline[$key]['group'] = "s";
                    }else{
                        $timeline[$key]['group'] = "n";    
                    }
                }else{
                    $timeline[$key]['months'] = $line['date']->diffInMonths($timeline[$key+1]['date']);
                    $timeline[$key]['group'] = "n";
                }
            }else{
                $timeline[$key]['months'] = 0;
                $timeline[$key]['group'] = "n";
            }
        }

        return $timeline;
    }

    public function avisarUsuariosAnalisisTesoreria(Request $request){

        $priorizaciones = \App\Models\PriorizaAnalisisTesoreria::where('convocatoria_id', $request->get('id_convocatoria'))->where('completado', 0)->get();

        if($priorizaciones){
            foreach($priorizaciones as $prioridad){
                $organismo = null;
                if($prioridad->convocatoria->departamento !== null){
                    $organismo = $prioridad->convocatoria->departamento;
                }elseif($prioridad->convocatoria->organo !== null){
                    $organismo = $prioridad->convocatoria->organo;
                }
                if($organismo !== null){
                    if($prioridad->user !== null && $prioridad->user->email_verified_at !== null){

                        $mail = new \App\Mail\AvisarPriorizarTesoreria($prioridad->convocatoria->Uri, $organismo->url, $prioridad->convocatoria->Titulo);
                        try{
                            Mail::to($prioridad->user->email)->queue($mail);
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                            return redirect()->back()->withErrors('Error al enviar el correo al usuario');
                        }

                        try{
                            $prioridad->completado = 1;
                            $prioridad->save();
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                            return redirect()->back()->withErrors('No se ha podido completar la solicitud en este momento, intentalo de nuevo pasados unos minutos.');
                        }
                    }

                }
            }

            return redirect()->back()->withSuccess('Avisados a todos los usuarios ('.$priorizaciones->count().') que han solicitado priorizar este cashflow');

        }

        return redirect()->back()->withErrors('No ha priorizaciones por enviar en este cashflow de esta convocatoria');

    }
 
}
