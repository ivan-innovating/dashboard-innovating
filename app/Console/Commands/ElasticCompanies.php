<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class ElasticCompanies extends Command
{
    const ALLINTERESES = array("231435000088214889","231435000088223017","231435000088214857","231435000088214861","231435000088214865",
    "231435000088214869","231435000088214873","231435000088214877","231435000089462012");
    const ORGANISMOS = "6668843";
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elastic:companies {cif?} {update?} {presupuesto?}';

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
        $cif = $this->argument('cif');
        $update = $this->argument('update');
        $presupuesto = $this->argument('presupuesto');

        if(isset($cif) && $cif != ""){
            $companies = \App\Models\Einforma::leftJoin('entidades', 'entidades.CIF', '=', 'einforma.identificativo')
            ->select('einforma.web as webs_einforma', 'einforma.identificativo as elastic_id', 'einforma.ccaa as einforma_ccaa', 'entidades.IntensidadAyudas as IntensidadAyudas', 'entidades.Sedes as Sedes', 'entidades.id as identidad',
            'entidades.Web as web_usuario', 'entidades.id as idInvestigadores', 'entidades.calculoCooperacion as calculoCooperacion', 'entidades.solicitudSPI as solicitudSPI', 'entidades.naturalezaEmpresa as naturalezaEmpresa', 'einforma.*')
            ->where('einforma.identificativo', $cif)->orderByDesc('einforma.ultimaActualizacion')->get();

        }else if($update == 1){
            $companies = \App\Models\Einforma::leftJoin('entidades', 'entidades.CIF', '=', 'einforma.identificativo')
            ->where('entidades.EntityUpdate', '>=', $date->format('Y-m-d'))
            ->select('einforma.web as webs_einforma', 'einforma.identificativo as elastic_id', 'einforma.ccaa as einforma_ccaa', 'entidades.IntensidadAyudas as IntensidadAyudas', 'entidades.Sedes as Sedes', 'entidades.id as identidad',
            'entidades.Web as web_usuario', 'entidades.id as idInvestigadores', 'entidades.calculoCooperacion as calculoCooperacion', 'entidades.solicitudSPI as solicitudSPI', 'entidades.naturalezaEmpresa as naturalezaEmpresa', 'einforma.*')->get();
        }else{
            $companies = \App\Models\Einforma::leftJoin('entidades', 'entidades.CIF', '=', 'einforma.identificativo')
            ->select('einforma.web as webs_einforma', 'einforma.identificativo as elastic_id', 'einforma.ccaa as einforma_ccaa', 'entidades.IntensidadAyudas as IntensidadAyudas', 'entidades.Sedes as Sedes', 'entidades.id as identidad',
            'entidades.Web as web_usuario', 'entidades.id as idInvestigadores', 'entidades.calculoCooperacion as calculoCooperacion', 'entidades.solicitudSPI as solicitudSPI', 'entidades.naturalezaEmpresa as naturalezaEmpresa', 'einforma.*')
            ->skip(1000)->take(24000)
            ->get();                
        }

        $i = 0;
        $cifs = array();

        foreach($companies as $key => $company){

            if(in_array($company->identificativo, $cifs)){
                continue;
            }

            array_push($cifs, $company->identificativo);

            if($company->naturalezaEmpresa !== null && in_array(self::ORGANISMOS, json_decode($company->naturalezaEmpresa, true))){
                Log::error("Empresa tipo organismo no se manda a elastic, Nombre(CIF):". $company->denominacion."(".$company->CIF.")");
                continue;
            }

            $totaleinformas = collect($companies)->where('identificativo', $company->identificativo)->count();

            if($totaleinformas > 1){
                $manualeinforma = collect($companies)->where('identificativo', $company->identificativo)->where('lastEditor', '!=', 'einforma')->sortByDesc('ultimaActualizacion')->first();
                if($company->cnaeEditado === null){
                    if(isset($manualeinforma) && $manualeinforma->cnaeEditado !== null){
                        $company->cnaeEditado = $manualeinforma->cnaeEditado;
                    }
                }
                if($company->objetoSocialEditado === null){
                    if(isset($manualeinforma) && $manualeinforma->objetoSocialEditado !== null){
                        $company->objetoSocialEditado = $manualeinforma->objetoSocialEditado;
                    }
                }

            }

            $this->info($key. " ultimo id enviado a elastic");

            $entity = \App\Models\Entidad::where('CIF', $company->identificativo)->first();

            if(!$entity){
                continue;
            }

            if(!isset($entity->empresaPageRank) && !isset($entity->valorTrl)){
                continue;
            }          

            ###NUEVA LOGICA MANDAR DATOS DE PERFIL DE FINANCIACION ACTIVO SINO LOS ANTIGUOS DE EMPRESA
            $idperfilesfinanciacion = array();
            $company->Textos_Tecnologia = "";

            if($entity->perfilFinanciero !== null && $entity->perfilFinanciero->perfil_intereses !== null){
                if(isset($entity->perfilFinanciero->perfil_intereses) && $entity->perfilFinanciero->perfil_intereses != "null"){
                    foreach(json_decode($entity->perfilFinanciero->perfil_intereses, true) as $interesname){
                        $name = str_replace("Pública - ", "", $interesname);
                        $interes = DB::table('Intereses')->where('Nombre', $name)->first();
                        if($interes){
                            $idperfilesfinanciacion[] = (string)$interes->Id_zoho;
                        }
                    }
                }
                if(isset($entity->perfilFinanciero->tags_tecnologias)){
                    $textostec = json_decode($entity->perfilFinanciero->tags_tecnologias, true);
                    foreach($textostec as $texto){
                        if($texto){
                            $company->Textos_Tecnologia .= ",".$texto;
                        }
                    }
                }
                $company->IntensidadAyudas = $entity->perfilFinanciero->intensidad_ayudas;
                $company->lider = ($entity->perfilFinanciero->liderar_consorcios == 0) ? false : true;
                if(isset($presupuesto) && $presupuesto !== null && $presupuesto > 0){
                    $company->gastoAnual = (float)$presupuesto;
                }
            }else{
                if(isset($entity->Intereses) && $entity->Intereses != "null"){
                    foreach(json_decode($entity->Intereses, true) as $interesname){
                        $name = str_replace("Pública - ", "", $interesname);
                        $interes = DB::table('Intereses')->where('Nombre', $name)->first();
                        if($interes){
                            $idperfilesfinanciacion[] = (string)$interes->Id_zoho;
                        }
                    }
                }
                if(isset($entity->TextosLineasTec)){
                    $textostec = json_decode($entity->TextosLineasTec, true);
                    foreach($textostec as $texto){
                        if($texto){
                            $company->Textos_Tecnologia .= ",".$texto;
                        }
                    }
                }
                $company->lider = ($entity->LiderarConsorcios == 0) ? false : true;
            }            

            if(empty($idperfilesfinanciacion)){
                $idperfilesfinanciacion = self::ALLINTERESES;
            }

            if($entity->esConsultoria == 1){
                array_push($idperfilesfinanciacion, "231435000088214863");
            }

            $company->idperfilesfinanciacion = $idperfilesfinanciacion;

            $sello =  DB::table('pymes')->where('CIF', $entity->CIF)->orderByDesc('validez')->first();
            $sellook = 0;
            $company->SelloPymeValidez = '01-01-1800';

            if($sello){
                $company->SelloPymeValidez = Carbon::parse($sello->validez)->format('d-m-Y');
                if(Carbon::parse($sello->validez) >= Carbon::now()){
                    $sellook = 1;
                }
            }

            $company->XPCooperacion = "No";
            $company->XPLider = false;

            if($company->calculoCooperacion !== null){
                $cooperacion = json_decode($company->calculoCooperacion);
                if($cooperacion->ambitoColaboracionNacional == 1){
                    $company->XPCooperacion = 'Nacional';
                }
                if($cooperacion->ambitoColaboracionEuropeo == 1){
                    if($company->XPCooperacion != "" && $company->XPCooperacion != "No"){
                        $company->XPCooperacion .= ',Internacional';
                    }else{
                        $company->XPCooperacion = 'Internacional';
                    }
                }
                if($cooperacion->liderProyecto == 1){
                    $company->XPLider = true;
                }
            }

            $textos = \App\Models\TextosElastic::where('CIF', $entity->CIF)->first();

            $company->Nombre = $entity->Nombre;
            $company->TextosTramitaciones = (isset($textos)) ? $textos->Textos_Tramitaciones.",".$entity->TextosTramitaciones : $entity->TextosTramitaciones;
            $company->TextosProyectos = (isset($textos)) ? $textos->Textos_Proyectos.",".$entity->TextosProyectos : $entity->TextosProyectos;
            
            $company->TextosTecnologia = (isset($textos)) ? $company->Textos_Tecnologia.",".$textos->Textos_Tecnologia.",".$entity->TextosTecnologia : $company->Textos_Tecnologia.",".$entity->TextosTecnologia;

            ### NUEVAS PALABRAS CLAVE SACADAS DESDE CHAT GPT 25/04/2004
            $s3_file = null;
            if(Storage::disk('s3_files')->exists('company_keywords/'.$entity->CIF.'.json')){
                $s3_file = Storage::disk('s3_files')->get('company_keywords/'.$entity->CIF.'.json');
            }

            if($s3_file !== null && $s3_file != ""){
                $s3_keywords = array_column(json_decode($s3_file, true), 'keyword');
                if(!empty($s3_keywords)){
                    $keywords = implode(",", $s3_keywords);
                    $company->TextosTecnologia .= $keywords;
                }                
            }                    
            ### FIN DE NUEVAS PALABRAS CLAVE SACADAS DESDE CHAT GPT 25/04/2004

            $company->TextosDocumentos = (isset($textos)) ? $textos->Textos_Documentos.",".$entity->TextosDocumentos : $entity->TextosDocumentos;
            if($company->entidad !== null){
                $company->TextosDocumentos .= ",".$company->entidad->Marca;
            }
            $company->naturalezaEmpresa = $entity->naturalezaEmpresa;
            $company->empresaPageRank = $entity->empresaPageRank;
            $company->uri = $entity->uri;
            $company->minimosubcontratar = $entity->MinimoSubcontratar;
            $company->SelloPyme = $sellook;
            $company->valorTrl = $entity->valorTrl;
            $company->cantidadImasD = $entity->cantidadImasD;
            $company->empresaPageRank = round($entity->empresaPageRank,0);
            $company->totalPatentes = \App\Models\Patentes::where('CIF',  $entity->CIF)->count();
            $company->totalConcesiones = \App\Models\Concessions::where('custom_field_cif',  $entity->CIF)->count();
            $company->totalPatentes += \App\Models\Concessions::where('custom_field_cif',  $entity->CIF)->where('id_organo', 650)->count();
            $company->featured = $entity->featured;
            
            $company->lastFinanciacion = '01-01-1800';
            $lastConcesion = $company->entidad->lastConcesion->first();
            if($company->XPLider === true && $lastConcesion){
                $company->lastFinanciacion = Carbon::parse($lastConcesion->fecha)->format('d-m-Y');
            }

            $company->lastFechaConcesion = '01-01-1800';
            if($lastConcesion){
                $company->lastFechaConcesion = Carbon::parse($lastConcesion->fecha)->format('d-m-Y');
            }

            if(in_array("6668840", json_decode($company->naturalezaEmpresa))){
                $company->cantidadImasD = \App\Models\Investigadores::where('id_ultima_experiencia', $company->idInvestigadores)->count();
            }

            $consultoraclientes = \App\Models\ConsultorasClientes::where('cliente_id', $company->identidad)->where('activo', 1)->get();
            $clientes = array();
            if($consultoraclientes){
                $clientes = collect($consultoraclientes)->pluck('consultora_id')->toArray();
            }
            $company->FlagsEntidad = $clientes;

            $IDAyudasConcedidas = array();
            $TipoFinanciacionConcedida = array();
            $IDOrgDeptConcedido = array();
            $IDInteresesConcedida = array();

            $concessions = \App\Models\Concessions::where('custom_field_cif', $entity->CIF)->get();
            if($concessions->isNotEmpty()){

                foreach($concessions as $concesion){
                    if($concesion->organo !== null){
                        array_push($IDOrgDeptConcedido, $concesion->organo->id);
                    }elseif($concesion->departamento !== null){
                        array_push($IDOrgDeptConcedido, $concesion->departamento->id);
                    }else{
                        if($concesion->id_organo !== null){
                            array_push($IDOrgDeptConcedido, $concesion->id_organo);
                        }elseif($concesion->id_departamento !== null){
                            array_push($IDOrgDeptConcedido, $concesion->id_departamento);
                        }
                    }
                }

                $IDOrgDeptConcedido = array_values(array_unique($IDOrgDeptConcedido));

            }

            $concessions = \App\Models\Proyectos::where('empresaPrincipal', $entity->CIF)->orWhere('empresasParticipantes', 'LIKE', '%'.$entity->CIF.'%')
            ->where('Estado', 'Cerrado')->whereNotNull('idAyudaAcronimo')->get();
            
            if($concessions->isNotEmpty()){
                $credito = 0;
                $fondoperdido = 0;
                foreach($concessions as $concesion){
                    if($concesion->concesion !== null){
                        if($concesion->concesion->equivalent_aid !== null){
                            if($concesion->concesion->equivalent_aid < $concesion->concesion->amount){
                                $credito = 1;
                            }else{
                                $fondoperdido = 1;
                            }
                        }else{
                            $credito = 1;
                        }
                    }
                    if($concesion->ayudaAcronimo !== null){
                        if($concesion->ayudaAcronimo->id_ayuda !== null){
                            $IDAyudasConcedidas[] = $concesion->ayudaAcronimo->id_ayuda;
                        }
                    }
                }

                $IDAyudasConcedidas = array_values(array_unique($IDAyudasConcedidas));

                if($credito == 1 && $fondoperdido == 1){
                    $TipoFinanciacionConcedida = ["Crédito","FondoPerdido"];
                }elseif($credito == 1 && $fondoperdido == 0){
                    $TipoFinanciacionConcedida = ["Crédito"];
                }elseif($credito == 0 && $fondoperdido == 1){
                    $TipoFinanciacionConcedida = ["FondoPerdido"];
                }elseif($credito == 0 && $fondoperdido == 0){
                    $TipoFinanciacionConcedida = ["Crédito"];
                }
            }

            $company->IDAyudasConcedidas = $IDAyudasConcedidas;
            $company->TipoFinanciacionConcedida = $TipoFinanciacionConcedida;
                        
            if(!empty($company->IDAyudasConcedidas)){
                foreach($company->IDAyudasConcedidas as $id){
                    $ayudas = \App\Models\Ayudas::where('id_ayuda', $id)->get();
                    if($ayudas->isNotEmpty()){
                        foreach($ayudas as $ayuda){                            
                            $IDOrgDeptConcedido[] = (string)$ayuda->Organismo;
                            if($ayuda->PerfilFinanciacion !== null && !empty(json_decode($ayuda->PerfilFinanciacion, true))){
                                $IDInteresesConcedida = array_merge($IDInteresesConcedida, json_decode($ayuda->PerfilFinanciacion, true));
                            }
                        }
                    }
                }
                $IDOrgDeptConcedido = array_values(array_unique($IDOrgDeptConcedido));
                $IDInteresesConcedida = array_values(array_unique($IDInteresesConcedida));
            }

            $company->IDOrgDeptConcedido = $IDOrgDeptConcedido;
            $company->IDInteresesConcedida = $IDInteresesConcedida;

            $DominiosEmpresa  = array();

            if(isset($company->webs_einforma) && !empty($company->webs_einforma)){
                if(is_array(json_decode($company->webs_einforma, true)) && !empty(json_decode($company->webs_einforma, true))){
                    foreach(json_decode($company->webs_einforma) as $web){
                        $domaintld = str_replace(['http://', 'https://', 'www.'], '', $web);
                        array_push($DominiosEmpresa, $domaintld);
                    }   
                }else{
                    $domaintld = str_replace(['http://', 'https://', 'www.'], '', $company->web_usuario);
                    array_push($DominiosEmpresa, $domaintld);
                }
            }
    
            $company->DominiosEmpresa = $DominiosEmpresa;

            $company->NumUsuariosInnovating = \App\Models\UsersEntidad::where('entidad_id', $entity->id)->count();
            $company->NumUsuariosNoInnovating = \App\Models\ZohoMails::where('Cif', $entity->CIF)->count();

            ##DESARROLLO PARA CUANDO PODAMOS MANDAR A ELASTIC EL CAMPO COMUNIDADES COMO UN ARRAY
            $Comunidades = array();
            if($company->Sedes !== null && !empty(json_decode($company->Sedes))){
                $sedes = json_decode($company->Sedes);
                if(isset($sedes->central) && $sedes->central !== null && !empty($sedes->central)){
                    array_push($Comunidades, mb_strtolower($sedes->central));
                }
                if(isset($sedes->otrassedes) && $sedes->otrassedes !== null && (is_array($sedes->otrassedes) && !empty($sedes->otrassedes))){
                    foreach($sedes->otrassedes as $sede){
                        array_push($Comunidades, mb_strtolower($sede));
                    }
                }
            }else{
                if(isset($company->ccaa) && $company->entidad->Ccaa !== null && !empty($company->ccaa)){
                    array_push($Comunidades, mb_strtolower($company->ccaa));
                }
            }

            if(empty($Comunidades)){
                if(isset($company->entidad->Ccaa) && $company->entidad->Ccaa !== null && !empty($company->entidad->Ccaa)){
                    array_push($Comunidades, mb_strtolower($company->entidad->Ccaa));
                }
            }

            if(isset($presupuesto) && $presupuesto !== null && $presupuesto > 0){
                $company->GastoIDMax = (float)$presupuesto;
            }else{
                $company->GastoIDMax = 0.0;

                if($company->gastoAnual !== null){
                    $coeficiente = null;
                    if($company->categoriaEmpresa == "Micro"){
                        $coeficiente = 0.85;
                    }
                    if($company->categoriaEmpresa == "Pequeña"){
                        $coeficiente = 0.4;
                    }
                    if($company->categoriaEmpresa == "Mediana"){
                        $coeficiente = 0.2;
                    }
                    if($company->categoriaEmpresa == "Grande"){
                        $coeficiente = 0.1;
                    }
                    if(in_array("6668838", json_decode($company->naturalezaEmpresa, true))){
                        $coeficiente = 0.8;
                    }
                    if($company->categoriaEmpresa == "Micro" || $company->categoriaEmpresa == "Pequeña" && $company->anioBalance < Carbon::now()->subYears(2)->format('Y')){
                        $company->GastoIDMax = round((float)$company->cantidadImasD*2, 2);
                    }else{
                        if($coeficiente !== null){
                            $company->GastoIDMax = round((float)$company->gastoAnual*$coeficiente, 2);
                        }
                    }
                }

                if($company->GastoIDMax == 0){
                    $company->GastoIDMax = (isset($company->gastoAnual))? $company->gastoAnual : 0;
                }

                ### HOTFIX si empresa pequeña o micro enviar el dato calculado a traves de concesiones 07/12/2023
                if($company->categoriaEmpresa == "Micro" || $company->categoriaEmpresa == "Pequeña"){
                    if($company->GastoIDMax === null){
                        $company->GastoIDMax = 0;
                    }
                    if($company->entidad->cantidadImasD > $company->GastoIDMax){
                        $company->GastoIDMax = (float)$company->entidad->cantidadImasD;
                    }
                }
            }

            $listadoEmails = array();
            if($entity->users->count() > 0){
                foreach($entity->users as $user){
                    $listadoEmails[] = $user->email;
                }
            }

            $zohoMails = \App\Models\ZohoMails::where('Codigo', '>', 0)->where('Cif', $entity->CIF)->get();
            if($zohoMails->count() > 0){
                foreach($zohoMails as $user){
                    $mails = json_decode($user->emails, true);
                    foreach($mails as $mail){
                        $listadoEmails[] = trim($mail);
                    }
                }
            }
            $company->listadoEmails = array_values(array_filter(array_unique($listadoEmails)));

            ##Datos para filtros financieros            
            $company->FechaUltimaPatenteRegistrada = "01-01-1800";
            $lastPatente = DB::table('patentes')->where('CIF',  $entity->CIF)->select('Fecha_publicacion')->orderBy('Fecha_publicacion', 'DESC')->first();
            if($lastPatente){
                $company->FechaUltimaPatenteRegistrada = Carbon::createFromFormat('Y-m-d', $lastPatente->Fecha_publicacion)->format('d-m-Y');
            }
            $company->UltimoEjercicioFinanciero = $company->anioBalance;
            $company->SPIAuto = ($company->solicitudSPI == 1) ? true : false;  
            $company->pasivoCorriente = ($company->pasivoCorriente === null) ? 0.00 : $company->pasivoCorriente;
            $company->pasivoNoCorriente = ($company->pasivoNoCorriente === null) ? 0.00 : $company->pasivoNoCorriente;
            $company->importeNetoCifraNegocios = ($company->importeNetoCifraNegocios === null) ? 0.00 : $company->importeNetoCifraNegocios;
            $company->GastosAnual = ($company->gastoAnual === null) ? $company->importeNetoCifraNegocios - $company->ebitda : $company->gastoAnual;
            $company->activoCorriente = ($company->activoCorriente === null) ? 0.00 : $company->activoCorriente;
            $company->ebitda = ($company->ebitda === null) ? 0.00 : $company->ebitda;
            $company->patrimonioNeto = ($company->patrimonioNeto === null) ? 0.00 : $company->patrimonioNeto;
            ##Fin de Datos para filtros financieros

            ###Campo nuevo agregado el 16/01/2024
            $company->LastUpdate = ($entity->lastUpdate === null) ? '01-01-1800' : Carbon::parse($entity->lastUpdate)->format('d-m-Y');
            #dd($company);

            ###Campo nuevo agregado el 21/05/2024
            $company->Country = $entity->pais;
            $company->LogoEmpresa = ($entity !== null && $entity->logo !== null) ? $entity->logo : "";

            $resultApi = $elasticApi->sendDataCompanies($company);

            if($resultApi === null && isset($cif) && $cif != ""){
                return Response::json(['error' => 'Error msg'], 404);
            }

            if(is_object($resultApi)){
                $this->info($company->identificativo. " resultado del envio a elastic: ".$resultApi->message);
            }else{
                $this->info($company->identificativo. " resultado del envio a elastic: ".$resultApi);
            }
            $this->info("Next skip value: ".$i);
            $i++;
        }

        $this->info("Creadas / actualizadas ". $companies->count()." empresas");

        $elasticEnvironment = config('services.elastic_ayudas.environment');
        /*$mail = new \App\Mail\ElasticCompanies($companies->count(), $elasticEnvironment);
        Mail::to('ivan@innovating.works')->send($mail);*/
        $message = "Total de empresas enviadas a elastic entorno '".$elasticEnvironment."': ".$i;

        /*try{
            Artisan::call('send:telegram_notification', [
                'message' =>  $message
            ]);
        }catch(Exception $e){
            dd($e->getMessage());
        }*/

        return COMMAND::SUCCESS;

    }
}

