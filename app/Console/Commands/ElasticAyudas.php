<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ElasticAyudas extends Command
{
    const PROYECTOS = "proyectos";
    const PROYECTO = "Proyecto";
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elastic:ayudas {id?} {tipo?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @return mixed
     */
    public function handle()
    {
        $date = Carbon::now()->subDays(1);
        $elasticApi = new \App\Libs\ElasticApi();
        $id = $this->argument('id');
        $tipo = $this->argument('tipo');

        #Envio de encaje no proyecto por id
        if($id != "0" && $id !== null && $tipo != "proyecto"){
            $encajes[] =  DB::table('Encajes_zoho')->leftJoin('convocatorias_ayudas', 'convocatorias_ayudas.Id', '=', 'Encajes_zoho.Ayuda_id')
            ->select('Encajes_zoho.*', 'convocatorias_ayudas.*', 'Encajes_zoho.Titulo as encaje_titulo', 'Encajes_zoho.descripcion as encaje_descripcion',
            'Encajes_zoho.PerfilFinanciacion as encaje_perfilfinanciacion', 'Encajes_zoho.Tipo as TipoEncaje', 'Encajes_zoho.id as encaje_id', 'Encajes_zoho.created_at as FechaCreacion')
            ->where('Encajes_zoho.id', $id)->where('convocatorias_ayudas.Publicada', 1)->where('convocatorias_ayudas.Estado', '!=', 'Cerrada')->where('Encajes_zoho.Tipo', '!=', 'Proyecto')->first();
        #Envio de encaje tipo proyecto por id
        }elseif($id != "0" && $id !== null && $tipo == "proyecto"){
            $encajes[] =  DB::table('Encajes_zoho')
            ->leftJoin('proyectos', 'proyectos.id', '=', 'Encajes_zoho.Proyecto_id')
            ->leftJoin('convocatorias_ayudas', 'convocatorias_ayudas.Id', '=', 'proyectos.idAyuda')
            ->select('Encajes_zoho.*', 'convocatorias_ayudas.*', 'proyectos.*', 'Encajes_zoho.Tipo as TipoEncaje', 'Encajes_zoho.Titulo as encaje_titulo', 'Encajes_zoho.descripcion as encaje_descripcion',
            'Encajes_zoho.PerfilFinanciacion as encaje_perfilfinanciacion', 'Encajes_zoho.id as encaje_id', 'Encajes_zoho.created_at as FechaCreacion', 'proyectos.Estado as estado_proyecto', 'proyectos.uri as url_proyecto')
            ->where('Encajes_zoho.id', $id)->where('Encajes_zoho.Tipo', 'Proyecto')->first();
        #Envio de todos los encajes de tipo proyecto
        }elseif($id == "0" && $tipo == "proyecto"){
            $encajes =  DB::table('Encajes_zoho')
            ->leftJoin('proyectos', 'proyectos.id', '=', 'Encajes_zoho.Proyecto_id')
            ->leftJoin('convocatorias_ayudas', 'convocatorias_ayudas.Id', '=', 'proyectos.idAyuda')
            ->select('Encajes_zoho.*', 'convocatorias_ayudas.*', 'proyectos.*', 'Encajes_zoho.Tipo as TipoEncaje', 'Encajes_zoho.Titulo as encaje_titulo', 'Encajes_zoho.descripcion as encaje_descripcion',
            'Encajes_zoho.PerfilFinanciacion as encaje_perfilfinanciacion', 'Encajes_zoho.id as encaje_id', 'Encajes_zoho.created_at as FechaCreacion', 'proyectos.Estado as estado_proyecto', 'proyectos.uri as url_proyecto')
            ->where('Encajes_zoho.Tipo', 'Proyecto')->get();
        #Envio de todos los encajes de tipo distinto a proyecto
        }else{
            $encajes =  DB::table('Encajes_zoho')->leftJoin('convocatorias_ayudas', 'convocatorias_ayudas.Id', '=', 'Encajes_zoho.Ayuda_id')
            ->select('Encajes_zoho.*', 'convocatorias_ayudas.*', 'Encajes_zoho.Titulo as encaje_titulo', 'Encajes_zoho.Tipo as TipoEncaje', 'Encajes_zoho.descripcion as encaje_descripcion',
            'Encajes_zoho.PerfilFinanciacion as encaje_perfilfinanciacion', 'Encajes_zoho.id as encaje_id', 'Encajes_zoho.created_at as FechaCreacion')
            ->where('convocatorias_ayudas.Publicada', 1)->where('convocatorias_ayudas.Estado', '!=', 'Cerrada')->where('Encajes_zoho.Tipo', '!=', 'Proyecto')
            ->get();
        }

        foreach($encajes as $encaje){

            if(!isset($encaje)){
                continue;
            }

            //$this->info($encaje->id." -- ".$encaje->Titulo);
            if(isset($encaje->Inicio) && isset($encaje->Ayuda_id)){
                if(Carbon::parse($encaje->Inicio) <= Carbon::now()){
                    \App\Models\Ayudas::where('id', $encaje->Ayuda_id)->update(['Estado' => 'Abierta']);
                    $encaje->Estado = "Abierta";
                    $ayuda = \App\Models\Ayudas::where('id', $encaje->Ayuda_id)->first();
                    $ayuda->Estado = "Abierta";
                    $this->createNoticia($ayuda);
                    try{    
                        Artisan::call('calcula:competitividad',
                            [
                                'id' => $encaje->Ayuda_id
                            ]
                        );
                    }catch(Exception $e){
                        Log::error($e->getMessage());                        
                    }
                }
            }
            
            if(isset($encaje->Fin) && isset($encaje->Ayuda_id)){
                if(Carbon::parse($encaje->Fin) < Carbon::now()){

                    $ayuda = \App\Models\Ayudas::where('id', $encaje->Ayuda_id)->first();
                    if($ayuda !== null){
                        $convAyuda = \App\Models\Convocatorias::where('id', $ayuda->id_ayuda)->first();
                        
                        if($convAyuda !== null){

                            $checkconvocatorias = 0;

                            if($ayuda->update_extinguida_ayuda == "1"){
                                
                                $checkconvocatorias += \App\Models\Ayudas::where('id_ayuda', $convAyuda->id)->
                                where('Estado', 'Abierta')->where('id', '!=', $encaje->id)->count();
                                $checkconvocatorias += \App\Models\Ayudas::where('id_ayuda', $convAyuda->id)
                                ->where('Estado', 'Próximamente')->where('id', '!=', $encaje->id)->count();
                                
                                if($checkconvocatorias == 0){
                                    try{
                                        $convAyuda->extinguida = 1;
                                        $convAyuda->save();
                                    }catch(Exception $e){
                                        Log::error('No se ha podido cerrar la ayuda de esta convocatoria: '.$e->getMessage());
                                    }
                                }
                            }

                            if($convAyuda->extinguida == 1){                        
                                \App\Models\Ayudas::where('id', $encaje->Ayuda_id)->update(['Estado' => 'Cerrada']);
                                $encaje->Estado = "Cerrada";                        
                                $ayuda->Estado = "Cerrada";
                                $this->createNoticia($ayuda);
                            }else{
                                \App\Models\Ayudas::where('id', $encaje->Ayuda_id)->update(['Estado' => 'Próximamente']);                                
                                $encaje->Estado = "Próximamente";
                                $ayuda->Estado = "Próximamente";                    
                            }
                        }
                    }
                }
            }

            $encaje->IdOrganismo = "";
            $encaje->uriOrganismo = "";
            $nombreOrganismo = "";
            $ayu = null;
            $encaje->NombreOrganismo = $nombreOrganismo;

            if(isset($encaje->TipoEncaje) && $encaje->TipoEncaje == self::PROYECTO){

                if(isset($encaje->IdAyuda)){
                    $ayu = \App\Models\Ayudas::where('id', $encaje->IdAyuda)->select(['Organismo'])->first();
                }else if(isset($encaje->Ayuda_id)){
                    $ayu = \App\Models\Ayudas::where('id', $encaje->Ayuda_id)->select(['Organismo'])->first();
                }

                if(!$ayu){
                    $pro = DB::table('proyectos')->where('id', $encaje->Proyecto_id)->first();
                    $ayu = \App\Models\Ayudas::where('id', $pro->IdAyuda)->select(['Organismo'])->first();
                }

                if($ayu !== null){
                    $dpto = DB::table('departamentos')->where('id', $ayu->Organismo)->select(['Nombre','id','Acronimo','url'])->first();
                    if(!$dpto){
                        $dpto = DB::table('organos')->where('id', $ayu->Organismo)->select(['Nombre','id','Acronimo','url'])->first();
                    }
                    if($dpto){
                        $nombreOrganismo = $dpto->Nombre;
                        $encaje->NombreOrganismo = $nombreOrganismo;
                        $encaje->IdOrganismo = $dpto->id;
                        $encaje->uriOrganismo = $dpto->url;
                    }
                }

            }else{
               
                $dpto = DB::table('departamentos')->where('id', $encaje->Organismo)->select(['Nombre','id','Acronimo','url'])->first();
                if(!$dpto){
                    $dpto = DB::table('organos')->where('id', $encaje->Organismo)->select(['Nombre','id','Acronimo','url'])->first();
                }
                if($dpto){
                    $nombreOrganismo = $dpto->Nombre;
                    $encaje->NombreOrganismo = $nombreOrganismo;
                    $encaje->IdOrganismo = $dpto->id;
                    $encaje->uriOrganismo = $dpto->url;

                }
            }

            if($encaje->TipoEncaje == self::PROYECTO){
                $encaje->cnaes_ok = array();
                if($encaje->Encaje_opcioncnaes != "Todos"){
                    if($encaje->Encaje_cnaes !== null && $encaje->Encaje_cnaes !== "null"){
                        foreach(json_decode($encaje->Encaje_cnaes, true) as $idcnae){
                            $cnaedb = DB::table('Cnaes')->where('Id_zoho', $idcnae)->select('Nombre')->first();
                            $encaje->cnaes_ok[] = mb_strtolower($cnaedb->Nombre);
                        }
                    }
                }
            }else{

                $encaje->cnaes_ok = array();
                $encaje->opcioncnae_ok = null;

                if($encaje->Tipo == "Interna" || $encaje->Tipo == "Linea"){
                    if(isset($encaje->Encaje_opcioncnaes)){
                        $encaje->opcioncnae_ok = $encaje->Encaje_opcioncnaes;
                        if($encaje->Encaje_opcioncnaes != "Todos"){
                            if($encaje->Encaje_cnaes !== null && $encaje->Encaje_cnaes !== "null"){
                                foreach(json_decode($encaje->Encaje_cnaes, true) as $idcnae){
                                    $cnaedb = DB::table('Cnaes')->where('Id_zoho', $idcnae)->select('Nombre')->first();
                                    if($cnaedb){
                                        $encaje->cnaes_ok[] = mb_strtolower($cnaedb->Nombre);
                                    }
                                }
                            }
                        }
                    }
                }else{
                    if($encaje->OpcionCNAE != "Todos"){
                        $encaje->Encaje_opcioncnaes = mb_strtolower($encaje->OpcionCNAE);
                        if($encaje->CNAES !== null && $encaje->CNAES !== "null"){
                            foreach(json_decode($encaje->CNAES, true) as $idcnae){
                                $cnaedb = DB::table('Cnaes')->where('Id_zoho', $idcnae)->select('Nombre')->first();
                                if($cnaedb){
                                    $encaje->cnaes_ok[] = mb_strtolower($cnaedb->Nombre);
                                }
                            }
                        }
                    }
                }
            }

            $tagsCompletas = array();

            if($encaje->TipoEncaje == self::PROYECTO){
                if($encaje->TagsTec !== null && $encaje->TagsTec != ""){
                    $tagsCompletas = array_merge($tagsCompletas, json_decode($encaje->TagsTec, true));
                }
                if(isset($encaje->tags) && $encaje->tags !== null && $encaje->tags != ""){
                    $tagsCompletas = array_merge($tagsCompletas, json_decode($encaje->tags, true));
                }
                $tagsCompletas = array_values(array_unique($tagsCompletas));                
            }else{
                if($encaje->TagsTec !== null && $encaje->TagsTec != ""){
                    if(is_array(json_decode($encaje->TagsTec))){
                        $tagsCompletas = array_merge($tagsCompletas, json_decode($encaje->TagsTec));
                    }else{
                        $tagsCompletas = array_merge($tagsCompletas, explode(",",$encaje->TagsTec));
                    }
                }
            }

            $keywords = "";
            $checkChatGPTKeywords = \App\Models\ChatGPTAyudasKeywords::where('id_ayuda', $encaje->Ayuda_id)->where('type', 'keywords')->first();
            if($checkChatGPTKeywords){
                $keywords = (is_array(json_decode($checkChatGPTKeywords->keywords, true)) && $checkChatGPTKeywords->keywords != "") ? json_decode($checkChatGPTKeywords->keywords, true) : "";
            }

            if($keywords != "" && is_array($keywords) && $encaje->es_europea == 0 && $encaje->Tipo == "Linea"){
                if(isset($keywords['keywords']) && !empty($keywords['keywords'])){
                    $tagsCompletas = array_merge($tagsCompletas, $keywords['keywords']);
                }
                if(isset($keywords['areas']) && !empty($keywords['areas'])){
                    $tagsCompletas = array_merge($tagsCompletas, $keywords['areas']);
                }
            }

            $encaje->TagsCompletas = $tagsCompletas;

            $ayuda_convocatoria = \App\Models\Ayudas::where('id', $encaje->Ayuda_id)->first();

            ###Campos nuevos agregados el 16/01/2024
            $encaje->NumeroMinimoEmpleados = 0;
            $encaje->NumeroMaximoEmpleados = 10000000;
            if($ayuda_convocatoria !== null){
                $encaje->NumeroMinimoEmpleados = ($ayuda_convocatoria->minEmpleados === null) ? 0 : $ayuda_convocatoria->minEmpleados;
                $encaje->NumeroMaximoEmpleados = ($ayuda_convocatoria->maxEmpleados === null) ? 10000000 : $ayuda_convocatoria->maxEmpleados;
            }

            $fechaMaxConstitucion = '01-01-1800';
            if($encaje->Tipo == "Target"){
                if($encaje->FechaMax){
                    $fechaMaxConstitucion = Carbon::parse($encaje->FechaMax)->format('d-m-Y');
                }elseif($encaje->Encaje_fechamax){
                    $fechaMaxConstitucion = Carbon::parse($encaje->Encaje_fechamax)->format('d-m-Y');
                }
            }else{
                if($encaje->FechaMaxConstitucion){
                    $fechaMaxConstitucion = Carbon::parse($encaje->FechaMaxConstitucion)->format('d-m-Y');
                }
                if($encaje->Meses){
                    $fechaMaxConstitucion = Carbon::now()->subMonths($encaje->Meses)->format('d-m-Y');
                }
            }

            $fechaMinConstitucion = "01-01-1800";
            if($encaje->FechaMinConstitucion){
                $fechaMinConstitucion = Carbon::parse($encaje->FechaMinConstitucion)->format('d-m-Y');
            }else if($encaje->MesesMin){
                $fechaMinConstitucion = Carbon::now()->subMonths($encaje->MesesMin)->format('d-m-Y');
            }

            $encaje->fechaMaxConstitucion = $fechaMaxConstitucion;
            $encaje->fechaMinConstitucion = $fechaMinConstitucion;

            if($encaje->fechaMaxConstitucion == "01-01-1800" &&  $encaje->fechaMinConstitucion != "01-01-1800"){
                $encaje->fechaMaxConstitucion = $encaje->fechaMinConstitucion;
            }

            $encaje->Country = null;
            if($encaje->Ambito == "Nacional"){
                $encaje->Country = (isset($encaje->pais)) ? $encaje->pais : "ES";
            }
            if($encaje->Ambito == "Comunidad Autónoma"){
                $encaje->Country = "ES";
            }
           
            $resultApi = $elasticApi->sendDataEncajes($encaje);
            if($resultApi !== NULL){
                $this->info($resultApi);
            }else{
                $this->error("Error");
            }

        }

        $this->info("Enviados a elastic: ".count($encajes)." Encajes");
        //
        /*$email =  config('services.users.superadminemail');
        $user = (Auth::check()) ? Auth::user()->email: 'invitado';
        $mail = new \App\Mail\AvisaErrores('Envio de ayudas a elastic: '.count($encajes)." Encajes", 'comando php', $user);
        Mail::to($email)->queue($mail);*/

    }

    private function createNoticia($ayuda){

        try{
            if($ayuda->Organismo){
                $dpto = DB::table('departamentos')->where('id', $ayuda->Organismo)->select(['Nombre','id','Acronimo'])->first();
                if(!$dpto){
                    $dpto = DB::table('organos')->where('id', $ayuda->Organismoo)->select(['Nombre','id','Acronimo'])->first();
                }
            }
            if($ayuda->Estado == "Abierta"){
                $mensaje = 'Se abre la línea de ayuda pública: '.$ayuda->Titulo;
                if($dpto){
                    $mensaje .= ' para el organismo: '.$dpto->Acronimo;
                }
                $user = 'system_abre';

            }else{
                $mensaje = 'Se ha cerrado la línea de ayuda pública: '.$ayuda->Titulo;
                if($dpto){
                    $mensaje .= ' para el organismo: '.$dpto->Acronimo;
                }
                $user = 'system_cierra';

            }

            $checknoticia = \App\Models\Noticias::where('id_ayuda', $ayuda->id)->where('id_organo', $ayuda->Organismo)
            ->where('user', $user)->first();

            if($checknoticia){

                $checknoticia->save();
                //$this->info('Se actualiza noticia para la ayuda: '.$ayuda->Titulo);

            }else{

                $noticia = new \App\Models\Noticias();
                $noticia->id_ayuda = $ayuda->id;
                $noticia->id_organo = ($ayuda->Organismo === null) ? null : $ayuda->Organismo;
                $noticia->texto = $mensaje;
                $noticia->fecha = Carbon::now();
                $noticia->user = $user;
                $noticia->created_at = Carbon::now();
                $noticia->save();
                //$this->info('Se genera noticia para la ayuda: '.$ayuda->Titulo);
            }

        }catch(Exception $e){
            Log::error($e->getMessage());
        }
    }

}

