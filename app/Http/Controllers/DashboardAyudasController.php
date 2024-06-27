<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardAyudasController extends Controller
{
    //
    const MESES = [
        1=>1, 
        2=>2,
        3=>3,
        4=>4,
        5=>5,
        6=>6,
        7=>7,
        8=>8,
        9=>9,
        10=>10,
        11=>11,
        12=>12,
    ];

    public function ayudas(){

        $ayudas = \App\Models\Convocatorias::orderByDesc('created_at')->orderByDesc('updated_at')->get();

        return view('admin.ayudas.ayudas', [
            'ayudas' => $ayudas
        ]);

    }

    public function crearAyuda(){

        return view('admin.ayudas.crear', [           
            'meses' => Self::MESES
        ]);

    }

    public function saveAyuda(Request $request){

        $ayuda = \App\Models\Convocatorias::where('acronimo', $request->get('acronimo'))->orWhere('titulo', $request->get('acronimo'))->first();

        if($ayuda){
            return redirect()->back()->withErrors('Ya existe una ayuda con ese acronimo o título');
        }

        try{
            $ayuda = new \App\Models\Convocatorias();            
            $ayuda->acronimo = $request->get('acronimo');
            $ayuda->titulo = $request->get('titulo');
            $ayuda->descripcion_corta = $request->get('descripcion');
            $ayuda->mes_apertura_1 = $request->get('mes_1');
            $ayuda->mes_apertura_2 = ($request->get('mes_2') !== null && $request->get('mes_2') != "") ? $request->get('mes_2') : null;
            $ayuda->mes_apertura_3 = ($request->get('mes_3') !== null && $request->get('mes_3') != "") ? $request->get('mes_3') : null;
            $ayuda->duracion_convocatorias = $request->get('duracion');
            $ayuda->es_indefinida = ($request->get('esindefinida') !== null) ? 1 : 0;
            $ayuda->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido guardar la ayuda en la base de datos');
        }

        return redirect()->back()->withSuccess('Nueva ayuda creada correctamente');
    }

    public function editarAyuda($id){

        $ayuda = \App\Models\Convocatorias::where('id', $id)->first();

        return view('admin.ayudas.editar', [
            'ayuda' => $ayuda,
            'meses' => Self::MESES
        ]);
    }

    public function editAyuda(Request $request){

        if($request->get('esindefinida') !== null){
            $mes = ($request->get('mes_1') === null) ? null : $request->get('mes_1');
            $duracion = null;
        }else{
            $mes = $request->get('mes_1');
            $duracion = $request->get('duracion');
        }

        try{ 
            $ayuda = \App\Models\Convocatorias::find($request->get('id'));           

            if(!$ayuda){
                return redirect()->back()->withErrors('No se ha podido actualizar la ayuda en la base de datos 1');
            }

            $ayuda->acronimo = $request->get('acronimo');
            $ayuda->titulo = $request->get('titulo');
            $ayuda->descripcion_corta = $request->get('descripcion');
            $ayuda->mes_apertura_1 = $mes;
            $ayuda->mes_apertura_2 = ($request->get('mes_2') !== null && $request->get('mes_2') != "") ? $request->get('mes_2') : null;
            $ayuda->mes_apertura_3 = ($request->get('mes_3') !== null && $request->get('mes_3') != "") ? $request->get('mes_3') : null;
            $ayuda->duracion_convocatorias = $duracion;
            $ayuda->es_indefinida = ($request->get('esindefinida') === null) ? 0 : 1;
            $ayuda->extinguida = ($request->get('extinguida') === null) ? 0 : 1;
            $ayuda->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido actualizar la ayuda en la base de datos 2');
        }

        return redirect()->back()->withSuccess('Ayuda actualizada correctamente');
    }
}
