<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;

class CalculaCalidadCantidadImasD extends Command
{
    const MINCANTIDAD = 130000;
    const ACTIVACION = 100000;
    const MINIMPORTE = 80000;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calcula:I+D {cif?} {new?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Según formula propia calculo de la cantidad y calidad de una empresa';

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
        $this->info(Carbon::now());
        $this->info('Inicio actualizar cantidad y calidad de I+D...');

        $cif = $this->argument('cif');
        $new = $this->argument('new');

        #actualizamos el trl de una sola empresa
        if(isset($cif) && !empty($cif)){

            $einforma = \App\Models\Einforma::where('identificativo', $cif)->orderBy('anioBalance', 'DESC')->orderBy('ultimaActualizacion', 'DESC')->first();

            if(!$einforma){
                $this->info("No hay datos de einforma para ese cif: ".$cif);
                return;
            }

            $empresa = \App\Models\Entidad::where('CIF', $cif)->first();

            $result = array('ayudas24meses' => 10, 'ayudas2448meses' => 10, 'ayudaseuropeas' => 10, 'CNAE' => 10, 'patente' => 10,
            'sellopyme' => 10, 'startup' => 10, 'investigadores' => 10);

            #Revisamos ayudas recibidas en los ultimos 24 meses
            $ayudas24meses = \App\Models\Concessions::where('custom_field_cif', $empresa->CIF)->where('fecha', '>=', Carbon::now()->subYears(2)->firstOfYear())->get();

            $result['ayudas24meses'] = $this->getTrlAyuda($ayudas24meses, false);

            #Revisamos ayudas recibidas en los ultimos 24 a 48 meses
            $ayudas24a48meses = \App\Models\Concessions::where('custom_field_cif', $empresa->CIF)
            ->where('fecha', '<=',Carbon::now()->subYears(2)->firstOfYear())->where('fecha', '>=',Carbon::now()->subYears(4)->firstOfYear())->get();

            $result['ayudas2448meses'] = $this->getTrlAyuda($ayudas24a48meses, true);

            $ayudaseuropeas = \App\Models\Concessions::where('custom_field_cif', $empresa->CIF)->get();

            if($ayudaseuropeas){

                #dd($ayudaseuropeas);
                $ok = 0;
                $h2020 = collect($ayudaseuropeas)->where('id_organo', 6522);
                if($h2020->isNotEmpty()){
                    $result['ayudaseuropeas'] = $this->getTrlAyuda($ayudaseuropeas, false);
                    $ok = 1;
                }
                if($ok == 0){
                    $fp7 = collect($ayudaseuropeas)->where('id_organo', 6521);
                    if($fp7->isNotEmpty()){
                        $result['ayudaseuropeas'] = $this->getTrlAyuda($ayudaseuropeas, false);
                        $ok = 1;
                    }
                }
                if($ok == 0){
                    $fp6 = collect($ayudaseuropeas)->where('id_organo', 6520);
                    if($fp6->isNotEmpty()){
                        $result['ayudaseuropeas'] = $this->getTrlAyuda($ayudaseuropeas, false);
                        $ok = 1;
                    }
                }
            }

            #Revisamos si tiene la empresa CNAE y vemos si podemos sacar el trlmedio de ese cnae de nuestra bbdd
            if(isset($einforma->cnaeEditado)){
                $trlcnae = \App\Models\Cnaes::where('Nombre', $einforma->cnaeEditado)->first();
                if($trlcnae){
                    $result['CNAE'] = (int)$trlcnae->TrlMedio;
                }
            }elseif(isset($einforma->cnae)){
                $trlcnae = \App\Models\Cnaes::where('Nombre', $einforma->cnae)->first();
                if($trlcnae){
                    $result['CNAE'] = (int)$trlcnae->TrlMedio;
                }
            }elseif(!empty(json_decode($empresa->Cnaes, true))){
                $cnae = json_decode($empresa->Cnaes);
                if(isset($cnae->display_value)){
                    $trlcnae = \App\Models\Cnaes::where('Nombre', $cnae->display_value)->first();
                }else{
                    $trlcnae = \App\Models\Cnaes::where('Nombre', $cnae[0])->first();
                }
                if($trlcnae){
                    $result['CNAE'] = (int)$trlcnae->TrlMedio;
                }
            }

            #Revisamos patentes con menos de 48meses o si concesiones organismo oficina de patentes(650) con menos de 48meses
            $patente = \App\Models\Patentes::where('CIF', $empresa->CIF)->where('Fecha_publicacion', '>=', Carbon::now()->subYears(4))->first();
            if($patente){
                $result['patente'] = 7;
            }
            $patente = \App\Models\Concessions::where('custom_field_cif', $empresa->CIF)->where('id_organo', 650)->where('fecha', '>=', Carbon::now()->subYears(4))->first();
            if($patente){
                $result['patente'] = 7;
            }

            #Revisamos si es una empresa startup menos de 3 años de fecha creacion
            $startup = \App\Models\Einforma::where('identificativo', $empresa->CIF)->where('fechaConstitucion', '>=', Carbon::now()->subYears(3))->first();
            if($startup){
                if($startup->esMercantil == 0){
                    $result['startup'] = 6;
                }
            }

            #Revisamos si ha tenido sello pyme en los ultimos 2 años
            $sellopyme = \App\Models\Pymes::where('CIF', $empresa->CIF)->where('validez', '>=', Carbon::now()->subYears(2)->firstOfYear())->first();
            if($sellopyme){
                $result['sellopyme'] = 6;
            }

            #Calculo de cantidad de i+d de una empresa
            $calidad = min($result);            
            $ayudas36meses = \App\Models\Concessions::where('custom_field_cif', $empresa->CIF)
            ->where('fecha', '>=', Carbon::now()->subYears(3)->firstOfYear())->where('amount', '>=', self::MINCANTIDAD)->where('id_organo', '!=', 5799)
            ->orderByDesc('equivalent_aid')->orderByDesc('amount')->orderByDesc('fecha')->get();
            $amount = $ayudas36meses->sum('amount');
            $cantidadimasd = null;
            $esayuda = 0;
            $eseinforma = 0;
            $coeficiente = 1.1;

            switch($einforma->categoriaEmpresa){                
                case "Grande":
                    $coeficiente = 1.1;
                    break;
                case "Mediana":
                    $coeficiente = 1.6;
                    break;                        
                case "Pequeña":
                    $coeficiente = 2.3;
                    break;                        
                case "Micro":
                    $coeficiente = 2.3;
                    break;                                                
            }
            
            if($ayudas36meses->isNotEmpty()){
                $organotrl = 10;
                foreach($ayudas36meses as $ayuda){

                   $organodpto = $ayuda->organo;
                   if(!$organodpto){
                        $organodpto = $ayuda->departamento;
                   }
                   if($organodpto){
                        if($organodpto->Tlr < $organotrl){
                            $organotrl = $organodpto->Tlr;
                        }
                   }
                }

                if(isset($organodpto->Tlr) && $organotrl < 10){
                    $cantidadimasd = $amount/$coeficiente;
                }else{
                    if($calidad <= 8){
                        $cantidadimasd = $amount/$coeficiente;
                    }                    
                }
                if($einforma->anioBalance >= Carbon::now()->subYears(2)->format('Y')){
                    if($cantidadimasd !== null && $cantidadimasd > $einforma->gastoAnual*0.9){
                        $cantidadimasd = $einforma->gastoAnual*0.9;
                    }
                }
                
            }else{
                
                if($einforma->trabajosInmovilizado > self::ACTIVACION){
                    if($startup){
                        if($einforma->PrimaEmision > self::ACTIVACION){
                            $cantidadimasd = $einforma->PrimaEmision*(1-($result['CNAE']/10));
                        }
                    }else{
                        if(isset($sellopyme) || $result['patente'] < 10 || $result['CNAE'] < 10){
                            $cantidadimasd = $einforma->trabajosInmovilizado;
                        }
                    }
                }else{
                    if($calidad <= 7){
                        if($einforma->gastoAnual <= 500000){
                            $cantidadimasd = $einforma->gastoAnual*0.7;
                        }
                        if($einforma->gastoAnual > 500000 && $einforma->gastoAnual <= 2000000){
                            $cantidadimasd = $einforma->gastoAnual*0.25;
                        }
                        if($einforma->gastoAnual > 2000000 && $einforma->gastoAnual <= 8000000){
                            $cantidadimasd = $einforma->gastoAnual*0.07;
                        }
                    }
                }
            }

            ### SOLO EMPRESAS PRIVADAS
            if(in_array("6668837", json_decode($empresa->naturalezaEmpresa))){
                #SI TRL > 6 Y MIN >= 1 INVESTIGADOR VALORTRL = 4
                $totalinvestigadores = \App\Models\Investigadores::where('id_ultima_experiencia', $empresa->id)->count();
                if($calidad > 6 && $totalinvestigadores > 0){
                    $result['investigadores'] = 5;
                }
                #SI INVESTIGADOR >= 1 cantidadI+D = NUM INVESTIGAORES * 130000
                $cantidadimasdinvestigadores = 0;
                if($totalinvestigadores > 0){
                    $cantidadimasdinvestigadores = $totalinvestigadores * 130000;
                }

                if($cantidadimasd < $cantidadimasdinvestigadores && $cantidadimasdinvestigadores > 0){
                    $cantidadimasd = $cantidadimasdinvestigadores;
                }
            }

            ### FIN DE SOLO EMPRESAS PRIVADAS

            if(in_array("6668838", json_decode($empresa->naturalezaEmpresa))){
                $calidad = 2;
            }

            $ayudas = $ayudas24meses->merge($ayudas24a48meses);
            $ayudas = $ayudas->merge($ayudaseuropeas);

            //$calculocooperacion = $this->getCalculoCooperacion($empresa->CIF);

            /*if($calidad == $empresa->valorTrl){
                $this->info('No ha habido cambio en el valor TRL de la empresa');
                return;                
            }*/

            try{
                $entidad = \App\Models\Entidad::where('id', $empresa->id)->first();                
                $entidad->valorTrl = $calidad;
                $entidad->calculoTrl = json_encode($result);
                $entidad->cantidadImasD = round($cantidadimasd,0);
                $entidad->entityUpdate = Carbon::now()->format('Y-m-d');
                $entidad->save();
            }catch(Exception $e){
                dd($e->getMessage());
            }

            try{
                $elasticdata = \App\Models\ElasticDataTable::where('NIF', $empresa->CIF)->first();  
                if($elasticdata){
                    $elasticdata->TRL = $calidad;
                    $elasticdata->gastoIDI = round($cantidadimasd,0);
                    $elasticdata->save();
                }
            }catch(Exception $e){
                dd($e->getMessage());
            }

            $this->info('Empresa: '.$empresa->Nombre.' url: '.env('APP_URL').'/empresa/'.$empresa->uri);

        #actualizamos el trl de todas las empresa
        }else{

            if(isset($new) && $new == "1"){
                $date = Carbon::now()->subDays(1);
                $empresas = \App\Models\Entidad::where('pais', 'ES')->where('EntityUpdate', '>=', $date->format('Y-m-d'))->whereNull('calculoTrl')->get();
            }else{
                $empresas = \App\Models\Entidad::where('pais', 'ES')->get();               
            }

            foreach($empresas as $key => $empresa){

                $einforma = \App\Models\Einforma::where('identificativo', $empresa->CIF)->orderByDesc('ultimaActualizacion')->first();
                if(!$einforma){
                    $this->info('No hay datos de einforma para este cif: '.$empresa->CIF);
                    $this->info('Empresas a saltar en calculo de I+D:'.$key);
                    continue;
                }

                $result = array('ayudas24meses' => 10, 'ayudas2448meses' => 10, 'ayudaseuropeas' => 10, 'CNAE' => 10, 'patente' => 10,
                'sellopyme' => 10, 'startup' => 10, 'investigadores' => 10);

                #Revisamos ayudas recibidas en los ultimos 24 meses
                $ayudas24meses = \App\Models\Concessions::where('custom_field_cif', $empresa->CIF)
                ->where('fecha', '>=', Carbon::now()->subYears(2))->orderByDesc('fecha')->orderByDesc('amount')->get();

                $result['ayudas24meses'] = $this->getTrlAyuda($ayudas24meses, false);

                #Revisamos ayudas recibidas en los ultimos 24 a 48 meses
                $ayudas24a48meses = \App\Models\Concessions::where('custom_field_cif', $empresa->CIF)
                ->where('fecha', '<=', Carbon::now()->subYears(2))->where('fecha', '>=', Carbon::now()->subYears(4))->orderByDesc('fecha')->orderByDesc('amount')->get();

                $result['ayudas2448meses'] = $this->getTrlAyuda($ayudas24a48meses, true);

                $ayudaseuropeas = \App\Models\Concessions::where('custom_field_cif', $empresa->CIF)->get();

                if($ayudaseuropeas){
                    $ok = 0;
                    $h2020 = collect($ayudaseuropeas)->where('id_organo', 6522);
                    if($h2020->isNotEmpty()){
                        $result['ayudaseuropeas'] = $this->getTrlAyuda($ayudaseuropeas, false);
                        $ok = 1;
                    }
                    if($ok == 0){
                        $fp7 = collect($ayudaseuropeas)->where('id_organo', 6521);
                        if($fp7->isNotEmpty()){
                            $result['ayudaseuropeas'] = $this->getTrlAyuda($ayudaseuropeas, false);
                            $ok = 1;
                        }
                    }
                    if($ok == 0){
                        $fp6 = collect($ayudaseuropeas)->where('id_organo', 6520);
                        if($fp6->isNotEmpty()){
                            $result['ayudaseuropeas'] = $this->getTrlAyuda($ayudaseuropeas, false);
                            $ok = 1;
                        }
                    }
                }

                #Revisamos si tiene la empresa CNAE y vemos si podemos sacar el trlmedio de ese cnae de nuestra bbdd
                if(isset($einforma->cnaeEditado)){
                    $trlcnae = \App\Models\Cnaes::where('Nombre', $einforma->cnaeEditado)->first();
                    if($trlcnae){
                        $result['CNAE'] = (int)$trlcnae->TrlMedio;
                    }
                }elseif(isset($einforma->cnae)){
                    $trlcnae = \App\Models\Cnaes::where('Nombre', $einforma->cnae)->first();
                    if($trlcnae){
                        $result['CNAE'] = (int)$trlcnae->TrlMedio;
                    }
                }else if($empresa->Cnaes !== "" && !empty(json_decode($empresa->Cnaes, true))){
                    $cnae = json_decode($empresa->Cnaes);
					if(strrpos($empresa->Cnaes, "display_value") !== false){
                    	$trlcnae = \App\Models\Cnaes::where('Nombre', $cnae->display_value)->first();
					}else{
						$trlcnae = \App\Models\Cnaes::where('Nombre', $cnae[0])->first();
					}
                    if($trlcnae){
                        $result['CNAE'] = (int)$trlcnae->TrlMedio;
                    }
                }

                #Revisamos patentes con menos de 48meses o si concesiones organismo oficina de patentes(650) con menos de 48meses
                $patente = \App\Models\Patentes::where('CIF', $empresa->CIF)->where('Fecha_publicacion', '>=', Carbon::now()->subYears(4))->first();
                if($patente){
                    $result['patente'] = 7;
                }
                $patente = \App\Models\Concessions::where('custom_field_cif', $empresa->CIF)->where('id_organo', 650)->where('fecha', '>=', Carbon::now()->subYears(4))->first();
                if($patente){
                    $result['patente'] = 7;
                }

                #Revisamos si es una empresa startup menos de 3 años de fecha creacion
                $startup = \App\Models\Einforma::where('identificativo', $empresa->CIF)->where('fechaConstitucion', '>=', Carbon::now()->subYears(3))->first();
                if($startup){
                    if($startup->esMercantil == 0){
                        $result['startup'] = 6;
                    }
                }

                #Revisamos si ha tenido sello pyme en los ultimos 2 años
                $sellopyme = \App\Models\Pymes::where('CIF', $empresa->CIF)->where('validez', '>=', Carbon::now()->subYears(2)->firstOfYear())->first();
                if($sellopyme){
                    $result['sellopyme'] = 6;
                }

                #Calculo de cantidad de i+d de una empresa
                $calidad = min($result);
                $ayudas36meses = \App\Models\Concessions::where('custom_field_cif', $empresa->CIF)
                ->where('fecha', '>=', Carbon::now()->subYears(3))->where('amount', '>=', self::MINCANTIDAD)->where('id_organo', '!=', 5799)->orderByDesc('equivalent_aid')->orderByDesc('amount')->orderByDesc('fecha')->get();
                $amount = $ayudas36meses->sum('amount');
                $cantidadimasd = null;
                $esayuda = 0;
                $eseinforma = 0;
                $coeficiente = 1.1;
                switch($einforma->categoriaEmpresa){
                    case "Grande":
                        $coeficiente = 1.1;
                        break;
                    case "Mediana":
                        $coeficiente = 1.6;
                        break;                        
                    case "Pequeña":
                        $coeficiente = 2;
                        break;                        
                    case "Micro":
                    $coeficiente = 2;
                        break;                                                
                }
                
                if($ayudas36meses->isNotEmpty()){
                    foreach($ayudas36meses as $ayuda){
                        $organotrl = 10;
                        foreach($ayudas36meses as $ayuda){
                           $organodpto = $ayuda->organo;
                           if(!$organodpto){
                                $organodpto = $ayuda->departamento;
                           }
                           if($organodpto){
                                if($organodpto->Tlr < $organotrl){
                                    $organotrl = $organodpto->Tlr;
                                }
                           }
                        }
        
                        if(isset($organodpto->Tlr) && $organotrl < 10){
                            $cantidadimasd = $amount/$coeficiente;
                            $esayuda = 1;
                        }else{
                            if($calidad <= 8){
                                $cantidadimasd = $amount/$coeficiente;
                                $esayuda = 1;
                            }
                        }
                        if($einforma->anioBalance >= Carbon::now()->subYears(2)->format('Y')){
                            if($cantidadimasd !== null && $cantidadimasd > $einforma->gastoAnual*0.9){
                                $cantidadimasd = $einforma->gastoAnual*0.9;
                            }                    
                        }
                    }
                }else{
                    if($einforma->trabajosInmovilizado > self::ACTIVACION){
                        if($startup){
                            if($einforma->PrimaEmision > self::ACTIVACION){
                                $cantidadimasd = $einforma->PrimaEmision*(1-($result['CNAE']/10));
                                $eseinforma = 1;
                            }
                        }else{
                            if(isset($sellopyme) || $result['patente'] < 10 || $result['CNAE'] < 10){
                                $cantidadimasd = $einforma->trabajosInmovilizado;
                            }
                        }
                    }else{
                        if($calidad <= 7){
                            if($einforma->gastoAnual <= 500000){
                                $cantidadimasd = $einforma->gastoAnual*0.4;
                            }
                            if($einforma->gastoAnual > 500000 && $einforma->gastoAnual <= 2000000){
                                $cantidadimasd = $einforma->gastoAnual*0.12;
                            }
                            if($einforma->gastoAnual > 2000000 && $einforma->gastoAnual <= 8000000){
                                $cantidadimasd = $einforma->gastoAnual*0.04;
                            }
                        }
                    }
                }

                ### SOLO EMPRESAS PRIVADAS
                if(in_array("6668837", json_decode($empresa->naturalezaEmpresa))){
                    #SI TRL > 6 Y MIN >= 1 INVESTIGADOR VALORTRL = 4
                    $totalinvestigadores = \App\Models\Investigadores::where('id_ultima_experiencia', $empresa->id)->count();
                    if($calidad > 6 && $totalinvestigadores > 0){
                        $result['investigadores'] = 4;
                    }
                    #SI INVESTIGADOR >= 1 cantidadI+D = NUM INVESTIGAORES * 130000
                    if($totalinvestigadores > 0){
                        $cantidadimasd = $totalinvestigadores * 130000;
                    }
                }
                ### FIN DE SOLO EMPRESAS PRIVADAS

                $this->info('Ultimo id: '.$key.' Empresa: '.$empresa->Nombre.' url: https://dios.innovating.works/empresa/'.$empresa->uri);
                /*$this->info('cantidad i+d sale de ayuda: '.$esayuda);
                $this->info('cantidad i+d sale de einforma: '.$eseinforma);
                $this->info('Cantidad en i+D: '.$cantidadimasd) ;
                $this->info('Valor minimo TRL: '.min($result));
                $this->info('Calculo de TRL: '.json_encode($result));*/

                if(isset($empresa->naturalezaEmpresa) && $empresa->naturalezaEmpresa !== "null" && $empresa->naturalezaEmpresa !== ""){
                    if(in_array("6668838", json_decode($empresa->naturalezaEmpresa))){
                        $calidad = 2;
                    }
                }

                //dump($result);
                $ayudas = $ayudas24meses->merge($ayudas24a48meses);
                $ayudas = $ayudas->merge($ayudaseuropeas);

                /*if($calidad == $empresa->valorTrl){
                    $this->info('No ha habido cambio en el valor TRL de la empresa');
                    continue;                
                }*/

                //$calculocooperacion = $this->getCalculoCooperacion($empresa->CIF);

                try{
                    $entidad = \App\Models\Entidad::where('id', $empresa->id)->first();                
                    $entidad->valorTrl = $calidad;
                    $entidad->calculoTrl = json_encode($result);
                    $entidad->cantidadImasD = round($cantidadimasd,0);
                    $entidad->entityUpdate = Carbon::now()->format('Y-m-d');
                    $entidad->save();
                }catch(Exception $e){
                    dd($e->getMessage());
                }

                try{
                    $elasticdata = \App\Models\ElasticDataTable::where('NIF', $empresa->CIF)->first();                
                    if($elasticdata){
                        $elasticdata->TRL = $calidad;
                        $elasticdata->gastoIDI = round($cantidadimasd,0);
                        $elasticdata->save();
                    }
                }catch(Exception $e){
                    dd($e->getMessage());
                }

                $this->info('Empresas a saltar en calculo de I+D:'.$key);
            }

        }

        $this->info(Carbon::now());
        $this->info('...Fin de actualizar cantidad y calidad de I+D');
        return 0;

    }

    function getTrlAyuda($ayudas, $es48){

        $trl = 10;
        $currenttrl = 10;
        $lastfondoperdido = 101;

        foreach($ayudas as $ayuda){

            if(isset($ayuda->id_organo)){                
                $organo = \App\Models\Organos::where('id', $ayuda->id_organo)->first();

                if($organo){
                    if($organo->Tlr == 10){
                        $trl = 10;   
                        if($trl <= $currenttrl){
                            $currenttrl = $trl;
                        }
                    }else{                        
                        if((int)$ayuda->amount >= self::MINCANTIDAD && (int)$ayuda->equivalent_aid >= self::MINIMPORTE){
                            $trl = (isset($organo->Tlr)) ? $organo->Tlr : 10;
                            if($trl <= $currenttrl){
                                $currenttrl = $trl;
                            }
                            if($ayuda->equivalent_aid > 0){
                                $result = (int)$ayuda->equivalent_aid * 100;
                                $fondoperdido = $result/(int)$ayuda->amount;
                            }else{
                                $fondoperdido = null;
                            }

                            if($fondoperdido){
                                if($lastfondoperdido > $fondoperdido && $lastfondoperdido < 101){
                                    continue;
                                }
                                $lastfondoperdido = $fondoperdido;

                                if($fondoperdido > 0 && $fondoperdido < 33){
                                    $trl = 7;
                                }
                                if($fondoperdido >= 33){
                                    $trl = 5;
                                }
                                if($fondoperdido > 60){
                                    $trl = 4;
                                }
                                if($es48){
                                    $trl = $trl+1;
                                }
                                if($trl <= $currenttrl){
                                    $currenttrl = $trl;
                                }

                            }
                        }
                    }
                }
                
            }

            if(isset($ayuda->id_departamento)){
                $departamento = \App\Models\Departamentos::where('id', $ayuda->id_departamento)->first();
                if($departamento){
                    if($departamento->Tlr == 10){
                        $trl = 10;    
                        if($trl <= $currenttrl){
                            $currenttrl = $trl;
                        }             
                    }else{                        
                        if((int)$ayuda->amount >= self::MINCANTIDAD && (int)$ayuda->equivalent_aid >= self::MINIMPORTE){
                            $trl = (isset($departamento->Tlr)) ? $departamento->Tlr : 10;
                            if($trl < $currenttrl){
                                $currenttrl = $trl;
                            }
                            if($ayuda->equivalent_aid > 0){
                                $result = (int)$ayuda->equivalent_aid * 100;
                                $fondoperdido = $result/(int)$ayuda->amount;
                            }else{
                                $fondoperdido = null;
                            }

                            if($fondoperdido){

                                if($lastfondoperdido > $fondoperdido && $lastfondoperdido < 101){
                                    continue;
                                }
                                $lastfondoperdido = $fondoperdido;

                                if($fondoperdido > 0 && $fondoperdido < 33){
                                    $trl = 7;
                                }
                                if($fondoperdido >= 33){
                                    $trl = 5;
                                }
                                if($fondoperdido > 60){
                                    $trl = 4;
                                }

                                if($es48){
                                    $trl = $trl+1;
                                }
                                if($trl < $currenttrl){
                                    $currenttrl = $trl;
                                }
                            }
                        }                
                    }
                }
            }

        }

        return ($trl <= $currenttrl) ? $trl : $currenttrl;

    }
}
