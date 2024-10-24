<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GetConvocatoriasEU extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:convocatorias_eu {organismo} {single?} {id?} {update?} {closed?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obtiene via guzzle todas las convocatorias con los filtros que necesitamos o una convocatoria mediante el json de datos individuales';

    
    public $client;
    public $baseurl;
    public $faceturl;
    public $topicurl;
    public $jsonurl;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->client = new GuzzleHttp\Client(
            [
                'headers' => [
                    'Cache-Control' => 'no-cache'
                ],
                #'debug' => 'true',
            ]
        );
        $this->baseurl = "https://api.tech.ec.europa.eu/search-api/prod/rest/search?apiKey=SEDIA&text=***&pageSize=100&pageNumber=";
        $this->faceturl = "https://api.tech.ec.europa.eu/search-api/prod/rest/facet?apiKey=SEDIA&text=****";
        $this->topicurl = "https://api.tech.ec.europa.eu/search-api/prod/rest/search?apiKey=SEDIA&text=";
        $this->jsonurl = "https://ec.europa.eu/info/funding-tenders/opportunities/data/topicDetails/";
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        
        #FOR TESTS: id = 
        ### VALORES IDS en las llamadas $response = $this->client->post($this->faceturl, []); php artisan get:convocatorias_eu 167264 > facets.txt
        /*foreach($data->facets as $d){
            echo $d->name." => [\n";
            foreach($d->values as $v){
                echo "\t".$v->rawValue.":".$v->value.":".$v->count."\n";
            }
            echo " ], \n";
        }*/
        ### FIN VALORES IDS en llamadas
        $single = $this->argument('single');
        $id = $this->argument('id');
        $update = $this->argument('update');
        $closed = $this->argument('closed');
        $organismo = $this->argument('organismo');

        $this->info(now());
        if(isset($closed) && $closed != "" && $closed == "closed"){

            try{
                $response = $this->client->post($this->baseurl."1", [
                    GuzzleHttp\RequestOptions::MULTIPART => [
                        [
                            'name'     => 'query',
                            'contents' => Storage::disk('s3_files')->get('/eusearch/params-h2020.json'),
                            'headers'  => ['Content-Type' => 'application/json']
                        ],
                        [
                            'name'     => 'sort',
                            'contents' => Storage::disk('s3_files')->get('/eusearch/sort.json'),
                            'headers'  => ['Content-Type' => 'application/json']
                        ],
                        [
                            'name'     => 'languages',
                            'contents' => Storage::disk('s3_files')->get('/eusearch/languages.json'),
                            'headers'  => ['Content-Type' => 'application/json']

                        ]
                    ]                                           
                ]);
                $body = $response->getBody();
                $data = json_decode($body->getContents());
            }catch(ClientException $e){
                Log::error($e->getResponse()->getBody()->getContents());            
                return COMMAND::FAILURE;
            }catch (ServerException $e){
                Log::error($e->getResponse()->getBody()->getContents());
                return COMMAND::FAILURE;
            }

            if(isset($single) && $single != "" && $single == "single"){
                $this->saveData($data, "convocatoria", $organismo);
            }else{
                $this->saveData($data, "ayudas", $organismo);
            }
          
            $chunks = (int) round($data->totalResults/$data->pageSize+1, 0);

            for($i = 2; $i<= $chunks; $i++){

                try{
                    $response = $this->client->post($this->baseurl.$i, [
                        GuzzleHttp\RequestOptions::MULTIPART => [
                            [
                                'name'     => 'query',
                                'contents' => Storage::disk('s3_files')->get('/eusearch/params-closed.json'),
                                'headers'  => ['Content-Type' => 'application/json']
                            ],
                            [
                                'name'     => 'sort',
                                'contents' => Storage::disk('s3_files')->get('/eusearch/sort.json'),
                                'headers'  => ['Content-Type' => 'application/json']
                            ],
                            [
                                'name'     => 'languages',
                                'contents' => Storage::disk('s3_files')->get('/eusearch/languages.json'),
                                'headers'  => ['Content-Type' => 'application/json']
                            ]
                        ]                                           
                    ]);
                    $body = $response->getBody();
                    $data = json_decode($body->getContents());
                }catch(ClientException $e){
                    Log::error($e->getMessage());            
                    return COMMAND::FAILURE;
                }catch (ServerException $e){
                    Log::error($e->getMessage());
                    return COMMAND::FAILURE;
                }

                $this->saveData($data, "ayudas", $organismo);
            }

        }elseif(isset($single) && $single != "" && $single == "single"){    

            if(isset($id) && $id != ""){
                dd($id);
            }else{
                $convocatoriaseuropeas = \App\Models\ConvocatoriasEURawData::where('organismo',$organismo)->whereNull('callTitle')->get();
                foreach($convocatoriaseuropeas as $convocatoria){

                    $id = ($convocatoria->callIdentifier === null)? $convocatoria->identifier : $convocatoria->callIdentifier;
                    try{                        
                        $response = $this->client->post($this->topicurl.$id, [
                            GuzzleHttp\RequestOptions::MULTIPART => [
                                [
                                    'name'     => 'languages',
                                    'contents' => Storage::disk('s3_files')->get('/eusearch/languages.json'),
                                    'headers'  => ['Content-Type' => 'application/json']
                                ]
                            ]                                                                          
                        ]);
                        $body = $response->getBody();
                        $data = json_decode($body->getContents());
                    }catch(ClientException $e){
                        Log::error($e->getMessage());            
                        continue;
                    }catch (ServerException $e){
                        Log::error($e->getMessage());
                        continue;
                    }
                    if(!empty($data->results)){
                        $this->saveData($data, "convocatoria", $organismo);
                    }
                }
            }            
        }elseif(isset($single) && $single != "" && $single == "json"){

            $convocatoriaseuropeas = \App\Models\ConvocatoriasEURawData::where('organismo',$organismo)->get();
            foreach($convocatoriaseuropeas as $key => $convocatoria){
                try{                        
                    $response = $this->client->get($this->jsonurl.mb_strtolower($convocatoria->identifier).".json", []);
                    $body = $response->getBody();
                    $data = json_decode($body->getContents());
                }catch(ClientException $e){
                    Log::info($key.": client");
                    Log::error($e->getMessage());            
                    continue;
                }catch (ServerException $e){
                    Log::info($key.": server");
                    Log::error($e->getMessage());
                    continue;
                }
                
                if(!empty($data->TopicDetails)){
                    $this->saveData($data, "topicdetails", $organismo);
                }
            }
        }else{

            $paramfile = "params.json";
            if(isset($update) && $update == 1){
                $paramfile = "params-update.json";
            }

            try{
                $response = $this->client->post($this->baseurl."1", [
                    GuzzleHttp\RequestOptions::MULTIPART => [
                        [
                            'name'     => 'query',
                            'contents' => Storage::disk('s3_files')->get('/eusearch/'.$paramfile),
                            'headers'  => ['Content-Type' => 'application/json']
                        ],
                        [
                            'name'     => 'sort',
                            'contents' => Storage::disk('s3_files')->get('/eusearch/sort.json'),
                            'headers'  => ['Content-Type' => 'application/json']
                        ],
                        [
                            'name'     => 'languages',
                            'contents' => Storage::disk('s3_files')->get('/eusearch/languages.json'),
                            'headers'  => ['Content-Type' => 'application/json']

                        ]
                    ]                                           
                ]);
                $body = $response->getBody();
                $data = json_decode($body->getContents());
            }catch(ClientException $e){
                Log::error($e->getResponse()->getBody()->getContents());            
                return COMMAND::FAILURE;
            }catch (ServerException $e){
                Log::error($e->getResponse()->getBody()->getContents());
                return COMMAND::FAILURE;
            }

            $this->saveData($data, "ayudas", $organismo);
          
            $chunks = (int) round($data->totalResults/$data->pageSize+1, 0);

            for($i = 2; $i<= $chunks; $i++){

                $paramfile = "params.json";
                if(isset($update) && $update == 1){
                    $paramfile = "params-update.json";
                }

                try{
                    $response = $this->client->post($this->baseurl.$i, [
                        GuzzleHttp\RequestOptions::MULTIPART => [
                            [
                                'name'     => 'query',
                                'contents' => Storage::disk('s3_files')->get('/eusearch/'.$paramfile),
                                'headers'  => ['Content-Type' => 'application/json']
                            ],
                            [
                                'name'     => 'sort',
                                'contents' => Storage::disk('s3_files')->get('/eusearch/sort.json'),
                                'headers'  => ['Content-Type' => 'application/json']
                            ],
                            [
                                'name'     => 'languages',
                                'contents' => Storage::disk('s3_files')->get('/eusearch/languages.json'),
                                'headers'  => ['Content-Type' => 'application/json']
                            ]
                        ]                                           
                    ]);
                    $body = $response->getBody();
                    $data = json_decode($body->getContents());
                }catch(ClientException $e){
                    Log::error($e->getMessage());            
                    return COMMAND::FAILURE;
                }catch (ServerException $e){
                    Log::error($e->getMessage());
                    return COMMAND::FAILURE;
                }

                $this->saveData($data, "ayudas", $organismo);
            }

        }

        $this->info(now());

        return COMMAND::SUCCESS;
    }

    private function saveData($data, $type, $organismo){

        if($type == "ayudas"){

            foreach($data->results as $convocatoria){

                if(isset($convocatoria->reference)){                
                    $rawDataEuropa = \App\Models\ConvocatoriasEURawData::where('reference', $convocatoria->reference)->first();
                }                   
                            
                if($rawDataEuropa){
                    try{
                        $rawDataEuropa->reference = $convocatoria->reference;
                        $rawDataEuropa->organismo = $organismo;
                        $rawDataEuropa->url = $convocatoria->url;                
                        $rawDataEuropa->language = $convocatoria->language;
                        $rawDataEuropa->summary = $convocatoria->summary;
                        $rawDataEuropa->DATASOURCE = $convocatoria->database;
                        $rawDataEuropa->programmePeriod = json_encode($convocatoria->metadata->programmePeriod);
                        $rawDataEuropa->url_json = $convocatoria->metadata->url[0];
                        $rawDataEuropa->startDate = $convocatoria->metadata->startDate[0];
                        $rawDataEuropa->title = $convocatoria->metadata->title[0];
                        $rawDataEuropa->frameworkProgramme = $convocatoria->metadata->frameworkProgramme[0];
                        $rawDataEuropa->publicationDocuments = (isset($convocatoria->metadata->publicationDocuments)) ? $convocatoria->metadata->publicationDocuments[0] : null;
                        $rawDataEuropa->callIdentifier = (isset($convocatoria->metadata->callIdentifier)) ? $convocatoria->metadata->callIdentifier[0] : null;
                        $rawDataEuropa->identifier = (isset($convocatoria->metadata->identifier)) ? $convocatoria->metadata->identifier[0] : null;
                        $rawDataEuropa->cenTagsA = (isset($convocatoria->metadata->cenTagsA)) ? json_encode($convocatoria->metadata->cenTagsA) : null;
                        $rawDataEuropa->typesOfAction = (isset($convocatoria->metadata->typesOfAction[0])) ? $convocatoria->metadata->typesOfAction[0] : null;
                        $rawDataEuropa->deadlineDate = (isset($convocatoria->metadata->deadlineDate)) ? json_encode($convocatoria->metadata->deadlineDate) : null;
                        $rawDataEuropa->typeOfMGAs = (isset($convocatoria->metadata->typeOfMGAs[0])) ? $convocatoria->metadata->typeOfMGAs[0] : null;
                        $rawDataEuropa->destinationGroup = (isset($convocatoria->metadata->destinationGroup[0])) ? $convocatoria->metadata->destinationGroup[0] : null;
                        $rawDataEuropa->destination = (isset($convocatoria->metadata->destination[0])) ? $convocatoria->metadata->destination[0] : null;
                        $rawDataEuropa->focusArea = (isset($convocatoria->metadata->focusArea[0])) ? $convocatoria->metadata->focusArea[0] : null;
                        $rawDataEuropa->keywords = (isset($convocatoria->metadata->keywords[0])) ? $convocatoria->metadata->keywords[0] : null;
                        $rawDataEuropa->save();
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        return;
                    }
                }else{

                    try{
                        $rawDataEuropa = new \App\Models\ConvocatoriasEURawData();
                        $rawDataEuropa->reference = $convocatoria->reference;
                        $rawDataEuropa->url = $convocatoria->url;                
                        $rawDataEuropa->language = $convocatoria->language;
                        $rawDataEuropa->summary = $convocatoria->summary;
                        $rawDataEuropa->DATASOURCE = $convocatoria->database;
                        $rawDataEuropa->programmePeriod = json_encode($convocatoria->metadata->programmePeriod);
                        $rawDataEuropa->url_json = $convocatoria->metadata->url[0];
                        $rawDataEuropa->startDate = $convocatoria->metadata->startDate[0];
                        $rawDataEuropa->title = $convocatoria->metadata->title[0];
                        $rawDataEuropa->frameworkProgramme = $convocatoria->metadata->frameworkProgramme[0];
                        $rawDataEuropa->publicationDocuments = (isset($convocatoria->metadata->publicationDocuments)) ? $convocatoria->metadata->publicationDocuments[0] : null;
                        $rawDataEuropa->callIdentifier = (isset($convocatoria->metadata->callIdentifier)) ? $convocatoria->metadata->callIdentifier[0] : null;
                        $rawDataEuropa->identifier = (isset($convocatoria->metadata->identifier)) ? $convocatoria->metadata->identifier[0] : null;
                        $rawDataEuropa->cenTagsA = (isset($convocatoria->metadata->cenTagsA)) ? json_encode($convocatoria->metadata->cenTagsA) : null;
                        $rawDataEuropa->typesOfAction = (isset($convocatoria->metadata->typesOfAction[0])) ? $convocatoria->metadata->typesOfAction[0] : null;
                        $rawDataEuropa->deadlineDate = (isset($convocatoria->metadata->deadlineDate)) ? json_encode($convocatoria->metadata->deadlineDate) : null;
                        $rawDataEuropa->typeOfMGAs = (isset($convocatoria->metadata->typeOfMGAs[0])) ? $convocatoria->metadata->typeOfMGAs[0] : null;
                        $rawDataEuropa->destinationGroup = (isset($convocatoria->metadata->destinationGroup[0])) ? $convocatoria->metadata->destinationGroup[0] : null;
                        $rawDataEuropa->destination = (isset($convocatoria->metadata->destination[0])) ? $convocatoria->metadata->destination[0] : null;
                        $rawDataEuropa->focusArea = (isset($convocatoria->metadata->focusArea[0])) ? $convocatoria->metadata->focusArea[0] : null;
                        $rawDataEuropa->keywords = (isset($convocatoria->metadata->keywords[0])) ? $convocatoria->metadata->keywords[0] : null;
                        $rawDataEuropa->save();
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        return;
                    }
                }

            }
        }

        if($type == "convocatoria"){
            foreach($data->results as $convocatoria){

                if(isset($convocatoria->metadata->callIdentifier)){
                    try{                    
                        $rawDataEuropa = \App\Models\ConvocatoriasEURawData::where('callIdentifier',  $convocatoria->metadata->callIdentifier[0])
                        ->where('reference', $convocatoria->reference)->first();
                    }catch(Exception $e){
                        Log::error("Convocatoria Europea Raw data no encontrada: ".$convocatoria->reference);
                        return;
                    }

                    if($rawDataEuropa){

                        $callTitle = (isset($convocatoria->callTitle)) ? $convocatoria->callTitle : null;
                        if($callTitle === null){
                            $callTitle = $rawDataEuropa->title;
                        }

                        $callIdentifier = (isset($convocatoria->metadata->callIdentifier)) ? $convocatoria->metadata->callIdentifier[0] : $rawDataEuropa->callIdentifier;
                        if($callIdentifier === null){
                            $callIdentifier = (isset($convocatoria->metadata->identifier)) ? $convocatoria->metadata->identifier[0] : $rawDataEuropa->identifier;
                        }

                        try{                                                          
                            $rawDataEuropa->ccm2Id = (isset($convocatoria->ccm2Id)) ? $convocatoria->ccm2Id : null;
                            $rawDataEuropa->cftId = (isset($convocatoria->cftId)) ? $convocatoria->cftId : null;
                            $rawDataEuropa->callTitle = $callTitle;
                            $rawDataEuropa->callccm2Id = (isset($convocatoria->callccm2Id)) ? $convocatoria->callccm2Id : null;
                            $rawDataEuropa->allowPartnerSearch = (isset($convocatoria->allowPartnerSearch)) ? $convocatoria->allowPartnerSearch : null;
                            $rawDataEuropa->programmeDivision = (isset($convocatoria->programmeDivision)) ? json_encode($convocatoria->programmeDivision) : null;
                            $rawDataEuropa->destinationDetails = (isset($convocatoria->destinationDetails)) ? $convocatoria->destinationDetails : null;
                            $rawDataEuropa->destinationDescription = (isset($convocatoria->destinationDescription)) ? $convocatoria->destinationDescription : null;
                            $rawDataEuropa->topicMGAs = (isset($convocatoria->topicMGAs)) ? json_encode($convocatoria->topicMGAs) : null;
                            $rawDataEuropa->sme = (isset($convocatoria->sme)) ? $convocatoria->sme : null;
                            $rawDataEuropa->status = (isset($convocatoria->actions[0]->status)) ? $convocatoria->actions[0]->status->abbreviation : 'Closed';
                            $rawDataEuropa->callIdentifier = $callIdentifier;
                            $rawDataEuropa->identifier = (isset($convocatoria->metadata->identifier)) ? $convocatoria->metadata->identifier[0] : $rawDataEuropa->identifier;
                            $rawDataEuropa->types = (isset($convocatoria->metadata->types)) ? $convocatoria->metadata->types : null;
                            $rawDataEuropa->typeOfAction = (isset($convocatoria->types->typeOfAction)) ? $convocatoria->types->typeOfAction : null;
                            $rawDataEuropa->typeOfMGA = (isset($convocatoria->types->typeOfMGA[0])) ? $convocatoria->types->typeOfMGA[0]->abbreviation : null;
                            $rawDataEuropa->plannedOpeningDate = (isset($convocatoria->actions[0]->plannedOpeningDate)) ? $convocatoria->actions[0]->plannedOpeningDate : null;
                            $rawDataEuropa->deadlineModel = (isset($convocatoria->metadata->deadlineModel)) ? $convocatoria->metadata->deadlineModel : null;                
                            $rawDataEuropa->description_html = (isset($convocatoria->budgetOverviewJSONItem->description)) ? strip_tags($convocatoria->budgetOverviewJSONItem->description) : null;
                            $rawDataEuropa->links = (isset($convocatoria->budgetOverviewJSONItem->links)) ? json_encode($convocatoria->budgetOverviewJSONItem->links) : null;
                            $rawDataEuropa->endDate = (isset($convocatoria->budgetOverviewJSONItem->links[0]->endDate)) ? $convocatoria->budgetOverviewJSONItem->links[0]->endDate : null;
                            $rawDataEuropa->mgaDescription = (isset($convocatoria->budgetOverviewJSONItem->links[0]->mgaDescription)) ? $convocatoria->budgetOverviewJSONItem->links[0]->mgaDescription : null;
                            $rawDataEuropa->mgaCode = (isset($convocatoria->budgetOverviewJSONItem->links[0]->mgaCode)) ? $convocatoria->budgetOverviewJSONItem->links[0]->mgaCode : null;
                            $rawDataEuropa->additionalDossiers = (isset($convocatoria->budgetOverviewJSONItem->additionalDossiers)) ? json_encode($convocatoria->budgetOverviewJSONItem->additionalDossiers) : null;
                            $rawDataEuropa->infoPackDossiers = (isset($convocatoria->budgetOverviewJSONItem->infoPackDossiers)) ? json_encode($convocatoria->budgetOverviewJSONItem->infoPackDossiers) : null;
                            $rawDataEuropa->latestInfos = (isset($convocatoria->budgetOverviewJSONItem->callDetailsJSONItem->latestInfos)) ? json_encode($convocatoria->budgetOverviewJSONItem->callDetailsJSONItem->latestInfos) : null;
                            $rawDataEuropa->hasForthcomingTopics = (isset($convocatoria->budgetOverviewJSONItem->callDetailsJSONItem->hasForthcomingTopics)) ? $convocatoria->budgetOverviewJSONItem->callDetailsJSONItem->hasForthcomingTopics : null;
                            $rawDataEuropa->hasOpenTopics = (isset($convocatoria->budgetOverviewJSONItem->callDetailsJSONItem->hasOpenTopics)) ? $convocatoria->budgetOverviewJSONItem->callDetailsJSONItem->hasOpenTopics : null;
                            $rawDataEuropa->allClosedTopics = (isset($convocatoria->budgetOverviewJSONItem->callDetailsJSONItem->allClosedTopics)) ? $convocatoria->budgetOverviewJSONItem->callDetailsJSONItem->allClosedTopics : null;
                            $rawDataEuropa->save();
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                            return;
                        }
                    }
                }

            }
        }

        if($type == "topicdetails"){
     
            $rawDataEuropa = \App\Models\ConvocatoriasEURawData::where('callIdentifier', $data->TopicDetails->callIdentifier)
            ->where('identifier', $data->TopicDetails->identifier)->first();
        
            if($rawDataEuropa){

                $actionMap = null;
                if(isset($data->TopicDetails->budgetOverviewJSONItem) && isset($data->TopicDetails->budgetOverviewJSONItem->budgetTopicActionMap) &&
                    !empty($data->TopicDetails->budgetOverviewJSONItem) && !empty($data->TopicDetails->budgetOverviewJSONItem->budgetTopicActionMap)){                        
                        foreach($data->TopicDetails->budgetOverviewJSONItem->budgetTopicActionMap as $key => $value){

                            if(is_object($value) && !is_array($value)){
                                continue;
                            }

                            $actionMap[$key]['action'] = (isset($value[0]->action)) ? $value[0]->action : null; 
                            $actionMap[$key]['deadlineModel'] = (isset($value[0]->deadlineModel)) ? $value[0]->deadlineModel : null;    
                            $actionMap[$key]['deadlineDates'] = (isset($value[0]->deadlineDates)) ? $value[0]->deadlineDates : null;   
                            $actionMap[$key]['budgetYearMap'] = (isset($value[0]->budgetYearMap)) ? $value[0]->budgetYearMap : null;
                            $actionMap[$key]['expectedGrants'] = (isset($value[0]->expectedGrants)) ? $value[0]->expectedGrants : null;    
                            $actionMap[$key]['minContribution'] = (isset($value[0]->minContribution)) ? $value[0]->minContribution : null;    
                            $actionMap[$key]['maxContribution'] = (isset($value[0]->maxContribution)) ? $value[0]->maxContribution : null;   
                            $actionMap[$key]['topicActionMap'] = (isset($value[0]->budgetTopicActionMap)) ? $value[0]->budgetTopicActionMap : null;                               
                        }
                    }

                $actions = null;
                $deadlineModel = null;
                $deadlineDates = null;
                $budgetYearMap = null;
                $expectedGrants = null;
                $minContribution = null;
                $maxContribution = null;
                $topicActionMap = null;

                if($actionMap !== null){
                    foreach($actionMap as $key => $value){
                        if($value['action'] !== null){
                            $actions[$key][] = $value['action'];
                        }
                        if($value['deadlineModel'] !== null){
                            $deadlineModel[$key][] = $value['deadlineModel'];
                        }
                        if($value['deadlineDates'] !== null){
                            $deadlineDates[$key][] = $value['deadlineDates'];   
                        }
                        if($value['budgetYearMap'] !== null){
                            $budgetYearMap[$key][] = $value['budgetYearMap'];
                        }
                        if($value['expectedGrants'] !== null){
                            $expectedGrants[$key][] = $value['expectedGrants'];
                        }
                        if($value['minContribution'] !== null){
                            $minContribution[$key][] = $value['minContribution'];
                        }
                        if($value['maxContribution'] !== null){
                            $maxContribution[$key][] = $value['maxContribution'];
                        }
                        if($value['topicActionMap'] !== null){
                            $topicActionMap[$key][] = $value['topicActionMap'];
                        }
                    }
                }

                $description = null;
                $conditions = null;
                if(isset($data->TopicDetails->description)){
                    $description = str_replace(['<p>','</p>'],["","\n\n"], strip_tags($data->TopicDetails->description, '<p>'));
                }
                if(isset($data->TopicDetails->conditions)){
                    $conditions = str_replace(['<p>','</p>'],["","\n\n"], strip_tags($data->TopicDetails->conditions, '<p>'));
                }

                try{                                              
                    
                    $rawDataEuropa->ccm2Id = (isset($data->TopicDetails->ccm2Id)) ? $data->TopicDetails->ccm2Id : $rawDataEuropa->ccm2Id;
                    $rawDataEuropa->cftId = (isset($data->TopicDetails->cftId)) ? $data->TopicDetails->cftId : $rawDataEuropa->cftId ;
                    $rawDataEuropa->callTitle = (isset($data->TopicDetails->callTitle)) ? $data->TopicDetails->callTitle : $rawDataEuropa->callTitle;
                    $rawDataEuropa->callccm2Id = (isset($data->TopicDetails->callccm2Id)) ? $data->TopicDetails->callccm2Id : $rawDataEuropa->callccm2Id;
                    $rawDataEuropa->allowPartnerSearch = (isset($data->TopicDetails->allowPartnerSearch)) ? $data->TopicDetails->allowPartnerSearch :  $rawDataEuropa->allowPartnerSearch;
                    $rawDataEuropa->programmeDivision = (isset($data->TopicDetails->programmeDivision)) ? json_encode($data->TopicDetails->programmeDivision) : $rawDataEuropa->programmeDivision;
                    $rawDataEuropa->topicMGAs = (!empty($data->TopicDetails->topicMGAs)) ? json_encode($data->TopicDetails->topicMGAs) : $rawDataEuropa->topicMGAs;
                    $rawDataEuropa->sme = (isset($data->TopicDetails->sme)) ? $data->TopicDetails->sme : null;
                    $rawDataEuropa->status = (isset($data->TopicDetails->actions[0]->status)) ? $data->TopicDetails->actions[0]->status->abbreviation : $rawDataEuropa->status;
                    $rawDataEuropa->typeOfAction = (isset($data->TopicDetails->actions[0]->types[0]->typeOfAction)) ? $data->TopicDetails->actions[0]->types[0]->typeOfAction : $rawDataEuropa->typeOfAction;
                    $rawDataEuropa->typeOfMGA = (isset($data->TopicDetails->actions[0]->types[0]->typeOfMGA[0]->abbreviation)) ? $data->TopicDetails->actions[0]->types[0]->typeOfMGA[0]->abbreviation : $rawDataEuropa->typeOfMGA;
                    $rawDataEuropa->plannedOpeningDate = (isset($data->TopicDetails->actions[0]->plannedOpeningDate)) ? $data->TopicDetails->actions[0]->plannedOpeningDate : $rawDataEuropa->plannedOpeningDate;                    
                    $rawDataEuropa->budgetTopicActionMap = (isset($data->TopicDetails->budgetOverviewJSONItem->budgetTopicActionMap)) ? json_encode($data->TopicDetails->budgetOverviewJSONItem->budgetTopicActionMap) : $rawDataEuropa->budgetTopicActionMap;
                    $rawDataEuropa->topicAction = ($actions !== null) ? json_encode($actions) : null;
                    $rawDataEuropa->topicActionMap = ($topicActionMap !== null) ? json_encode($topicActionMap) : null;
                    $rawDataEuropa->budgetYearMap = ($budgetYearMap !== null) ? json_encode($budgetYearMap) : null;
                    $rawDataEuropa->expectedGrants = ($expectedGrants !== null) ? json_encode($expectedGrants) : null;
                    $rawDataEuropa->minContribution = ($minContribution !== null) ? json_encode($minContribution) : null;
                    $rawDataEuropa->maxContribution = ($maxContribution !== null) ? json_encode($maxContribution) : null;
                    $rawDataEuropa->deadlineModel = ($deadlineModel !== null) ? json_encode($deadlineModel) : $rawDataEuropa->deadlineModel;                
                    $rawDataEuropa->deadlineDates = ($deadlineDates !== null) ? json_encode($deadlineDates) : $rawDataEuropa->deadlineDates;   
                    $rawDataEuropa->callIdentifier = (isset($convocatoria->TopicDetails->callIdentifier)) ? $convocatoria->TopicDetails->callIdentifier : $rawDataEuropa->callIdentifier;
                    $rawDataEuropa->identifier = (isset($convocatoria->TopicDetails->identifier)) ? $convocatoria->TopicDetails->identifier : $rawDataEuropa->identifier;
                    $rawDataEuropa->budgetYearsColumns = (isset($data->TopicDetails->budgetOverviewJSONItem->budgetYearsColumns)) ? json_encode($data->TopicDetails->budgetOverviewJSONItem->budgetYearsColumns) : $rawDataEuropa->budgetYearsColumns;
                    $rawDataEuropa->description_html = ($description !== null) ? $description : $rawDataEuropa->description_html;
                    $rawDataEuropa->conditions = ($conditions !== null) ? $conditions : $rawDataEuropa->conditions;
                    $rawDataEuropa->links = (isset($data->TopicDetails->links) && !empty($data->TopicDetails->links[0])) ? json_encode($data->TopicDetails->links[0]) : $rawDataEuropa->links;
                    $rawDataEuropa->endDate = (!empty($data->TopicDetails->links[0]) && isset($data->TopicDetails->links[0]->endDate)) ? $data->TopicDetails->links[0]->endDate : $rawDataEuropa->endDate;
                    $rawDataEuropa->startDate = (!empty($data->TopicDetails->links[0]) && isset($data->TopicDetails->links[0]->startDate)) ? $data->TopicDetails->links[0]->startDate : $rawDataEuropa->startDate;
                    $rawDataEuropa->mgaDescription = (!empty($data->TopicDetails->links[0]) && isset($data->TopicDetails->links[0]->mgaDescription)) ? $data->TopicDetails->links[0]->mgaDescription : $rawDataEuropa->mgaDescriptio;
                    $rawDataEuropa->mgaCode = (!empty($data->TopicDetails->links[0]) && isset($data->TopicDetails->links[0]->mgaCode)) ? $data->TopicDetails->links[0]->mgaCode : $rawDataEuropa->mgaCode;
                    $rawDataEuropa->additionalDossiers = (isset($data->TopicDetails->additionalDossiers)) ? json_encode($data->TopicDetails->additionalDossiers) : $rawDataEuropa->additionalDossiers;
                    $rawDataEuropa->infoPackDossiers = (isset($data->TopicDetails->infoPackDossiers)) ? json_encode($data->TopicDetails->infoPackDossiers) : $rawDataEuropa->infoPackDossiers;
                    $rawDataEuropa->latestInfos = (isset($data->TopicDetails->callDetailsJSONItem->latestInfos)) ? json_encode($data->TopicDetails->callDetailsJSONItem->latestInfos) : $rawDataEuropa->latestInfos;
                    $rawDataEuropa->hasForthcomingTopics = (isset($data->TopicDetails->callDetailsJSONItem->hasForthcomingTopics)) ? $data->TopicDetails->callDetailsJSONItem->hasForthcomingTopics : $rawDataEuropa->hasForthcomingTopics;
                    $rawDataEuropa->hasOpenTopics = (isset($data->TopicDetails->callDetailsJSONItem->hasOpenTopics)) ? $data->TopicDetails->callDetailsJSONItem->hasOpenTopics : $rawDataEuropa->hasOpenTopics;
                    $rawDataEuropa->allClosedTopics = (isset($data->TopicDetails->callDetailsJSONItem->allClosedTopics)) ? $data->TopicDetails->callDetailsJSONItem->allClosedTopics : $rawDataEuropa->allClosedTopics;
                    $rawDataEuropa->keywords = (isset($data->TopicDetails->keywords)) ? $data->TopicDetails->keywords : $rawDataEuropa->keywords;
                    $rawDataEuropa->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return;
                }
            }
            
        }

    }
}

