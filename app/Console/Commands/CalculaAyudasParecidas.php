<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculaAyudasParecidas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calcula:ayudas_parecidas {id} {all?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dada una ayuda calcula las ayudas parecidas según las reglas de este excel: https://docs.google.com/spreadsheets/d/1C8KWtCZ9xFY6Vge7bc5vTI_YvYXk_jXqZWG5WRtRfjw/edit#gid=833033063';

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

        $all = $this->argument('all');
        $id = $this->argument('id');

        if(isset($all) && $all == "all" && $id == ""){

            $todasayudas = \App\Models\Ayudas::all();

            foreach($todasayudas as $currentayuda){

                $ayudas =  \App\Models\Ayudas::where('Estado', '!=', 'Cerrada')->where('Publicada', 1)
                ->where('Presentacion', $currentayuda->Presentacion)
                ->get();
                
                $ayudasparecidas = array();
                if($ayudas->isNotEmpty()){
                    foreach($ayudas as $ayuda){

                        if($ayuda->id == $currentayuda->id){
                            //$this->info($ayuda->Acronimo.': mismo id continue');
                            continue;
                        }

                        $score = 0;
                        $score = $this->getParecidasScore($currentayuda, $ayuda, $score);

                        if($score !== false){
                            #relleno objeto con ids de ayudas parecidas
                            array_push($ayudasparecidas, ['id' => $ayuda->id, 'score' => $score]);
                        }else{
                            continue;
                        }
                        if($currentayuda->Ambito == "Comunidad Autónoma" && $ayuda->Ambito == "Comunidad Autónoma"){
                            $currentccaas = json_decode($currentayuda->Ccaas);
                            $ccaas = json_decode($ayuda->Ccaas);
                            if(is_array($currentccaas) && is_array($ccaas)){
                                $checkccaas = array_intersect($currentccaas, $ccaas);
                                if(empty($checkccaas)){
                                    $this->info($ayuda->Acronimo.': no coinciden CCAAs continue');
                                    continue;
                                }else{
                                    $score += count($checkccaas);
                                }
                            }else{
                                continue;
                            }
                        }
    
                        #Check Intensidad
                        $minintensidad = $ayuda->Intensidad-1;
                        $maxintensidad = $ayuda->Intensidad+1;
                        if($currentayuda->Intensidad < $minintensidad){                       
                            $this->info($ayuda->Acronimo.': no coinciden en intensidad continue');                        
                            continue;                    
                        }
    
                        #Aumento de score por intensidad
                        if($currentayuda->Intensidad >= $maxintensidad){  
                            $score++;
                        }
    
                        #Check presupuesto
                        if(($currentayuda->PresupuestoMax === null || $currentayuda->PresupuestoMax == 0) && $ayuda->PresupuestoMax !== null){
                            $currentayuda->PresupuestoMax = $ayuda->PresupuestoMax;    
                        }
                        if($currentayuda->PresupuestoMax !== null && ($ayuda->PresupuestoMax === null || $ayuda->PresupuestoMax == 0)){
                            $ayuda->PresupuestoMax = $currentayuda->PresupuestoMax;    
                        }
                        if(($currentayuda->PresupuestoMin === null || $currentayuda->PresupuestoMin == 0) && $ayuda->PresupuestoMin !== null){
                            $currentayuda->PresupuestoMin = $ayuda->PresupuestoMin;    
                        }
                        if($currentayuda->PresupuestoMin !== null && ($ayuda->PresupuestoMin === null || $ayuda->PresupuestoMin == 0)){
                            $ayuda->PresupuestoMin = $currentayuda->PresupuestoMin;    
                        }
    
                        $maxpresupuestos = [$currentayuda->PresupuestoMax, $ayuda->PresupuestoMax];
                        $minpresupuestos = [$currentayuda->PresupuestoMin, $ayuda->PresupuestoMin];
                        $checkmaxdistintosvalores = (count(array_unique($maxpresupuestos, SORT_REGULAR)) === 1);
                        $checkmindistintosvalores = (count(array_unique($minpresupuestos, SORT_REGULAR)) === 1);
    
                        ###Revisamos que los valores en los dos arrays no son iguales sin son iguales entonces es un match de ayuda parecida
                        if($checkmaxdistintosvalores === true && $checkmindistintosvalores === true){
                            $score += 2;
                        }
                        if($checkmaxdistintosvalores === false || $checkmindistintosvalores === false){
    
                            $maxpresupuesto = max($maxpresupuestos);
                            $minpresupuesto = min($minpresupuestos);
                            $rango = $maxpresupuesto-$minpresupuesto;
                            $maxminpresupuesto = min($maxpresupuestos);
                            $minmaxpresupuesto = max($minpresupuestos);
                            $cruce = $minmaxpresupuesto-$maxminpresupuesto;
                        }
    
                        if($currentayuda->es_europea == 0){
                            if(($currentayuda->FechaMinConstitucion === null && $currentayuda->MesesMin === null) && ($ayuda->FechaMinConstitucion !== null || $ayuda->MesesMin !== null)){
                                $this->info($ayuda->Acronimo.': no tienen fecha min de constitucion la convocatoria comparada continue 4');
                                continue;
                            }                            
                        }
    
                        #Aumento de score por fecha maxima o meses maximos
                        if($currentayuda->FechaMaxConstitucion !== null && $ayuda->FechaMaxConstitucion !== null){
                            $score += 2;
                        }
    
                        if($currentayuda->Meses !== null && $ayuda->Meses !== null){
                            $score += 2;
                        }
    
                        #relleno objeto con ids de ayudas parecidas
                        array_push($ayudasparecidas, ['id' => $ayuda->id, 'score' => $score]);
                    }
                }

                #guardamos los valores en el campo nuevo 
                try{
                    \App\Models\AyudasRelacionadas::where('ayuda_id', $currentayuda->id)->delete();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return COMMAND::FAILURE;
                }

                if(!empty($ayudasparecidas)){

                    foreach($ayudasparecidas as $ayuda){

                        $ayudarelacionada = \App\Models\AyudasRelacionadas::where('ayuda_id', $currentayuda->id)->where('ayuda_id_relacionada', $ayuda['id'])->first();

                        if(!$ayudarelacionada){
                            $ayudarelacionada = new \App\Models\AyudasRelacionadas();
                        }
                                                
                        try{
                            $ayudarelacionada->ayuda_id = $currentayuda->id;
                            $ayudarelacionada->ayuda_id_relacionada = $ayuda['id'];
                            $ayudarelacionada->score = $ayuda['score'];
                            $ayudarelacionada->save();
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                            return COMMAND::FAILURE;
                        }
                        
                    }
                } 
                  
            }     
        }else{

            if(!isset($id) || $id == ""){
                return COMMAND::FAILURE;
            }

            $currentayuda = \App\Models\Ayudas::find($id);

            if(!$currentayuda || $currentayuda === null){
                return COMMAND::FAILURE;
            }

            $ayudas = \App\Models\Ayudas::where('Estado', '!=', 'Cerrada')->where('Publicada', 1)
            ->where('Presentacion', $currentayuda->Presentacion)
            ->get();

            $ayudasparecidas = array();

            if($ayudas->isNotEmpty()){
                foreach($ayudas as $ayuda){

                    if($ayuda->id == $currentayuda->id){
                        //$this->info($ayuda->Acronimo.': mismo id continue');
                        continue;
                    }

                    $score = 0;
                    $score = $this->getParecidasScore($currentayuda, $ayuda, $score);

                    if($score !== false){
                        #relleno objeto con ids de ayudas parecidas
                        array_push($ayudasparecidas, ['id' => $ayuda->id, 'score' => $score]);
                    }else{
                        continue;
                    }

                }
            }

            #guardamos los valores en el campo nuevo 
            try{
                \App\Models\AyudasRelacionadas::where('ayuda_id', $currentayuda->id)->delete();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return COMMAND::FAILURE;
            }

            if(!empty($ayudasparecidas)){

                foreach($ayudasparecidas as $ayuda){

                    $ayudarelacionada = \App\Models\AyudasRelacionadas::where('ayuda_id', $currentayuda->id)->where('ayuda_id_relacionada', $ayuda['id'])->first();

                    if(!$ayudarelacionada){
                        $ayudarelacionada = new \App\Models\AyudasRelacionadas();
                    }
                                        
                    try{
                        $ayudarelacionada->ayuda_id = $currentayuda->id;
                        $ayudarelacionada->ayuda_id_relacionada = $ayuda['id'];
                        $ayudarelacionada->score = $ayuda['score'];
                        $ayudarelacionada->save();
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        return COMMAND::FAILURE;
                    }
                    
                }
            }
        }

        return COMMAND::SUCCESS;
    }

    function getParecidasScore($currentayuda, $ayuda, $score){

        if(str_contains($ayuda->objetivoFinanciacion, $currentayuda->objetivoFinanciacion) === false){
            //$this->info($ayuda->Acronimo.': no coincide objetivo de financiacion');
            return false;
        }

        if($ayuda->TematicaObligatoria == 1 && $currentayuda->TematicaObligatoria == 1){
            //$this->info($ayuda->Acronimo.': si tematica recisamos intereses');
            $currentintereses = json_decode($currentayuda->PerfilFinanciacion);
            $intereses = json_decode($ayuda->PerfilFinanciacion);
            if(is_array($currentintereses) && is_array($intereses)){
                $idinnovacurrent = array_search("231435000088214861", $currentintereses);
                if($idinnovacurrent === false){
                    $idinnovacurrent = array_search("231435000088214865", $currentintereses);
                }

                $idinnovaayuda = array_search("231435000088214861", $intereses);
                if($idinnovaayuda === false){
                    $idinnovaayuda = array_search("231435000088214865", $intereses);
                }

                if($idinnovacurrent === false && $idinnovaayuda === false){
                    //$this->info($ayuda->Acronimo.': no match por I+d e Innovacion');                          
                    $checkintereses = array_intersect($currentintereses, $intereses);                        
                    if(empty($checkintereses)){                        
                        //$this->info($ayuda->Acronimo.': no coinciden intereese continue');                    
                        return false;
                    }else{
                        $score++;
                    }                        
                }

                if($idinnovacurrent !== false && $idinnovaayuda === false){
                    $checkintereses = array_intersect($currentintereses, $intereses);                       
                    if(empty($checkintereses)){                        
                        //$this->info($ayuda->Acronimo.': no coinciden intereese continue');                    
                        return false;
                    }else{
                        $score++;
                    }                  
                }                        
                if(array_search("231435000088214861", $intereses) !== false && array_search("231435000088214861", $intereses) !== false){
                    $score++;
                }else{
                    if($idinnovacurrent !== false && $idinnovaayuda !== false){
                        $score--;
                    }
                }
            }else{
                //$this->info($ayuda->Acronimo.': no coinciden areas continue');                    
                return false;
            }                            
        }elseif($ayuda->TematicaObligatoria == 1 && $currentayuda->TematicaObligatoria == 0){
            //$this->info($ayuda->Acronimo.': no coinciden tematica obligatoria continue');                    
            return false;
        }

        #Check Intereses
        $currentintereses = json_decode($currentayuda->PerfilFinanciacion);
        $intereses = json_decode($ayuda->PerfilFinanciacion);
        if(is_array($currentintereses) && is_array($intereses)){

            $idinnovacurrent = array_search("231435000088214861", $currentintereses);
            if($idinnovacurrent === false){
                $idinnovacurrent = array_search("231435000088214865", $currentintereses);
            }

            $idinnovaayuda = array_search("231435000088214861", $intereses);
            if($idinnovaayuda === false){
                $idinnovaayuda = array_search("231435000088214865", $intereses);
            }

            if($idinnovacurrent === false && $idinnovaayuda === false){
                //$this->info($ayuda->Acronimo.': no match por I+d e Innovacion');                          
                $checkintereses = array_intersect($currentintereses, $intereses);                        
                if(empty($checkintereses)){                        
                    //$this->info($ayuda->Acronimo.': no coinciden intereese continue');                    
                    return false;
                }else{
                    $score++;
                }                        
            }

            if($idinnovacurrent !== false && $idinnovaayuda === false){
                $checkintereses = array_intersect($currentintereses, $intereses);                       
                if(empty($checkintereses)){                        
                    //$this->info($ayuda->Acronimo.': no coinciden intereese continue');                    
                    return false;
                }else{
                    $score++;
                }                  
            }           
            
            if(array_search("231435000088214861", $intereses) !== false && array_search("231435000088214861", $currentintereses) !== false){
                $score++;
            }else{
                if($idinnovacurrent !== false && $idinnovaayuda !== false){
                    $score--;
                }
            }
            if($idinnovacurrent === false && $idinnovaayuda !== false){
                $checkintereses = array_intersect($currentintereses, $intereses);                       
                if(empty($checkintereses)){                        
                    //$this->info($ayuda->Acronimo.': no coinciden intereese continue');                    
                    return false;
                }else{
                    $score++;
                }                  
            }
        }else{
            //$this->info($ayuda->Acronimo.': no coinciden intereses continue');                    
            return false;
        }   

        ###Check ayudas json areas 
        ### - si ayudas estan abiertas o proximamente
        ### - si intersect areas count areas intersect y sumar score
        if(($currentayuda->Estado == "Abierta" || $currentayuda->Estado == "Próximamente") &&
        ($ayuda->Estado == "Abierta" || $ayuda->Estado == "Próximamente")){

            if($currentayuda->keywords !== null && isset($currentayuda->keywords->keywords) && $currentayuda->keywords->keywords !== null
             && $ayuda->keywords !== null && isset($ayuda->keywords->keywords) && $ayuda->keywords->keywords !== null){
                $currentareas = json_decode($currentayuda->keywords->keywords, true);
                $areas = json_decode($ayuda->keywords->keywords, true);
                if(is_array($currentareas) && is_array($areas) && $currentareas['status'] == "OK" && $areas['status'] == "OK"){
                    $checkareas = array_intersect($currentareas['areas'], $areas['areas']);
                    if(!empty($checkareas)){         
                        $score += count($checkareas)+1;
                    }else{
                        
                    }                    
                    $checkkeywords = array_intersect($currentareas['keywords'], $areas['keywords']);
                    if(empty($checkareas) && empty($checkkeywords)){         
                        //$this->info($ayuda->Acronimo.': no coinciden areas continue');                               
                        return false;                                    
                    }else{
                        $score += count($checkkeywords)+1;
                    }                    
                }else{
                    //$this->info($ayuda->Acronimo.': no coinciden areas continue');                               
                    return false;
                }
            }
        }

        #Check CCAAs
        if($currentayuda->Ambito == "Europea" && ($ayuda->Ambito == "Comunidad Autónoma" || $ayuda->Ambito == "Nacional")){
            #Do nothing
        }
        if($currentayuda->Ambito == "Nacional" && $ayuda->Ambito == "Comunidad Autónoma"){
            //$this->info($ayuda->Acronimo.': no coinciden ambitos nacional continue');                               
            return false;
        }
        if($currentayuda->Ambito == "Comunidad Autónoma" && $ayuda->Ambito == "Comunidad Autónoma"){
            $currentccaas = json_decode($currentayuda->Ccaas);
            $ccaas = json_decode($ayuda->Ccaas);
            if(is_array($currentccaas) && is_array($ccaas)){
                $checkccaas = array_intersect($currentccaas, $ccaas);
                if(empty($checkccaas)){
                    //$this->info($ayuda->Acronimo.': no coinciden CCAAs continue');
                    return false;
                }else{
                    $score += count($checkccaas);
                }
            }else{
                return false;
            }
        }

        #Check Intensidad
        $minintensidad = $ayuda->Intensidad-1;
        $maxintensidad = $ayuda->Intensidad+1;
        if($currentayuda->Intensidad < $minintensidad){                       
           $score -= 2;                
        }

        #Aumento de score por intensidad
        if($currentayuda->Intensidad >= $maxintensidad){  
            $score++;
        }

        #Check presupuesto
        if(($currentayuda->PresupuestoMax === null || $currentayuda->PresupuestoMax == 0) && $ayuda->PresupuestoMax !== null){
            $currentayuda->PresupuestoMax = $ayuda->PresupuestoMax;    
        }
        if($currentayuda->PresupuestoMax !== null && ($ayuda->PresupuestoMax === null || $ayuda->PresupuestoMax == 0)){
            $ayuda->PresupuestoMax = $currentayuda->PresupuestoMax;    
        }
        if(($currentayuda->PresupuestoMin === null || $currentayuda->PresupuestoMin == 0) && $ayuda->PresupuestoMin !== null){
            $currentayuda->PresupuestoMin = $ayuda->PresupuestoMin;    
        }
        if($currentayuda->PresupuestoMin !== null && ($ayuda->PresupuestoMin === null || $ayuda->PresupuestoMin == 0)){
            $ayuda->PresupuestoMin = $currentayuda->PresupuestoMin;    
        }

        $maxpresupuestos = [$currentayuda->PresupuestoMax, $ayuda->PresupuestoMax];
        $minpresupuestos = [$currentayuda->PresupuestoMin, $ayuda->PresupuestoMin];
        $checkmaxdistintosvalores = (count(array_unique($maxpresupuestos, SORT_REGULAR)) === 1);
        $checkmindistintosvalores = (count(array_unique($minpresupuestos, SORT_REGULAR)) === 1);

        ###Revisamos que los valores en los dos arrays no son iguales sin son iguales entonces es un match de ayuda parecida
        if($checkmaxdistintosvalores === true && $checkmindistintosvalores === true){
            $score += 2;
        }
        if($checkmaxdistintosvalores === false || $checkmindistintosvalores === false){

            $maxpresupuesto = max($maxpresupuestos);
            $minpresupuesto = min($minpresupuestos);
            $rango = $maxpresupuesto-$minpresupuesto;
            $maxminpresupuesto = min($maxpresupuestos);
            $minmaxpresupuesto = max($minpresupuestos);
            $cruce = $minmaxpresupuesto-$maxminpresupuesto;

            if($cruce == 0){
                //$this->info($ayuda->Acronimo.': no coinciden en cruce de presupuesto continue');
                return false;
            }
            
            if($cruce > 0 && $rango > 0){
                $result = $cruce/$rango;
                if($result < 0.6){
                    //$this->info($ayuda->Acronimo.': no coinciden en cruce/rango continue');
                    return false;
                }
            }
            #Aumento de score por presupuesto
            if($cruce > 0 || $cruce < 0){
                $score++;
            }
        }

        if($currentayuda->es_europea == 0){
            if(($currentayuda->FechaMinConstitucion === null || $currentayuda->MesesMin === null) && ($ayuda->FechaMinConstitucion !== null || $ayuda->MesesMin !== null)){
                //$this->info($ayuda->Acronimo.': no tienen fecha min de constitucion la convocatoria comparada continue 4');
                return false;
            }                                   
        }

        #Aumento de score por fecha maxima o meses maximos
        if($currentayuda->FechaMaxConstitucion !== null && $ayuda->FechaMaxConstitucion !== null){
            $score += 2;
        }

        if($currentayuda->Meses !== null && $ayuda->Meses !== null){
            $score += 2;
        }

        return $score;
    }
}
