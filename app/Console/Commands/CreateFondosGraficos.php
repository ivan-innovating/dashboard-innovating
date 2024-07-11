<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateFondosGraficos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:fondos_graficos {id?} {last?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea los datos para mostrar los gráficos en las páginas nuevas de fondos';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $id = $this->argument('id');
        $fondo = \App\Models\Fondos::find($id);
        $tosearch = "";
        $condition = "";
        if($fondo->matches_budget_application !== null){
            foreach(json_decode($fondo->matches_budget_application, true) as $text){
                if($text !== null){                    
                    $tosearch .= $text."|";
                }                    
            }
            $tosearch = rtrim($tosearch,"|");
            $condition = "REGEXP";
        }else{
            $tosearch = $this->getFondoSearchName($fondo->id);
            $condition = "LIKE";
        }

        if($tosearch == "" || $condition == ""){
            return COMMAND::FAILURE;
        }

        \Carbon\Carbon::setlocale(config('app.locale'));
        $startDate = \Carbon\Carbon::now()->subMonths(12)->startOfMonth();
        $endDate = \Carbon\Carbon::now()->endOfMonth();
        $concessions = \App\Models\Concessions::where('budget_application', $condition, $tosearch)->get();
        $concesiones = $concessions->where('created_at', '>=', $startDate)->where('created_at', '<=', $endDate);

        ##DATOS GRAFICO SUMA TOTAL DE CONCESIONES POR MES
        $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(12)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(12)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(11)->endOfMonth()->format('Y-m-d'))->count()];
        $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(11)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(11)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(10)->endOfMonth()->format('Y-m-d'))->count()];
        $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(10)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(10)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(9)->endOfMonth()->format('Y-m-d'))->count()];
        $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(9)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(9)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(8)->endOfMonth()->format('Y-m-d'))->count()];
        $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(8)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(8)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(7)->endOfMonth()->format('Y-m-d'))->count()];
        $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(7)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(7)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(6)->endOfMonth()->format('Y-m-d'))->count()];
        $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(6)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(6)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(5)->endOfMonth()->format('Y-m-d'))->count()];
        $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(5)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(5)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(4)->endOfMonth()->format('Y-m-d'))->count()];
        $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(4)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(4)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(3)->endOfMonth()->format('Y-m-d'))->count()];
        $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(3)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(3)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(2)->endOfMonth()->format('Y-m-d'))->count()];
        $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(2)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(2)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(1)->endOfMonth()->format('Y-m-d'))->count()];
        $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(1)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(1)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->endOfMonth()->format('Y-m-d'))->count()];

        $check = 0;
        if(!empty($totalconcesiones)){
            $check = max(array_column($totalconcesiones, "total"));
        }

        if($check > 0 && $fondo->mostrar_graficos == 0){
            try{
                $fondo->mostrar_graficos = 1;
                $fondo->save();
            }catch(Exception $e){
                dd($e->getMessage());
            }
        }

        $checkfondo = \App\Models\GraficosFondos::where('type', 'grafico-1')->where('id_fondo', $fondo->id)->first();

        if($checkfondo){
            try{
                $checkfondo->datos = json_encode($totalconcesiones, JSON_UNESCAPED_UNICODE);
                $checkfondo->activo = ($check > 0) ? 1 : 0;
                $checkfondo->save();
            }catch(Exception $e){
                dd($e->getMessage());
            }
        }else{
            try{
                $grafico = new \App\Models\GraficosFondos();
                $grafico->id_fondo = $fondo->id;
                $grafico->type = "grafico-1";
                $grafico->nombre = "Número de Concesiones Resgistadas en los últimos 12 Meses";
                $grafico->datos = json_encode($totalconcesiones, JSON_UNESCAPED_UNICODE);
                $grafico->activo = ($check > 0) ? 1 : 0;
                $grafico->save();
            }catch(Exception $e){
                dd($e->getMessage());
            }
        }

        ##DATOS GRAFICO SUMA TOTAL DE AMOUNTS DE CONCESIONES POR MES
        $totaldinero[] = ["mes" => ucfirst(now()->subMonths(12)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(12)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(11)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
        $totaldinero[] = ["mes" => ucfirst(now()->subMonths(11)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(11)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(10)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
        $totaldinero[] = ["mes" => ucfirst(now()->subMonths(10)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(10)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(9)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
        $totaldinero[] = ["mes" => ucfirst(now()->subMonths(9)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(9)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(8)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
        $totaldinero[] = ["mes" => ucfirst(now()->subMonths(8)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(8)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(7)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
        $totaldinero[] = ["mes" => ucfirst(now()->subMonths(7)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(7)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(6)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
        $totaldinero[] = ["mes" => ucfirst(now()->subMonths(6)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(6)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(5)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
        $totaldinero[] = ["mes" => ucfirst(now()->subMonths(5)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(5)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(4)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
        $totaldinero[] = ["mes" => ucfirst(now()->subMonths(4)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(4)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(3)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
        $totaldinero[] = ["mes" => ucfirst(now()->subMonths(3)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(3)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(2)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
        $totaldinero[] = ["mes" => ucfirst(now()->subMonths(2)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(2)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(1)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
        $totaldinero[] = ["mes" => ucfirst(now()->subMonths(1)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(1)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->endOfMonth()->format('Y-m-d'))->sum->amount,0)];

        $check = 0;
        if(!empty($totaldinero)){
            $check = max(array_column($totaldinero, "total"));
        }

        if($check > 0 && $fondo->mostrar_graficos == 0){
            try{
                $fondo->mostrar_graficos = 1;
                $fondo->save();
            }catch(Exception $e){
                dd($e->getMessage());
            }
        }

        $checkfondo = \App\Models\GraficosFondos::where('type', 'grafico-2')->where('id_fondo', $fondo->id)->first();

        if($checkfondo){
            try{
                $checkfondo->datos = json_encode($totaldinero, JSON_UNESCAPED_UNICODE);
                $checkfondo->activo = ($check > 0) ? 1 : 0;
                $checkfondo->save();
            }catch(Exception $e){
                dd($e->getMessage());
            }
        }else{
            try{
                $grafico = new \App\Models\GraficosFondos();
                $grafico->id_fondo = $fondo->id;
                $grafico->type = "grafico-2";
                $grafico->nombre = "Importe concesiones registradas últimos 12 meses";
                $grafico->datos = json_encode($totaldinero, JSON_UNESCAPED_UNICODE);
                $grafico->activo = ($check > 0) ? 1 : 0;
                $grafico->save();
            }catch(Exception $e){
                dd($e->getMessage());
            }
        }


         ##DATOS GRAFICO TAMAÑOS DE EMPRESA CON CONCESIONES
         $empresatipo[] = ['tipo' => 'Micro', "total" => $concesiones->filter(function($concesion){
            if($concesion->entidad !== null && $concesion->entidad->einforma !== null){
                if($concesion->entidad->einforma->categoriaEmpresa == "Micro"){
                    return true;
                }
            }
            return false;
        })->count()];
        $empresatipo[] = ['tipo' => 'Pequeña', "total" => $concesiones->filter(function($concesion){
            if($concesion->entidad !== null && $concesion->entidad->einforma !== null){
                if($concesion->entidad->einforma->categoriaEmpresa == "Pequeña"){
                    return true;
                }
            }
            return false;
        })->count()];
        $empresatipo[] = ['tipo' => 'Mediana', "total" => $concesiones->filter(function($concesion){
            if($concesion->entidad !== null && $concesion->entidad->einforma !== null){
                if($concesion->entidad->einforma->categoriaEmpresa == "Mediana"){
                    return true;
                }
            }
            return false;
        })->count()];
        $empresatipo[] = ['tipo' => 'Grande', "total" => $concesiones->filter(function($concesion){
            if($concesion->entidad !== null && $concesion->entidad->einforma !== null){
                if($concesion->entidad->einforma->categoriaEmpresa == "Grande"){
                    return true;
                }
            }
            return false;
        })->count()];
        $empresatipo[] = ['tipo' => 'Desconocido', "total" => $concesiones->filter(function($concesion){
            if($concesion->entidad !== null && $concesion->entidad->einforma !== null){
                if($concesion->entidad->einforma->categoriaEmpresa == "Desconocido" || $concesion->entidad->einforma->categoriaEmpresa == "Error"){
                    return true;
                }
            }
            return false;
        })->count()];

        $check = 0;
        if(!empty($empresatipo)){
            $check = max(array_column($empresatipo, "total"));
        }

        $checkfondo = \App\Models\GraficosFondos::where('type', 'grafico-3')->where('id_fondo', $fondo->id)->first();

        if($checkfondo){
            try{
                $checkfondo->datos = json_encode($empresatipo, JSON_UNESCAPED_UNICODE);
                $checkfondo->activo = ($check > 0) ? 1 : 0;
                $checkfondo->save();
            }catch(Exception $e){
                dd($e->getMessage());
            }
        }else{
            try{
                $grafico = new \App\Models\GraficosFondos();
                $grafico->id_fondo = $fondo->id;
                $grafico->type = "grafico-3";
                $grafico->nombre = "Importe concesiones registradas por categoría de empresa últimos 12 meses";
                $grafico->datos = json_encode($empresatipo, JSON_UNESCAPED_UNICODE);
                $grafico->activo = ($check > 0) ? 1 : 0;
                $grafico->save();
            }catch(Exception $e){
                dd($e->getMessage());
            }
        }

        $empresacnaes = array();       
        $cifs = array();     
        foreach ($concesiones as $key => $concesion) {            
            if($concesion->entidad !== null && $concesion->entidad->einforma !== null){                    
                $cnae = substr($concesion->entidad->einforma->cnae, 0, strripos($concesion->entidad->einforma->cnae,"-") -3);
                $cnae = str_replace('"', '', $cnae);
                if(array_search($cnae, array_column($empresacnaes, 'cnaeid')) === false && !in_array($concesion->entidad->CIF,$cifs)){                        
                    $empresacnaes[$cnae]['cnaeid'] = $cnae;
                    $empresacnaes[$cnae]['cnae'] = $concesion->entidad->einforma->cnae;
                    $empresacnaes[$cnae]['amount'] = $concesion->amount;
                    $empresacnaes[$cnae]['total'] = 1;
                    $cifs[] = $concesion->entidad->CIF;
                }else{
                    if(isset($empresacnaes[$cnae])){
                        $empresacnaes[$cnae]['amount'] += $concesion->amount;
                        $empresacnaes[$cnae]['total'] += 1;
                    }
                }
            }
        }

        $key_values = array_column($empresacnaes, 'total'); 
        array_multisort($key_values, SORT_DESC, $empresacnaes);
        $empresacnaes = array_values($empresacnaes);

        $check = 0;
        if(!empty($empresacnaes)){
            $check = max(array_column($empresacnaes, "total"));
        }

        $checkfondo = \App\Models\GraficosFondos::where('type', 'grafico-4')->where('id_fondo', $fondo->id)->first();

        if($checkfondo){
            try{
                $checkfondo->datos = json_encode($empresacnaes, JSON_UNESCAPED_UNICODE);
                $checkfondo->activo = ($check > 0) ? 1 : 0;
                $checkfondo->save();
            }catch(Exception $e){
                dd($e->getMessage());
            }
        }else{
            try{
                $grafico = new \App\Models\GraficosFondos();
                $grafico->id_fondo = $fondo->id;
                $grafico->type = "grafico-4";
                $grafico->nombre = "Importe concesiones por CNAE 2 dígitos en los últimos 12 meses";
                $grafico->datos = json_encode($empresacnaes, JSON_UNESCAPED_UNICODE);
                $grafico->activo = ($check > 0) ? 1 : 0;
                $grafico->save();
            }catch(Exception $e){
                dd($e->getMessage());
            }
        }

        $empresaccaas = array();       
        $ccaas = array();     
        foreach ($concesiones as $key => $concesion) {            
            if($concesion->entidad !== null && $concesion->entidad->einforma !== null && $concesion->entidad->einforma->ccaa !== null){        
                $ccaa = mb_strtolower(str_replace(" ", "", $concesion->entidad->einforma->ccaa));
                if(array_search($concesion->entidad->einforma->ccaa, array_column($empresaccaas, 'ccaa')) === false && !in_array($concesion->entidad->einforma->ccaa,$ccaas)){                        
                    $empresaccaas[$ccaa]['ccaa'] = $concesion->entidad->einforma->ccaa;
                    $empresaccaas[$ccaa]['amount'] = $concesion->amount;
                    $empresaccaas[$ccaa]['total'] = 1;
                    $ccaas[] = $concesion->entidad->einforma->ccaa;
                }else{
                    if(isset($empresaccaas[$ccaa])){
                        $empresaccaas[$ccaa]['amount'] += $concesion->amount;
                        $empresaccaas[$ccaa]['total'] += 1;
                    }
                }
            }
        }

        $key_values = array_column($empresaccaas, 'total'); 
        array_multisort($key_values, SORT_DESC, $empresaccaas);
        $empresaccaas = array_values($empresaccaas);

        $check = 0;
        if(!empty($empresaccaas)){
            $check = max(array_column($empresaccaas, "total"));
        }

        $checkfondo = \App\Models\GraficosFondos::where('type', 'grafico-5')->where('id_fondo', $fondo->id)->first();

        if($checkfondo){
            try{
                $checkfondo->datos = json_encode($empresaccaas, JSON_UNESCAPED_UNICODE, ENT_QUOTES);
                $checkfondo->activo = ($check > 0) ? 1 : 0;
                $checkfondo->save();
            }catch(Exception $e){
                dd($e->getMessage());
            }
        }else{
            try{
                $grafico = new \App\Models\GraficosFondos();
                $grafico->id_fondo = $fondo->id;
                $grafico->type = "grafico-5";
                $grafico->nombre = "Importe concesiones por Comunidad Autónoma en los últimos 12 meses";
                $grafico->datos = json_encode($empresaccaas, JSON_UNESCAPED_UNICODE, ENT_QUOTES);
                $grafico->activo = ($check > 0) ? 1 : 0;
                $grafico->save();
            }catch(Exception $e){
                dd($e->getMessage());
            }
        }

        $totaldinero = $concessions->sum('amount');
        $totalconcesiones = $concessions->count();

        $totalprivadas = $concessions->filter(function ($item) {
            if(str_starts_with($item->custom_field_cif, 'A') || str_starts_with($item->custom_field_cif, 'B') || str_starts_with($item->custom_field_cif, 'G')){
                return true;
            }
            return false;
        })->values();

        $totalprivadasdinero = $totalprivadas->sum('amount');
        $totalprivadasconcesiones = $totalprivadas->count();
        $totalotrosdinero = $totaldinero - $totalprivadasdinero;
        $totalotrosconcesiones = $totalconcesiones - $totalprivadasconcesiones;

        $totaldepartamentos = $concessions->filter(function ($item){
            if($item->id_departamento !== null){
                return true;
            }
            return false;
        })->values();

        $totalorganos = $concessions->filter(function ($item){
            if($item->id_organo !== null){
                return true;
            }
            return false;
        })->values();

        if($totaldepartamentos->isEmpty()){
            $datosdepartamentos = null;
        }else{
            $data = $totaldepartamentos->groupBy('id_departamento');
            $datosdepartamnetostotales = $data->map(function ($group){
                return [
                    'id_departamento' => $group->first()['id_departamento'], // opposition_id is constant inside the same group, so just take the first or whatever.
                    'amount' => $group->sum('amount'),
                    'total' => $group->count()                  
                ];
            });

            $data2 = $totalprivadas->whereNotNull('id_departamento')->groupBy('id_departamento');
            $datosdepartamentosprivadas = $data2->map(function($group) {
                return [
                    'id_departamento' => $group->first()['id_departamento'], // opposition_id is constant inside the same group, so just take the first or whatever.
                    'amount' => $group->sum('amount'),
                    'total' => $group->count()          
                ];
            });
                 
            foreach($datosdepartamnetostotales as $key => $totals){  
                $datosdepartamentos[$key]['id_organismo'] = $totals['id_departamento'];
                $dpto = \App\Models\Departamentos::find($totals['id_departamento']);
                if($dpto){
                    $datosdepartamnetos[$key]['dpto_nombre'] = ($dpto->Acronimo !== null) ? $dpto->Acronimo : $dpto->Nombre;
                    $datosdepartamnetos[$key]['dpto_url'] = $dpto->url;
                }
                $datosdepartamentos[$key]['totaldinero'] = $totals['amount'];
                $datosdepartamentos[$key]['totalconcesiones'] = $totals['total'];
                $datosdepartamentos[$key]['totaldineroprivadas'] = $datosdepartamentosprivadas[$key]['amount'];
                $datosdepartamentos[$key]['totalconcesionesprivadas'] = $datosdepartamentosprivadas[$key]['total'];
                $datosdepartamentos[$key]['totaldinerootros'] = $totals['amount'] - $datosdepartamentosprivadas[$key]['amount'];
                $datosdepartamentos[$key]['totalconcesionesotros'] = $totals['total'] - $datosdepartamentosprivadas[$key]['total'];
            }
                         
        }

        if($totalorganos->isEmpty()){
            $datosorganos = null;
        }else{
            $data = $totalorganos->groupBy('id_organo');
            $datosorganostotales = $data->map(function ($group){
                return [
                    'id_organo' => $group->first()['id_organo'], // opposition_id is constant inside the same group, so just take the first or whatever.
                    'amount' => $group->sum('amount'),
                    'total' => $group->count()                  
                ];
            });
               
            $data2 = $totalprivadas->whereNotNull('id_organo')->groupBy('id_organo');
            $datosorganosprivadas = $data2->map(function($group) {
                return [
                    'id_organo' => $group->first()['id_organo'], // opposition_id is constant inside the same group, so just take the first or whatever.
                    'amount' => $group->sum('amount'),
                    'total' => $group->count()          
                ];
            });
                 
            foreach($datosorganostotales as $key => $totals){                
                $datosorganos[$key]['id_organismo'] = $totals['id_organo'];
                $dpto = \App\Models\Organos::find($totals['id_organo']);
                if($dpto){
                    $datosorganos[$key]['dpto_nombre'] = ($dpto->Acronimo !== null) ? $dpto->Acronimo : $dpto->Nombre;
                    $datosorganos[$key]['dpto_url'] = $dpto->url;
                }
                $datosorganos[$key]['totaldinero'] = $totals['amount'];
                $datosorganos[$key]['totalconcesiones'] = $totals['total'];
                $datosorganos[$key]['totaldineroprivadas'] = $datosorganosprivadas[$key]['amount'];
                $datosorganos[$key]['totalconcesionesprivadas'] = $datosorganosprivadas[$key]['total'];
                $datosorganos[$key]['totaldinerootros'] = $totals['amount'] - $datosorganosprivadas[$key]['amount'];
                $datosorganos[$key]['totalconcesionesotros'] = $totals['total'] - $datosorganosprivadas[$key]['total'];
            }

        }

        $totales = array('totaldinero' => $totaldinero, 'totalconcesiones' => $totalconcesiones, 'totalprivadasdinero' => $totalprivadasdinero, 'totalotrosdinero' => $totalotrosdinero, 
        'totalprivadasconcesiones' => $totalprivadasconcesiones, 'totalotrosconcesiones' => $totalotrosconcesiones, 'datosdepartamentos' => $datosdepartamentos, 'datosorganos' => $datosorganos);

        $checkfondo = \App\Models\GraficosFondos::where('type', 'grafico-totales')->where('id_fondo', $fondo->id)->first();

        if($checkfondo){
            try{
                $checkfondo->datos = json_encode($totales, JSON_UNESCAPED_UNICODE);
                $checkfondo->activo = 1;
                $checkfondo->save();
            }catch(Exception $e){
                dd($e->getMessage());
            }
        }else{
            try{
                $grafico = new \App\Models\GraficosFondos();
                $grafico->id_fondo = $fondo->id;
                $grafico->type = "grafico-totales";
                $grafico->nombre = "Totales para pintar en bloque izquierdo";
                $grafico->datos = json_encode($totales, JSON_UNESCAPED_UNICODE);
                $grafico->activo = 1;
                $grafico->save();
            }catch(Exception $e){
                dd($e->getMessage());
            }
        }

        return COMMAND::SUCCESS;
    }

    private function getFondoSearchName($id){

        $tosearch = "";

        switch($id){
            case 1:
                $tosearch = "%Fondos Feder%";#ok
            break;
            case 2:
                $tosearch = "%Nextgen%";#ok
            break;
            case 3:
                $tosearch = "%MRR%";#ok
            break;
            case 4:
                $tosearch = "%Justa%";#ok
            break;
            case 5:
                $tosearch = "%FEMP%";#ok
            break;
            case 6:
                $tosearch = "%Rural%";#ok
            break;
            case 7:
                $tosearch = "%CDTI-EUROSTARS%";#ok
            break;
            case 8:
                $tosearch = "%EEA GRANTS%";#ok
            break; 
            case 9:
                $tosearch = "%CRIN%";#ok
            break;  
        }                                                                      

        return $tosearch;
    }
}
