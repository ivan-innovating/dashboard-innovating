<?php


namespace App\Libs;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PhpParser\Node\Stmt\TryCatch;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function PHPSTORM_META\map;

class ElasticApi
{
    public $client;
    public $apikey;

    function __construct() {

        $elasticEnvironment = config('services.elastic_ayudas.environment');
        $urlEndpoint = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.endpoint');
        $this->apikey = config('services.elastic_ayudas.servers.'.$elasticEnvironment.'.api_key');

        $this->client = new \GuzzleHttp\Client([
            'base_uri' => $urlEndpoint,
        ]);

    }

    public function sendDataCompanies($company){

        $cnaes = array();

        if(isset($company->cnae)){
            $cnaes = $company->cnae;
        }else if(isset($company->Cnaes)){
            $companycnaes = json_decode($company->Cnaes);

            if(!empty($companycnaes)){
                foreach($companycnaes as $key => $cnae){
                    if($key == "display_value"){
                        $cnaes = $cnae;
                    }
                }
            }
        }
        if(isset($company->cnaeEditado)){
            $cnaes = $company->cnaeEditado;
        }

        $objetoSocial = (isset($company->objetoSocial)) ? strip_tags($company->objetoSocial) : '';

        if((isset($company->objetoSocialEditado))){
            $objetoSocial = strip_tags($company->objetoSocialEditado);
        }

        if(isset($company->simulada) && $company->simulada == 1){
            $cif = $company->CIF;
            if($company->id_analisis !== null){
                $cif = $company->CIF."-".$company->id_analisis;
            }

            $dataSend = [
                'ID' => $cif,
                'Nombre' => mb_strtolower($company->Nombre),
                'NIF' => $company->CIF,
                'ComunidadAutonoma' => (!empty($company->Comunidades)) ? $company->Comunidades : [mb_strtolower($company->ccaa_ok)],
                'CategoriaEmpresa' => $company->categoriaEmpresa,
                'CNAE' => (empty($cnaes)) ? '': $cnaes,
                'FechaConstitucion' => ($company->fechaConstitucion === null) ? '01-01-1800' : Carbon::parse($company->fechaConstitucion)->format('d-m-Y'),
                'PerfilesFinanciacion' => (!isset($company->idperfilesfinanciacion)) ? array() : array_values(array_unique($company->idperfilesfinanciacion)),
                'TextosTecnologia' => (isset($company->TextosTecnologia)) ? mb_substr(strip_tags(trim($company->TextosTecnologia, ",")),0,2000) : mb_substr(strip_tags(trim($company->Textos_Tecnologia, ",")),0,2000),
                'TextosTramitaciones' => (isset($company->Textos_Tramitaciones)) ? mb_substr(strip_tags($company->Textos_Tramitaciones),0,2000) : '' ,
                'TextosProyectos' => (isset($company->Textos_Proyectos)) ? mb_substr(strip_tags($company->Textos_Proyectos),0, 2000) : '' ,
                'TextosDocumentos' => (isset($company->Textos_Documentos)) ? mb_substr(strip_tags($company->Textos_Documentos),0,2000) : '',
                //TODO: ENVIAR esta informacion REAL de ZOHO
                'PageRank' => $company->empresaPageRank,
                'ObjetoSocial' => $objetoSocial,
                'NumAyudas' => (isset($company->totalConcesiones))? $company->totalConcesiones : 0,
                'NumPatentes' => (isset($company->totalPatentes))? $company->totalPatentes : 0,
                'SelloPyme' => ($company->SelloPyme == 1) ? true : false,
                'LastPerfilFin' => (int)$company->anioBalance,
                'ListadoEmails' => $company->listadoEmails,
                'LinkInterno' => (isset($company->uri)) ? $company->uri : '',
                'EstadoEntidad' => mb_strtolower($company->situacion),
                //Nuevos campos añadidos Marzo - 2022
                'Naturaleza' => json_decode($company->naturalezaEmpresa, true),
                //'SituacionCrisis' => (isset($company->EmpresaCrisis)) ? true : false,
                //Nuevos campos añadidos 03/05/2022
                'TRL' => (isset($company->valorTrl))? $company->valorTrl : 10,
                'GastoIDI' => (isset($company->cantidadImasD))? round($company->cantidadImasD,0) : 0,
                'TotalGastoAnual' => (isset($company->gastoAnual))? $company->gastoAnual : 0,
                'FlagsEntidad' => $company->FlagsEntidad,
                'SubcontratacionMinimaPartner' => 0,
                'ClienteDeducible' => "",
                //Nuevos campos añadidos 05/07/2022
                'FechaSelloPyme' => $company->SelloPymeValidez,
                'LastDateFin' => $company->lastFinanciacion,
                //'Lider' => $company->lider,
                'MinIntensidad' => $company->IntensidadAyudas,
                //Nuevos campos añadidos 13/12/2022
                'FiltroLider' => $company->lider,
                'XPCooperacion' => $company->XPCooperacion,
                'XPLider' => $company->XPLider,
                'DominiosEmpresa' => $company->DominiosEmpresa,
                'Empleados' => (isset($company->empleados)) ? $company->empleados : 0,
                'GastoIDMax' => $company->GastoIDMax,
                //Nuevos campos añadidos 08/06/2022
                'IDAyudasConcedidas' => ($company->IDAyudasConcedidas === null) ? []: $company->IDAyudasConcedidas,
                'IDOrgDeptConcedido' => ($company->IDOrgDeptConcedido === null) ? [] : $company->IDOrgDeptConcedido,
                'IDInteresesConcedido' => ($company->IDInteresesConcedida === null) ? [] : $company->IDInteresesConcedida,
                'TipoFinanciacionConcedido' => ($company->TipoFinanciacionConcedida === null) ? [] : $company->TipoFinanciacionConcedida,
                'NumUsuariosInnovating' => $company->NumUsuariosInnovating,
                'NumUsuariosNoInnovating' => $company->NumUsuariosNoInnovating,
                'Featured' => ($company->featured !== null && $company->featured == 1) ? true : false,
                'LastUpdate' => ($company->LastUpdate === null) ? "01-01-1800" : $company->LastUpdate,
                'Country' => $company->Country
            ];


        }else{

            $dataSend = [
                'ID' => (string)$company->elastic_id,
                'Nombre' => (isset($company->Nombre)) ? mb_strtolower($company->Nombre) : mb_strtolower($company->denominacion),
                'NIF' => $company->identificativo,
                'ComunidadAutonoma' => (!empty($company->Comunidades)) ? $company->Comunidades : [mb_strtolower($company->einforma_ccaa)],
                'CategoriaEmpresa' => $company->categoriaEmpresa,
                'CNAE' => (empty($cnaes)) ? '': $cnaes,
                'FechaConstitucion' => ($company->fechaConstitucion === null) ? '01-01-1800' : Carbon::parse($company->fechaConstitucion)->format('d-m-Y'),
                'PerfilesFinanciacion' => (!isset($company->idperfilesfinanciacion)) ? array() : array_values(array_unique($company->idperfilesfinanciacion)),
                'TextosTecnologia' => (isset($company->TextosTecnologia)) ? mb_substr(strip_tags(trim($company->TextosTecnologia, ",")),0,2000) : '',
                'TextosTramitaciones' => (isset($company->TextosTramitaciones)) ? mb_substr(strip_tags($company->TextosTramitaciones),0,2000) : '' ,
                'TextosProyectos' => (isset($company->TextosProyectos)) ? mb_substr(strip_tags($company->TextosProyectos),0, 2000) : '' ,
                'TextosDocumentos' => (isset($company->TextosDocumentos)) ? mb_substr(strip_tags($company->TextosDocumentos),0,2000) : '',
                //TODO: ENVIAR esta informacion REAL de ZOHO
                'PageRank' => $company->empresaPageRank,
                'ObjetoSocial' => $objetoSocial,
                'NumAyudas' => (isset($company->totalConcesiones))? $company->totalConcesiones : 0,
                'NumPatentes' => (isset($company->totalPatentes))? $company->totalPatentes : 0,
                'SelloPyme' => ($company->SelloPyme == 1) ? true : false,
                'LastPerfilFin' => (int)$company->anioBalance,
                'ListadoEmails' => $company->listadoEmails,
                'LinkInterno' => (isset($company->uri)) ? $company->uri : '',
                'EstadoEntidad' => mb_strtolower($company->situacion),
                //Nuevos campos añadidos Marzo - 2022
                'Naturaleza' => json_decode($company->naturalezaEmpresa, true),
                //'SituacionCrisis' => (isset($company->EmpresaCrisis)) ? true : false,
                //Nuevos campos añadidos 03/05/2022
                'TRL' => (isset($company->valorTrl))? $company->valorTrl : 10,
                'GastoIDI' => (isset($company->cantidadImasD))? round($company->cantidadImasD,0) : 0,
                'TotalGastoAnual' => (isset($company->gastoAnual))? $company->gastoAnual : 0,
                'FlagsEntidad' => $company->FlagsEntidad,
                'SubcontratacionMinimaPartner' => (isset($company->minimosubcontratar)) ? $company->minimosubcontratar : 0,
                'ClienteDeducible' => (!empty($company->Codigo_cliente))? $company->Codigo_cliente : "",
                //Nuevos campos añadidos 05/07/2022
                'FechaSelloPyme' => $company->SelloPymeValidez,
                'LastDateFin' => $company->lastFinanciacion,
                //'Lider' => $company->lider,
                'MinIntensidad' => $company->IntensidadAyudas,
                //Nuevos campos añadidos 13/12/2022
                'FiltroLider' => $company->lider,
                'XPCooperacion' => $company->XPCooperacion,
                'XPLider' => $company->XPLider,
                'DominiosEmpresa' => $company->DominiosEmpresa,
                'Empleados' => (isset($company->empleados)) ? $company->empleados : 0,
                'GastoIDMax' => $company->GastoIDMax,
                'IDAyudasConcedidas' => ($company->IDAyudasConcedidas === null) ? []: $company->IDAyudasConcedidas,
                //Nuevos campos añadidos 08/06/2022
                'IDOrgDeptConcedido' => ($company->IDOrgDeptConcedido === null) ? [] : $company->IDOrgDeptConcedido,
                'IDInteresesConcedido' => ($company->IDInteresesConcedida === null) ? [] : $company->IDInteresesConcedida,
                'TipoFinanciacionConcedido' => ($company->TipoFinanciacionConcedida === null) ? [] : $company->TipoFinanciacionConcedida,
                'NumUsuariosInnovating' => $company->NumUsuariosInnovating,
                'NumUsuariosNoInnovating' => $company->NumUsuariosNoInnovating,
                'Featured' => ($company->featured !== null && $company->featured == 1) ? true : false,
                'LastUpdate' => $company->LastUpdate,
                'Country' => $company->Country
            ];
        }

        ##Datos para filtros financieros 
        $dataSend['ActivoFijo'] = ($company->activoNoCorriente === null) ? 0 : $company->activoNoCorriente;
        $dataSend['BeneficioAnual'] = $company->ebitda;
        $dataSend['Circulante'] = $company->activoCorriente - $company->pasivoCorriente;            
        $dataSend['Ingresos'] = $company->importeNetoCifraNegocios;
        $dataSend['MargenEndeudamiento'] = $company->patrimonioNeto -$company->pasivoNoCorriente;
        $dataSend['UltimoEjercicioFinanciero'] = (int)$company->anioBalance;
        $dataSend['SPIAuto'] = $company->SPIAuto;            
        $dataSend['ActivoCorriente'] = $company->activoCorriente;
        $dataSend['GastosAnual'] = $company->GastosAnual;
        $dataSend['PasivoCorriente'] = $company->pasivoCorriente;
        $dataSend['PasivoNoCorriente'] = $company->pasivoNoCorriente;
        $dataSend['PatrimonioNeto'] = $company->patrimonioNeto;
        $dataSend['TrabajosInmovilizados'] = $company->trabajosInmovilizado;
        $dataSend['FechaUltimaPatenteRegistrada'] = $company->FechaUltimaPatenteRegistrada;
        ##Fin de datos para filtros financieros 

        try {
            $response = $this->client->post('company',[
                'json' => $dataSend,
                'headers' => [
                    'x-api-key' => $this->apikey
                ]
            ]);
            if($response->getStatusCode() == 201){
                //$responseApi = json_decode((string)$response->getBody());
                $responseApi = json_decode((string)$response->getBody());
                //dump($responseApi);
                return $responseApi;
                //dd($responseApi);
            }else{
                $responseApi = json_decode((string)$response->getBody());
                //dump($responseApi);
                return $responseApi;
            }
        } catch (ClientException $e) {
            //dump($dataSend);
            Log::error(\GuzzleHttp\Psr7\Message::toString($e->getRequest()));
            Log::error(\GuzzleHttp\Psr7\Message::toString($e->getResponse()));
            return NULL;
        }catch (ServerException $e) {
            //dump($dataSend);
            Log::error(\GuzzleHttp\Psr7\Message::toString($e->getRequest()));
            Log::error(\GuzzleHttp\Psr7\Message::toString($e->getResponse()));
            return NULL;
        }
        

    }

    public function sendDataEncajes($encaje){

        $comunidadesAutonomas = [];
        $categoriaSolicitantes = [];
        if(isset($encaje->TipoEncaje) && $encaje->TipoEncaje == "Proyecto"){
            if($encaje->Encaje_ccaa !== null && $encaje->Encaje_ccaa != "null"){
                $comunidadesAutonomas = json_decode($encaje->Encaje_ccaa, true);
            }
            if($encaje->Encaje_categoria !== null && $encaje->Encaje_categoria != "null"){
                $categoriaSolicitantes = json_decode($encaje->Encaje_categoria,true);
            }else{
                $categoriaSolicitantes = ["Micro","Pequeña","Mediana","Grande"];
            }
        }else{
            if($encaje->Ccaas !== null && $encaje->Ccaas != "null"){
                $comunidadesAutonomas = json_decode($encaje->Ccaas, true);
            }
            if($encaje->Categoria !== null && $encaje->Categoria != "null"){
                $categoriaSolicitantes = json_decode($encaje->Categoria,true);
            }else{
                $categoriaSolicitantes = ["Micro","Pequeña","Mediana","Grande"];
            }
        }
        if(!empty($comunidadesAutonomas)){
            foreach($comunidadesAutonomas as $key => $ca){
                $comunidadesAutonomas[$key] = mb_strtolower($ca);
            }
        }

        $areasTec = '';
        /*if(!empty($encaje['AreasTec']) && is_array($encaje['AreasTec'])){
            foreach ($encaje['AreasTec'] as $area){
                $areasTec .= $area['display_value'].' ';
            }
        }*/

        $tagsTec = '';
        if(isset($encaje->TagsTec) && !empty($encaje->TagsTec)){
            if(is_array(json_decode($encaje->TagsTec, true)) && !empty(json_decode($encaje->TagsTec, true))){
                foreach(json_decode($encaje->TagsTec, true) as $tag){
                    $tagsTec .= mb_strtolower($tag).",";
                }
            }
        }   

        $perfilIntereses = array();

        if($encaje->PerfilFinanciacion !== null || $encaje->PerfilFinanciacion != "null"){
            $perfilIntereses = json_decode($encaje->PerfilFinanciacion, true);
        }
        if(!$perfilIntereses || empty($perfilIntereses) && $encaje->PerfilFinanciacion !== null){
            $perfilIntereses = json_decode($encaje->PerfilFinanciacion, true);
        }

        if($encaje->tipoPartner == "Consultoría"){
            $perfilIntereses[] = "231435000088214863";
        }



        $fechaInicio = "";
        if($encaje->Inicio){
            $fechaInicio =  Carbon::parse($encaje->Inicio)->format('d-m-Y');
        }

        if($fechaInicio == "" || $fechaInicio === null){
            $fechaInicio = "01-01-1800";
        }

        $fechaFin = "";
        if($encaje->Fin){
            $fechaFin =  Carbon::parse($encaje->Fin)->format('d-m-Y');
        }

        if($fechaFin == "" || $fechaFin === null){
            $fechaFin = "01-01-1800";
        }

        $encaje_titulo = "";
        if($encaje->encaje_titulo){
            $encaje_titulo = $encaje->encaje_titulo;
        }

        $acronimo = "";
        if($encaje->Acronimo){
            $acronimo = $encaje->Acronimo;
        }

        $trl = 10;
        if($encaje->Encaje_trl && $encaje->Encaje_trl < 10){
            $trl = $encaje->Encaje_trl;
        }elseif(!isset($encaje->Encaje_trl) || $encaje->Encaje_trl == 10){
            if(isset($encaje->Trl) && $encaje->Trl < 10){
                $trl = $encaje->Trl;
            }
        }

        $presupuesto = 0;

        ### ESTA logica se ira a tomar por saco cuando este funcionado los campos de pruesputeso max y min en elastic

        ##TIPOEncaje = proyecto
        if(isset($encaje->Encaje_presupuesto) && $encaje->Encaje_presupuesto > 0){
            $presupuesto = $encaje->Encaje_presupuesto;        
        #ENCAJE PARA AYUDAS EUROPEAS POR PRESUPUESTO ESTIMADO PARTICIPANTE
        }elseif($encaje->PresupuestoParticipante !== null){
            $presupuesto = $encaje->PresupuestoParticipante;        
        #TIPOEncaje = linea || interno
        }elseif(isset($encaje->PresupuestoMin) && $encaje->PresupuestoMin > 0){
            if($encaje->DuracionMax !== null){
                $duracion = round($encaje->DuracionMax/12,0,PHP_ROUND_HALF_UP);
                $duracion = ($duracion <= 0) ? 1: $duracion;
                $presupuesto = $encaje->PresupuestoMin/$duracion;
            }else{
                $presupuesto = $encaje->PresupuestoMin/2;
            }
        }
        if(($encaje->PresupuestoMin == 0 && $encaje->PresupuestoMax == 0) || ($encaje->PresupuestoMax === null && $encaje->PresupuestoMin === null)){
            //$presupuestoMin = 10000000;
            //$presupuestoMax = 10000000;
        }

        ### FIN DE ESTA logica se ira a tomar por saco cuando este funcionado los campos de pruesputeso max y min en elastic

        $tagsCompletas = rtrim($tagsTec,",").",".$encaje->PalabrasClaveES.",".$encaje->PalabrasClaveEN;

        if(isset($encaje->TipoEncaje) && $encaje->TipoEncaje == "Proyecto"){

            if(isset($encaje->Encaje_opcioncnaes) && $encaje->Encaje_opcioncnaes == "Todos"){
                $encaje->cnaes_ok = array();
            }

            if($encaje->tipoPartner == "Consultoría"){
                $encaje->tipoEncaje = "consultoria";                
                $fechaMaxConstitucion = '01-01-1800';
                $encaje->Encaje_ambito = "nacional";
                $encaje->Encaje_opcioncnaes = "todos";
                $encaje->naturalezaPartner = json_encode(["6668839"]);
            }else{
                $encaje->tipoEncaje = "proyecto";
                $perfilIntereses = json_decode($encaje->PerfilFinanciacion);
            }

            if(empty($perfilIntereses)){
                if($encaje->tipoPartner == "cooperacion" || $encaje->tipoPartner == "Cooperación"){
                    $perfilIntereses[] = "231435000088214889";
                }
                if($encaje->tipoPartner == "subcontratacion" || $encaje->tipoPartner == "Subcontratación"){
                    $perfilIntereses[] = "231435000088214862";
                }
            }

            if($perfilIntereses === null){
                $perfilIntereses = array();
            }

            if($encaje->encaje_perfilfinanciacion !== null){
                foreach(json_decode($encaje->encaje_perfilfinanciacion, true) as $perfil){
                    array_push($perfilIntereses, (string)$perfil);
                }
                if($encaje->tipoPartner == "cooperacion" || $encaje->tipoPartner == "Cooperación"){
                    array_push($perfilIntereses, "231435000088214889");
                }
                if($encaje->tipoPartner == "subcontratacion" || $encaje->tipoPartner == "Subcontratación"){                    
                    array_push($perfilIntereses, "231435000088214862");
                }
                if($encaje->tipoPartner == "Consultoría" | $encaje->tipoPartner == "consultoria"){
                    array_push($perfilIntereses, "231435000088214862");
                }
            }
         
            if($encaje->naturalezaPartner == "null"){
                $encaje->naturalezaPartner = null;
            }
      
            $dataSend = [
                'ID' => (string)$encaje->encaje_id,
                'Organismo' => (isset($encaje->NombreOrganismo)) ? $encaje->NombreOrganismo : '',
                'InicioConvocatoria' => $fechaInicio,
                'FinalConvocatoria' => $fechaFin,
                'Acronimo' => $acronimo,
                'TituloAyuda' => $encaje->encaje_titulo,
                'Ambito' => (isset($encaje->Encaje_ambito)) ? mb_strtolower($encaje->Encaje_ambito) : mb_strtolower($encaje->Ambito) ,
                'ComunidadesAutonomas' => $comunidadesAutonomas,
                'Opcion_CNAE' => ($encaje->Encaje_opcioncnaes !== "Todos") ? mb_strtolower($encaje->Encaje_opcioncnaes) : "todos",
                'CNAES' => $encaje->cnaes_ok,
                //'FechaMinConstitucion' => $fechaMinConstitucion,
                'FechaMaxConstitucion' => $encaje->fechaMaxConstitucion,
                'CategoriasSolicitantes' => ($categoriaSolicitantes !== null) ? json_decode(json_encode($categoriaSolicitantes), true) : array(),
                'PerfilIntereses' => ($perfilIntereses === null) ? [] : array_values(array_unique($perfilIntereses)),
                'NombreTematica' => $encaje_titulo,
                'Descripcion' => trim(preg_replace('/\s\s+/', ' ', strip_tags($encaje->encaje_descripcion))),
                'AreasTecnologicas' => $areasTec,
                'TagsTecnologia' => array_values($encaje->TagsCompletas),
                'PalabrasClaveEN' => (!empty($encaje->PalabrasClaveEN)) ? $encaje->PalabrasClaveEN : '',
                'PalabrasClaveES' => (!empty($tagsCompletas)) ? $tagsCompletas : '',
                'IDAyuda' => (string)$encaje->Ayuda_id,
                //Nuevos campos añadidos Marzo - 2022
                'TipoEncaje' => mb_strtolower($encaje->tipoEncaje),
                'OrganismoID' => (string)$encaje->IdOrganismo,
                'Naturaleza' => (isset($encaje->naturalezaPartner)) ? json_decode($encaje->naturalezaPartner, true) : array(),
                'SituacionCrisis' => ($encaje->EmpresaCrisis == 1) ? "no crisis" :  "no filtra",
                //Nuevos campos añadidos 27 abr-2022
                'TRLMin' => (int)$trl,
                'PresupuestoMin' => $presupuesto,
                'TematicaObligatoria' => ($encaje->TematicaObligatoria == 1) ? true : false,
                //Nuevos campos añadidos 30 jul-2022
                'Convocatoria' => (isset($encaje->Convocatoria)) ? $encaje->Convocatoria : '',
                'FechaCreacion' => (isset($encaje->FechaCreacion)) ? Carbon::parse($encaje->FechaCreacion)->format('d-m-Y') : '01-01-2022',
                'Intensidad' => 4,
                'Razon' => '',
                'Url' => $encaje->url_proyecto,
                //Nuevos campos añadidos 13/12/2022                 
                'XPCooperacion' => '',//enviar vacio si es null enviar string formato "true" o "false"
                'XPLider' => '',//enviar vacio si es null enviar string formato "true" o "false"
                'NumeroMinimoEmpleados' => $encaje->NumeroMinimoEmpleados,
                'NumeroMaximoEmpleados' => $encaje->NumeroMaximoEmpleados,
                'Country' => ($encaje->Country === null) ? "" : $encaje->Country,
            ];

        }else{

            if(empty($perfilIntereses)){
                return "Encaje/Ayuda con perfil intereses vacío";
            }
            $opcion = mb_strtolower($encaje->Encaje_opcioncnaes);

            if($opcion === null || $opcion == ""){
                $opcion = ($encaje->OpcionCNAE !== "Todos") ? mb_strtolower($encaje->OpcionCNAE): "todos";
                if(isset($encaje->opcioncnae_ok)){
                    $opcion = mb_strtolower($encaje->opcioncnae_ok);
                }
                if($encaje->CNAES && $opcion != "todos"){
                    if($encaje->CNAES !== null && $encaje->CNAES != "null"){
                        foreach(json_decode($encaje->CNAES, true) as $idcnae){
                            $cnaedb = DB::table('Cnaes')->where('Id_zoho', $idcnae)->select('Nombre')->first();
                            if($cnaedb){
                                $encaje->cnaes_ok[] = mb_strtolower($cnaedb->Nombre);
                            }
                        }
                    }
                }
            }else{

                if(empty($encaje->cnaes_ok)){
                    $encaje->cnaes_ok = array();

                    if($opcion != "Todos"){
                        if($encaje->Encaje_cnaes){
                            if($encaje->Encaje_cnaes !== null && $encaje->Encaje_cnaes != "null"){
                                foreach(json_decode($encaje->Encaje_cnaes, true) as $idcnae){
                                    $cnaedb = DB::table('Cnaes')->where('Id_zoho', $idcnae)->select('Nombre')->first();
                                    if($cnaedb){
                                        $encaje->cnaes_ok[] = mb_strtolower($cnaedb->Nombre);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            try{
                $url = $encaje->uriOrganismo."/".$encaje->Uri;
            }catch(Exception $e){
                #dump($encaje);
                dd($e->getMessage());
            }

            $tagsElastic = array_filter(explode(",",$tagsCompletas));

            if(!empty($tagsElastic)){
                foreach($tagsElastic as $key => $tag){
                    $tagsElastic[$key] = str_replace('"','', trim($tag, " ,[]\n\r\t\v\x00"));
                }
            }

            if($encaje->naturalezaPartner == "null"){
                $encaje->naturalezaPartner = null;
            }

            if($encaje->PresupuestoParticipante !== null && $encaje->PresupuestoParticipante > 0){
                $presupuesto = $encaje->PresupuestoParticipante;
            }

            $dataSend = [
                'ID' => (string)$encaje->encaje_id,
                'Organismo' => $encaje->NombreOrganismo,
                'InicioConvocatoria' => $fechaInicio,
                'FinalConvocatoria' => $fechaFin,
                'Acronimo' => $acronimo,
                'TituloAyuda' => $encaje->Titulo,
                'Ambito' => mb_strtolower($encaje->Ambito),
                'ComunidadesAutonomas' => $comunidadesAutonomas,
                'Opcion_CNAE' => $opcion,
                'CNAES' => $encaje->cnaes_ok,
                'FechaMaxConstitucion' => $encaje->fechaMaxConstitucion,
                'CategoriasSolicitantes' => ($categoriaSolicitantes !== null) ? json_decode(json_encode($categoriaSolicitantes), true) : array(),
                'PerfilIntereses' => $perfilIntereses,
                'NombreTematica' => $encaje_titulo,
                'Descripcion' => trim(preg_replace('/\s\s+/', ' ', strip_tags($encaje->encaje_descripcion))),
                'AreasTecnologicas' => $areasTec,
                'TagsTecnologia' => array_values($encaje->TagsCompletas),
                'PalabrasClaveEN' => (isset($encaje->PalabrasClaveEN)) ? $encaje->PalabrasClaveEN : '',
                'PalabrasClaveES' => (!empty($tagsCompletas)) ? $tagsCompletas : '',
                'IDAyuda' => (string)$encaje->Ayuda_id,
                //Nuevos campos añadidos Marzo - 2022
                'TipoEncaje' => mb_strtolower($encaje->Tipo),
                'OrganismoID' => (string)$encaje->IdOrganismo,
                'Naturaleza' => (isset($encaje->naturalezaPartner)) ? json_decode($encaje->naturalezaPartner, true) : array(),
                'SituacionCrisis' => ($encaje->EmpresaCrisis == 1) ? "no crisis" :  "no filtra",
                //Nuevos campos añadidos 27 abr-2022
                'TRLMin' => (int)$trl,
                'PresupuestoMin' => $presupuesto,
                //'PresupuestoMax' => $presupuestoMax,
                'TematicaObligatoria' => ($encaje->TematicaObligatoria == 1) ? true : false,
                //Nuevos campos añadidos 30 jul-2022
                'Convocatoria' => '',
                'FechaCreacion' => (isset($encaje->FechaCreacion)) ? Carbon::parse($encaje->FechaCreacion)->format('d-m-Y') : '01-01-2022',
                'Intensidad' => $encaje->Intensidad,
                'Razon' => '',
                'Url' => $url,
                //Nuevos campos añadidos 13/12/2022
                'XPCooperacion' => '',//enviar vacio si es null enviar string formato "true" o "false"
                'XPLider' => '',//enviar vacio si es null enviar string formato "true" o "false"
                'NumeroMinimoEmpleados' => $encaje->NumeroMinimoEmpleados-5,// se añade un -5 para provocar encajes por pocos empleados caso empresa 1 empleado ayuda min 2
                'NumeroMaximoEmpleados' => $encaje->NumeroMaximoEmpleados+5,// se añade un +5 para provocar encajes por muchos empleados caso empresa 10 empleado ayuda max 10
                'Country' => ($encaje->Country === null) ? "" : $encaje->Country,
            ];

            if($encaje->Ambito === null || !$encaje->OpcionCNAE === null || $encaje->Titulo === null){
                return "Encaje/Ayuda con datos incompletos";
            }
        }

        try{

            $response = $this->client->post('publicAid',[
                'json' => $dataSend,
                'headers' => [
                    'x-api-key' => $this->apikey
                ]
            ]);

            if($response->getStatusCode() == 201){
                $responseApi = json_decode((string)$response->getBody());
                //dd($responseApi);
                $responseApi = json_decode((string)$response->getBody());
                if(isset($responseApi->message)){
                    return $responseApi->message;
                }else{
                    return $responseApi;
                }
                //dd($responseApi);
            }else{
                $responseApi = json_decode((string)$response->getBody());
                //dd($responseApi);
                if(isset($responseApi->message)){
                    return $responseApi->message;
                }else{
                    return $responseApi;
                }
                return $responseApi->message;
            }

        }catch (\GuzzleHttp\Exception\ServerException $e){
            //dump($encaje);
            //dump($comunidadesAutonomas);
            dd($e->getResponse()->getBody()->getContents());
            return NULL;
        }catch (\GuzzleHttp\Exception\ClientException $e){
            //dump($encaje);
            //dump($comunidadesAutonomas);
            dd($e->getMessage());
            dd($e->getResponse()->getBody()->getContents());
            return NULL;
        }

    }

    public function sendDataProyectos($proyecto){


        $dataSend = array(
            "ID" => (string)$proyecto->id,
            "Acronimo" => (isset($proyecto->Acronimo)) ? $proyecto->Acronimo : '',
            "Titulo" => (isset($proyecto->Titulo)) ? $proyecto->Titulo : '',
            "Objetivo" => (isset($proyecto->Descripcion)) ? $proyecto->Descripcion : '',
            "Innovacion" => "",
            //"PalabrasClaveES" => $proyecto->CampoTexto1,
            "CampoTexto1" => $proyecto->CampoTexto1,
            "CampoTexto2" => $proyecto->CampoTexto2,
            "OtrosTextos" => $proyecto->OtrosTextos,
            "IDOrganismo" => (isset($proyecto->IdOrganismo)) ? (string)$proyecto->IdOrganismo : '',
            "Organismo" => (isset($proyecto->OrganismoNombre)) ? $proyecto->OrganismoNombre : '',
            "FechaInicio" => (isset($proyecto->inicio)) ? Carbon::parse($proyecto->inicio)->format('d-m-Y') : '01-01-1800',
            "FechaFinal" => (isset($proyecto->fin)) ? Carbon::parse($proyecto->fin)->format('d-m-Y') : '01-01-1800',
            "Presupuesto" => (isset($proyecto->presupuestoTotal)) ? (float)$proyecto->presupuestoTotal : 0,
            "UrlProyecto" => $proyecto->uri,
            "Ambito" => (isset($proyecto->ambitoConvocatoria)) ? $proyecto->ambitoConvocatoria : '',
            "TipoConvocatoria" => (isset($proyecto->tipoConvocatoria)) ? $proyecto->tipoConvocatoria : '',
            "Estado" => $proyecto->Estadorevisado,
            "TextoHtmlPartners" => $proyecto->TextoHtmlPartners,

        );

        try{

            $response = $this->client->post('project',[
                'json' => $dataSend,
                'headers' => [
                    'x-api-key' => $this->apikey
                ]
            ]);

            if($response->getStatusCode() == 201){
                $responseApi = json_decode((string)$response->getBody());
                //dd($responseApi);
                $responseApi = json_decode((string)$response->getBody());
                return $responseApi;
                //dd($responseApi);
            }else{
                $responseApi = json_decode((string)$response->getBody());
                //dd($responseApi);
                return $responseApi;
            }

        }catch (\GuzzleHttp\Exception\ServerException $e){
            //dump($encaje);
            //dump($dataSend);
            //dd($e->getResponse()->getBody()->getContents());
            Log::error($e->getMessage());
            return NULL;
        }catch (\GuzzleHttp\Exception\ClientException $e){
            //dump($encaje);
            //dump($dataSend);
            //dd($e->getResponse()->getBody()->getContents());
            Log::error($e->getMessage());
            return NULL;
        }

    }

    public function sendDataConcessions($concesion){

        $dataSend = [
            'ID' => $concesion->ID,
            'NIFCompany' => $concesion->NIFCompany,
            'EmpresaBeneficiaria' => $concesion->EmpresaBeneficiaria,
            'UrlEmpresaBeneficiaria' => $concesion->UrlEmpresaBeneficiaria,
            'Administration' => $concesion->Administration,
            'Department' => $concesion->Department,
            'Organ' => $concesion->Organ,
            'Announcement' => $concesion->Announcement,
            'UrlBbrr' => $concesion->UrlBbrr,
            'BudgetApplication' => $concesion->BudgetApplication,
            'GrantDate' => $concesion->GrantDate,
            'Amount' => $concesion->Amount,
            'Instrument' => $concesion->Instrument,
            'EquivalentAid' => $concesion->EquivalentAid,
            'Date' => $concesion->Date,
            'IdConvocatoria' => $concesion->IdConvocatoria,
            'MinimiTexto' => $concesion->MinimiTexto,
            'EsMinimi' => $concesion->EsMinimi,
            'TieneDetalle' => $concesion->TieneDetalle,
            'PalabrasClave' => $concesion->PalabrasClave,
            'IdSecundario' => $concesion->IdSecundario,
            'ConcesionType' => $concesion->ConcesionType,
            'IdOrganismo' => $concesion->IdOrganismo,
            'TipoOrganismo' => $concesion->TipoOrganismo,
            'Date' => $concesion->Date,
            'IdConvocatoria' => $concesion->IdConvocatoria,
            'MinimiTexto' => $concesion->MinimiTexto,
            'TieneDetalle' => $concesion->TieneDetalle,
            'PalabrasClave' => $concesion->PalabrasClave,
            'IdSecundario' => $concesion->IdSecundario,
        ];

        try {
            $response = $this->client->post('concession',[
                'json' => $dataSend,
                'headers' => [
                    'x-api-key' => $this->apikey
                ]
            ]);
            if($response->getStatusCode() == 201){
                //$responseApi = json_decode((string)$response->getBody());
                $responseApi = json_decode((string)$response->getBody());                   
                //dump($responseApi);
                return $responseApi;
                //dd($responseApi);
            }else{
                $responseApi = json_decode((string)$response->getBody());
                //dump($responseApi);
                return $responseApi;
            }
        } catch (ClientException $e) {
            //dump($dataSend);
            Log::error(\GuzzleHttp\Psr7\Message::toString($e->getRequest()));
            Log::error(\GuzzleHttp\Psr7\Message::toString($e->getResponse()));
            return NULL;
        }catch (ServerException $e) {
            //dump($dataSend);
            Log::error(\GuzzleHttp\Psr7\Message::toString($e->getRequest()));
            Log::error(\GuzzleHttp\Psr7\Message::toString($e->getResponse()));
            return NULL;
        }
        

    }

    public function sendDataInvestigadores($investigador){

        $dataSend = [
            'ID' => $investigador->ID,
            'TypeID' => $investigador->TypeID,
            'LastExperience' => $investigador->LastExperience,
            'ScholarLink' => $investigador->ScholarLink,
            'Email' => $investigador->Email,
            'NIFCompany' => $investigador->NIFCompany,
            'Url' => $investigador->Url,
            'Keywords' => $investigador->Keywords,
            'ResearcherName' => $investigador->ResearcherName,
            'TotalWorks' => $investigador->TotalWorks,
            'ScholarLink' => $investigador->ScholarLink,
            'Description' => $investigador->Description,
            'OrganizationName' => $investigador->OrganizationName,
            'IDLastExperience' => $investigador->IDLastExperience,
            'Link' => $investigador->Link,
            'StartDateLastExperience' => $investigador->StartDateLastExperience   
        ];

        try {
            $response = $this->client->post('research',[
                'json' => $dataSend,
                'headers' => [
                    'x-api-key' => $this->apikey
                ]
            ]);
            if($response->getStatusCode() == 201){
                //$responseApi = json_decode((string)$response->getBody());
                $responseApi = json_decode((string)$response->getBody());                   
                //dump($responseApi);
                return $responseApi;
                //dd($responseApi);
            }else{
                $responseApi = json_decode((string)$response->getBody());
                //dump($responseApi);
                return $responseApi;
            }
        } catch (ClientException $e) {
            //dump($dataSend);
            Log::error(\GuzzleHttp\Psr7\Message::toString($e->getRequest()));
            Log::error(\GuzzleHttp\Psr7\Message::toString($e->getResponse()));
            return NULL;
        }catch (ServerException $e) {
            //dump($dataSend);
            Log::error(\GuzzleHttp\Psr7\Message::toString($e->getRequest()));
            Log::error(\GuzzleHttp\Psr7\Message::toString($e->getResponse()));
            return NULL;
        }

    }

    public function deleteCompany($id){

        try {
            $response = $this->client->delete('company/'.$id,[
                'headers' => [
                    'x-api-key' => $this->apikey
                ]
            ]);

            if($response->getStatusCode() == 202){
                //$responseApi = json_decode((string)$response->getBody());

                $responseApi = json_decode((string)$response->getBody());
                //dump($responseApi);
                return $responseApi;
                //dd($responseApi);
            }else{
                $responseApi = json_decode((string)$response->getBody());
                return $responseApi;
            }
        } catch (ClientException $e) {
            //dump($dataSend);
            //echo \GuzzleHttp\Psr7\Message::toString($e->getRequest());
            //echo \GuzzleHttp\Psr7\Message::toString($e->getResponse());
            return NULL;
        }catch (ServerException $e) {
            //dump($dataSend);            
            Log::error(\GuzzleHttp\Psr7\Message::toString($e->getRequest()));
            Log::error(\GuzzleHttp\Psr7\Message::toString($e->getResponse()));
            return NULL;
        }

    }

    public function deleteEncaje($id){

        try {
            $response = $this->client->delete('publicAid/'.$id,[
                'headers' => [
                    'x-api-key' => $this->apikey
                ]
            ]);

            if($response->getStatusCode() == 202){
                //$responseApi = json_decode((string)$response->getBody());
                return true;
                //dd($responseApi);
            }else{
                return NULL;
            }
        } catch (ClientException $e) {
            //dump($e->getResponse());
            //Log::error(\GuzzleHttp\Psr7\Message::toString($e->getRequest()));
            //Log::error(\GuzzleHttp\Psr7\Message::toString($e->getResponse()));
            return "borrada";
        }catch (ServerException $e) {
            //dump($e->getResponse());
            //Log::error(\GuzzleHttp\Psr7\Message::toString($e->getRequest()));
            //Log::error(\GuzzleHttp\Psr7\Message::toString($e->getResponse()));
            return NULL;
        }


    }


    public function deleteProyecto($id){

        try {
            $response = $this->client->delete('project/'.$id,[
                'headers' => [
                    'x-api-key' => $this->apikey
                ]
            ]);

            if($response->getStatusCode() == 202){
                //$responseApi = json_decode((string)$response->getBody());
                return true;
                //dd($responseApi);
            }else{
                return NULL;
            }
        } catch (ClientException $e) {
            //dump($e->getResponse());
            Log::error(\GuzzleHttp\Psr7\Message::toString($e->getRequest()));
            Log::error(\GuzzleHttp\Psr7\Message::toString($e->getResponse()));
            return "borrada";
        }catch (ServerException $e) {
            //dump($e->getResponse());
            Log::error(\GuzzleHttp\Psr7\Message::toString($e->getRequest()));
            Log::error(\GuzzleHttp\Psr7\Message::toString($e->getResponse()));
            return NULL;
        }


    }
}
