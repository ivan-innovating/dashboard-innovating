<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardPatentesController extends Controller
{
    //
    public function patentes(){

        $patentes = \App\Models\Patentes::all();
        $patentesconmatch = collect($patentes)->where('MatchCif', '!=', '')->where('CIF', '!=', '');

        return view('admin.patentes.patentes', [
            'patentesconmatch' => $patentesconmatch,
        ]);
    }

    public function patentesSinCIF(){

        $patentes = \App\Models\Patentes::all();
        $patentessinmatch = collect($patentes)->where('MatchCif', '')->where('CIF', '');
        $patentes2 = collect($patentes)->where('MatchCif', null)->where('CIF', null);
        $patentessinmatch->merge($patentes2);

        return view('admin.patentes.patentessincif', [
            'patentessinmatch' => $patentessinmatch,
        ]);
    }



    public function editarPatente(Request $request){

        $patente = \App\Models\Patentes::where('id', $request->route('id'))->first();

        if(!$patente){
            return abort(404);
        }

        return view('admin.patentes.editar', [
            'patente' => $patente,
        ]);
    }

    public function editPatente(Request $request){

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
                return redirect()->back()->withSuccess('Error actualizando la Patente');
            }
        }

        return redirect()->back()->withSuccess('Patente actualizada correctamente');

    }

}
