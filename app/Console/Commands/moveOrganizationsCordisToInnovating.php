<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

##########
/*
    Logica para actualizar datos CORDIS, importante realizarlo siempre en este orden, 
    1. subir los json a su carpeta en el S3
    1.2 Si es necesario borrar todos los proyectos y participantes de los proyectos antes de importa
    2. pasar el nombre de la carpeta S3 como parámetro al comando import:cordis_json 
    3. ejecutar primero el comando app:move-projects-cordis-to-innovating, crear proyectos en innovating
    4. despues app:move-organizations-cordis-to-innovating crear los participantes y las emrpesas si es necesario en innovating(en caso de no existir)
*/
##########
class moveOrganizationsCordisToInnovating extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:move-organizations-cordis-to-innovating';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear los participantes de los proyectos y los lideres de los mismos, si la empresa no existe en innovating tambien la crea en entidades';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $cordisorganizations = \App\Models\OrganizationsCordisRawData::orderByDesc('created_at')->get();

        foreach($cordisorganizations as $organization){

            $cif = null;
            if($organization->country == "ES" && $organization->vatNumber != ""){
                $cif = str_replace("ES","", $organization->vatNumber);
            }elseif($organization->vatNumber != ""){
                $cif = $organization->vatNumber;
            }else{
                $cif = $organization->nutsCode;
            }

            if($cif !== null){
                ###CREAMOS EMPRESA EN INNOVATING SINO EXISTE
                $entidad = \App\Models\Entidad::where('CIF', $cif)->first();
                if(!$entidad){
                    $entidad = \App\Models\Entidad::where('vatNumber', $cif)->first();
                    if(!$entidad){
                        
                        $uri = cleanUriBeforeSave(str_replace(" ","-",mb_strtolower(quitar_tildes($organization->name))));
                        $naturalezas = array();
                        $esuniversidad = 0;
                        $escentro = 0;

                        if($organization->activityType == "PRC"){
                            array_push($naturalezas, "6668837");
                            $esuniversidad = 0;
                            $escentro = 0;
                        }
                        if($organization->activityType == "PUB"){
                            array_push($naturalezas, "6668840");
                            $esuniversidad = 1;
                            $escentro = 0;
                        }
                        if($organization->activityType == "REC"){
                            array_push($naturalezas, "6668838");
                            $esuniversidad = 0;
                            $escentro = 1;
                        }

                        $entidad = new \App\Models\Entidad();
                        try{
                            $entidad->Nombre = $organization->name;
                            $entidad->Web = ($organization->organizationURL != "") ? $organization->organizationURL : "";
                            $entidad->pais = $organization->country;
                            $entidad->CIF = $cif;
                            $entidad->vatNumber = ($organization->vatNumber != "") ? $organization->vatNumber : $organization->nutsCode;
                            $entidad->uri = $uri;
                            $entidad->Cnaes = "";
                            $entidad->Marca = ($organization->shortName != "") ? $organization->shortName : "";
                            $entidad->Direccion = $organization->street." ".$organization->city;
                            $entidad->Intereses = json_encode(["I+D","Innovación","Digitalización","Cooperación","Subcontratación"], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                            $entidad->entityUpdate = Carbon::now();
                            $entidad->naturalezaEmpresa = (empty($naturalezas)) ? json_encode(["6668837"]) : json_encode($naturalezas);
                            $entidad->esUniversidadPrivada = $esuniversidad;
                            $entidad->esCentroTecnologico = $escentro;
                            $entidad->save();
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                            return COMMAND::FAILURE;
                        }
                    }
                }

                ###CREAMOS PARTICIPANTE DEL PROYECTO SI ES LIDER LO AÑADIMOS COMO LIDER            
                $rawdata = \App\Models\ProjectsCordisRawData::where('project_id', $organization->project_id)->first();
                $proyecto = \App\Models\Proyectos::where('id_raw_data', $rawdata->id)->first();

                $participante = null;
                
                if($proyecto){
                    $participante = \App\Models\Participantes::where('cif_participante', $cif)->where('id_proyecto', $proyecto->id)->first();
                }

                if(!$participante){
                    $participante = new \App\Models\Participantes();

                    if($rawdata && $proyecto){

                        try{

                            if($organization->country == "ES" && $organization->vatNumber != ""){
                                    $participante->cif_participante = str_replace("ES","", $organization->vatNumber);
                                }elseif($organization->vatNumber != ""){
                                    $participante->cif_participante = $organization->vatNumber;
                                }else{
                                    $participante->cif_participante = $organization->nutsCode;
                                }
                                $participante->nombre_participante = $organization->name;
                                $participante->id_proyecto = $proyecto->id;
                                $participante->ayuda_eq_socio = $organization->netEcContribution;
                                $participante->presupuesto_socio = $organization->totalCost;
                                $participante->save();

                        }catch(Exception $e){
                            Log::error($e->getMessage());
                            return COMMAND::FAILURE;
                        }
                    }

                    if($organization->role == "coordinator"){

                        if($rawdata && $proyecto){
                            try{

                                \App\Models\Proyectos::where('id_raw_data', $rawdata->id)->update([
                                    'empresaPrincipal' => $cif
                                ]);
                            
                            }catch(Exception $e){
                                Log::error($e->getMessage());
                                return COMMAND::FAILURE;
                            }
                        }

                    }
                }
            }

        }

        return COMMAND::SUCCESS;
    }
}

