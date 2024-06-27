<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CacheController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('cache.index');
    }

    public function delete(Request $request){

        if($request->get('index')){
            try{
                \Artisan::call('clear:redis index');
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->route('cache.index')->with('error', 'Error al borrar la cache');
            }
        }

        if($request->get('resultados')){
            try{
                \Artisan::call('clear:redis search');
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->route('cache.index')->with('error', 'Error al borrar la cache');
            }
        }

        if($request->get('empresas')){
            try{
                \Artisan::call('clear:redis single:empresa');
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->route('cache.index')->with('error', 'Error al borrar la cache');
            }
        }

        if($request->get('empresasproyectos')){
            try{
                \Artisan::call('clear:redis single:empresa:uri:proyectos');
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->route('cache.index')->with('error', 'Error al borrar la cache');
            }
        }

        if($request->get('empresasconcesiones')){
            try{
                \Artisan::call('clear:redis single:empresa:uri:concesiones');
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->route('cache.index')->with('error', 'Error al borrar la cache');
            }
        }

        if($request->get('organos')){
            try{
                //\Artisan::call('clear:redis innovating_works_single:organos');
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->route('cache.index')->with('error', 'Error al borrar la cache');
            }
        }

        if($request->get('ayudas')){
            try{
                \Artisan::call('clear:redis single:ayudas');
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->route('cache.index')->with('error', 'Error al borrar la cache');
            }
        }

        if($request->get('concesiones')){
            try{
                \Artisan::call('clear:redis single:concesiones');
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->route('cache.index')->with('error', 'Error al borrar la cache');
            }
        }

        if($request->get('publicaciones')){
            try{
                \Artisan::call('clear:redis single:noticias');
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->route('cache.index')->with('error', 'Error al borrar la cache');
            }
        }

        if($request->get('ayuda')){
            try{
                \Artisan::call('clear:redis single:ayuda');
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->route('cache.index')->with('error', 'Error al borrar la cache');
            }
        }

        if($request->get('organoproyectos')){
            try{
                \Artisan::call('clear:redis single:organo:proyectos');
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->route('cache.index')->with('error', 'Error al borrar la cache');
            }
        }

        if($request->get('organoestadisticas')){
            try{
                \Artisan::call('clear:redis single:organismo:uri');
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->route('cache.index')->with('error', 'Error al borrar la cache');
            }
        }

        if($request->get('fondos')){
            try{
                \Artisan::call('clear:redis single:fondos:uri');
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->route('cache.index')->with('error', 'Error al borrar la cache');
            }
        }


        return redirect()->route('cache.index')->with('success', 'Cache borrada');
    }

    public function deleteLaravelCache(){

        try{
            \Artisan::call('cache:clear');
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->route('cache.index')->withErrors('Error al borrar la cache del motor inteligente');
        }

        return redirect()->route('cache.index')->withSuccess('Cache del motor inteligente borrada');

    }

}
