<?php

namespace App\Console\Commands;

use Brick\Math\BigNumber;
use Carbon\Carbon;
use Doctrine\DBAL\Types\BigIntType;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MoveConvocatoriasEuRawData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'move:convocatorias_eu_innovating';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mueve las convocatorias EU de tabla raw_data a la de ayudas y convocatorias_ayudas de innovating';

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

        $this->info(now());

        $convocatoriaseu = \App\Models\ConvocatoriasEURawData::whereNotNull('status')->whereNotNull('callIdentifier')->whereNotNull('callTitle')->get();
        //$convocatoriaseu = \App\Models\ConvocatoriasEURawData::where('id', 465)->get();

        foreach($convocatoriaseu as $conv){

            $ayuda = \App\Models\Convocatorias::where('acronimo', $conv->callIdentifier)->where('titulo', $conv->callTitle)->first();

            if(!$ayuda){
                $ayuda = new \App\Models\Convocatorias();
                try{
                    $ayuda->acronimo = $conv->callIdentifier;
                    $ayuda->titulo = $conv->callTitle;
                    $ayuda->es_indefinida = 1;
                    $ayuda->extinguida = 0;
                    $ayuda->save();
                }catch(Exception $e){
                    dd($e->getMessage());
                }
            }                

            $status = null;
            if($conv->Status == "Open") {
                $status = "Abierta";
            }elseif($conv->status == "Closed"){
                $status = "Cerrada";
            }elseif($conv->status == "Forthcoming"){ 
                $status = "Próximamente";
            }

            if($status === null){

                try{
                    if($conv->endDate !== null && Carbon::parse($conv->endDate) >= Carbon::now() && $conv->startDate !== null && Carbon::parse($conv->startDate) <= Carbon::now()){
                        $status = "Abierta";
                    }elseif($conv->endDate !== null && Carbon::now() > Carbon::parse($conv->endDate)){
                        $status = "Cerrada";
                    }else{
                        $status = "Próximamente";
                    }
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    $status = "Cerrada";
                }
            }
            
            $convocatoria = \App\Models\Ayudas::where('acronimo', $conv->identifier)->where('es_europea', 1)
            ->where('id_raw_data', $conv->id)->first();

            if($conv->identifier !== null){
            
                if(!$convocatoria){

                    $presupuesto = null;
                    if(isset($conv->budgetYearMap)){
                        $year = Carbon::now()->format('Y');
                        $bugetArray = (json_decode($conv->budgetYearMap, true));
                        if(isset($bugetArray[$year])){
                            $presupuesto = $bugetArray[$year];
                        }
                    }
                    $convocatoria = new \App\Models\Ayudas();
                    $shortDescription = substr($conv->description_html,0, strpos($conv->description_html,"</br>"));

                    if($conv !== null){
                        $indice = null;
                        if($conv->budgetTopicActionMap !== null){
                            foreach(json_decode($conv->budgetTopicActionMap, true) as $key => $value){
                                if(!isset($value[0]) || strripos($value[0]['action'],$conv->identifier) !== false){
                                    $indice = $key;
                                    break;
                                }
                            }
                        }
                        if($indice !== null){
                            if($conv->minContribution !== null){
                                $array = json_decode($conv->minContribution, true);
                                $convocatoria->FondoPerdidoMinimoNominal = (isset($array[$indice][0])) ? $array[$indice][0] : null;
                            }
                            if($conv->maxContribution !== null){
                                $array = json_decode($conv->maxContribution, true);
                                $convocatoria->FondoPerdidoMaximoNominal = (isset($array[$indice][0])) ? $array[$indice][0] : null;
                            }    
                        }
                    }   

                    if($conv->typeOfAction !== null){
                        $typeOfAction = \App\Models\TypeOfActions::where('nombre', $conv->typeOfAction)->first();                       
                    }

                    try{                    
                        $convocatoria->id_ayuda = $ayuda->id;
                        $convocatoria->IdConvocatoriaStr = $conv->identifier;
                        $convocatoria->Acronimo = $conv->identifier;
                        $convocatoria->Titulo = $conv->title;
                        #$convocatoria->Presentacion = "";
                        $convocatoria->Link = "https://ec.europa.eu/info/funding-tenders/opportunities/portal/screen/opportunities/topic-details/".mb_strtolower($conv->identifier)."?keywords=".$conv->identifier;
                        $convocatoria->Organismo = $conv->organismo;
                        #$convocatoria->FondosEuropeos = "";
                        $convocatoria->PerfilFinanciacion = '["231435000088214861"]';
                        #$convocatoria->FechaMax = "";
                        #$convocatoria->Meses = "";
                        #$convocatoria->MesesMin = "";
                        #$convocatoria->FechaMaxConstitucion = "";
                        #$convocatoria->FechaMinConstitucion = "";
                        $convocatoria->Categoria = '["Micro","Pequeña","Mediana","Grande"]'; // DEFAULT TODOS
                        $convocatoria->naturalezaConvocatoria = '["6668837","6668838","6668839","6668840","6668841","6668842","6668843"]'; // DEFAULT TODOS
                        #$convocatoria->tiposObligatorios = "";
                        $convocatoria->Presupuesto = $presupuesto;
                        #$convocatoria->PresupuestoConsorcio = "";
                        $convocatoria->Ambito = "Europeo";
                        $convocatoria->OpcionCNAE = "Todos"; // DEFAULT TODOS
                        #$convocatoria->CNAES = "";
                        $convocatoria->DescripcionCorta = $shortDescription;
                        $convocatoria->DescripcionLarga = $conv->description_html;
                        #$convocatoria->RequisitosTecnicos = "";
                        #$convocatoria->RequisitosParticipante = "";
                        #$convocatoria->Convocatoria = "";
                        #$convocatoria->maxEmpleados = "";
                        #$convocatoria->minEmpleados = "";
                        if(is_numeric($conv->startDate) && stripos($conv->startDate, " ") === false){
                            $convocatoria->Inicio = Carbon::createFromTimestampMs($conv->startDate);
                        }else{
                            $convocatoria->Inicio = Carbon::parse($conv->startDate);
                        }
                        if($conv->endDate !== null){
                            if(is_numeric($conv->endDate) && stripos($conv->endDate, " ") === false){
                                $convocatoria->Fin = Carbon::createFromTimestampMs($conv->endDate);
                            }else{
                                $convocatoria->Fin = Carbon::parse($conv->endDate);
                            }
                        }elseif($conv->deadlineDate !== null){
                            if(is_numeric(json_decode($conv->deadlineDate)[0]) && stripos(json_decode($conv->deadlineDate)[0], " ") === false){
                                $convocatoria->Fin  = Carbon::createFromTimestampMs(json_decode($conv->deadlineDate)[0]);
                            }else{
                                $convocatoria->Fin  = Carbon::parse(json_decode($conv->deadlineDate)[0]);
                            }
                        }elseif($conv->deadlineDates !== null){
                            $dates = json_decode($conv->deadlineDates, true);
                            if(!empty($dates)){
                                $key = array_key_first($dates);
                                if(isset($dates[$key]) && isset($dates[$key][0]) && isset($dates[$key][0][0])){
                                    $convocatoria->Fin  = Carbon::createFromFormat("d F Y", $dates[$key][0][0]);
                                }
                            }
                        }
                        $convocatoria->Estado = $status;
                        $convocatoria->InformacionDefinitiva = 0;
                        $convocatoria->Competitiva = "Muy Competitiva";
                        $convocatoria->Uri = str_replace(" ","-", preg_replace("/[^a-zA-Z0-9\-\s]/", "", seo_quitar_tildes(mb_strtolower(trim($conv->title)))));
                        #$convocatoria->Ccaas = "";
                        $convocatoria->Featured = 0;
                        $convocatoria->TipoFinanciacion = '["Fondo perdido"]';
                        $convocatoria->CapitulosFinanciacion = '["Personal", "Materiales", "Subcontrataciones", "Amortizaciones", "Activos", "Otros gastos"]';
                        $convocatoria->CentroTecnologico = 0;
                        $convocatoria->CondicionesFinanciacion = null;
                        #$convocatoria->PresupuestoMin = "";
                        #$convocatoria->PresupuestoMax = "";
                        #$convocatoria->DuracionMin = "";
                        #$convocatoria->DuracionMax = "";
                        #$convocatoria->Garantias = "";
                        #$convocatoria->IDInnovating = "";
                        $convocatoria->Trl = 5;
                        $convocatoria->objetivoFinanciacion = "Proyectos";
                        $convocatoria->PorcentajeFondoPerdido = 100;
                        $convocatoria->PorcentajeCreditoMax = 0;
                        #$convocatoria->DeduccionMax = "";
                        #$convocatoria->NivelCompetitivo = "";
                        $convocatoria->TiempoMedioResolucion = 6;
                        $convocatoria->SelloPyme = 1;
                        #$convocatoria->EmpresaCrisis = "";
                        #$convocatoria->InformeMotivado = "";
                        #$convocatoria->TextoCondiciones = null;

                        $convocatoria->FondoTramo = "fondo";
                        $convocatoria->LastEditor = "system";
                        $convocatoria->Publicada = 0;
                        #$convocatoria->AplicacionIntereses = "";
                        #$convocatoria->PorcentajeIntereses = "";
                        #$convocatoria->MesesCarencia = "";
                        #$convocatoria->AnosAmortizacion = "";
                        $convocatoria->EfectoIncentivador = 1;
                        $convocatoria->Minimis = 0;
                        #$convocatoria->CondicionesEspeciales = "";
                        $convocatoria->TematicaObligatoria = 1;
                        #$convocatoria->instrucciones2 = "";
                        #$convocatoria->pregunta2 = "";
                        #$convocatoria->instrucciones1 = "";
                        #$convocatoria->pregunta1 = "";
                        #$convocatoria->Analisis = "";
                        $convocatoria->Intensidad = "4";
                        #$convocatoria->Dnsh = "";
                        #$convocatoria->MensajeDnsh = "";
                        #$convocatoria->esDeGenero = "";
                        #$convocatoria->textoGenero = "";
                        $convocatoria->datosMetricas = null;
                        $convocatoria->esMetricable = 0;
                        $convocatoria->update_extinguida_ayuda = "2";
                        $convocatoria->es_europea = 1;
                        $convocatoria->id_raw_data = $conv->id;
                        if($typeOfAction){
                            ###Forzamos a publicada cuando una ayuda europea tiene un type of action peticion Claudio jira IW-453
                            $convocatoria->Publicada = $typeOfAction->publicar_ayudas;
                            $convocatoria->type_of_action_id = $typeOfAction->id;                        
                            $convocatoria->Trl = $typeOfAction->trl;
                            $convocatoria->objetivoFinanciacion = $typeOfAction->objetivo_financiacion;
                            $convocatoria->CapitulosFinanciacion = $typeOfAction->capitulos_financiacion;
                            if($typeOfAction->fondo_perdido_maximo != $typeOfAction->fondo_perdido_minimo){
                                $convocatoria->PorcentajeFondoPerdido = $typeOfAction->fondo_perdido_maximo;
                                $convocatoria->FondoPerdidoMinimo = $typeOfAction->fondo_perdido_minimo;
                            }else{
                                $convocatoria->PorcentajeFondoPerdido = $typeOfAction->fondo_perdido_minimo;
                            }
                            $convocatoria->TipoFinanciacion = $typeOfAction->tipo_financiacion;  
                            $convocatoria->Categoria = $typeOfAction->categoria;
                            $convocatoria->naturalezaConvocatoria = $typeOfAction->naturaleza;
                            $convocatoria->PerfilFinanciacion = ($typeOfAction->perfil_financiacion === null) ? '["231435000088214861"]' : $typeOfAction->perfil_financiacion; 
                            $convocatoria->Presentacion = json_decode($typeOfAction->presentacion)[0]; 

                            $convocatoria->TextoConsorcio = ($typeOfAction->texto_consorcio === null) ? null : $typeOfAction->texto_consorcio;
                            $convocatoria->CondicionesFinanciacion = ($typeOfAction->condiciones_financiacion === null) ? null : $typeOfAction->condiciones_financiacion;
                            if($convocatoria->FondoPerdidoMaximoNominal !== null){
                                $convocatoria->PresupuestoMax = $convocatoria->FondoPerdidoMaximoNominal*($typeOfAction->fondo_perdido_maximo/100);
                            }
                            if($convocatoria->FondoPerdidoMinimoNominal !== null){
                                $convocatoria->PresupuestoMin = $convocatoria->FondoPerdidoMinimoNominal*($typeOfAction->fondo_perdido_minimo/100);
                                $convocatoria->FondoPerdidoMinimoNominal = $convocatoria->FondoPerdidoMinimoNominal*($typeOfAction->fondo_perdido_minimo/100);
                            }
                        }else{
                            $convocatoria->Trl = 5;
                            $convocatoria->objetivoFinanciacion = "Proyectos";
                            $convocatoria->PorcentajeFondoPerdido = 100;  
                            $convocatoria->TipoFinanciacion = '["Fondo perdido"]';
                            $convocatoria->Categoria = '["Micro","Pequeña","Mediana","Grande"]'; // DEFAULT TODOS
                            $convocatoria->naturalezaConvocatoria = '["6668837","6668838","6668839","6668840","6668841","6668842","6668843"]'; // DEFAULT TODOS         
                            $convocatoria->PerfilFinanciacion = '["231435000088214861"]'; 
                            $convocatoria->Presentacion = "Consorcio";       
                            $convocatoria->CondicionesFinanciacion = strip_tags($conv->conditions);
                        }
                        $convocatoria->save();
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        dd($e->getMessage());
                    }
                    
                    if($conv->keywords !== null && !empty(json_decode($conv->keywords))){

                        $checkencajeLinea = \App\Models\Encaje::where('Acronimo', $convocatoria->Acronimo)->where('Tipo', "Linea")
                        ->where('Ayuda_id', $convocatoria->id)->first();

                        $tagsTec = "";
                        if(!empty($conv->keywords) && is_array(json_decode($conv->keywords))){
                            $tagsTec = implode(",",json_decode($conv->keywords));
                        }

                        $checkChatGPTKeywords = \App\Models\ChatGPTAyudasKeywords::where('id_ayuda', $convocatoria->id)->where('type', 'keywords')->first();

                        $keywords = "";
                        if($checkChatGPTKeywords){
                            $keywords = (is_array(json_decode($checkChatGPTKeywords->keywords, true)) && $checkChatGPTKeywords->keywords != "") ? json_decode($checkChatGPTKeywords->keywords, true) : "";
                        }
                        
                        if(!$checkencajeLinea){

                            if($keywords != "" && is_array($keywords)){
                                if(isset($keywords['keywords']) && !empty($keywords['keywords'])){
                                    $tagsTec .= implode(",", $keywords['keywords']);
                                }
                                if(isset($keywords['areas']) && !empty($keywords['areas'])){
                                    $tagsTec .= implode(",", $keywords['areas']);
                                }
                            }
                            
                            try{
                                $encaje = new \App\Models\Encaje();
                                $encaje->Ayuda_id = $convocatoria->id;
                                $encaje->Titulo = "Temática principal";
                                $encaje->Descripcion = $shortDescription;
                                $encaje->Acronimo = $convocatoria->Acronimo;
                                $encaje->Tipo = "Linea";
                                $encaje->naturalezaPartner = $convocatoria->naturalezaConvocatoria;
                                $encaje->PerfilFinanciacion = $convocatoria->PerfilFinanciacion;
                                $encaje->TagsTec = $tagsTec;   
                                $encaje->save();
                            }catch(Exception $e){
                                Log::error($e->getMessage());
                                dd($e->getMessage());
                            }                          
                            
                        }else{

                            $tagsActuales = explode(",",$checkencajeLinea->TagsTec);
                            $tagsChatGpt = array();
                            if($keywords != "" && is_array($keywords)){
                                if(isset($keywords['keywords']) && !empty($keywords['keywords'])){
                                    $tagsChatGpt = $keywords['keywords'];
                                }
                                if(isset($keywords['areas']) && !empty($keywords['areas'])){
                                    $tagsChatGpt = array_merge($tagsChatGpt, $keywords['areas']);
                                }
                            }

                            $arraydiff = array_diff($tagsChatGpt, $tagsActuales);

                            if(!empty($arraydiff) && count($arraydiff) > 0){
                                   
                                try{             
                                    $checkencajeLinea->TagsTec = implode(",", $tagsActuales).",".implode(",", $arraydiff);
                                    $checkencajeLinea->save();
                                }catch(Exception $e){
                                    Log::error($e->getMessage());
                                    dd($e->getMessage());
                                }                          
                            }
                        }

                        $checkencajeInterno = \App\Models\Encaje::where('Acronimo', 'INTERNO')->where('Tipo', "Interna")
                        ->where('Ayuda_id', $convocatoria->id)->first();

                        if(!$checkencajeInterno){
                           
                            try{
                                $encaje = new \App\Models\Encaje();
                                $encaje->Ayuda_id = $convocatoria->id;
                                $encaje->Titulo = "INTERNO";
                                $encaje->Acronimo = "INTERNO";
                                $encaje->Tipo = "Interna";
                                $encaje->naturalezaPartner = $convocatoria->naturalezaConvocatoria;
                                $encaje->PerfilFinanciacion = $convocatoria->PerfilFinanciacion;
                                $encaje->save();
                            }catch(Exception $e){
                                Log::error($e->getMessage());
                                dd($e->getMessage());
                            }
                         
                        }
                        
                    }else{

                        $checkencajeLinea = \App\Models\Encaje::where('Acronimo', $convocatoria->Acronimo)->where('Tipo', "Linea")
                        ->where('Ayuda_id', $convocatoria->id)->first();

                        $checkChatGPTKeywords = \App\Models\ChatGPTAyudasKeywords::where('id_ayuda', $convocatoria->id)->where('type', 'keywords')->first();
                        
                        $keywords = "";
                        if($checkChatGPTKeywords){
                            $keywords = (is_array(json_decode($checkChatGPTKeywords->keywords, true)) && $checkChatGPTKeywords->keywords != "") ? json_decode($checkChatGPTKeywords->keywords, true) : "";
                        }

                        if(!$checkencajeLinea){

                            $tagsTec = null;

                            if($keywords != "" && is_array($keywords)){
                                if(isset($keywords['keywords']) && !empty($keywords['keywords'])){
                                    $tagsTec .= implode(",", $keywords['keywords']);
                                }
                                if(isset($keywords['areas']) && !empty($keywords['areas'])){
                                    $tagsTec .= implode(",", $keywords['areas']);
                                }
                            }
                            
                            try{
                                $encaje = new \App\Models\Encaje();
                                $encaje->Ayuda_id = $convocatoria->id;
                                $encaje->Titulo = "Temática principal";
                                $encaje->Descripcion = $shortDescription;
                                $encaje->Acronimo = $convocatoria->Acronimo;
                                $encaje->Tipo = "Linea";
                                $encaje->naturalezaPartner = $convocatoria->naturalezaConvocatoria;
                                $encaje->PerfilFinanciacion = $convocatoria->PerfilFinanciacion;
                                $encaje->TagsTec = $tagsTec;   
                                $encaje->save();
                            }catch(Exception $e){
                                Log::error($e->getMessage());
                                dd($e->getMessage());
                            }                          
                            
                        }else{

                            $tagsActuales = explode(",",$checkencajeLinea->TagsTec);
                            $tagsChatGpt = array();
                            if($keywords != "" && is_array($keywords)){
                                if(isset($keywords['keywords']) && !empty($keywords['keywords'])){
                                    $tagsChatGpt = $keywords['keywords'];
                                }
                                if(isset($keywords['areas']) && !empty($keywords['areas'])){
                                    $tagsChatGpt = array_merge($tagsChatGpt, $keywords['areas']);
                                }
                            }

                            $arraydiff = array_diff($tagsChatGpt, $tagsActuales);

                            if(!empty($arraydiff) && count($arraydiff) > 0){
                                   
                                try{             
                                    $checkencajeLinea->TagsTec = implode(",", $tagsActuales).",".implode(",", $arraydiff);   
                                    $checkencajeLinea->save();
                                }catch(Exception $e){
                                    Log::error($e->getMessage());
                                    dd($e->getMessage());
                                }                          
                            }
                        }

                        $checkencajeInterno = \App\Models\Encaje::where('Acronimo', 'INTERNO')->where('Tipo', "Interna")
                        ->where('Ayuda_id', $convocatoria->id)->first();
    
                        if(!$checkencajeInterno){
                           
                            try{
                                $encaje = new \App\Models\Encaje();
                                $encaje->Ayuda_id = $convocatoria->id;
                                $encaje->Titulo = "INTERNO";
                                $encaje->Acronimo = "INTERNO";
                                $encaje->Tipo = "Interna";
                                $encaje->naturalezaPartner = $convocatoria->naturalezaConvocatoria;
                                $encaje->PerfilFinanciacion = $convocatoria->PerfilFinanciacion;
                                $encaje->save();
                            }catch(Exception $e){
                                Log::error($e->getMessage());
                                dd($e->getMessage());
                            }

                        }
    
                    }

                    ###Matcheamos los proyectos con las convocatorias
                    $matchProyecto = \App\Models\Proyectos::whereNull('IdAyuda')->where('subCall', $conv->identifier)->orWhere('masterCall', $conv->identifier)->count();
                    if($matchProyecto > 0 && $conv->identifier !== null){
                        try{
                            \App\Models\Proyectos::whereNull('IdAyuda')->where('subCall', $conv->identifier)->orWhere('masterCall', $conv->identifier)->update([
                                'IdAyuda' => $convocatoria->id,
                                'updated_at' => Carbon::now()
                            ]);                            
                        }catch(Exception $e){
                            Log::error('Error matcheado convocatoria con proyecto');
                            Log::error($e->getMessage());
                        }
                    }elseif($matchProyecto == 0){
                        $matchProyecto = \App\Models\Proyectos::whereNull('IdAyuda')->where('subCall', $conv->callIdentifier)->orWhere('masterCall', $conv->callIdentifier)->count();
                        if($matchProyecto > 0 && $conv->callIdentifier !== null){
                            try{
                                \App\Models\Proyectos::whereNull('IdAyuda')->where('subCall', $conv->callIdentifier)->orWhere('masterCall', $conv->callIdentifier)->update([
                                    'IdAyuda' => $convocatoria->id,
                                    'updated_at' => Carbon::now()
                                ]);         
                            }catch(Exception $e){
                                Log::error('Error matcheado convocatoria con proyecto');
                                Log::error($e->getMessage());
                            }
                        }
                    }

                }else{
                    
                    /*if($convocatoria->chatgptdata->count() > 0){

                        $data = $convocatoria->chatgptdata->where('type', 'descripcion_corta');
                        if($data->first() !== null && $data->first()->response !== null && $data->first()->response != ""){
                            $shortDescription = $data->first()->response;
                        }else{
                            $shortDescription = substr($conv->description_html,0, strpos($conv->description_html,"\n"));    
                        }

                        $data = $convocatoria->chatgptdata->where('type', 'descripcion_larga');
                        if($data->first() !== null && $data->first()->response !== null && $data->first()->response != ""){
                            $longDescription = $data->first()->response;
                        }else{
                            $longDescription = substr($conv->description_html,0, strpos($conv->description_html,"\n"));    
                        }

                        $data = $convocatoria->chatgptdata->where('type', 'requisitos_tecnicos');
                        if($data->first() !== null && $data->first()->response !== null && $data->first()->response != ""){
                            $requisitosTecnicos = $data->first()->response;
                        }else{
                            $requisitosTecnicos = substr($conv->description_html,0, strpos($conv->description_html,"\n"));    
                        }

                    }else{*/
                        $shortDescription = substr($conv->description_html,0, strpos($conv->description_html,"\n"));
                        $longDescription = strip_tags($conv->description_html);                        
                        if($convocatoria->chatgptdata->count() > 0){                        
                            $data = $convocatoria->chatgptdata->where('type', 'requisitos_tecnicos');
                            if($data->first() !== null && $data->first()->response !== null && $data->first()->response != ""){
                                $requisitosTecnicos = $data->first()->response;
                            }else{
                                $requisitosTecnicos = substr($conv->description_html,0, strpos($conv->description_html,"\n"));    
                            }
                        }else{
                            $requisitosTecnicos =  substr($conv->description_html,0, strpos($conv->description_html,"\n"));
                        }
                    //}

                    $typeOfAction = null;
                    $subfondos = null;
                    $presupuesto = null;

                    if($conv !== null){
                        $indice = null;
                        if($conv->budgetTopicActionMap !== null){
                            foreach(json_decode($conv->budgetTopicActionMap, true) as $key => $value){
                                if(!isset($value[0]) || strripos($value[0]['action'],$conv->identifier) !== false){
                                    $indice = $key;
                                    break;
                                }
                            }
                        }
                        if($indice !== null){
                            if($conv->minContribution !== null){
                                $array = json_decode($conv->minContribution, true);
                                $convocatoria->FondoPerdidoMinimoNominal = (isset($array[$indice][0])) ? $array[$indice][0] : null;
                            }
                            if($conv->maxContribution !== null){
                                $array = json_decode($conv->maxContribution, true);
                                $convocatoria->FondoPerdidoMaximoNominal = (isset($array[$indice][0])) ? $array[$indice][0] : null;
                            }    
                        
                            if(isset($conv->budgetYearMap)){
                                $bugetArray = (json_decode($conv->budgetYearMap, true));
                                if(isset($bugetArray[$indice])){
                                    $year = Carbon::now()->format('Y');
                                    if(isset($bugetArray[$indice][0][$year])){
                                        $presupuesto = $bugetArray[$indice][0][$year];
                                    }
                                }
                            }
                        }
                    }
    
                    if($conv->programmeDivision !== null){
                        if(is_array(json_decode($conv->programmeDivision, true)) && !empty(json_decode($conv->programmeDivision, true))){
                            foreach(json_decode($conv->programmeDivision, true) as $subfondo){
                                $subfondos[] = $subfondo['id'];
                            }
                        }
                    }

                    if($conv->typeOfAction !== null){
                        $typeOfAction = \App\Models\TypeOfActions::where('nombre', $conv->typeOfAction)->first();                       
                    }

                    try{                    
                        $convocatoria->id_ayuda = $ayuda->id;
                        $convocatoria->IdConvocatoriaStr = $conv->identifier;
                        $convocatoria->Acronimo = $conv->identifier;
                        $convocatoria->Titulo = $conv->title;
                        $convocatoria->Link = "https://ec.europa.eu/info/funding-tenders/opportunities/portal/screen/opportunities/topic-details/".mb_strtolower($conv->identifier)."?keywords=".$conv->identifier;
                        $convocatoria->Organismo = 6523;
                        #$convocatoria->FechaMax = "";
                        #$convocatoria->Meses = "";
                        #$convocatoria->MesesMin = "";
                        #$convocatoria->FechaMaxConstitucion = "";
                        #$convocatoria->FechaMinConstitucion = "";               
                        #$convocatoria->tiposObligatorios = "";
                        $convocatoria->Presupuesto = $presupuesto;
                        #$convocatoria->PresupuestoConsorcio = "";
                        $convocatoria->Ambito = "Europea";
                        $convocatoria->OpcionCNAE = "Todos"; // DEFAULT TODOS
                        #$convocatoria->CNAES = "";
                        $convocatoria->DescripcionCorta = strip_tags($shortDescription);
                        $convocatoria->DescripcionLarga = $longDescription;
                        $convocatoria->RequisitosTecnicos = $requisitosTecnicos;
                        #$convocatoria->RequisitosParticipante = "";
                        #$convocatoria->Convocatoria = "";
                        #$convocatoria->maxEmpleados = "";
                        #$convocatoria->minEmpleados = "";                        
                        if(is_numeric($conv->startDate) && stripos($conv->startDate, " ") === false){
                            $convocatoria->Inicio = Carbon::createFromTimestampMs($conv->startDate);
                        }else{
                            $convocatoria->Inicio = Carbon::parse($conv->startDate);
                        }
                        if($conv->endDate !== null){
                            if(is_numeric($conv->endDate) && stripos($conv->endDate, " ") === false){
                                $convocatoria->Fin = Carbon::createFromTimestampMs($conv->endDate);
                            }else{
                                $convocatoria->Fin = Carbon::parse($conv->endDate);
                            }
                        }elseif($conv->deadlineDate !== null){
                            if(is_numeric(json_decode($conv->deadlineDate)[0]) && stripos(json_decode($conv->deadlineDate)[0], " ") === false){
                                $convocatoria->Fin  = Carbon::createFromTimestampMs(json_decode($conv->deadlineDate)[0]);
                            }else{
                                $convocatoria->Fin  = Carbon::parse(json_decode($conv->deadlineDate)[0]);
                            }
                        }elseif($conv->deadlineDates !== null){
                            $dates = json_decode($conv->deadlineDates, true);
                            if(!empty($dates)){

                                dd($dates[$key][0][0]);
                                $key = array_key_first($dates);
                                if(isset($dates[$key]) && isset($dates[$key][0]) && isset($dates[$key][0][0])){
                                    $convocatoria->Fin  = Carbon::createFromFormat("d F Y", $dates[$key][0][0]);
                                }
                            }
                        }
                        $convocatoria->Estado = $status;
                        $convocatoria->InformacionDefinitiva = 0;
                        $convocatoria->Competitiva = "Muy Competitiva";
                        $convocatoria->Uri = str_replace(" ","-", preg_replace("/[^a-zA-Z0-9\-\s]/", "", seo_quitar_tildes(mb_strtolower(trim($conv->title)))));
                        #$convocatoria->Ccaas = "";
                        $convocatoria->Featured = 0;
                        $convocatoria->CentroTecnologico = 0;                        
                        #$convocatoria->PresupuestoMin = "";
                        #$convocatoria->PresupuestoMax = "";
                        #$convocatoria->DuracionMin = "";
                        #$convocatoria->DuracionMax = "";
                        #$convocatoria->Garantias = "";
                        #$convocatoria->IDInnovating = "";
                        $convocatoria->PorcentajeCreditoMax = 0;
                        #$convocatoria->DeduccionMax = "";
                        #$convocatoria->NivelCompetitivo = "";
                        $convocatoria->TiempoMedioResolucion = 6;
                        $convocatoria->SelloPyme = 1;
                        #$convocatoria->EmpresaCrisis = "";
                        #$convocatoria->InformeMotivado = "";
                        #$convocatoria->TextoCondiciones = null;
                        #$convocatoria->TextoConsorcio = "";
                        $convocatoria->FondoTramo = "fondo";
                        $convocatoria->LastEditor = "system";
                        $convocatoria->Publicada = $convocatoria->Publicada;
                        #$convocatoria->AplicacionIntereses = "";
                        #$convocatoria->PorcentajeIntereses = "";
                        #$convocatoria->MesesCarencia = "";
                        #$convocatoria->AnosAmortizacion = "";
                        $convocatoria->EfectoIncentivador = 1;
                        $convocatoria->Minimis = 0;
                        #$convocatoria->CondicionesEspeciales = "";
                        $convocatoria->TematicaObligatoria = 1;
                        #$convocatoria->instrucciones2 = "";
                        #$convocatoria->pregunta2 = "";
                        #$convocatoria->instrucciones1 = "";
                        #$convocatoria->pregunta1 = "";
                        #$convocatoria->Analisis = "";
                        $convocatoria->Intensidad = "4";
                        #$convocatoria->Dnsh = "";
                        #$convocatoria->MensajeDnsh = "";
                        #$convocatoria->esDeGenero = "";
                        #$convocatoria->textoGenero = "";
                        $convocatoria->datosMetricas = null;
                        $convocatoria->esMetricable = 0;
                        $convocatoria->update_extinguida_ayuda = "2";
                        $convocatoria->es_europea = 1;
                        $convocatoria->id_raw_data = $conv->id;
                        $convocatoria->FondosEuropeos = json_encode(["10"]);
                        $convocatoria->subfondos = ($subfondos !== null) ? json_encode($subfondos, JSON_UNESCAPED_UNICODE) : null;
                        $convocatoria->CapitulosFinanciacion = '["Personal", "Materiales", "Subcontrataciones", "Amortizaciones", "Activos", "Otros gastos"]'; 

                        if($typeOfAction){
                            ###Forzamos a publicada cuando una ayuda europea tiene un type of action peticion Claudio jira IW-453
                            $convocatoria->Publicada = $typeOfAction->publicar_ayudas;
                            $convocatoria->type_of_action_id = $typeOfAction->id;                        
                            $convocatoria->Trl = $typeOfAction->trl;
                            $convocatoria->objetivoFinanciacion = $typeOfAction->objetivo_financiacion;
                            $convocatoria->CapitulosFinanciacion = $typeOfAction->capitulos_financiacion;
                            if($typeOfAction->fondo_perdido_maximo != $typeOfAction->fondo_perdido_minimo){
                                $convocatoria->PorcentajeFondoPerdido = $typeOfAction->fondo_perdido_maximo;
                                $convocatoria->FondoPerdidoMinimo = $typeOfAction->fondo_perdido_minimo;
                            }else{
                                $convocatoria->PorcentajeFondoPerdido = $typeOfAction->fondo_perdido_minimo;
                            }
                            $convocatoria->TipoFinanciacion = $typeOfAction->tipo_financiacion;  
                            $convocatoria->Categoria = $typeOfAction->categoria;
                            $convocatoria->naturalezaConvocatoria = $typeOfAction->naturaleza;
                            $convocatoria->PerfilFinanciacion = ($typeOfAction->perfil_financiacion === null) ? '["231435000088214861"]' : $typeOfAction->perfil_financiacion; 
                            $convocatoria->Presentacion = json_decode($typeOfAction->presentacion)[0]; 

                            $convocatoria->TextoConsorcio = ($typeOfAction->texto_consorcio === null) ? null : $typeOfAction->texto_consorcio;
                            $convocatoria->CondicionesFinanciacion = ($typeOfAction->condiciones_financiacion === null) ? null : $typeOfAction->condiciones_financiacion;
                            if($convocatoria->FondoPerdidoMaximoNominal !== null){
                                $convocatoria->PresupuestoMax = $convocatoria->FondoPerdidoMaximoNominal*($typeOfAction->fondo_perdido_maximo/100);
                            }
                            if($convocatoria->FondoPerdidoMinimoNominal !== null){
                                $convocatoria->PresupuestoMin = $convocatoria->FondoPerdidoMinimoNominal*($typeOfAction->fondo_perdido_minimo/100);
                                $convocatoria->FondoPerdidoMinimoNominal = $convocatoria->FondoPerdidoMinimoNominal*($typeOfAction->fondo_perdido_minimo/100);
                            }
                        }else{
                            $convocatoria->Trl = 5;
                            $convocatoria->objetivoFinanciacion = "Proyectos";
                            $convocatoria->PorcentajeFondoPerdido = 100;  
                            $convocatoria->TipoFinanciacion = '["Fondo perdido"]';
                            $convocatoria->Categoria = '["Micro","Pequeña","Mediana","Grande"]'; // DEFAULT TODOS
                            $convocatoria->naturalezaConvocatoria = '["6668837","6668838","6668839","6668840","6668841","6668842","6668843"]'; // DEFAULT TODOS         
                            $convocatoria->PerfilFinanciacion = '["231435000088214861"]'; 
                            $convocatoria->Presentacion = "Consorcio";       
                            $convocatoria->CondicionesFinanciacion = strip_tags($conv->conditions);
                        }
                        $convocatoria->save();
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        dd($e->getMessage());
                    }

                    if($conv->keywords !== null && !empty(json_decode($conv->keywords))){

                        $checkencajeLinea = \App\Models\Encaje::where('Acronimo', $convocatoria->Acronimo)->where('Tipo', "Linea")
                        ->where('Ayuda_id', $convocatoria->id)->first();

                        $tagsTec = "";
                        if(!empty($conv->keywords) && is_array(json_decode($conv->keywords))){
                            $tagsTec = implode(",",json_decode($conv->keywords));
                        }

                        $checkChatGPTKeywords = \App\Models\ChatGPTAyudasKeywords::where('id_ayuda', $convocatoria->id)->where('type', 'keywords')->first();

                        $keywords = "";
                        if($checkChatGPTKeywords){
                            $keywords = (is_array(json_decode($checkChatGPTKeywords->keywords, true)) && $checkChatGPTKeywords->keywords != "") ? json_decode($checkChatGPTKeywords->keywords, true) : "";
                        }
                        
                        if(!$checkencajeLinea){
                            
                            if($keywords != "" && is_array($keywords)){
                                if(isset($keywords['keywords']) && !empty($keywords['keywords'])){
                                    $tagsTec .= implode(",", $keywords['keywords']);
                                }
                                if(isset($keywords['areas']) && !empty($keywords['areas'])){
                                    $tagsTec .= implode(",", $keywords['areas']);
                                }
                            }

                            try{
                                $encaje = new \App\Models\Encaje();
                                $encaje->Ayuda_id = $convocatoria->id;
                                $encaje->Titulo = "Temática principal";
                                $encaje->Descripcion = $shortDescription;
                                $encaje->Acronimo = $convocatoria->Acronimo;
                                $encaje->Tipo = "Linea";
                                $encaje->naturalezaPartner = $convocatoria->naturalezaConvocatoria;
                                $encaje->PerfilFinanciacion = $convocatoria->PerfilFinanciacion;
                                $encaje->TagsTec = $tagsTec;   
                                $encaje->save();
                            }catch(Exception $e){
                                Log::error($e->getMessage());
                                dd($e->getMessage());
                            }                    
                            
                        }else{

                            $tagsActuales = explode(",",$checkencajeLinea->TagsTec);
                            $tagsChatGpt = array();
                            if($keywords != "" && is_array($keywords)){
                                if(isset($keywords['keywords']) && !empty($keywords['keywords'])){
                                    $tagsChatGpt = $keywords['keywords'];
                                }
                                if(isset($keywords['areas']) && !empty($keywords['areas'])){
                                    $tagsChatGpt = array_merge($tagsChatGpt, $keywords['areas']);
                                }
                            }

                            $arraydiff = array_diff($tagsChatGpt, $tagsActuales);

                            if(!empty($arraydiff) && count($arraydiff) > 0){
                                   
                                try{             
                                    $checkencajeLinea->TagsTec = implode(",", $tagsActuales).",".implode(",", $arraydiff);   
                                    $checkencajeLinea->save();
                                }catch(Exception $e){
                                    Log::error($e->getMessage());
                                    dd($e->getMessage());
                                }                          
                            }
                        }

                        $checkencajeInterno = \App\Models\Encaje::where('Acronimo', 'INTERNO')->where('Tipo', "Interna")
                        ->where('Ayuda_id', $convocatoria->id)->first();

                        if(!$checkencajeInterno){
                           
                            try{
                                $encaje = new \App\Models\Encaje();
                                $encaje->Ayuda_id = $convocatoria->id;
                                $encaje->Titulo = "INTERNO";
                                $encaje->Acronimo = "INTERNO";
                                $encaje->Tipo = "Interna";
                                $encaje->naturalezaPartner = $convocatoria->naturalezaConvocatoria;
                                $encaje->PerfilFinanciacion = $convocatoria->PerfilFinanciacion;
                                $encaje->save();
                            }catch(Exception $e){
                                Log::error($e->getMessage());
                                dd($e->getMessage());
                            }                           
                            
                        }
                        
                    }else{

                        $checkencajeLinea = \App\Models\Encaje::where('Acronimo', $convocatoria->Acronimo)->where('Tipo', "Linea")
                        ->where('Ayuda_id', $convocatoria->id)->first();

                        $checkChatGPTKeywords = \App\Models\ChatGPTAyudasKeywords::where('id_ayuda', $convocatoria->id)->where('type', 'keywords')->first();

                        $keywords = "";
                        if($checkChatGPTKeywords){
                            $keywords = (is_array(json_decode($checkChatGPTKeywords->keywords, true)) && $checkChatGPTKeywords->keywords != "") ? json_decode($checkChatGPTKeywords->keywords, true) : "";
                        }

                        if(!$checkencajeLinea){
                            
                            $tagsTec = null;

                            if($keywords != "" && is_array($keywords)){
                                if(isset($keywords['keywords']) && !empty($keywords['keywords'])){
                                    $tagsTec .= implode(",", $keywords['keywords']);
                                }
                                if(isset($keywords['areas']) && !empty($keywords['areas'])){
                                    $tagsTec .= implode(",", $keywords['areas']);
                                }
                            }

                            try{
                                $encaje = new \App\Models\Encaje();
                                $encaje->Ayuda_id = $convocatoria->id;
                                $encaje->Titulo = "Temática principal";
                                $encaje->Descripcion = $shortDescription;
                                $encaje->Acronimo = $convocatoria->Acronimo;
                                $encaje->Tipo = "Linea";
                                $encaje->naturalezaPartner = $convocatoria->naturalezaConvocatoria;
                                $encaje->PerfilFinanciacion = $convocatoria->PerfilFinanciacion;
                                $encaje->TagsTec = $tagsTec;   
                                $encaje->save();
                            }catch(Exception $e){
                                Log::error($e->getMessage());
                                dd($e->getMessage());
                            }                          

                        }else{

                            $tagsActuales = explode(",",$checkencajeLinea->TagsTec);
                            $tagsChatGpt = array();
                            if($keywords != "" && is_array($keywords)){
                                if(isset($keywords['keywords']) && !empty($keywords['keywords'])){
                                    $tagsChatGpt = $keywords['keywords'];
                                }
                                if(isset($keywords['areas']) && !empty($keywords['areas'])){
                                    $tagsChatGpt = array_merge($tagsChatGpt, $keywords['areas']);
                                }
                            }

                            $arraydiff = array_diff($tagsChatGpt, $tagsActuales);

                            if(!empty($arraydiff) && count($arraydiff) > 0){
                                   
                                try{             
                                    $checkencajeLinea->TagsTec = implode(",", $tagsActuales).",".implode(",", $arraydiff);   
                                    $checkencajeLinea->save();
                                }catch(Exception $e){
                                    Log::error($e->getMessage());
                                    dd($e->getMessage());
                                }                          
                            }
                        }

                        $checkencajeInterno = \App\Models\Encaje::where('Acronimo', 'INTERNO')->where('Tipo', "Interna")
                        ->where('Ayuda_id', $convocatoria->id)->first();
    
                        if(!$checkencajeInterno){
                           
                            try{
                                $encaje = new \App\Models\Encaje();
                                $encaje->Ayuda_id = $convocatoria->id;
                                $encaje->Titulo = "INTERNO";
                                $encaje->Acronimo = "INTERNO";
                                $encaje->Tipo = "Interna";
                                $encaje->naturalezaPartner = $convocatoria->naturalezaConvocatoria;
                                $encaje->PerfilFinanciacion = $convocatoria->PerfilFinanciacion;
                                $encaje->save();
                            }catch(Exception $e){
                                Log::error($e->getMessage());
                                dd($e->getMessage());
                            }
                           
                        }
    
                    }

                    ###Matcheamos los proyectos con las convocatorias

                    $matchProyecto = \App\Models\Proyectos::whereNull('IdAyuda')->where('subCall', $conv->identifier)->orWhere('masterCall', $conv->identifier)->count();
                    if($matchProyecto > 0 && $conv->identifier !== null){
                        try{
                            \App\Models\Proyectos::whereNull('IdAyuda')->where('subCall', $conv->identifier)->orWhere('masterCall', $conv->identifier)->update([
                                'IdAyuda' => $convocatoria->id,
                                'updated_at' => Carbon::now()
                            ]);                            
                        }catch(Exception $e){
                            Log::error('Error matcheado convocatoria con proyecto');
                            Log::error($e->getMessage());
                        }
                    }elseif($matchProyecto == 0){
                        $matchProyecto = \App\Models\Proyectos::whereNull('IdAyuda')->where('subCall', $conv->callIdentifier)->orWhere('masterCall', $conv->callIdentifier)->count();
                        if($matchProyecto > 0 && $conv->callIdentifier !== null){
                            try{
                                \App\Models\Proyectos::whereNull('IdAyuda')->where('subCall', $conv->callIdentifier)->orWhere('masterCall', $conv->callIdentifier)->update([
                                    'IdAyuda' => $convocatoria->id,
                                    'updated_at' => Carbon::now()
                                ]);         
                            }catch(Exception $e){
                                Log::error('Error matcheado convocatoria con proyecto');
                                Log::error($e->getMessage());
                            }
                        }
                    }
                }
            }

            
        }

        $this->info(now());

        return COMMAND::SUCCESS;
    }
}
