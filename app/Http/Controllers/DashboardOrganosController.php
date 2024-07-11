<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardOrganosController extends Controller
{
    //
    public function organos(){

        $organos = \App\Models\Organos::orderByDesc('updated_at')->orderByDesc('created_at')->get();
        
        return view('admin.organos.organos', [
            'organos' => $organos
        ]);
    }

    public function editarOrgano($id){

        if($id == "" || $id === null){
            return abort(419);
        }

        $organismo = \App\Models\Organos::find($id);

        if($organismo === null){
            return abort(419);
        }

        $ministerios = \App\Models\Ministerios::get();

        return view('admin.organos.editar', [
            'organismo' => $organismo,
            'ministerios' => $ministerios
        ]);
    }

    public function editOrgano(Request $request){

        $organismo = \App\Models\Organos::find($request->get('id'));
       
        if($organismo === null){
            return redirect()->back()->withErrors('No se ha encontrado el organimos para editar');
        }

        try{
            $organismo->Acronimo = $request->get('acronimo');
            $organismo->url = $request->get('url');
            $organismo->Web = $request->get('web');
            $organismo->Descripcion = $request->get('descripcion');
            $organismo->Tlr = $request->get('tlr');
            $organismo->id_ministerio = $request->get('ministerio');
            $organismo->visibilidad = ($request->get('visibilidad') === null) ? 0 : 1;
            $organismo->scrapper = ($request->get('importante') === null) ? 0 : 1;
            $organismo->esFondoPerdido = ($request->get('fondoperdido') === null) ? 0 : 1;
            $organismo->proyectosImportados = ($request->get('proyectosimportados') === null) ? 0 : 1;
            $organismo->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido guardar el organimos en la bbdd');
        }

        return redirect()->back()->withSuccess('Organismo actualizado correctamente');
    }
}
