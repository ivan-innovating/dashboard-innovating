<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
class importCordisProjectsJson extends Command
{
    const HORIZONEUROPE = 6523;
    const H2020 = 6522;
    const FP7 = 6521;
    const FP6 = 6520;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:cordis_json {folder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa datos de cordis de los archivos json subidos al S3, organization.json, project.json, etc...';

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

        $folder = $this->argument('folder');

        if($folder === null){
            $this->info('No se ha especificado el argumento directorio de los json');
            return COMMAND::FAILURE;
        }

        $lastimport = \App\Models\Settings::where('group', 'scrapper')->where('name', 'cordis_lastimport_'.$folder)->first();

        if($lastimport){
            $checkdate = Carbon::parse(json_decode($lastimport->payload, true)['fecha'])->subDays(2);
        }else{
            $checkdate = Carbon::now()->subDays(2);
        }
        
        /*if(Storage::disk('s3_files')->exists('proyectos/import/'.$folder.'/organization.json')){
            
            $last_modified = Storage::disk('s3_files')->lastModified('proyectos/import/'.$folder.'/organization.json');
            $fecha = Carbon::parse(Carbon::createFromTimestamp($last_modified)->toDateTimeString())->addDays(1); 

            if($fecha > $checkdate){
                $organizations = json_decode(Storage::disk('s3_files')->get('proyectos/import/'.$folder.'/organization.json'), true);
                foreach($organizations as $organization){

                    if($organization['vatNumber'] !== null && $organization['vatNumber'] !== ""){
                        $organizationrawdata = \App\Models\OrganizationsCordisRawData::where('project_id', $organization['projectID'])->where('vatNumber', $organization['vatNumber'])->first();
                    }else{
                        $organizationrawdata = \App\Models\OrganizationsCordisRawData::where('project_id', $organization['projectID'])->where('nutsCode', $organization['nutsCode'])->first();
                    }

                    if(!$organizationrawdata){
                        $organizationrawdata = new \App\Models\OrganizationsCordisRawData();
                    }

                    try{
                        $organizationrawdata->project_id = $organization['projectID'];
                        $organizationrawdata->SME = $organization['SME'];
                        $organizationrawdata->active = $organization['active'];
                        $organizationrawdata->activityType = $organization['activityType'];
                        $organizationrawdata->city = $organization['city'];
                        $organizationrawdata->contactForm = $organization['contactForm'];
                        $organizationrawdata->contentUpdateDate = $organization['contentUpdateDate'];
                        $organizationrawdata->ecContribution = ($organization['ecContribution'] != "") ? $organization['ecContribution'] : 0;
                        $organizationrawdata->country = $organization['country'];
                        $organizationrawdata->endOfParticipation =  $organization['endOfParticipation'];
                        $organizationrawdata->geolocation = $organization['geolocation'];
                        $organizationrawdata->name = substr($organization['name'],0, 150);
                        $organizationrawdata->netEcContribution = ($organization['netEcContribution'] != "") ? $organization['netEcContribution'] : 0;
                        $organizationrawdata->nutsCode = $organization['nutsCode'];
                        $organizationrawdata->order = $organization['order'];
                        $organizationrawdata->organisationID = $organization['organisationID'];
                        $organizationrawdata->organizationURL = $organization['organizationURL'];
                        $organizationrawdata->postCode = $organization['postCode'];
                        $organizationrawdata->projectAcronym = $organization['projectAcronym'];
                        $organizationrawdata->rcn = $organization['rcn'];
                        $organizationrawdata->shortName = substr($organization['shortName'], 0 , 100);
                        $organizationrawdata->street = $organization['street'];
                        $organizationrawdata->role = $organization['role'];
                        $organizationrawdata->totalCost = ($organization['totalCost'] != "") ? $organization['totalCost'] : 0;
                        $organizationrawdata->vatNumber = $organization['vatNumber'];
                        $organizationrawdata->save();
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        continue;
                    }

                }

                if(!$lastimport){
                    $lastimport = new \App\Models\Settings();                    
                }

                try{
                    $lastimport->group = "scrapper";
                    $lastimport->name = "cordis_lastimport_".$folder;
                    $lastimport->locked = 0;
                    $lastimport->payload = json_encode(['fecha' => $fecha]);
                    $lastimport->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return COMMAND::FAILURE;
                }
            }

        }*/

        if(Storage::disk('s3_files')->exists('proyectos/import/'.$folder.'/project.json')){
            
            $last_modified = Storage::disk('s3_files')->lastModified('proyectos/import/'.$folder.'/project.json');
            $fecha = Carbon::createFromTimestamp($last_modified); 

            if($fecha > $checkdate){

                $projects = json_decode(Storage::disk('s3_files')->get('proyectos/import/'.$folder.'/project.json'), true);

                foreach($projects as $project){

                    $projectrawdata = \App\Models\ProjectsCordisRawData::where('project_id', $project['id'])->first();

                    if(!$projectrawdata){
                        $projectrawdata = new \App\Models\ProjectsCordisRawData();
                    }

                    $idOrganismo = self::HORIZONEUROPE;
                    if($folder == "h2020"){
                        $idOrganismo = self::H2020;
                    }elseif($folder == "fp7"){
                        $idOrganismo = self::FP7;
                    }elseif($folder == "fp7"){
                        $idOrganismo = self::FP6;
                    }

                    $titulo = substr(preg_replace("/[^A-Za-z0-9À-Ùà-ú@.!? ]/u",' ', str_replace(array("\r", "\n"), '', $project['title'])),0, 254);

                    try{
                       $projectrawdata->project_id = $project['id'];
                       $projectrawdata->id_organismo = $idOrganismo;
                       $projectrawdata->acronym =  mb_convert_encoding(str_replace(["“","”"], "",$project['acronym']), "UTF-8");
                       $projectrawdata->contentUpdateDate = $project['contentUpdateDate'];
                       $projectrawdata->ecMaxContribution = $project['ecMaxContribution'];
                       $projectrawdata->ecSignatureDate = $project['ecSignatureDate'];
                       $projectrawdata->endDate = ($project['endDate'] == "") ? $project['ecSignatureDate'] : $project['endDate'];
                       $projectrawdata->frameworkProgramme = $project['frameworkProgramme'];
                       $projectrawdata->fundingScheme = $project['fundingScheme'];
                       $projectrawdata->grantDoi = $project['grantDoi'];
                       $projectrawdata->legalBasis = $project['legalBasis'];
                       $projectrawdata->nature = $project['nature'];
                       $projectrawdata->objective = mb_convert_encoding(str_replace(["“","”"], "", $project['objective']), "UTF-8");
                       $projectrawdata->rcn = $project['rcn'];
                       $projectrawdata->status = $project['status'];
                       $projectrawdata->subCall = $project['subCall'];
                       $projectrawdata->title = $titulo;
                       $projectrawdata->topics = $project['topics'];
                       $projectrawdata->totalCost = $project['totalCost'];
                       $projectrawdata->save();
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        return COMMAND::FAILURE;
                    }
                }

                if(!$lastimport){
                    $lastimport = new \App\Models\Settings();
                }
                
                try{
                    $lastimport->group = "scrapper";
                    $lastimport->name = "cordis_lastimport_".$folder;
                    $lastimport->locked = 0;
                    $lastimport->payload = json_encode(['fecha' => $fecha]);
                    $lastimport->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return COMMAND::FAILURE;
                }
            }
            
        }

        if(Storage::disk('s3_files')->exists('proyectos/import/'.$folder.'/euroSciVoc.json')){
            
            $last_modified = Storage::disk('s3_files')->lastModified('proyectos/import/'.$folder.'/euroSciVoc.json');
            $fecha = Carbon::createFromTimestamp($last_modified)->toDateTimeString(); 
            
            if($fecha > $checkdate){
                $scivoc = json_decode(Storage::disk('s3_files')->get('proyectos/import/'.$folder.'/euroSciVoc.json'), true);

                foreach($scivoc as $sci){

                    $projectrawdata = \App\Models\ProjectsCordisRawData::where('project_id', $sci['projectID'])->first();
                    if(!$projectrawdata){
                        continue;
                    }

                    if($projectrawdata->keywords != ""){
                        $keywords = json_decode($projectrawdata->keywords, true);
                    }else{
                        $keywords = array();
                    }

                    $customkeywords = explode("/",$sci['euroSciVocPath']);
                    if(!empty($customkeywords)){
                        foreach($customkeywords as $keyword){
                            if(!in_array($keyword, $keywords)){
                                array_push($keywords, $keyword);
                            }
                        }    
                    }

                    if(!in_array($sci['euroSciVocTitle'], $keywords)){
                        array_push($keywords, $sci['euroSciVocTitle']);
                    }

                    try{
                        $projectrawdata->keywords = json_encode($keywords, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT);
                        $projectrawdata->save();
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        return COMMAND::FAILURE;
                    }
                }

                if(!$lastimport){
                    $lastimport = new \App\Models\Settings();
                }
                
                try{
                    $lastimport->group = "scrapper";
                    $lastimport->name = "cordis_lastimport_".$folder;
                    $lastimport->locked = 0;
                    $lastimport->payload = json_encode(['fecha' => $fecha]);
                    $lastimport->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return COMMAND::FAILURE;
                }
            }
        }

        if(Storage::disk('s3_files')->exists('proyectos/import/'.$folder.'/webLink.json')){

            $last_modified = Storage::disk('s3_files')->lastModified('proyectos/import/'.$folder.'/webLink.json');
            $fecha = Carbon::createFromTimestamp($last_modified)->toDateTimeString(); 

            if($fecha > $checkdate){
                $weblinks = json_decode(Storage::disk('s3_files')->get('proyectos/import/'.$folder.'/webLink.json'), true);

                foreach($weblinks as $weblink){
                    $projectrawdata = \App\Models\ProjectsCordisRawData::where('project_id', $weblink['projectID'])->first();
                    if(!$projectrawdata){
                        continue;
                    }

                    try{
                        $projectrawdata->physUrl = $weblink['physUrl'];
                        $projectrawdata->save();
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        return COMMAND::FAILURE;
                    }

                }

                if(!$lastimport){
                    $lastimport = new \App\Models\Settings();
                }
                
                try{
                    $lastimport->group = "scrapper";
                    $lastimport->name = "cordis_lastimport_".$folder;
                    $lastimport->locked = 0;
                    $lastimport->payload = json_encode(['fecha' => $fecha]);
                    $lastimport->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return COMMAND::FAILURE;
                }
            }
            
        }

        if(Storage::disk('s3_files')->exists('proyectos/import/'.$folder.'/legalBasis.json')){

            $last_modified = Storage::disk('s3_files')->lastModified('proyectos/import/'.$folder.'/legalBasis.json');
            $fecha = Carbon::createFromTimestamp($last_modified)->toDateTimeString(); 
            
            if($fecha > $checkdate){
                $legalbasis = json_decode(Storage::disk('s3_files')->get('proyectos/import/'.$folder.'/legalBasis.json'), true);

                foreach($legalbasis as $legal){
                    $projectrawdata = \App\Models\ProjectsCordisRawData::where('project_id', $legal['projectID'])->first();
                    if(!$projectrawdata){
                        continue;
                    }

                    if($projectrawdata->keywords != ""){
                        $keywords = json_decode($projectrawdata->keywords, true);
                    }else{
                        $keywords = array();
                    }

                    if(!in_array($legal['title'], $keywords)){
                        array_push($keywords, $legal['title']);
                    }

                    try{
                        $projectrawdata->keywords = json_encode($keywords, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT);
                        $projectrawdata->save();
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        return COMMAND::FAILURE;
                    }

                }

                if(!$lastimport){
                    $lastimport = new \App\Models\Settings();
                }
                
                try{
                    $lastimport->group = "scrapper";
                    $lastimport->name = "cordis_lastimport_".$folder;
                    $lastimport->locked = 0;
                    $lastimport->payload = json_encode(['fecha' => $fecha]);
                    $lastimport->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return COMMAND::FAILURE;
                }
            }
        }

        $this->info(now());

        return COMMAND::SUCCESS;
    }
}
