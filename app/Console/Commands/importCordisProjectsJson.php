<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class importCordisProjectsJson extends Command
{

    const ORGANISMO = 6523;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:cordis_json';

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

        $lastimport = \App\Models\Settings::where('group', 'scrapper')->where('name', 'cordis_lastimport')->first();

        if($lastimport){
            $checkdate = Carbon::parse(json_decode($lastimport->payload, true)['fecha'])->subDays(2);
        }else{
            $checkdate = Carbon::now()->subDays(2);
        }
        
        if(Storage::disk('s3_files')->exists('proyectos/import/organization.json')){
            
            $last_modified = Storage::disk('s3_files')->lastModified('proyectos/import/organization.json');
            $fecha = Carbon::parse(Carbon::createFromTimestamp($last_modified)->toDateTimeString())->addDays(1); 

            if($fecha > $checkdate){
                $organizations = json_decode(Storage::disk('s3_files')->get('proyectos/import/organization.json'), true);
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
                    $lastimport->name = "cordis_lastimport";
                    $lastimport->locked = 0;
                    $lastimport->payload = json_encode(['fecha' => $fecha]);
                    $lastimport->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return COMMAND::FAILURE;
                }
            }

        }

        if(Storage::disk('s3_files')->exists('proyectos/import/project.json')){
            
            $last_modified = Storage::disk('s3_files')->lastModified('proyectos/import/project.json');
            $fecha = Carbon::createFromTimestamp($last_modified); 

            if($fecha > $checkdate){

                $projects = json_decode(Storage::disk('s3_files')->get('proyectos/import/project.json'), true);

                foreach($projects as $project){

                    $projectrawdata = \App\Models\ProjectsCordisRawData::where('project_id', $project['id'])->first();

                    if(!$projectrawdata){
                        $projectrawdata = new \App\Models\ProjectsCordisRawData();
                    }

                    try{
                       $projectrawdata->project_id = $project['id'];
                       $projectrawdata->id_organismo = self::ORGANISMO;
                       $projectrawdata->acronym =  mb_convert_encoding(str_replace(["“","”"], "",$project['acronym']), "UTF-8");
                       $projectrawdata->contentUpdateDate = $project['contentUpdateDate'];
                       $projectrawdata->ecMaxContribution = $project['ecMaxContribution'];
                       $projectrawdata->ecSignatureDate = $project['ecSignatureDate'];
                       $projectrawdata->endDate = $project['endDate'];
                       $projectrawdata->frameworkProgramme = $project['frameworkProgramme'];
                       $projectrawdata->fundingScheme = $project['fundingScheme'];
                       $projectrawdata->grantDoi = $project['grantDoi'];
                       $projectrawdata->legalBasis = $project['legalBasis'];
                       $projectrawdata->nature = $project['nature'];
                       $projectrawdata->objective = mb_convert_encoding(str_replace(["“","”"], "", $project['objective']), "UTF-8");
                       $projectrawdata->rcn = $project['rcn'];
                       $projectrawdata->status = $project['status'];
                       $projectrawdata->subCall = $project['subCall'];
                       $projectrawdata->title = substr(mb_convert_encoding(str_replace(["“","”"], "", $project['title']), "UTF-8"),0,190);
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
                    $lastimport->name = "cordis_lastimport";
                    $lastimport->locked = 0;
                    $lastimport->payload = json_encode(['fecha' => $fecha]);
                    $lastimport->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return COMMAND::FAILURE;
                }
            }
            
        }

        if(Storage::disk('s3_files')->exists('proyectos/import/euroSciVoc.json')){
            
            $last_modified = Storage::disk('s3_files')->lastModified('proyectos/import/euroSciVoc.json');
            $fecha = Carbon::createFromTimestamp($last_modified)->toDateTimeString(); 
            
            if($fecha > $checkdate){
                $scivoc = json_decode(Storage::disk('s3_files')->get('proyectos/import/euroSciVoc.json'), true);

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
                    $lastimport->name = "cordis_lastimport";
                    $lastimport->locked = 0;
                    $lastimport->payload = json_encode(['fecha' => $fecha]);
                    $lastimport->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return COMMAND::FAILURE;
                }
            }
        }

        if(Storage::disk('s3_files')->exists('proyectos/import/webLink.json')){

            $last_modified = Storage::disk('s3_files')->lastModified('proyectos/import/webLink.json');
            $fecha = Carbon::createFromTimestamp($last_modified)->toDateTimeString(); 

            if($fecha > $checkdate){
                $weblinks = json_decode(Storage::disk('s3_files')->get('proyectos/import/webLink.json'), true);

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
                    $lastimport->name = "cordis_lastimport";
                    $lastimport->locked = 0;
                    $lastimport->payload = json_encode(['fecha' => $fecha]);
                    $lastimport->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return COMMAND::FAILURE;
                }
            }
            
        }

        if(Storage::disk('s3_files')->exists('proyectos/import/legalBasis.json')){

            $last_modified = Storage::disk('s3_files')->lastModified('proyectos/import/legalBasis.json');
            $fecha = Carbon::createFromTimestamp($last_modified)->toDateTimeString(); 
            
            if($fecha > $checkdate){
                $legalbasis = json_decode(Storage::disk('s3_files')->get('proyectos/import/legalBasis.json'), true);

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
                    $lastimport->name = "cordis_lastimport";
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
