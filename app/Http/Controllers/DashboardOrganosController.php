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

    public function departamentos(){

        $departamentos = \App\Models\Departamentos::orderByDesc('updated_at')->orderByDesc('created_at')->get();
        
        return view('admin.departamentos.departamentos', [
            'departamentos' => $departamentos
        ]);
    }


    public function crearOrgano(){

        $ministerios = \App\Models\Ministerios::get();
        $paises = \App\Models\Paises::where('mostrar', 1)->get();
        $ccaas = \App\Models\Ccaa::get();

        return view('admin.organos.crear', [
            'ministerios' => $ministerios,
            'paises' => $paises,
            'ccaas' => $ccaas
        ]);
    }

    public function crearDepartamento(){

        $ccaas = \App\Models\Ccaa::get();
        $paises = \App\Models\Paises::where('mostrar', 1)->get();

        return view('admin.departamentos.crear', [
            'ccaas' => $ccaas,
            'paises' => $paises
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
        $ccaas = getAllCcaas();
        $paises = \App\Models\Paises::where('mostrar', 1)->get();

        return view('admin.organos.editar', [
            'organismo' => $organismo,
            'ministerios' => $ministerios,
            'ccaas' => $ccaas,
            'paises' => $paises,
        ]);
    }

    public function editarDepartamento($id){

        if($id == "" || $id === null){
            return abort(419);
        }

        $organismo = \App\Models\Departamentos::find($id);

        if($organismo === null){
            return abort(419);
        }

        $ccaas = \App\Models\Ccaa::get();
        $paises = \App\Models\Paises::where('mostrar', 1)->get();

        return view('admin.departamentos.editar', [
            'organismo' => $organismo,
            'ccaas' => $ccaas,
            'paises' => $paises,
        ]);
    }

    public function saveOrgano(Request $request){

        $url = cleanUriBeforeSave(str_replace(" ","-", mb_strtolower($request->get('acronimo'))));
        try{
            $organo = new \App\Models\Organos();
            $organo->Nombre = $request->get('nombre');
            $organo->Acronimo = $request->get('acronimo');
            $organo->id_ministerio = $request->get('ministerio');
            $organo->url = $url;
            $organo->pais = $request->get('pais');
            $organo->es_interno = 1;
            $organo->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido guardar el organimos en la bbdd');
        }

        return redirect()->back()->withSuccess('Organismo creado correctamente');
    
    }

    public function saveDepartamento(Request $request){

        $url = cleanUriBeforeSave(str_replace(" ","-", mb_strtolower($request->get('acronimo'))));
        try{
            $departamento = new \App\Models\Departamentos();
            $departamento->Nombre = $request->get('nombre');
            $departamento->Acronimo = $request->get('acronimo');
            $departamento->id_ccaa = $request->get('ccaa');
            $departamento->url = $url;
            $departamento->pais = $request->get('pais');
            $departamento->es_interno = 1;
            $departamento->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido guardar el organimos en la bbdd');
        }

        return redirect()->back()->withSuccess('Organismo creado correctamente');
    
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
            $organismo->pais = $request->get('pais');
            $organismo->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido guardar el organimos en la bbdd');
        }

        return redirect()->back()->withSuccess('Organismo actualizado correctamente');
    }

    public function editDepartamento(Request $request){

        $organismo = \App\Models\Departamentos::find($request->get('id'));
       
        if($organismo === null){
            return redirect()->back()->withErrors('No se ha encontrado el organimos para editar');
        }

        try{
            $organismo->Acronimo = $request->get('acronimo');
            $organismo->url = $request->get('url');
            $organismo->Web = $request->get('web');
            $organismo->Descripcion = $request->get('descripcion');
            $organismo->Tlr = $request->get('tlr');
            $organismo->id_ccaa = $request->get('ccaa');
            $organismo->visibilidad = ($request->get('visibilidad') === null) ? 0 : 1;
            $organismo->scrapper = ($request->get('importante') === null) ? 0 : 1;
            $organismo->esFondoPerdido = ($request->get('fondoperdido') === null) ? 0 : 1;
            $organismo->proyectosImportados = ($request->get('proyectosimportados') === null) ? 0 : 1;
            $organismo->pais = $request->get('pais');
            $organismo->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido guardar el organimos en la bbdd');
        }

        return redirect()->back()->withSuccess('Organismo actualizado correctamente');
    }

    public function ministerios(){

        $ministerios = \App\Models\Ministerios::orderByDesc('updated_at')->orderByDesc('created_at')->get();
        
        return view('admin.ministerios.ministerios', [
            'ministerios' => $ministerios
        ]);
    }

    public function ccaas(){

        $ccaas = \App\Models\Ccaa::orderByDesc('updated_at')->orderByDesc('created_at')->get();
        
        return view('admin.ccaas.ccaas', [
            'ccaas' => $ccaas
        ]);
    }
}
