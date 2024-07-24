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
        $rawdata = \App\Models\ProyectosRawData::get();

        $ocurrences = array('/ s.a.$/', '/ s.l.$/', '/ S.A.$/', '/ S.L.$/', '/ SA$/', '/ SL$/', '/ SAU$/', '/ S.A.U.$/', '/ s.a.u.$/', '/ sa.$/', '/ sl.$/', '/ sau.$/', '/ S.A.L.$/', '/ S.L.L$/', '/ S L$/', '/ s.a.$/',
        '/ slp$/', '/ slu$/', '/ slne$/', '/ slg$/', '/ sll$/', '/ s.a.$/');

        foreach($rawdata as $data){
            $values = json_decode($data->jsondata, true);
            $tipoConvocatoria = "Individual";
            $lider = false;
            if(str_ends_with($values['TituloProyecto'], ")")){                
                $substr = substr($values['TituloProyecto'],strripos($values['TituloProyecto'],"("), strlen($values['TituloProyecto']));                
                if(strripos($substr, "/") !== false && strlen($substr) <= 7){
                    $tipoConvocatoria = "Consorcio";
                    $values['TituloProyecto'] = trim(substr($values['TituloProyecto'],0, strripos($values['TituloProyecto'],"(")));                    
                }                                
                if(strripos($substr, "(1/") !== false){
                    $lider = true;
                }
            }            

            $values['CodigoEntidad'] = str_replace("-","",$values['CodigoEntidad']);
            $uri = str_replace(" ","-",trim(cleanUriProyectosBeforeSave(seo_quitar_tildes(mb_strtolower($values['TituloProyecto'])))));
            $unix = substr(time(),-6);
            $expediente = "INV".$data->id.$unix;
            $nombre = preg_replace($ocurrences, '', $values['RazonSocial'], 1);

            $proyecto = \App\Models\Proyectos::where('id_raw_data', $data->id)->where('id_organismo', $data->id_organimo)->first();

            if(!$proyecto){
                $proyecto = new \App\Models\Proyectos();

                try{
                    $proyecto->id_raw_data = $data->id;
                    $proyecto->organismo = $data->id_organimo;
                    if($lider === true){
                        $proyecto->empresaPrincipal = $values['CodigoEntidad'];
                        $proyecto->nombreEmpresa = $nombre;
                    }else{
                        $proyecto->empresaPrincipal = "XXXXXXXXX";
                    }
                    $proyecto->empresasParticipantes = json_encode([]);
                    $proyecto->idAyuda = (isset($values['id_convocatoria'])) ? $values['id_convocatoria'] : null;
                    $proyecto->Titulo = $values['TituloProyecto']; 
                    $proyecto->uri = $uri;               
                    $proyecto->importado = 1;
                    $proyecto->esEuropeo = 0;
                    $proyecto->tipoConvocatoria = $tipoConvocatoria;
                    $proyecto->ambitoConvocatoria = 'nacional';
                    $proyecto->Fecha = Carbon::parse($values['FechaAprobacion'])->format('Y-m-d');
                    $proyecto->Estado = "Cerrado";
                    $proyecto->Tematicas = $values['AreaSectorialN1'].",".$values['AreaSectorialN2'];
                    $proyecto->Fondos = $values['OrigenFondos'];
                    $proyecto->Expediente = $expediente;
                    $proyecto->PresupuestoTotal = $values['Presupuesto'];
                    $proyecto->AyudaEq = $values['AportacionCDTI'];
                    //$proyectonuevo->FinanciacionPublica = (float)str_replace(".","",$row['financiacion_publica_del_proyecto']);                                    
                    //$proyecto->Acronimo = $data->id;
                    //$proyecto->tituloAyuda = $row['nombre_ayuda'];
                    //$proyecto->save();
                    //$proyecto->PresupuestoSocio = (float)str_replace(".","",$row['presupuesto_socio']);
                    //$proyecto->AyudaEqSocio = ($row['ayuda_pub_eq_socio'] !== null) ? (float)str_replace(".","",$row['ayuda_pub_eq_socio']) : null;
                    //$proyecto->tipoFinanPublica = $row['financiacion_publica_del_socio'];
                                                    

                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return COMMAND::FAILURE;
                }

                try{
                    \App\Models\Participantes::updateOrCreate(
                        [
                            'cif_participante' => $values['CodigoEntidad'],
                            'nombre_participante' => $nombre,
                            'id_proyecto' => $proyecto->id,
                            'id_concesion' => null,
                        ],
                        [
                            'presupuesto_socio' => ($values['Presupuesto'] !== null) ? (float)$values['Presupuesto'] : null,
                            'ayuda_eq_socio' => ($values['AportacionCDTI'] !== null) ? (float)$values['AportacionCDTI'] : null,
                        ]
                    );
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return COMMAND::FAILURE;
                }
            }else{
                
                try{
                    if($lider === true){
                        $proyecto->empresaPrincipal = $values['CodigoEntidad'];
                        $proyecto->nombreEmpresa = $nombre;                        
                    }
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
                            'nombre_participante' => $nombre,
                            'id_proyecto' => $proyecto->id,
                            'id_concesion' => null                            
                        ],
                        [
                            'presupuesto_socio' => ($values['Presupuesto'] !== null) ? (float)str_replace(".","",$values['Presupuesto']) : null,
                            'ayuda_eq_socio' => ($values['AportacionCDTI'] !== null) ? (float)str_replace(".","",$values['AportacionCDTI']) : null,
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
