<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use stdClass;

class EnviarDatosBeagleController extends Controller
{
    //
    const AMOUNT = 40000;

    public function index(){

        return view('beagle.index',[                     
        ]);

    }

    public function getConcessions(Request $request){

        $data = json_decode($request->get('data'), true);
        $desde = Carbon::createFromFormat("d/m/Y", $data['desde'])->format('Y-m-d');        
        $hasta = \Carbon\Carbon::parse($desde)->endOfMonth()->format('Y-m-d');

        if($desde !== null && !empty($data)){

            $concesiones = \App\Models\Concessions::where('fecha', '>=', $desde)->where('fecha', '<=', $hasta)->where('amount', '>=', self::AMOUNT)->get();

            if($concesiones->isEmpty()){
                return response()->json("No hay datos", 404); 
            }

            $cifs1 = $concesiones->filter(function ($item)  {                
                return str_starts_with($item->custom_field_cif, "A");
            });
            $cifs2 = $concesiones->filter(function ($item)  {                
                return str_starts_with($item->custom_field_cif, "B");
            });

            $totalorganismos = $cifs1->whereNotNull('id_organo')->groupBy('id_organo')->count() + $cifs2->whereNotNull('id_organo')->groupBy('id_organo')->count();
            $totalorganismos += $cifs1->whereNotNull('id_departamento')->groupBy('id_departamento')->count() + $cifs2->whereNotNull('id_departamento')->groupBy('id_departamento')->count();
            $totalnifs = $cifs1->whereNotNull('custom_field_cif')->groupBy('custom_field_cif')->count() + $cifs2->whereNotNull('custom_field_cif')->groupBy('custom_field_cif')->count();

            return response()->json("Total concesiones: ".$concesiones->count()." de ".$totalorganismos." Organismos para un total de ".$totalnifs." CIFs", 200); 
        }

        return response()->json("No hay datos", 404); 
    }

    public function sendConcessionsBeagle(Request $request){

        $desde = Carbon::createFromFormat("d/m/Y", $request->get('desde'))->format('Y-m-d');        
        $hasta = \Carbon\Carbon::parse($desde)->endOfMonth()->format('Y-m-d');
        $concesiones = \App\Models\Concessions::where('fecha', '>=', $desde)->where('fecha', '<=', $hasta)->where('amount', '>=', self::AMOUNT)->get();

        if($concesiones->isEmpty()){
            return response()->json("No hay datos", 404); 
        }

        $zoho = new \App\Libs\ZohoCreatorV2();  
        $data = new stdClass();
        $i = 0;

        $arraydepartamentos = $concesiones->whereNotNull('id_departamento')->pluck('id_departamento')->toArray();

        foreach(array_values(array_unique($arraydepartamentos)) as $iddepartamento){

            ### NO ENVIAR CONCESIONES ENISA
            if($iddepartamento == 6438){
                continue;
            }

            $concesion = $concesiones->where('id_departamento', $iddepartamento)->first();
            $cifs1 = $concesiones->where('id_departamento', $iddepartamento)->filter(function ($item)  {                
                return str_starts_with($item->custom_field_cif, "A");
            })->pluck('custom_field_cif')->toArray();
            $cifs2 = $concesiones->where('id_departamento', $iddepartamento)->filter(function ($item)  {                
                return str_starts_with($item->custom_field_cif, "B");
            })->pluck('custom_field_cif')->toArray();
            $nombre = ($concesion->departamento->Acronimo !== null) ? $concesion->departamento->Acronimo.": ".$concesion->departamento->Nombre : $concesion->departamento->Nombre;
            $title = "Concesiones ".$nombre." Mes ".$desde." - ".$hasta." de más de ".self::AMOUNT. "€";
            $cifs = array_merge(array_values(array_unique($cifs1)), array_values(array_unique($cifs2)));
    
            if(!empty($cifs)){

                $i++;
                $data = new stdClass();
                $data->Acronimo = $title." ".$request->get('titulo');
                $data->Descripcion = $request->get('mensaje');
                $data->Pitch = ($request->get('speech') === null) ? $request->get('mensaje') : $request->get('speech');
                $data->NIFSPendientes = json_encode($cifs);
                $data->LinkAyuda = ($request->get('link') === null) ? '' : $request->get('link');
                $data->ResponsableAlCrear = "Auto";
                $data->ResponsableReAbrir = "Auto";
                $data->FechaMaxima = ($request->get('fechamax') === null) ? Carbon::now()->addDays(7)->format('d-m-Y') : Carbon::createFromFormat('d/m/Y', $request->get('fechamax'))->format('d-m-Y');
                $data->Prioridad = ($request->get('prioridad') === null) ? "Baja" : $request->get('prioridad');
                $data->DiasSeguimiento = 100;

                try{
                    $response = $zoho->addRecords('PropuestasInnovating',$data);           
                    //dd($response);
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return redirect()->back()->withErrors("Error en el envio o la conexion con beagle");
                }

            }
        }
  
        $arrayorganos = $concesiones->whereNotNull('id_organo')->pluck(['id_organo'])->toArray();

        foreach(array_unique($arrayorganos) as $idorgano){

            ### NO ENVIAR CONCESIONES ENISA
            if($idorgano == 6438){
                continue;
            }
        
            $concesion = $concesiones->where('id_organo', $idorgano)->first();
            $cifs1 = $concesiones->where('id_organo', $idorgano)->filter(function ($item)  {                
                return str_starts_with($item->custom_field_cif, "A");
            })->pluck('custom_field_cif')->toArray();;
            $cifs2 = $concesiones->where('id_organo', $idorgano)->filter(function ($item)  {                
                return str_starts_with($item->custom_field_cif, "B");
            })->pluck('custom_field_cif')->toArray();

            $nombre = ($concesion->organo->Acronimo !== null) ? $concesion->organo->Acronimo.": ".$concesion->organo->Nombre : $concesion->organo->Nombre;
            $title = "Concesiones ".$nombre." Mes ".$desde." - ".$hasta." de más de ".self::AMOUNT. "€";
            $cifs = array_merge(array_values(array_unique($cifs1)), array_values(array_unique($cifs2)));           

            if(!empty($cifs)){

                $i++;
                $data = new stdClass();
                $data->Acronimo = $title." ".$request->get('titulo');
                $data->Descripcion = $request->get('mensaje');
                $data->Pitch = ($request->get('speech') === null) ? $request->get('mensaje') : $request->get('speech');
                $data->NIFSPendientes = json_encode($cifs);
                $data->LinkAyuda = ($request->get('link') === null) ? '' : $request->get('link');
                $data->ResponsableAlCrear = "Auto";
                $data->ResponsableReAbrir = "Auto";
                $data->FechaMaxima = ($request->get('fechamax') === null) ? Carbon::now()->addDays(7)->format('d-m-Y') : Carbon::createFromFormat('d/m/Y', $request->get('fechamax'))->format('d-m-Y');
                $data->Prioridad = ($request->get('prioridad') === null) ? "Baja" : $request->get('prioridad');
                $data->DiasSeguimiento = 100;

                try{
                    $response = $zoho->addRecords('PropuestasInnovating',$data);           
                    //dd($response);
                    //Log::info($response);
                    //Log::info(json_encode($cifs));
                    //Log::info($nombre);
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return redirect()->back()->withErrors("Error en el envio o la conexion con beagle");
                }

            }

        }

        return redirect()->back()->withSuccess('Enviados cifs desde concesiones de un total de '.$i.' departamentos/organos a beagle');

    }

}
