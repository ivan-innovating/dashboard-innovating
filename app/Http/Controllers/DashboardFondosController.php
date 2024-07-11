<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardFondosController extends Controller
{
    //
    public function fondos(){

        $fondos = \App\Models\Fondos::get();
      
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

    public function subfondos(Request $request){}

    public function typeofactions(Request $request){}

    public function budgetyearmap(Request $request){}
}
