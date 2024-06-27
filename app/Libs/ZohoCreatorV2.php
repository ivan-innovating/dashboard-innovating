<?php


namespace App\Libs;

use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpParser\Node\Stmt\TryCatch;


class ZohoCreatorV2
{
    public $access_token = '';

    function __construct() {

        $zoho = \App\Models\ZohoApi::first();
        if($zoho){
            $this->access_token = $zoho->access_token;
        }

    }

    public function getProfile($contact_id){

        $cache_key = 'profile_'.$contact_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($contact_id) {
            $res = $this->_getRecords('bonicontactos','ID=='.$contact_id);

            if($res){
                return $res[0];
            }

            return NULL;
        });

        return $value;

    }

    public function getSubcapitulosFromColaboradores($contact_id){

        return $this->_getRecords('bonisubcapitulos','Colaboradores.contains("'.$contact_id.'")');

    }

    public function getBonisubcapitulosFromId($subcapitulo_id){

        $cache_key = 'getBonisubcapitulosFromId_'.$subcapitulo_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($subcapitulo_id) {
            $res = $this->_getRecords('bonisubcapitulos','ID=='.$subcapitulo_id);

            if($res){
                return $res[0];
            }

            return NULL;
        });

        return $value;

    }

    public function getbonientidad($entidad_id){

        $cache_key = 'entidad_'.$entidad_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($entidad_id) {
            $res = $this->_getRecords('bonientidad','ID=='.$entidad_id);

            if($res){
                return $res[0];
            }

            return NULL;
        });

        return $value;

    }

    public function getperfilfin($id){

        $cache_key = 'getperfilfin_'.$id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($id) {
            $res = $this->_getRecords('boniperfilfin','ID=='.$id);

            if($res){
                return $res[0];
            }

            return NULL;
        });

        return $value;

    }

    public function getperfiltec($id){

        $cache_key = 'getperfiltec_'.$id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($id) {
            $res = $this->_getRecords('boniperfiltec','ID=='.$id);

            if($res){
                return $res[0];
            }

            return NULL;
        });

        return $value;

    }

    public function getProyecto($project_id){

        $cache_key = 'proyecto_'.$project_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($project_id) {
            $res = $this->_getRecords('boniproyectos','ID='.$project_id);

            if($res){
                return $res[0];
            }

            return NULL;
        });

        return $value;

    }

    public function getActividad($actividad_id){

        $cache_key = 'actividad_'.$actividad_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($actividad_id) {
            $res = $this->_getRecords('boniactividades','ID=='.$actividad_id);

            if($res){
                return $res[0];
            }

            return NULL;
        });

        return $value;

    }

    public function getItem($item_id){

        $cache_key = 'item_'.$item_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($item_id) {
            $res = $this->_getRecords('boniitems','ID=='.$item_id);

            if($res){
                return $res[0];
            }

            return NULL;
        });

        return $value;

    }

    public function updateProfile($contact_id,$data){

        $return = [
            'status' => false,
            'message' => ''
        ];

        $update_result = $this->_updateRecordByID('bonicontactos',$contact_id,$data);

        if($update_result['code'] == 3000){
            $return['status'] = true;
            $return['message'] = $update_result['message'];
        }else{
            $return['status'] = false;
            $return['message'] = $update_result['message'];
        }

        //Tanto si ha ido bien como ha ido mal voy a vaciar la cache por si acaso....
        $cache_key_profile = 'profile_'.$contact_id;
        Cache::forget($cache_key_profile);

        return $return;
    }

    public function updateBoniEntidadPdf360($entidad_id, $name){

        $client = new \GuzzleHttp\Client();

        $multipart_data = [
            [
                'name' => 'file',
                'contents' => Storage::disk('s3_files')->get('pdfs/analisis-360/'.$name),
                'filename' => "Analisis-360.pdf"
            ]
        ];

        try{           
            $res = $client->request('POST', 'https://creator.zoho.com/api/v2/sergio.galiano/erp/report/bonientidad/'.$entidad_id.'/AnalisisInnovating/upload', [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken '.$this->access_token
                ],
                'multipart' => $multipart_data,
               
            ]);

            if($res->getStatusCode() == 200){
                $data = json_decode((string)$res->getBody(),true);
            }else{
                Log::error("No ha devuelto status code 200: ".$res);
                return json_encode(['response' => 'KO']);
            }
        }catch (\GuzzleHttp\Exception\RequestException $e){
            Log::error($e->getMessage());
            return json_encode(['response' => 'KO']);
        }    

        try{
            $data_update = [
                'LastAnalisisInnovating' => Carbon::now()->format('d-m-Y H:i:s')
            ];   
            $this->_updateRecordByID('bonientidad',$entidad_id,$data_update);

        }catch (\GuzzleHttp\Exception\RequestException $e){
            Log::error($e->getMessage());
            return json_encode(['response' => 'KO']);
        }    

        return json_encode(['response' => 'OK']);
    }

    public function updateBoniEntidad($entidad_id,$data){

        $return = [
            'status' => false,
            'message' => ''
        ];

        $update_result = $this->_updateRecordByID('bonientidad',$entidad_id,$data);

        if($update_result['code'] == 3000){
            $return['status'] = true;
            $return['message'] = $update_result['message'];
        }else{
            $return['status'] = false;
            $return['message'] = $update_result['message'];
        }

        $cache_key = 'entidad_'.$entidad_id;
        Cache::forget($cache_key);

        return $return;
    }

    public function updateSubcapitulos($id,$data){

        $return = [
            'status' => false,
            'message' => ''
        ];

        $update_result = $this->_updateRecordByID('bonisubcapitulos',$id,$data);

        if($update_result['code'] == 3000){
            $return['status'] = true;
            $return['message'] = $update_result['message'];
        }else{
            $return['status'] = false;
            $return['message'] = $update_result['message'];
        }

        //Tanto si ha ido bien como ha ido mal voy a vaciar la cache por si acaso....
        //$cache_key_profile = 'profile_'.$contact_id;
        //Cache::forget($cache_key_profile);

        return $return;
    }

    public function updateProyectoGasto($id,$data){

        $return = [
            'status' => false,
            'message' => ''
        ];

        $update_result = $this->_updateRecordByID('bonigastosproyecto',$id,$data);

        if($update_result['code'] == 3000){
            $return['status'] = true;
            $return['message'] = $update_result['message'];
        }else{
            $return['status'] = false;
            $return['message'] = $update_result['message'];
        }
        return $return;
    }


    public function updateAyuda($ayudaId,$data){

        $return = [
            'status' => false,
            'message' => ''
        ];

        $update_result = $this->_updateRecordByID('boniayudas',$ayudaId,$data);

        if($update_result['code'] == 3000){
            $return['status'] = true;
            $return['message'] = $update_result['message'];
        }else{
            $return['status'] = false;
            $return['message'] = $update_result['message'];
        }

        return $return;
    }

    public function updateClasificaciones($id,$data){

        $return = [
            'status' => false,
            'message' => ''
        ];

        $update_result = $this->_updateRecordByID('boniclasificaciones',$id,$data);

        if($update_result['code'] == 3000){
            $return['status'] = true;
            $return['message'] = $update_result['message'];
        }else{
            $return['status'] = false;
            $return['message'] = $update_result['message'];
        }

        return $return;
    }

    public function updateBoniTeamwork($ID,$data){

        $return = [
            'status' => false,
            'message' => ''
        ];

        $update_result = $this->_updateRecordByID('boniteamworks',$ID,$data);

        if($update_result['code'] == 3000){
            $return['status'] = true;
            $return['message'] = $update_result['message'];
        }else{
            $return['status'] = false;
            $return['message'] = $update_result['message'];
        }

        return $return;
    }


    public function getActivities($activities_ids){

        $num_ids = count($activities_ids);
        if(is_array($activities_ids) && $num_ids > 0){

            $cache_key = 'activities_'.md5(serialize($activities_ids));

            $value = Cache::remember($cache_key, now()->addHours(2), function () use($activities_ids,$num_ids) {

                //calculamos el criteria....
                $criteria = '';

                if($num_ids > 1){
                    $criteria .= '';

                    $arrCriterials = [];
                    foreach ($activities_ids as $id) {
                        $arrCriterials[] = 'ID='.$id.'';
                    }
                    $criteria .= implode(' || ',$arrCriterials);

                    $criteria .= '';
                }else{
                    $criteria = 'ID='.$activities_ids[0].'';
                }

                $res = $this->_getRecords('boniactividades',$criteria);
                return $res;
            });

            return $value;

        }else{
            return false;
        }
    }

    public function getActivitiesFromEntidad($entidad_id){
        $res = $this->_getRecords('boniactividades','IDEntidad='.$entidad_id.' && IDTeamWork>0');
        return $res;
    }


    public function getbonitramitacionesFromEntidad($entidad_id){

        $cache_key = 'getbonitramitacionesFromEntidad_'.$entidad_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($entidad_id) {
            $res = $this->_getRecords('bonitramitaciones','Entidad.ID='.$entidad_id);

            return $res;
        });

        return $value;

    }

    public function getPerfilbusqueda(){

        $cache_key = 'getPerfilbusqueda';

        $value = Cache::remember($cache_key, now()->addHours(2), function () {
            $res = $this->_getRecords('perfilbusqueda');

            return $res;
        });

        return $value;

    }

    public function getElasticEntidadDate($date = '', $from = NULL,$limit = NULL){
        return $this->_getRecords('ElasticEntidad','ElasticLastUpdate >= "'.$date.'" && Intereses.ID > 0',$from,$limit);
        //para insertar solo algunas pruebas de Sergio
        //return $this->_getRecords('ElasticEntidad','Codigo > 0',$from,$limit);
    }

    public function getElasticEntidad($from = NULL,$limit = NULL){
        return $this->_getRecords('ElasticEntidad','Intereses.ID > 0',$from,$limit);
    }

    public function getElasticEntidadById($entidad_id){
        return $this->_getRecords('ElasticEntidad','ID = '.$entidad_id);
    }

    public function getElasticEncajes(){
        return $this->_getRecords('ElasticEncajes');
    }

    public function getElasticEncajeById($encaje_id){
        return $this->_getRecords('ElasticEncajes','ID = '.$encaje_id);
    }

    public function getElasticEncajeByLineaIdTipo($linea_id){
        return $this->_getRecords('ElasticEncajes','Linea.ID = '.$linea_id.' && Tipo != "Interna"');
    }

    public function getImputations($contact_id){

        $cache_key = 'imputations_'.$contact_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($contact_id) {

            //$inicioMes = now()->startOfMonth()->subMonth(4)->format('d-m-Y');

            //$res = $this->_getRecords('boniimputaciones','Contacto.ID='.$contact_id.' && InicioMes>="'.$inicioMes.'"');
            $res = $this->_getRecords('boniimputaciones','Contacto.ID='.$contact_id .' && Actividad.ID > 0');

            return $res;
        });

        return $value;

    }

    public function getImputation($imputation_id){

        $cache_key = 'imputation_'.$imputation_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($imputation_id) {

            //$inicioMes = now()->startOfMonth()->subMonth(4)->format('d-m-Y');

            //$res = $this->_getRecords('boniimputaciones','Contacto.ID='.$contact_id.' && InicioMes>="'.$inicioMes.'"');
            $res = $this->_getRecords('boniimputaciones','ID='.$imputation_id );
            if($res){
                return $res[0];
            }

            return NULL;
        });

        return $value;

    }

    public function getImputationsFromContactActividad($contact_id,$actividad_id){

        $cache_key = 'getImputationsFromContactActividad_'.$contact_id.'_'.$actividad_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($contact_id,$actividad_id) {

            $inicioMes = now()->startOfMonth()->subMonth(4)->format('d-m-Y');

            $res = $this->_getRecords('boniimputaciones','Contacto.ID='.$contact_id.' && InicioMes>="'.$inicioMes.'" && Actividad.ID='.$actividad_id);

            return $res;
        });

        return $value;

    }

    public function updateImputation($id,$data,$contact_id){


        $return = [
            'status' => false,
            'message' => ''
        ];


        $update_result = $this->_updateRecordByID('boniimputaciones',$id,$data);

        if($update_result['code'] == 3000){
            $return['status'] = true;
            $return['message'] = $update_result['message'];
        }else{
            $return['status'] = false;
            $return['message'] = $update_result['message'];
        }

        //Tanto si ha ido bien como ha ido mal voy a vaciar la cache por si acaso....
        $cache_key_imputation = 'imputations_'.$contact_id;
        Cache::forget($cache_key_imputation);

        return $return;


    }

    public function uploadFileCV($id,$file,$contact_id,$filename = 'Adjunto'){

        $return = [
            'status' => false,
            'message' => ''
        ];

        $client = new \GuzzleHttp\Client();

        $multipart_data = [
            [
                'name' => 'file',
                'contents' => fopen($file->path(), 'r'),
                'filename' => $file->getClientOriginalName()
            ]
        ];

        try{
            $res = $client->request('POST', 'https://creator.zoho.com/api/v2/sergio.galiano/erp/report/bonicontactos/'.$id.'/'.$filename.'/upload', [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken '.$this->access_token
                ],
                'multipart' => $multipart_data
            ]);

            if($res->getStatusCode() == 200){
                //Tanto si ha ido bien como ha ido mal voy a vaciar la cache por si acaso....
                $cache_key_imputation = 'cv_'.$id;
                Cache::forget($cache_key_imputation);

            }

        }catch (\GuzzleHttp\Exception\RequestException $e){
            $return['message'] = 'Error al conectar';
        }

        return $return;
    }

    public function uploadFileImputation($id,$file,$contact_id,$filename = 'Adjunto'){

        $return = [
            'status' => false,
            'message' => ''
        ];

        $client = new \GuzzleHttp\Client();

        $multipart_data = [
            [
                'name' => 'file',
                'contents' => fopen($file->path(), 'r'),
                'filename' => $file->getClientOriginalName()
            ]
        ];

        try{
            $res = $client->request('POST', 'https://creator.zoho.com/api/v2/sergio.galiano/erp/report/boniimputaciones/'.$id.'/'.$filename.'/upload', [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken '.$this->access_token
                ],
                'multipart' => $multipart_data
            ]);

            if($res->getStatusCode() == 200){

                /*$response_xml = simplexml_load_string($res->getBody());

                if((string)$response_xml->result->form->fileupdate->status == 'Success'){
                    $return['status'] = true;
                    $return['message'] = 'Success';
                }else{
                    $return['status'] = false;
                    $return['message'] = 'Error';
                }*/

                //Tanto si ha ido bien como ha ido mal voy a vaciar la cache por si acaso....
                $cache_key_imputation = 'imputations_'.$contact_id;
                Cache::forget($cache_key_imputation);
            }

        }catch (\GuzzleHttp\Exception\RequestException $e){
            $return['message'] = 'Error al conectar';
        }

        return $return;
    }

    public function uploadFileSubcapitulo($id,$file,$filename = 'Adjunto'){

        $return = [
            'status' => false,
            'message' => ''
        ];

        $client = new \GuzzleHttp\Client();

        $multipart_data = [
            [
                'name' => 'file',
                'contents' => fopen($file->path(), 'r'),
                'filename' => $file->getClientOriginalName()
            ]
        ];

        try{
            $res = $client->request('POST', 'https://creator.zoho.com/api/v2/sergio.galiano/erp/report/bonisubcapitulos/'.$id.'/'.$filename.'/upload', [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken '.$this->access_token
                ],
                'multipart' => $multipart_data
            ]);

            if($res->getStatusCode() == 200){

                /*$response_xml = simplexml_load_string($res->getBody());

                if((string)$response_xml->result->form->fileupdate->status == 'Success'){
                    $return['status'] = true;
                    $return['message'] = 'Success';
                }else{
                    $return['status'] = false;
                    $return['message'] = 'Error';
                }*/
            }

        }catch (\GuzzleHttp\Exception\RequestException $e){
            $return['message'] = 'Error al conectar';
        }

        return $return;
    }

    public function getTeamwork($teamwork_id){

        $cache_key = 'teamworksinfo_'.$teamwork_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($teamwork_id) {

            $res = $this->_getRecords('boniteamworks','ID='.$teamwork_id);

            if($res){
                return $res[0];
            }

            return NULL;
        });

        return $value;

    }

    public function getTeamworks($entidad_id){

        $cache_key = 'teamworks_'.$entidad_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($entidad_id) {

            $res = $this->_getRecords('boniteamworks','Entidad.ID='.$entidad_id);

            return $res;
        });

        return $value;

    }

    public function getTeamworksByManager($user_id){

        $cache_key = 'getTeamworksByManager_'.$user_id;

        $value = Cache::remember($cache_key, now()->addHour(2), function () use($user_id) {

            $res = $this->_getRecords('boniteamworks','Managers.ID.contains("'.$user_id.'")');

            return $res;
        });

        return $value;

    }

    public function getTeamworksByManagerAndId($user_id,$teamwork_id){

        $cache_key = 'getTeamworksByManagerAndId_'.$user_id.'_'.$teamwork_id;

        $value = Cache::remember($cache_key, now()->addHour(2), function () use($user_id,$teamwork_id) {
            $res = $this->_getRecords('boniteamworks','Managers.ID.contains("'.$user_id.'") && ID='.$teamwork_id);

            if($res){
                return $res[0];
            }

            return $res;
        });

        return $value;

    }


    public function getTeamworksTeam($teamworks_id){

        $cache_key = 'teamworks_'.$teamworks_id.'_teams';

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($teamworks_id) {

            $res = $this->_getRecords('bonicontactos','Teamwork.ID='.$teamworks_id);

            return $res;
        });

        return $value;

    }

    public function getBoniInstrucciones($entidad_id){

        $cache_key = 'getBoniInstrucciones_'.$entidad_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($entidad_id) {
            $res = $this->_getRecords('boniinstrucciones','Entidad.ID='.$entidad_id.' || Ambito == "Generico"');
            return $res;
        });

        return $value;

    }

    public function getBoniInstruccionesFromEntidadID($entidad_id,$id){

        $cache_key = 'getBoniInstruccionesFromEntidadID_'.$entidad_id.'_'.$id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($entidad_id,$id) {
            $res = $this->_getRecords('boniinstrucciones','Entidad.ID='.$entidad_id.' && ID='.$id);
            if($res){
                return $res[0];
            }
            return NULL;
        });

        return $value;

    }

    public function getBoniInstruccionesFromID($id){

        $cache_key = 'getBoniInstruccionesFromID'.'_'.$id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($id) {
            $res = $this->_getRecords('boniinstrucciones','ID='.$id);
            if($res){
                return $res[0];
            }
            return NULL;
        });

        return $value;

    }


    public function getBonicontactosFromEntidad($entidad_id){

        $res = $this->_getRecords('bonicontactos','Entidad.ID='.$entidad_id.' && Teamwork.ID>0');

        return $res;

    }

    public function getResponsablesFromEntidadTags($entidad_id,$tags){

        $res = $this->_getRecords('bonicontactos','Entidad.ID='.$entidad_id.' && Tags.contains("'.$tags.'")');

        return $res;

    }

    public function getMaxColaboradoresFromEntidad($entidad_id){

        $res = $this->_getRecords('bonicontactos','Entidad.ID='.$entidad_id.' && CalculoNoTecnico = "Notecnico" && Email.contains("@")');

        return $res;

    }

    public function getBonicontactosListadoActividadContains($id_actividad){

        $cache_key = 'getBonicontactosListadoActividadContains_'.$id_actividad;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($id_actividad) {

            $res = $this->_getRecords('bonicontactos','ListadoActividades.ID.contains("'.$id_actividad.'")');

            if($res){
                return $res;
            }else{
                return [];
            }
        });

        return $value;

    }

    public function getTeamworksActivities($teamworks_id, $from= 0, $limit = 10){

        $cache_key = 'teamworks_'.$teamworks_id.'_activities';

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($teamworks_id, $from, $limit) {

            $res = $this->_getRecords('boniactividades','TeamWorks.contains("'.$teamworks_id.'")');

            return $res;

        });

        return $value;

    }



    public function getEntidadActivities($entidad_id){

        $cache_key = 'admin_'.$entidad_id.'_activities';

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($entidad_id) {

            $res = $this->_getRecords('boniactividades','IDEntidad='.$entidad_id);
            return $res;
        });

        return $value;

    }

    public function getTeamworksExportXls($teamworks_id, $limit = NULL){

        if($limit){

            $cache_key = 'teamworks_xls_view'.$teamworks_id.'_imputations';

            $value = Cache::remember($cache_key, now()->addHours(2), function () use($teamworks_id, $limit) {

                $res = $this->_getRecords('boniimputaciones','Actividad.TeamWorks.contains("'.$teamworks_id.'")', null, $limit);

                return $res;
            });

        }else{
            $cache_key = 'teamworks_xls'.$teamworks_id.'_imputations';

            $value = Cache::remember($cache_key, now()->addHours(2), function () use($teamworks_id) {

                $res = $this->_getRecords('boniimputaciones','Actividad.TeamWorks.contains("'.$teamworks_id.'")');

                return $res;
            });

        }

        return $value;

    }


    public function getTeamworksImputations($teamworks_id){

        $cache_key = 'teamworks_'.$teamworks_id.'_imputations';

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($teamworks_id) {

            $inicioMes = now()->startOfMonth()->subMonth(8)->format('d-m-Y');


            $res = $this->_getRecords('boniimputaciones','Actividad.TeamWorks.contains("'.$teamworks_id.'") && InicioMes >= "'.$inicioMes.'"');

            return $res;
        });

        return $value;

    }

    public function getImputationsFromActividad($actividad_id){

        $cache_key = 'actividad_'.$actividad_id.'_imputations';

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($actividad_id) {

            $inicioMes = now()->startOfMonth()->subMonth(16)->format('d-m-Y');


            $res = $this->_getRecords('boniimputaciones','Actividad.ID='.$actividad_id.' && InicioMes >= "'.$inicioMes.'"');

            return $res;
        });

        return $value;

    }

    public function getImputationsFromEntidadYear($entidad_id,$year){

        $cache_key = 'getImputationsFromEntidadYear_'.$entidad_id.$year.'_imputations';

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($entidad_id,$year) {


            $res = $this->_getRecords('boniimputaciones','Ejercicio='.$year.' && Contacto.IDEntidad='.$entidad_id);

            return $res;
        });

        return $value;

    }

    public function getBoniAdmin($entidad_id,$year){

        $cache_key = 'getBoniAdmin_'.$entidad_id.$year.'_imputations';

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($entidad_id,$year) {

            $res = $this->_getRecords('boniadmin','Ejercicio='.$year.' && Contacto.IDEntidad='.$entidad_id);

            return $res;
        });

        return $value;

    }

    public function getBoniItemsEntidadYear($entidad_id,$year){

        $cache_key = 'getBoniItemsEntidadYear_'.$entidad_id.$year.'_imputations';

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($entidad_id,$year) {

            $res = $this->_getRecords('boniitems','Ejercicio='.$year.' && Entidad.ID='.$entidad_id);

            return $res;
        });

        return $value;

    }

    public function getBoniAdminTeamwork($teamwork,$year){

        $cache_key = 'getBoniAdminTeamwork_'.$teamwork.$year;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($teamwork,$year) {

            $res = $this->_getRecords('boniadmin','Ejercicio='.$year.' && Contacto.IDTeamwork='.$teamwork);

            return $res;
        });

        return $value;

    }


    public function getAdminTechnician($contact_id){

        $cache_key = 'admin_'.$contact_id.'_technician';

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($contact_id) {

            $res = $this->_getRecords('bonicontactos','Entidad.ID='.$contact_id);

            return $res;
        });

        return $value;

    }

    public function addTechnician($data){

        $return = [
            'status' => false,
            'message' => ''
        ];

        $res = $this->_addRecords('Contacto',$data);

        if($res && $res['code'] == 3000){
            $return['status'] = true;
            $return['message'] = $res['message'];
        }else{
            $return['status'] = false;
            $return['message'] = $res['message'];
        }

        return $return;
    }

    public function addActivity($data){

        $return = [
            'status' => false,
            'message' => ''
        ];

        $res = $this->_addRecords('Actividad',$data);

        if($res && $res['code'] == 3000){
            $return['status'] = true;
            $return['message'] = $res['message'];
        }else{
            $return['status'] = false;
            $return['message'] = $res['message'];
        }

        return $return;
    }

    public function editActivity($activity_id, $data){

        $return = [
            'status' => false,
            'message' => ''
        ];

        $update_result = $this->_updateRecordByID('boniactividades',$activity_id,$data);

        if($update_result['code'] == 3000){
            $return['status'] = true;
            $return['message'] = $update_result['message'];
        }else{
            $return['status'] = false;
            $return['message'] = $update_result['message'];
        }

        //Tanto si ha ido bien como ha ido mal voy a vaciar la cache por si acaso....
        $cache_key_actividad = 'actividad_'.$activity_id;;
        Cache::forget($cache_key_actividad);

        return $return;
    }

    public function getCambiosContacto($contact_id){

        $cache_key = 'cambios_'.$contact_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($contact_id) {

            $res = $this->_getRecords('bonicambios','Contacto.ID='.$contact_id);

            return $res;
        });

        return $value;

    }

    public function getCambiosFromContactoStatusAccion($contact_id,$status,$accion){

        $cache_key = 'getCambiosFromContactoStatusAccion_'.$contact_id.'_'.$status.'_'.$accion;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($contact_id,$status,$accion) {

            $res = $this->_getRecords('bonicambios','Contacto.ID='.$contact_id.' && Status ="'.$status.'" && Accion="'.$accion.'"' );

            return $res;
        });

        return $value;

    }

    public function getCambiosTeamwork($teamwork_id, $from = 0, $limit = 10){

        $cache_key = 'getCambiosTeamwork_'.$teamwork_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($teamwork_id, $from, $limit) {

            $res = $this->_getRecords('bonicambios','Actividad.TeamWorks.contains("'.$teamwork_id.'") || Contacto.Teamwork ='.$teamwork_id, $from, $limit);

            return $res;
        });

        return $value;

    }

    public function getCambiosEntidad($entidad_id, $from = 0, $limit = 10){

        $cache_key = 'getCambiosEntidad_'.$entidad_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($entidad_id, $from, $limit) {

            $res = $this->_getRecords('bonicambios','IDEntidad='.$entidad_id, $from, $limit);

            return $res;
        });

        return $value;

    }

    public function getBoniexplotacionesFromEntidad($entidad_id){

        $cache_key = 'getBoniexplotacionesFromEntidad_'.$entidad_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($entidad_id) {

            $res = $this->_getRecords('boniexplotaciones','Entidad.ID='.$entidad_id);

            return $res;
        });

        return $value;

    }

    public function getBonidocumentosFromEntidad($entidad_id){

        $cache_key = 'getBonidocumentosFromEntidad_'.$entidad_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($entidad_id) {

            $res = $this->_getRecords('bonidocumentos','Entidad.ID='.$entidad_id);

            return $res;
        });

        return $value;

    }

    public function getBonicontratosFromEntidad($entidad_id){

        $cache_key = 'getBonicontratosFromEntidad_'.$entidad_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($entidad_id) {

            $res = $this->_getRecords('bonicontratos','Entidad.ID='.$entidad_id);

            return $res;
        });

        return $value;

    }

    public function getBonifacturasFromEntidad($entidad_id){

        $cache_key = 'getBonifacturasFromEntidad_'.$entidad_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($entidad_id) {

            $res = $this->_getRecords('bonifacturas','Entidad.ID='.$entidad_id);

            return $res;
        });

        return $value;

    }



    public function getCambiosFromActividad($actividad_id){

        $cache_key = 'cambiosFromActividad_'.$actividad_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($actividad_id) {

            $res = $this->_getRecords('bonicambios','Actividad.ID='.$actividad_id);

            return $res;
        });

        return $value;

    }

    public function getCambiosFromActividadStatusAccionUnirBaja($actividad_id,$status){

        $cache_key = 'getCambiosFromActividadStatusAccionUnirBaja_'.$actividad_id.'_'.$status;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($actividad_id,$status) {

            $res = $this->_getRecords('bonicambios','Actividad.ID='.$actividad_id.' && Status ="'.$status.'" && (Accion="Unir Actividad" || Accion="Baja Actividad")');

            return $res;
        });

        return $value;

    }

    public function getBonifasesByEntidadId($entidad_id){

        $cache_key = 'getBonifasesByEntidadId_'.$entidad_id;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($entidad_id) {

            $res = $this->_getRecords('bonifases','Entidad.ID='.$entidad_id);

            return $res;
        });

        return $value;

    }

    public function getBonifasesByEntidadIdYear($entidad_id,$year){

        $cache_key = 'getBonifasesByEntidadId_'.$entidad_id.'_'.$year;

        $value = Cache::remember($cache_key, now()->addHours(2), function () use($entidad_id,$year) {

            $res = $this->_getRecords('bonifases','Entidad.ID='.$entidad_id.' && Ejercicio.ID='.$year);
            return $res;

        });

        return $value;

    }

    public function getBoniejercicios(){

        $cache_key = 'getBoniejercicios';

        $value = Cache::remember($cache_key, now()->addHours(2), function () {

            $res = $this->_getRecords('boniejercicios');

            return $res;
        });

        return $value;

    }



    public function newCambio($data){

        $return = [
            'status' => false,
            'message' => ''
        ];

        $res = $this->_addRecords('Cambio',$data);

        if($res && $res['code'] == 3000){
            $return['status'] = true;
            $return['message'] = $res['message'];
        }else{
            $return['status'] = false;
            $return['message'] = $res['message'];
        }

        return $return;


    }

    public function bonicontactos(){

        $criteria = 'Nombre != "" && Email != "" && Password != ""';

        $res_1 = $this->_getRecords('bonicontactos',$criteria,0,200);

        if($res_1 === NULL){
            $res_1 = [];
        }

        $res_2 = $this->_getRecords('bonicontactos',$criteria,201,200);

        if($res_2 === NULL){
            $res_2 = [];
        }

        $res_3 = $this->_getRecords('bonicontactos',$criteria,401,200);

        if($res_3 === NULL){
            $res_3 = [];
        }

        return array_merge($res_1,$res_2,$res_3);
    }

    public function addImputation($data){

        $return = [
            'status' => false,
            'message' => ''
        ];

        $res = $this->_addRecords('ImputacionMes',$data);

        if($res && $res['code'] == 3000){
            $return['status'] = true;
            $return['message'] = $res['message'];
        }else{
            $return['status'] = false;
            //$return['message'] = $res['message'];
        }

        return $return;


    }

    public function addRecords($report_link_name = '',$data = []){

        $client = new \GuzzleHttp\Client();

        $query = [];

        try{
            $res = $client->request('POST', 'https://creator.zoho.com/api/v2/sergio.galiano/erp/form/'.$report_link_name, [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken '.$this->access_token
                ],
                'json' => [
                    'data' => $data
                ]
            ]);

            if($res->getStatusCode() == 200){
                $data = json_decode((string)$res->getBody(),true);
                return $data;
            }else{
                Log::error('no ha devuevlto un codigo 200');
                return NULL;
            }

        }catch (\GuzzleHttp\Exception\RequestException $e){
            Log::error($e->getMessage());
            return NULL;
        }
    }


    private function _addRecords($report_link_name = '',$data = []){

        $client = new \GuzzleHttp\Client();

        $query = [];

        try{
            $res = $client->request('POST', 'https://creator.zoho.com/api/v2/sergio.galiano/erp/form/'.$report_link_name, [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken '.$this->access_token
                ],
                'json' => [
                    'data' => $data
                ]
            ]);

            if($res->getStatusCode() == 200){
                $data = json_decode((string)$res->getBody(),true);
                return $data;
            }else{
                return NULL;
            }

        }catch (\GuzzleHttp\Exception\RequestException $e){
            return NULL;
        }
    }

    public function getRecords($report_link_name = '',$criteria = NULL,$from = NULL,$limit = NULL){

        //TODO: OJO con el hash que no funciona
        $hash = md5($report_link_name.'___'.$criteria);

        $cache_key = 'getRecords_'.$hash;

        $value = Cache::remember($cache_key, now()->addHour(2), function () use($report_link_name,$criteria,$from,$limit) {

            $res = $this->_getRecords($report_link_name,$criteria,$from,$limit);

            return $res;
        });

        return $value;
    }

    public function _getRecords($report_link_name = '',$criteria = NULL,$from = NULL,$limit = NULL){

        $client = new \GuzzleHttp\Client();

        $query = [];

        if($from !== NULL){
            $query['from'] = $from;
        }

        if($limit !== NULL){
            $query['limit'] = $limit;
        }

        if($criteria !== NULL){
            $query['criteria'] = $criteria;
        }

        try{
            $res = $client->request('GET', 'https://creator.zoho.com/api/v2/sergio.galiano/erp/report/'.$report_link_name, [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken '.$this->access_token
                ],
                'query' => $query
            ]);

            /*logger($report_link_name);
            logger($query);
            logger("------------");*/

            //var_dump($res->getStatusCode());

            if($res->getStatusCode() == 200){
                $data = json_decode((string)$res->getBody(),true);
                return $data['data'];
            }else{
                return NULL;
            }

        }catch (\GuzzleHttp\Exception\RequestException $e){
            return NULL;
        }
    }


    private function _updateRecordByID($report_link_name = '',$recordID = NULL, $data_update = []){

        $client = new \GuzzleHttp\Client();

        try{
            $res = $client->request('PATCH', 'https://creator.zoho.com/api/v2/sergio.galiano/erp/report/'.$report_link_name.'/'.$recordID, [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken '.$this->access_token
                ],
                'json' => [
                    'data' => $data_update
                ]
            ]);

            if($res->getStatusCode() == 200){
                $data = json_decode((string)$res->getBody(),true);
                return $data;
            }else{
                return NULL;
            }
        }catch (\GuzzleHttp\Exception\RequestException $e){
            return NULL;
        }
    }
}
