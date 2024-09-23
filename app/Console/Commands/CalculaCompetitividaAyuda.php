<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculaCompetitividaAyuda extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calcula:competitividad {id} {new}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcula la competitividad de una ayuda y guarda el resultado en 3 variables diferentes: 
        
        1. Ayuda europea
        2. Ayuda nacional con convocatorias cerradas anterior Y presupuesto total de la convocatoria>0
            a. No tiene un comino: solo el presupuesto de la convocatoria y el presupuesto minimo por proyecto. Ambos >0.    
            b. Tiene concesiones concedidas pero no rechazadas vinculadas a la ayuda   
            c. Tiene concesiones concedidas y rechazadas.
    
        CASO 1:  directamente lo cogemos de la variable “expectedGrants”. Nada mas.
        CASO 2 a:  Divides el Presupuesto Total de la convocatoria / Presupuesto minimo de proyecto = > Esto te da el número de proyectos que pueden ser financiados.
        CASO 2 b:  Aplicas CASO 2 a y Calculas el número de concesiones concedidas de la convocatoria anterior.
        CASO 2 c: Aplicas lo mismo que en el caso 2a + 2b  pero además de la última convocatoria calculas el número de Propuestas aceptadas en porcentaje con las (Aceptadas + Rechazadas).';

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
        $new = $this->argument('new');

        if(isset($id) && $id != "" && $id !== null){
            $convocatoria = \App\Models\Ayudas::find($id);

            if($convocatoria->es_europea == 1){
                $indice = null;
                $expectedGrants = null;
                if($convocatoria->rawdataEU !== null && $convocatoria->rawdataEU->budgetTopicActionMap !== null){
                    foreach(json_decode($convocatoria->rawdataEU->budgetTopicActionMap, true) as $key => $value){
                        if(strripos($value[0]['action'],$convocatoria->rawdataEU->identifier) !== false){
                            $currentconvocatoria[$key] = [];
                            $indice = $key;
                            break;
                        }
                    }
                    if($convocatoria->rawdataEU->expectedGrants !== null && $indice !== null){
                        $array = json_decode($convocatoria->rawdataEU->expectedGrants, true);
                        $expectedGrants = (isset($array[$indice][0])) ? $array[$indice][0] : null;
                    }
                }

                if($expectedGrants !== null){
                    $this->saveConvocatoria($convocatoria, "expectedGrants", $expectedGrants);
                }else{
                    $this->saveConvocatoria($convocatoria, "expectedGrants", $expectedGrants);
                }
            }else{
                $expectedGrants = null;

                if($convocatoria->Presupuesto !== null && $convocatoria->Presupuesto > 0 && $convocatoria->PresupuestoMin !== null && $convocatoria->PresupuestoMin > 0){                    
                    $expectedGrants = (int) round($convocatoria->Presupuesto/$convocatoria->PresupuestoMin,0,PHP_ROUND_HALF_UP);                 
                }
                if($expectedGrants === null){
                    if($convocatoria->Presupuesto !== null && $convocatoria->Presupuesto > 0 && $convocatoria->PresupuestoMax !== null && $convocatoria->PresupuestoMax > 0){                    
                        $expectedGrants = (int) round($convocatoria->Presupuesto/$convocatoria->PresupuestoMax,0,PHP_ROUND_HALF_UP);                 
                    }   
                }
                if($expectedGrants !== null){
                    $this->saveConvocatoria($convocatoria, "expectedGrants", $expectedGrants);
                }else{
                    $this->saveConvocatoria($convocatoria, "expectedGrants", $expectedGrants);
                }

                if($convocatoria->convocatoria->es_indefinida == 0){

                    $grantsLastConv = null;
                    $successPercent = null;
                    if($convocatoria->proyectos !== null && $expectedGrants !== null){

                        $proyectosfinanciados = $convocatoria->proyectos->where('Estado', 'Cerrado');
                        $proyectosrechazados = $convocatoria->proyectos->where('Estado', 'Desestimado');

                        if($proyectosfinanciados->count() > 0){
                            $grantsLastConv = $proyectosfinanciados->count();
                            if($grantsLastConv !== null){
                                $this->saveConvocatoria($convocatoria, "grantsLastConv", $grantsLastConv);
                            }
                        }
                        if($proyectosfinanciados->count() > 0 && $proyectosrechazados->count() > 0){
                            $total = ($proyectosfinanciados->count() + $proyectosrechazados->count())/100;
                            $successPercent = round($proyectosfinanciados->count()/$total, 2, PHP_ROUND_HALF_UP);
                            if($successPercent !== null){
                                $this->saveConvocatoria($convocatoria, "successPercent", $successPercent);
                            }
                        }
                    }
                }else{
                    $this->saveConvocatoria($convocatoria, "grantsLastConv", null);
                    $this->saveConvocatoria($convocatoria, "successPercent", null);
                }

            }

        }
        if($id == "" && isset($new) && $new != "" && $new !== null && $new == "new"){

            $convocatorias = \App\Models\Ayudas::where('updated_at', '>=', Carbon::now()->subDays(1))->get();

            foreach($convocatorias as $convocatoria){

                if($convocatoria->es_europea == 1){
                    $indice = null;
                    $expectedGrants = null;
                    if($convocatoria->rawdataEU !== null && $convocatoria->rawdataEU->budgetTopicActionMap !== null){
                        foreach(json_decode($convocatoria->rawdataEU->budgetTopicActionMap, true) as $key => $value){
                            if(strripos($value[0]['action'],$convocatoria->rawdataEU->identifier) !== false){
                                $currentconvocatoria[$key] = [];
                                $indice = $key;
                                break;
                            }
                        }
                        if($convocatoria->rawdataEU->expectedGrants !== null && $indice !== null){
                            $array = json_decode($convocatoria->rawdataEU->expectedGrants, true);
                            $expectedGrants = (isset($array[$indice][0])) ? $array[$indice][0] : null;
                        }
                    }
    
                    if($expectedGrants !== null){
                        $this->saveConvocatoria($convocatoria, "expectedGrants", $expectedGrants);
                    }else{
                        $this->saveConvocatoria($convocatoria, "expectedGrants", $expectedGrants);
                    }
                }else{

                    $expectedGrants = null;
                    if($convocatoria->Presupuesto !== null && $convocatoria->Presupuesto > 0 && $convocatoria->PresupuestoMin !== null && $convocatoria->PresupuestoMin >= 10000 && $convocatoria->update_extinguida_ayuda == 0){                    
                        $expectedGrants = (int) round($convocatoria->Presupuesto/$convocatoria->PresupuestoMin,0,PHP_ROUND_HALF_UP);                 
                    }
                    if($expectedGrants === null){
                        if($convocatoria->Presupuesto !== null && $convocatoria->Presupuesto > 0 && $convocatoria->PresupuestoMax !== null && $convocatoria->PresupuestoMax > 0){                    
                            $expectedGrants = (int) round($convocatoria->Presupuesto/$convocatoria->PresupuestoMax,0,PHP_ROUND_HALF_UP);                 
                        }   
                    }
                    if($expectedGrants !== null){
                        $this->saveConvocatoria($convocatoria, "expectedGrants", $expectedGrants);
                    }else{
                        $this->saveConvocatoria($convocatoria, "expectedGrants", $expectedGrants);
                    }

                    if($convocatoria->convocatoria->es_indefinida == 0){                  
                                        
                        if($convocatoria->IdConvocatoriaStr != "" && $convocatoria->Fin !== null && $convocatoria->Organismo !== null){
                            $idstring = substr($convocatoria->IdConvocatoriaStr, 0, strripos($convocatoria->IdConvocatoriaStr,"#")-2);
                            $convocatoriaAnterior = \App\Models\Ayudas::where('Organismo', $convocatoria->Organismo)->where('id', '!=',  $convocatoria->id)
                            ->where('Presupuesto', '>', 0)->where('Estado','Cerrada')->whereNotNull('Fin')
                            ->where('Fin', '<', $convocatoria->Fin)->where('IdConvocatoriaStr', 'LIKE', '%'.$idstring.'%')
                            ->orderByDesc('Fin')->first();
        
                            $grantsLastConv = null;
                            $successPercent = null;
        
                            if($convocatoriaAnterior !== null && $convocatoriaAnterior->proyectos !== null){
        
                                $proyectosfinanciados = $convocatoriaAnterior->proyectos->where('Estado', 'Cerrado');
                                $proyectosrechazados = $convocatoriaAnterior->proyectos->where('Estado', 'Desestimado');
        
                                if($proyectosfinanciados->count() > 0){
                                    $grantsLastConv = $proyectosfinanciados->count();
                                    if($grantsLastConv !== null){
                                        $this->saveConvocatoria($convocatoria, "grantsLastConv", $grantsLastConv);
                                    }else{
                                        $this->saveConvocatoria($convocatoria, "grantsLastConv", $grantsLastConv);
                                    }
                                }
                                if($proyectosfinanciados->count() > 0 && $proyectosrechazados->count() > 0){
                                    $total = ($proyectosfinanciados->count() + $proyectosrechazados->count())/100;
                                    $successPercent = round($proyectosfinanciados->count()/$total, 2, PHP_ROUND_HALF_UP);
                                    if($successPercent !== null){
                                        $this->saveConvocatoria($convocatoria, "successPercent", $successPercent);
                                    }else{
                                        $this->saveConvocatoria($convocatoria, "successPercent", $successPercent);
                                    }
                                }
                            }
                        }
                    }else{
                        $this->saveConvocatoria($convocatoria, "grantsLastConv", null);
                        $this->saveConvocatoria($convocatoria, "successPercent", null);
                    }
                }

            }

        }

        return COMMAND::SUCCESS;
    }

    function saveConvocatoria($convocatoria, $type, $value){

        if($type == "expectedGrants"){
            try{    
                $convocatoria->NumeroConcesionesEsperadas = $value;
                $convocatoria->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return COMMAND::FAILURE;
            }
        }

        if($type == "grantsLastConv"){
            try{    
                $convocatoria->NumeroConcesionesConvocatoriaAnterior = $value;
                $convocatoria->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return COMMAND::FAILURE;
            }
        }

        if($type == "successPercent"){
            try{    
                $convocatoria->PorcentajeExitoConvocatoria = $value;
                $convocatoria->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return COMMAND::FAILURE;
            }
        }
    }
}
