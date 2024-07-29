<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardPaginaAyudaController extends Controller
{
    //
    public function paginasAyuda(){

        $paginasayuda = \App\Models\Help::all();
       
        return view('admin.paginasayuda.paginasayuda', [
            'paginasayuda' => $paginasayuda
        ]);

    }

    public function carpetasAyuda(){

        $carpetas = \App\Models\FolderHelp::all();
        $carpetasArray = $carpetas->pluck('nombre_carpeta', 'id')->toArray();

        return view('admin.paginasayuda.carpetasayuda', [
            'carpetas' => $carpetas,
            'carpetasarray' => $carpetasArray 
        ]);
    }

    public function editarPagina($id){

        $pagina = \App\Models\Help::where('id', $id)->first();

        if(!$pagina){
            return abort(404);
        }
        
        $carpetasArray = \App\Models\FolderHelp::pluck('nombre_carpeta', 'id')->toArray();

        return view('admin.paginasayuda.editarpagina', [
            'pagina' => $pagina,
            'carpetasarray' => $carpetasArray 
        ]);
    }

    public function editarCarpeta($id){

        $carpeta = \App\Models\FolderHelp::where('id', $id)->first();

        if(!$carpeta){
            return abort(404);
        }

        return view('admin.paginasayuda.editarcarpeta', [
            'carpeta' => $carpeta,
        ]);
    }


    public function editPagina(Request $request){

        if($request->get('type') == "nueva" && ($request->get('id') === null || $request->get('id') == "null")){

            try{
                $nuevapagina = new \App\Models\Help;
                $nuevapagina->titulo = $request->get('titulo');
                $nuevapagina->descripcion = $request->get('descripcion');
                $nuevapagina->link = $request->get('link');
                $nuevapagina->position = $request->get('posicion');
                $nuevapagina->id_carpeta = ($request->get('carpetas') === null) ? null : json_encode($request->get('carpetas'));
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

    public function editCarpeta(Request $request){

        if($request->get('type') == "nueva" && ($request->get('id') === null || $request->get('id') == "null")){

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

    public function crearPagina(){

        $carpetasArray = \App\Models\FolderHelp::pluck('nombre_carpeta', 'id')->toArray();

        return view('admin.paginasayuda.crearpagina', [
            'carpetasarray' => $carpetasArray 
        ]);
    }

    public function crearCarpeta(){

        return view('admin.paginasayuda.crearcarpeta', [           
        ]);
    }

}
