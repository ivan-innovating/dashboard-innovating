<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class moveCDTIProjectsToInnovating extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:move-cdti-projects-to-innovating';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mueve los proyectos scrappeados de CDTI la tabla de rawdata a la tabla proyectos innovating';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $rawdata = \App\Models\ProyectosRawData::where('updated_at', '>=', Carbon::now()->subDays(7))->get();

        $ocurrences = array('/ s.a.$/', '/ s.l.$/', '/ S.A.$/', '/ S.L.$/', '/ SA$/', '/ SL$/', '/ SAU$/', '/ S.A.U.$/', '/ s.a.u.$/', '/ sa.$/', '/ sl.$/', '/ sau.$/', '/ S.A.L.$/', '/ S.L.L$/', '/ S L$/', '/ s.a.$/',
        '/ slp$/', '/ slu$/', '/ slne$/', '/ slg$/', '/ sll$/', '/ s.a.$/');

        foreach($rawdata as $data){

            $values = json_decode($data->jsondata, true);
            $tipoConvocatoria = "Individual";
            if(str_ends_with($values['TituloProyecto'], ")")){                
                $substr = substr($values['TituloProyecto'],strripos($values['TituloProyecto'],"("), strlen($values['TituloProyecto']));                
                if(strripos($substr, "/") !== false && strlen($substr) <= 7){
                    $tipoConvocatoria = "Consorcio";
                    $values['TituloProyecto'] = trim(substr($values['TituloProyecto'],0, strripos($values['TituloProyecto'],"(")));                    
                }                                
            }            

            $values['CodigoEntidad'] = str_replace("-","",$values['CodigoEntidad']);
            $uri = substr(str_replace(" ","-",trim(cleanUriProyectosBeforeSave(seo_quitar_tildes(mb_strtolower(preg_replace("/[^A-Za-z0-9À-Ùà-ú@.!? ]/",'',str_replace(array("\r", "\n"), '', $values['TituloProyecto']))))))),0,254);
            $titulo = substr(preg_replace("/[^A-Za-z0-9À-Ùà-ú@.!? ]/u",' ', str_replace(array("\r", "\n"), '', $values['TituloProyecto'])),0, 254);

            $unix = substr(time(),-6);
            $expediente = "INV".$data->id.$unix;
            $nombre = rtrim(preg_replace($ocurrences, '', $values['RazonSocial'], 1),",");
            $convocatoria = (isset($values['id_convocatoria'])) ? \App\Models\Ayudas::find($values['id_convocatoria']) : null;
            $organismo = \App\Models\Organos::find($data->id_organismo);
            if(!$organismo){
                $organismo = \App\Models\Departamentos::find($data->id_organismo);
            }

            $acronimo = ($organismo->Acronimo !== null && $organismo->Acronimo != "") ? $organismo->Acronimo : substr($organismo->Nombre,0, 5); #{AcronimoOrganismo){values['IdTipologia']}{unixtime};
            $acronimo .= $values['IdTipologia'].$unix;

            $proyecto = \App\Models\Proyectos::where('organismo', $data->id_organismo)->where('Titulo', $titulo)->first();

            if(!$proyecto){
                $proyecto = new \App\Models\Proyectos();

                $ambitoConvocatoria = "nacional";
                $entidad = \App\Models\Entidad::where('CIF', $values['CodigoEntidad'])->first();
                if($entidad){
                    if($entidad->pais != "ES"){
                        $ambitoConvocatoria = "internacional";
                    }
                }

                $this->info($data->id.": ultimo titulo proyecto añadido: ".$titulo." (".$uri.")");
                $this->info($data->id.": ultima fecha proyecto añadida: ".Carbon::parse($values['FechaAprobacion'])->format('Y-m-d'));

                try{
                    $proyecto->id_raw_data = $data->id;
                    $proyecto->organismo = $data->id_organismo;
                    $proyecto->empresaPrincipal = $values['CodigoEntidad'];
                    $proyecto->nombreEmpresa = $nombre;
                    $proyecto->PresupuestoSocio = ($values['Presupuesto'] !== null && $values['Presupuesto'] > 0) ? (float)$values['Presupuesto'] : 0;
                    $proyecto->AyudaEqSocio = ($values['AportacionCDTI'] !== null && $values['AportacionCDTI'] > 0) ? (float)$values['AportacionCDTI'] : 0;
                    $proyecto->empresasParticipantes = json_encode([]);
                    $proyecto->NumParticipantes = 1;
                    $proyecto->idAyuda = (isset($values['id_convocatoria'])) ? $values['id_convocatoria'] : null;
                    $proyecto->Titulo = $titulo; 
                    $proyecto->uri = $uri;               
                    $proyecto->importado = 1;
                    $proyecto->esEuropeo = 0;
                    $proyecto->tipoConvocatoria = $tipoConvocatoria;
                    $proyecto->ambitoConvocatoria = $ambitoConvocatoria; ##comprobar si es el organismo tiene pais "ES" = nacional sino es internacional
                    $proyecto->Fecha = Carbon::parse($values['FechaAprobacion'])->format('Y-m-d');
                    $proyecto->Estado = "Cerrado";
                    $proyecto->Tematicas = $values['AreaSectorialN1'].",".$values['AreaSectorialN2'];
                    $proyecto->Fondos = $values['OrigenFondos'];
                    $proyecto->Expediente = $expediente;
                    $proyecto->PresupuestoTotal = $values['Presupuesto'];
                    $proyecto->AyudaEq = $values['AportacionCDTI'];
                    $proyecto->FinanciacionPublica = 0; 
                    #$proyecto->Acronimo = $acronimo; #{AcronimoOrganismo){values['IdTipologia']}{unixtime} #posible valor interno
                    if($convocatoria){
                        $proyecto->tituloAyuda = $convocatoria->Titulo;   
                        $proyecto->idAyudaAcronimo = $convocatoria->IdConvocatoriaStr;
                        #si convocatoria tipoFinanPublica => #Subvención = fondo || #Préstamo = credito || #Subvención/Préstamo = fondo y credito
                        if(in_array("Fondo perdido", json_decode($convocatoria->TipoFinanciacion),true) && in_array("Crédito", json_decode($convocatoria->TipoFinanciacion),true)){
                            $proyecto->tipoFinanPublica = "Subvención/Préstamo";    
                        }elseif(in_array("Fondo perdido", json_decode($convocatoria->TipoFinanciacion),true) && !in_array("Crédito", json_decode($convocatoria->TipoFinanciacion),true)){
                            $proyecto->tipoFinanPublica = "Subvención";    
                        }elseif(!in_array("Fondo perdido", json_decode($convocatoria->TipoFinanciacion),true) && in_array("Crédito", json_decode($convocatoria->TipoFinanciacion),true)){
                            $proyecto->tipoFinanPublica = "Préstamo";    
                        }
                    }                
                    $proyecto->save();
                                                    
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return COMMAND::FAILURE;
                }

                try{
                    \App\Models\Participantes::updateOrCreate(
                        [
                            'cif_participante' => $values['CodigoEntidad'],
                            'id_proyecto' => $proyecto->id,
                            'id_concesion' => null,
                        ],
                        [
                            'nombre_participante' => $nombre,
                            'presupuesto_socio' => ($values['Presupuesto'] !== null && $values['Presupuesto'] > 0) ? (float)$values['Presupuesto'] : null,
                            'ayuda_eq_socio' => ($values['AportacionCDTI'] !== null && $values['AportacionCDTI'] > 0) ? (float)$values['AportacionCDTI'] : null,
                        ]
                    );
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return COMMAND::FAILURE;
                }
            }else{   

                $ambitoConvocatoria = "nacional";
                $entidad = \App\Models\Entidad::where('CIF', $values['CodigoEntidad'])->first();
                if($entidad){
                    if($entidad->pais != "ES"){
                        $ambitoConvocatoria = "internacional";
                    }
                }

                if(isset($values['id_convocatoria'])){
                    $convocatoria = \App\Models\Ayudas::find($values['id_convocatoria']);
                }

                try{
                    if((float)$values['Presupuesto'] > (float)$proyecto->PresupuestoSocio){
                        $proyecto->empresaPrincipal = $values['CodigoEntidad'];
                        $proyecto->nombreEmpresa = $nombre; 
                        $proyecto->PresupuestoSocio = ($values['Presupuesto'] !== null && $values['Presupuesto'] > 0) ? (float)$values['Presupuesto'] : 0;
                        $proyecto->AyudaEqSocio = ($values['AportacionCDTI'] !== null && $values['AportacionCDTI'] > 0) ? (float)$values['AportacionCDTI'] : 0;                       
                    }
                    $participantes = json_decode($proyecto->empresasParticipantes);
                    if(!in_array($values['CodigoEntidad'], $participantes)){
                        array_push($participantes, $values['CodigoEntidad']);
                        $proyecto->empresasParticipantes = json_encode($participantes);
                        $proyecto->NumParticipantes = $proyecto->NumParticipantes +1;
                    }
                    if($convocatoria){
                        $proyecto->tituloAyuda = $convocatoria->Titulo;   
                        $proyecto->idAyudaAcronimo = $convocatoria->IdConvocatoriaStr;
                        #si convocatoria tipoFinanPublica => #Subvención = fondo || #Préstamo = credito || #Subvención/Préstamo = fondo y credito
                        if(in_array("Fondo perdido", json_decode($convocatoria->TipoFinanciacion),true) && in_array("Crédito", json_decode($convocatoria->TipoFinanciacion),true)){
                            $proyecto->tipoFinanPublica = "Subvención/Préstamo";    
                        }elseif(in_array("Fondo perdido", json_decode($convocatoria->TipoFinanciacion),true) && !in_array("Crédito", json_decode($convocatoria->TipoFinanciacion),true)){
                            $proyecto->tipoFinanPublica = "Subvención";    
                        }elseif(!in_array("Fondo perdido", json_decode($convocatoria->TipoFinanciacion),true) && in_array("Crédito", json_decode($convocatoria->TipoFinanciacion),true)){
                            $proyecto->tipoFinanPublica = "Préstamo";    
                        }
                    }        
                    $proyecto->ambitoConvocatoria = $ambitoConvocatoria; ##comprobar si es el organismo tiene pais "ES" = nacional sino es internacional
                    $proyecto->idAyuda = (isset($values['id_convocatoria'])) ? $values['id_convocatoria'] : null;
                    $proyecto->PresupuestoTotal = $proyecto->PresupuestoTotal + $values['Presupuesto'];
                    $proyecto->AyudaEq = $proyecto->AyudaEq + $values['AportacionCDTI'];
                    $proyecto->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return COMMAND::FAILURE;
                }

                try{
                    \App\Models\Participantes::updateOrCreate(
                        [
                            'cif_participante' => $values['CodigoEntidad'],
                            'id_proyecto' => $proyecto->id,
                            'id_concesion' => null                            
                        ],
                        [
                            'nombre_participante' => $nombre,
                            'presupuesto_socio' => ($values['Presupuesto'] !== null && $values['Presupuesto'] > 0) ? (float)$values['Presupuesto'] : null,
                            'ayuda_eq_socio' => ($values['AportacionCDTI'] !== null && $values['AportacionCDTI'] > 0) ? (float)$values['AportacionCDTI'] : null,
                        ]
                    );
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return COMMAND::FAILURE;
                }
            }

        }

        return COMMAND::SUCCESS;
    }
}
