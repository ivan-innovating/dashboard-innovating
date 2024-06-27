<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use App\Http\Settings\GeneralSettings;
use App\Models\UsersEntidad;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelSettings\GeneralSettingsRequest;
use Image;
use stdClass;

class DashboardAjaxController extends Controller
{
    const ALLINTERESES = array("231435000088214889","231435000088223017","231435000088214857","231435000088214861","231435000088214865",
    "231435000088214869","231435000088214873","231435000088214877","231435000089462012");
    const CONSORCIOS = array("231435000088214889");

    public function getDptoOrgano(Request $request){

        $id = $request->get('id');
        $tipo = $request->get('tipo');

        if($tipo == "organo"){
            $organo = DB::table('organos')->where('id', $id)->first();
            $ministerio = DB::table('ministerios')->where('id', $organo->id_ministerio)->first();

            $organo->extradata = null;
            if($ministerio){
                $organo->extradata = $ministerio->Nombre;
            }

            return response()->json(json_encode($organo));
        }

        if($tipo == "departamento"){
            $departamento = DB::table('departamentos')->where('id', $id)->first();
            $ccaa = DB::table('ccaa')->where('id', $departamento->id_ccaa)->first();

            $departamento->extradata = null;
            if($ccaa){
                $departamento->extradata = $ccaa->Nombre;
            }

            return response()->json(json_encode($departamento));

        }


        return false;
    }

    public function saveDptoOrgano(Request $request){

        $id = $request->get('id');
        $tipo = $request->get('tipo');
        $name = $request->get('nombre_innovating');
        $acronimo = $request->get('acronimo');
        $web = $request->get('web');
        $desc = $request->get('descripcion');
        $tlr = $request->get('tlr');
        $minister = $request->get('minister');
        $ccaa = $request->get('ccaa');

        /*if(!$acronimo && !$web && !$desc){
            die("No se han completado todos los campos");
        }*/

        if(!$tipo || !$id){
            die("Error en el guardado de datos");
        }

        if($tipo == "departamento"){

            $check = DB::table('departamentos')->where('Acronimo', $acronimo)->where('id', '!=', $id)->count();

            if($check >= 1){
                return Response::json(array('error' => 'el acronimo esta repetido'));
            }

            $check = DB::table('organos')->where('Acronimo', $acronimo)->count();

            if($check >= 1){
                return Response::json(array('error' => 'el acronimo esta repetido'));
            }

            if($minister){
                $url = cleanUriBeforeSave(str_replace(" ","-", mb_strtolower($request->get('url'))));
                try{
                    DB::table('departamentos')->where('id', $id)->update(
                        [
                            'Nombre_innovating' => $name,
                            'Acronimo' => $acronimo,
                            'Web' => $web,
                            'Descripcion' => $desc,
                            'Tlr' => $tlr,
                            'url' => $url,
                            'id_ministerio' => $minister,
                            'scrapper' => ($request->get('importante') === null) ? 0 : 1,
                            'esFondoPerdido' => ($request->get('fondoperdido') === null) ? 0 : 1,
                            'visibilidad' => ($request->get('visibilidad') === null) ? 0 : 1,
                            'proyectosImportados' => ($request->get('proyectosimportados') === null) ? 0 : 1,
                        ]
                    );
                }catch(Exception $e){
                    die($e->getMessage());
                }
            }else{
                $url = cleanUriBeforeSave(str_replace(" ","-", mb_strtolower($request->get('url'))));
                try{
                    DB::table('departamentos')->where('id', $id)->update(
                        [
                            'Nombre_innovating' => $name,
                            'Acronimo' => $acronimo,
                            'Web' => $web,
                            'Descripcion' => $desc,
                            'Tlr' => $tlr,
                            'url' => $url,
                            'scrapper' => ($request->get('importante') === null) ? 0 : 1,
                            'esFondoPerdido' => ($request->get('fondoperdido') === null) ? 0 : 1,
                            'visibilidad' => ($request->get('visibilidad') === null) ? 0 : 1,
                            'proyectosImportados' => ($request->get('proyectosimportados') === null) ? 0 : 1,
                        ]
                    );
                }catch(Exception $e){
                    die($e->getMessage());
                }
            }
        }
        if($tipo == "organo"){

            $check = DB::table('organos')->where('Acronimo', $acronimo)->where('id', '!=', $id)->count();

            if($check >= 1){
                return Response::json(array('error' => 'el acronimo esta repetido'));
            }

            $check = DB::table('departamentos')->where('Acronimo', $acronimo)->count();

            if($check >= 1){
                return Response::json(array('error' => 'el acronimo esta repetido'));
            }

            if($ccaa){
                $url = cleanUriBeforeSave(str_replace(" ","-", mb_strtolower($request->get('url'))));
                try{
                    DB::table('organos')->where('id', $id)->update(
                        [
                            'Nombre_innovating' => $name,
                            'Acronimo' => $acronimo,
                            'Web' => $web,
                            'Descripcion' => $desc,
                            'Tlr' => $tlr,
                            'url' => $url,
                            'id_ccaa' => $ccaa,
                            'scrapper' => ($request->get('importante') === null) ? 0 : 1,
                            'esFondoPerdido' => ($request->get('fondoperdido') === null) ? 0 : 1,
                            'visibilidad' => ($request->get('visibilidad') === null) ? 0 : 1,
                            'proyectosImportados' => ($request->get('proyectosimportados') === null) ? 0 : 1,
                        ]
                    );
                }catch(Exception $e){
                    die($e->getMessage());
                }
            }else{
                $url = cleanUriBeforeSave(str_replace(" ","-", mb_strtolower($request->get('url'))));
                try{
                    DB::table('organos')->where('id', $id)->update(
                        [
                            'Nombre_innovating' => $name,
                            'Acronimo' => $acronimo,
                            'Web' => $web,
                            'Descripcion' => $desc,
                            'Tlr' => $tlr,
                            'url' => $url,
                            'scrapper' => ($request->get('importante') === null) ? 0 : 1,
                            'esFondoPerdido' => ($request->get('fondoperdido') === null) ? 0 : 1,
                            'visibilidad' => ($request->get('visibilidad') === null) ? 0 : 1,
                            'proyectosImportados' => ($request->get('proyectosimportados') === null) ? 0 : 1,
                        ]
                    );
                }catch(Exception $e){
                    die($e->getMessage());
                }
            }

        }
        if($tipo == "ministerio"){
            try{
                DB::table('ministerios')->where('id', $id)->update(
                    [
                        'Acronimo' => $acronimo,
                        'Web' => $web,
                        'Descripcion' => $desc
                    ]
                );
            }catch(Exception $e){
                die($e->getMessage());
            }
        }
        if($tipo == "ccaa"){
            try{
                DB::table('ccaa')->where('id', $id)->update(
                    [
                        'Acronimo' => $acronimo,
                        'Web' => $web,
                        'Descripcion' => $desc
                    ]
                );
            }catch(Exception $e){
                die($e->getMessage());
            }
        }

        if($request->get('proyectosimportados') !== null){

            try{
                \App\Models\Proyectos::where('organismo', $id)->where('esEuropeo', 1)->where('importado', 0)->whereNotNull('id_europeo')->delete();
            }catch(Exception $e){
                die($e->getMessage());
            }

            return "Actualizado ".$tipo." y borrados sus proyectos scrapeados";
        }

        return "Actualizado ".$tipo;

    }

    public function saveAyuda(Request $request){

        $id = $request->get('id');
        $organismo = $request->get('data');

        $ayuda = $ayuda = \App\Models\Ayudas::where('id', $id)->first();

        if(!$ayuda){
            die("Error ayuda no encontrada");
        }

        try{
            $ayuda = \App\Models\Ayudas::where('id', $id)->update(
                [
                    'Organismo' => $organismo,
                    'LastEditor' => Auth::user()->email,
                    'updated_at' => Carbon::now(),
                ]
            );
        }catch(Exception $e){
            die($e->getMessage());
        }

        $urlfinal = "editconvocatoria/".$ayuda->id;

        return $urlfinal;

    }

    public function editarAyuda(Request $request){

        $id = $request->get('id');

        $intereses = json_encode($request->get('intereses'));
        $categoria = json_encode($request->get('categoria'));
        $descCorta = strip_tags($request->get('desccorta'));
        if(!empty($descCorta)){
            $descCorta = $request->get('desccorta');
        }
        $descLarga = strip_tags($request->get('desclarga'));
        if(!empty($descLarga)){
            $descLarga = $request->get('desclarga');
        }
        $requisitos = strip_tags($request->get('requisitos'));
        if(!empty($desccorta)){
            $requisitos = $request->get('requisitos');
        }

        $organismo = ($request->get('departamento')) ? $request->get('departamento') : $request->get('organo');

        $ccaas = null;
        if($request->get('ambito') == "Comunidad Autónoma"){
            $ccaas = json_encode($request->get('ccaas'));
        }else{
            $ccaas = null;
        }
        $cnaes = null;
        if($request->get('opcionCNAE') != "Todos"){
            $cnaes = json_encode($request->get('cnaes'));
        }else{
            $cnaes = null;
        }

        $featured = 0;
        if($request->get('featured')){
            $featured = 1;
        }

        if($request->get('tematicaobligatoria') !== null){
            $totalencajeslinea = \App\Models\Encaje::where('Ayuda_id', $id)->where('Tipo', 'Linea')->count();
            if($totalencajeslinea == 0){
                return Response::json(array(
                    'code'      =>  403,
                    'message'   =>  'No se puede guardar una ayuda de temática obligatoria que no tenga un encaje de tipo línea'
                ), 403);
            }
        }

        $convocatoriasenayuda = \App\Models\Ayudas::where('id_ayuda', $request->get('id_ayuda'))->where('Estado', '!=', 'Cerrada')
        ->where('es_europea', 0)->get();

        if($convocatoriasenayuda->count() > 1){
            foreach($convocatoriasenayuda as $convoca){
                if($convoca->id != $id){
                    if($convoca->Inicio !== null && $convoca->Fin !== null 
                        && $request->get('inicio') !== null &&  $request->get('fin') !== null){
                        if(Carbon::createFromFormat('d/m/Y', $request->get('inicio')) >= Carbon::createFromFormat('Y-m-d', $convoca->Inicio)
                            && Carbon::createFromFormat('d/m/Y', $request->get('fin')) <= Carbon::createFromFormat('Y-m-d', $convoca->Fin)){
                            return Response::json(array(
                                'code'      =>  403,
                                'message'   =>  'No se puede guardar una convocatoria que tiene fechas de inicio o fin entre las fechas de otra convocatoria de la ayuda seleccionada'
                            ), 403);
                        }              
                    }
                }
            }
        }       

        $presupuestomin = 0;
        if(!empty($request->get('presupuestomin'))){
            $presupuestomin = $request->get('presupuestomin');
        }
        $presupuestomax = 0;
        if(!empty($request->get('presupuestomax'))){
            $presupuestomax = $request->get('presupuestomax');
        }
        $duracionmin = null;
        if(!empty($request->get('duracionmin'))){
            $duracionmin = $request->get('duracionmin');
        }
        $duracionmax = null;
        if(!empty($request->get('duracionmax'))){
            $duracionmax = $request->get('duracionmax');
        }
        $garantias = null;
        if(!empty($request->get('garantias'))){
            $garantias = $request->get('garantias');
        }
        $capitulos = null;
        if(!empty($request->get('capitulos'))){
            $capitulos = json_encode($request->get('capitulos'));
        }
        $condicionesespeciales = null;
        if(!empty($request->get('textocondicionesespeciales'))){
            $condicionesespeciales = $request->get('textocondicionesespeciales');
        }

        $condicinesfinanciacion = $request->get('condicionesfinanciacion');

        $estado = $request->get('estado');
        if($request->get('inicio') != "" && $request->get('inicio') !== null){
            if(Carbon::createFromFormat('d/m/Y', $request->get('inicio')) <= Carbon::now()){
                $estado = 'Abierta';
            }
        }
        if($request->get('fin') != "" && $request->get('fin') !== null){
            if(Carbon::createFromFormat('d/m/Y', $request->get('fin')) < Carbon::now()){
                $estado = 'Cerrada';
            }
        }
        //dd($request->all());

        if($request->get('fechamax') !== null){
            $meses = null;
        }else{
            $meses = $request->get('meses');
        }

        if($request->get('meses') !== null){
            $fechamax = null;
        }else{
            $fechamax = $request->get('fechamax');
        }

        if($request->get('fechamin') !== null){
            $mesesmin = null;
        }else{
            $mesesmin = $request->get('mesesmin');
        }

        if($request->get('mesesmin') !== null){
            $fechamin = null;
        }else{
            $fechamin = $request->get('fechamin');
        }

        if(is_numeric($request->get('uri'))){
            $str = quitar_tildes(mb_strtolower($request->get('titulo')));
            $uri = preg_replace("/[^0-9a-zÀ-ÿ\-]/", '', $str);
        }else{
            $str = quitar_tildes(mb_strtolower($request->get('uri')));
            $uri = preg_replace("/[^0-9a-zÀ-ÿ\-]/", '', $str);
        }

        $intensidad = 0;

        if($request->get('porcentajefondoperdido') == 0 && $request->get('porcentajecreditomax') == 0 && $request->get('aplicacionintereses') == "No"
            && (in_array("Crédito", $request->get('tipofinanciacion'))
                && !in_array("Fondo perdido", $request->get('tipofinanciacion')))){
            $intensidad = 1;
        }

        if($request->get('porcentajefondoperdido') == 0 && $request->get('porcentajecreditomax') == 0 && $request->get('porcentajeintereses') > 0
            && (in_array("Crédito", $request->get('tipofinanciacion'))
                && !in_array("Fondo perdido", $request->get('tipofinanciacion')))){
            $intensidad = 1;
        }

        if($request->get('porcentajefondoperdido') == 0 && $request->get('porcentajecreditomax') > 0
            && $request->get('aplicacionintereses') != "No" && $request->get('aplicacionintereses') != null){
                $intensidad = 1;
        }
        if($request->get('porcentajefondoperdido') == 0 && $request->get('porcentajecreditomax') > 0
            && $request->get('aplicacionintereses') == "No" && $request->get('aplicacionintereses') != null){
                $intensidad = 2;
        }

        if($request->get('porcentajefondoperdido') > 0 && $request->get('porcentajecreditomax') > 0){
            $intensidad = 3;
        }

        if($request->get('porcentajefondoperdido') == 0 && $request->get('porcentajecreditomax') == 0
            && (in_array("Fondo perdido", $request->get('tipofinanciacion'))
                && in_array("Crédito", $request->get('tipofinanciacion')))){
            $intensidad = 3;
        }

        if($request->get('porcentajefondoperdido') > 0 && $request->get('porcentajecreditomax') == 0){
            $intensidad = 4;
        }

        if($request->get('porcentajefondoperdido') == 0 && $request->get('porcentajecreditomax') == 0
            && in_array("Fondo perdido", $request->get('tipofinanciacion'))){
            $intensidad = 4;
        }

        $infodefinitiva = ($request->get('infodefinitiva') === null) ? 0 : 1;

        $dnsh = ($request->get('dnsh') === null) ? 'no definido' : $request->get('dnsh');

        $fondos = ($request->get('fondos') === null) ? null :  json_encode($request->get('fondos'));

        $ayuda = \App\Models\Ayudas::where('id', $id)->first();

        if(empty($ayuda->IdConvocatoriaStr)){
            $acronimo = cleanUriBeforeSave($request->get('acronimo'));
            $idAcronimo = rtrim(mb_strtoupper(mb_substr(str_replace(" ","",$acronimo),0,6)));

            if($request->get('inicio')){
                $idAcronimo .= Carbon::createFromFormat('d/m/Y', $request->get('inicio'))->format('Y');
            }elseif($request->get('fin')){
                $idAcronimo .= Carbon::createFromFormat('d/m/Y', $request->get('fin'))->format('Y');
            }else{
                $idAcronimo .= Carbon::now()->format('Y');
            }

            $checkIdAcronimo = \App\Models\Ayudas::where('IdConvocatoriaStr', 'LIKE', '%'.$idAcronimo.'%')->count();

            if($checkIdAcronimo > 0){
                $total = $checkIdAcronimo+1;
                $idAcronimo .= "#".$total;
            }else{
                $idAcronimo .= "#1";
            }
        }else{
            $idAcronimo = $ayuda->IdConvocatoriaStr;
        }

        $fechaEmails = ($request->get('fechaemails') != "") ? Carbon::createFromFormat('d/m/Y', $request->get('fechaemails'))->format('Y-m-d') : null;

        if($fechaEmails === null && $request->get('inicio') !== null){
            $fechaEmails = Carbon::createFromFormat('d/m/Y', $request->get('inicio'))->format('Y-m-d');
        }

        $typeOfAction = null;
        if($request->get('old_tpa') != $request->get('type_of_action_id')){
            if($ayuda->type_of_action_id !== $request->get('type_of_action_id')){
                $typeOfAction = \App\Models\TypeOfActions::find($request->get('type_of_action_id'));
            }            
        }

        try{
            $updateayuda = \App\Models\Ayudas::find($id);
            if(!$updateayuda){
                return Response::json(array(
                    'code'      =>  403,
                    'message'   =>  'No se puede guardar la convocatoria, intentelo de nuevo pasados unos minutos'
                ), 403);
            }
            
            if($typeOfAction === null){
                $updateayuda->Categoria = $categoria;
                $updateayuda->naturalezaConvocatoria = (!empty($request->get('naturaleza_convocatoria'))) ? json_encode($request->get('naturaleza_convocatoria')) : null;
                $updateayuda->Presentacion = $request->get('presentacion');
                $updateayuda->TipoFinanciacion = json_encode($request->get('tipofinanciacion'));
                $updateayuda->Trl = ($request->get('trl') != "") ? $request->get('trl') : null;
                $updateayuda->objetivoFinanciacion = $request->get('objetivoFinanciacion');
                $updateayuda->PorcentajeFondoPerdido = $request->get('porcentajefondoperdido');
                $updateayuda->FondoPerdidoMinimo = $request->get('porcentajefondoperdidominimo');
            }else{
                $updateayuda->Categoria = $typeOfAction->categoria;
                $updateayuda->naturalezaConvocatoria = $typeOfAction->naturaleza;
                $updateayuda->Presentacion = $typeOfAction->presentacion;
                $updateayuda->TipoFinanciacion = $typeOfAction->tipo_financiacion;
                $updateayuda->Trl = $typeOfAction->trl;
                $updateayuda->objetivoFinanciacion = $typeOfAction->objetivo_financiacion;
                $updateayuda->FondoPerdidoMinimo = $typeOfAction->fondo_perdido_minimo;
                $updateayuda->PorcentajeFondoPerdido = $typeOfAction->fondo_perdido_maximo;
            }           
            
            if($ayuda->rawdataEU !== null){
                $indice = null;
                if($ayuda->rawdataEU->budgetTopicActionMap !== null){
                    foreach(json_decode($ayuda->rawdataEU->budgetTopicActionMap, true) as $key => $value){
                        if(strripos($value[0]['action'],$ayuda->rawdataEU->identifier) !== false){
                            $indice = $key;
                            break;
                        }
                    }
                }
                if($indice !== null){
                    if($ayuda->rawdataEU->minContribution !== null){
                        $array = json_decode($ayuda->rawdataEU->minContribution, true);
                        $updateayuda->FondoPerdidoMaximoNominal = (isset($array[$indice][0])) ? $array[$indice][0] : null;
                    }
                    if($ayuda->rawdataEU->maxContribution !== null){
                        $array = json_decode($ayuda->rawdataEU->maxContribution, true);
                        $updateayuda->FondoPerdidoMinimoNominal = (isset($array[$indice][0])) ? $array[$indice][0] : null;
                    }    
                }
            }

            $updateayuda->id_ayuda = $request->get('id_ayuda');
            $updateayuda->IdConvocatoriaStr = $idAcronimo;
            $updateayuda->InformacionDefinitiva = $infodefinitiva;
            $updateayuda->Acronimo = $request->get('acronimo');
            $updateayuda->Titulo = $request->get('titulo');
            $updateayuda->Uri = $uri;
            $updateayuda->Link = $request->get('link');          
            $updateayuda->Intensidad = $intensidad;
            $updateayuda->PerfilFinanciacion = $intereses;
            $updateayuda->Presupuesto = $request->get('presupuesto');
            $updateayuda->PresupuestoConsorcio = $request->get('presupuestoconsorcio');
            $updateayuda->PresupuestoParticipante = ($request->get('presupuestoparticipante') === "" || $request->get('presupuestoparticipante') === null) ? null : $request->get('presupuestoparticipante');
            $updateayuda->NumeroParticipantes = ($request->get('numeroparticipantes') === null) ? 1 : $request->get('numeroparticipantes'); 
            $updateayuda->Ambito = $request->get('ambito');
            $updateayuda->opcionCNAE = $request->get('opcionCNAE');
            $updateayuda->CNAES = $cnaes;
            $updateayuda->DescripcionCorta = $descCorta;
            $updateayuda->DescripcionLarga = $descLarga;
            $updateayuda->RequisitosTecnicos = $requisitos;
            $updateayuda->RequisitosParticipante = ($request->get('requisitos_participantes') !== null) ? strip_tags($request->get('requisitos_participantes')) : null;
            $updateayuda->Estado = $estado;
            $updateayuda->Competitiva = $request->get('competitiva');
            $updateayuda->Organismo = $organismo;
            $updateayuda->Ccaas = $ccaas;
            $updateayuda->Featured = $featured;           
            $updateayuda->CapitulosFinanciacion = $capitulos;
            $updateayuda->CondicionesFinanciacion = $condicinesfinanciacion;
            $updateayuda->CondicionesEspeciales = $condicionesespeciales;
            $updateayuda->PresupuestoMin = $presupuestomin;
            $updateayuda->PresupuestoMax = $presupuestomax;
            $updateayuda->DuracionMin = $duracionmin;
            $updateayuda->DuracionMax = $duracionmax;
            $updateayuda->Garantias = $garantias;
            $updateayuda->Inicio = ($request->get('inicio') != "") ? Carbon::createFromFormat('d/m/Y', $request->get('inicio'))->format('Y-m-d') : null;
            $updateayuda->Fin = ($request->get('fin') != "") ? Carbon::createFromFormat('d/m/Y', $request->get('fin'))->format('Y-m-d') : null;
            $updateayuda->fechaEmails = $fechaEmails;
            $updateayuda->MesesMin = ($mesesmin) ? $mesesmin : null;
            $updateayuda->FechaMinConstitucion = ($fechamin) ? Carbon::createFromFormat('d/m/Y', $fechamin)->format('Y-m-d') : null;
            $updateayuda->Meses = ($meses) ? $meses : null;
            $updateayuda->FechaMaxConstitucion = ($fechamax) ? Carbon::createFromFormat('d/m/Y', $fechamax)->format('Y-m-d') : null;            
            $updateayuda->PorcentajeCreditoMax = $request->get('porcentajecreditomax');            
            $updateayuda->CreditoMinimo = $request->get('porcentajecreditominimo');
            $updateayuda->DeduccionMax = $request->get('deduccionmax');
            $updateayuda->NivelCompetitivo = ($request->get('nivelcompetitivo')) ? $request->get('nivelcompetitivo') : null;
            $updateayuda->TiempoMedioResolucion = ($request->get('tiempomedioresolucion')) ? $request->get('tiempomedioresolucion') : null;
            $updateayuda->SelloPyme = ($request->get('sellopyme')) ? 1 : 0;
            $updateayuda->EmpresaCrisis = ($request->get('empresacrisis')) ? 1 : 0;
            $updateayuda->InformeMotivado = ($request->get('informemotivado')) ? 1 : 0;
            $updateayuda->TextoCondiciones = ($request->get('textocondiciones')) ? $request->get('textocondiciones') : null;
            $updateayuda->TextoConsorcio = ($request->get('textoconsorcio')) ? $request->get('textoconsorcio') : null;
            $updateayuda->FondoTramo = $request->get('fondotramo');
            $updateayuda->LastEditor = Auth::user()->email;
            $updateayuda->updated_at = Carbon::now();
            $updateayuda->AplicacionIntereses = $request->get('aplicacionintereses');
            $updateayuda->PorcentajeIntereses = $request->get('porcentajeintereses');
            $updateayuda->AnosAmortizacion = $request->get('anosamortizacion');
            $updateayuda->MesesCarencia = $request->get('mesescarencia');
            $updateayuda->Minimis = ($request->get('minimis')) ? 1 : 0;
            $updateayuda->TematicaObligatoria = ($request->get('tematicaobligatoria')) ? 1 : 0;
            $updateayuda->EfectoIncentivador = ($request->get('efectoincentivador')) ? 1 : 0;
            $updateayuda->Dnsh = $dnsh;
            $updateayuda->MensajeDnsh = ($dnsh == "opcional") ? $request->get('mensajednsh') : null;
            $updateayuda->FondosEuropeos = $fondos;
            $updateayuda->esDeGenero = ($request->get('esdegenero')) ? 1 : 0;
            $updateayuda->textoGenero = ($request->get('esdegenero')) ? $request->get('textodegenero') : null;
            $updateayuda->minEmpleados = ($request->get('minempleados')) ? $request->get('minempleados') : null;
            $updateayuda->maxEmpleados = ($request->get('maxempleados')) ? $request->get('maxempleados') : null;
            $updateayuda->tiposObligatorios = (empty($request->get('tiposobligatorios'))) ? null : json_encode($request->get('tiposobligatorios'));
            $updateayuda->update_extinguida_ayuda = ($request->get('update_extinguida_ayuda') !== null) ? $request->get('update_extinguida_ayuda') : null;
            $updateayuda->subfondos = ($request->get('subfondos') === null) ? null :  json_encode($request->get('subfondos'));
            $updateayuda->type_of_action_id = ($request->get('type_of_action_id') === null) ? null :  $request->get('type_of_action_id');
            $updateayuda->save();
        }catch(Exception $e){
            die($e->getMessage());
        }

        $encajes = DB::table('Encajes_zoho')->where('Ayuda_id', $id)->get();

        if($encajes){
            foreach($encajes as $encaje){
                try{
                    Artisan::call('elastic:ayudas', [
                        'id' =>  $encaje->id
                    ]);
                }catch(Exception $e){
                    dd($e->getMessage());
                }
            }
        }
        
        try{
            Artisan::call('calcula:ayudas_parecidas', [
                'id' =>  $id
            ]);
        }catch(Exception $e){
            dd($e->getMessage());
        }

        $current = \App\Models\Ayudas::where('id', $id)->first();

        if($current->Publicada == 1){
            try{
                Artisan::call('check:seo_pages', [
                    'id' =>  $id
                ]);
            }catch(Exception $e){
                dd($e->getMessage());
            }
        }
        
        return "Ayuda actualizada";
    }

    public function borrarAyuda(Request $request){

        $id = $request->get('id');

        try{
            $ayuda = \App\Models\Ayudas::where('id', $id)->delete();
        }catch(Exception $e){
            die($e->getMessage());
        }

        return "Ayuda borrada";
    }

    public function rechazaConvocatoria(Request $request){

        $id = $request->get('id');

        try{
            DB::table('convocatorias')->where('IDConvocatoria', $id)->update(
                [
                    'Analisis' => 'Rechazada'
                ]
            );

        }catch(Exception $e){
            return $e->getMessage();
        }

        return "Convocatoria ".$id." rechazada";
    }

    public function updateTextosEmpresa(Request $request){

        $cif = $request->get('cif');

        $company = \App\Models\Entidad::where('CIF', $cif)->first();

        if($request->get('nuevocif') !== null){
            if($cif != $request->get('nuevocif')){
                $checkcifexist =  \App\Models\Entidad::where('CIF', $request->get('nuevocif'))->first();
                if($checkcifexist){
                    return redirect()->back()->with('error', 'Error ese CIF pertenece a una empresa ya existente.');
                }else{
                    $checkcifexist = \App\Models\Entidad::where('CIF', $request->get('nuevocif'))->first();
                    if($checkcifexist){
                        return redirect()->back()->with('error', 'Error ese CIF pertenece a una empresa ya existente.');
                    }
                }
                $message = "se ha solicitado el cambio de CIF para la empresa: ".$company->Nombre." de ".$cif." a ".$request->get('nuevocif');
                try{
                    Artisan::call('send:telegram_notification', [
                        'message' =>  $message
                    ]);
                }catch(Exception $e){
                    Log::error($e->getMessage());
                }
            }
        }

        if($request->get('uri') != $request->get('nuevauri')){
            $checkcifexist = DB::table('entidades')->where('uri', $request->get('nuevauri'))->first();
            if($checkcifexist){
                return redirect()->back()->with('error', 'Error esa url ya pertenece a una empresa existente.');
            }else{
                $checkcifexist = DB::table('CifsnoZoho')->where('uri', $request->get('nuevauri'))->first();
                if($checkcifexist){
                    return redirect()->back()->with('error', 'Error esa url ya pertenece a una empresa existente.');
                }
            }
        }

        if($request->file('logo') !== null){

            if($company->logo != $request->file('logo')->getClientOriginalName()){

                try{
                    $file = $request->file('logo');
                    $name = time()."-".$company->id."-".$request->file('logo')->getClientOriginalName();       
                    $img = Image::make($file);
                    $img->resize(100, 100, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })->save('/tmp/'.$name);
                                 
                    Storage::disk('s3_files')->put('images/'.$name, $img, ['visibility' => "public-read"]);                    
                    Storage::disk('s3_files')->delete('images/'.$company->logo);
                    //$file->move(public_path('images'), $name);
                    //File::delete(public_path('images')."/".$company->logo);
                    DB::table('entidades')->where('CIF', $cif)->update([
                        'logo' => $name
                    ]);
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return redirect()->back()->with('error', 'No se ha podido actualizar el logo.');
                }
            }
        }

        try{
            DB::table('TextosElastic')->updateOrInsert(
                [
                    'CIF' => $cif
                ],
                [
                'Textos_Documentos' => $request->get('Textos_Documentos'),
                'Textos_Tecnologia' => $request->get('Textos_Tecnologia'),
                'Textos_Proyectos' => $request->get('Textos_Proyectos'),
                'Textos_Tramitaciones' => $request->get('Textos_Tramitaciones'),

                ]
            );
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar los textos de la empresa.');
        }

        $opcionesMenu = array();
        $opcionesMenu['Notificaciones'] = ($request->get('notificaciones') !== null) ? 1 : 0;
        $opcionesMenu['Financiacion'] = ($request->get('financiacion') !== null) ? 1 : 0;
        $opcionesMenu['Proyectos'] = ($request->get('proyectos') !== null) ? 1 : 0;
        $opcionesMenu['Mensajes'] = ($request->get('mensajes') !== null) ? 1 : 0;
        $opcionesMenu['Perfil'] = ($request->get('perfil') !== null) ? 1 : 0;

        $web = ($request->get('web') !== null) ? $request->get('web') : $company->Web;

        if($request->get('consultoria') !== null){

            $intereses = json_decode($company->Intereses);
            if(is_array($intereses)){
                if(!in_array("Consultoría", $intereses)){
                    array_push($intereses, "Consultoría");
                }
            }else{
                $intereses = ["Consultoría"];
            }

            $entity = \App\Models\Entidad::where('CIF', $cif)->first();
            if($entity->naturalezaEmpresa !== null){
                $naturalezas = json_decode($entity->naturalezaEmpresa, true);
                if(!in_array("6668839", $naturalezas)){
                    array_push($naturalezas, "6668839");
                }
            }
             
            try{
                $entity->Nombre = $request->get('nombre');
                $entity->NumeroLineasTec = $request->get('NumeroLineasTec');
                $entity->esConsultoria = 1;
                $entity->naturalezaEmpresa = json_encode($naturalezas);
                $entity->Intereses = json_encode($intereses);
                $entity->maxProyectos = $request->get('maxproyectos');
                $entity->uri = $request->get('nuevauri');
                $entity->cantidadImasD = $request->get('cantidadimasdmanual');
                $entity->valorTRL = $request->get('trlmanual');
                $entity->esCentroTecnologico = ($request->get('centrotecnologico') === null) ? 0 : 1;
                $entity->esUniversidadPrivada = ($request->get('universidadprivada') === null) ? 0 : 1;
                $entity->crearBusquedas = ($request->get('crearbusquedas') === null) ? 0 : 1;
                $entity->crearAnalisis = ($request->get('crearanalisis') === null) ? 0 : 1;
                $entity->maxAdhocSearchs = $request->get('maxadhocsearchs');
                $entity->organismo_universidad = ($request->get('organismo_universidad') === null) ? null : $request->get('organismo_universidad');
                $entity->simula_empresas = ($request->get('simularentidades') === null) ? 0 : 1;
                $entity->filtro_clientes = ($request->get('filtroclientes') === null) ? 0 : 1;
                $entity->total_simulaciones = ($request->get('totalsimulaciones') === null) ? 50 : $request->get('totalsimulaciones');
                $entity->opcionesMenu = json_encode($opcionesMenu);
                $entity->Web = $web;
                $entity->ver_equipo = ($request->get('verequipo') === null) ? 0 : 1;
                $entity->upload_pdfs = ($request->get('uploadpdf') === null) ? 0 : 1;
                $entity->ver_graficos_organismo = ($request->get('vergraficos') === null) ? 0 : 1;
                $entity->busqueda_financiera = ($request->get('busquedafinanciera') === null) ? 0 : 1;
                $entity->featured = ($request->get('featured') === null) ? 0 : 1;
                $entity->promotor_proyectos = ($request->get('promotor_proyectos') === null) ? 0 : 1;
                $entity->enable_custom_smtp = ($request->get('enable_custom_smtp') === null) ? 0 : 1;
                $entity->anios_startup = ($request->get('anios_startup') === null) ? 5 : $request->get('anios_startup');
                $entity->twitter_url = ($request->get('twitter_url') === null) ? null : $request->get('twitter_url');
                $entity->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->with('error', 'Error al actualizar la información de la empresa.');
            }

        }else{

            $intereses = json_decode($company->Intereses);

            if(is_array($intereses)){
                if (($key = array_search("Consultoría", $intereses)) !== false) {
                    unset($intereses[$key]);
                }
            }
            if(empty($intereses)){
                $intereses = null;
            }

            try{
                \App\Models\Entidad::where('CIF', $cif)->update([
                    'Nombre' => $request->get('nombre'),
                    'NumeroLineasTec' => $request->get('NumeroLineasTec'),
                    'esConsultoria' => 0,
                    'Intereses' => (is_array($intereses)) ? json_encode($intereses) : $intereses,
                    'maxProyectos' => $request->get('maxproyectos'),
                    'uri' => $request->get('nuevauri'),
                    'cantidadImasD' => $request->get('cantidadimasdmanual'),
                    'valorTRL' => $request->get('trlmanual'),
                    'esCentroTecnologico' => ($request->get('centrotecnologico') === null) ? 0 : 1,
                    'esUniversidadPrivada' => ($request->get('universidadprivada') === null) ? 0 : 1,
                    'crearBusquedas' => ($request->get('crearbusquedas') === null) ? 0 : 1,
                    'crearAnalisis' => ($request->get('crearanalisis') === null) ? 0 : 1,
                    'simula_empresas' => ($request->get('simularentidades') === null) ? 0 : 1,
                    'filtro_clientes' => ($request->get('filtroclientes') === null) ? 0 : 1,
                    'total_simulaciones' => ($request->get('totalsimulaciones') === null) ? 50 : $request->get('totalsimulaciones'),
                    'maxAdhocSearchs' => $request->get('maxadhocsearchs'),
                    'organismo_universidad' => ($request->get('organismo_universidad') === null) ? null : $request->get('organismo_universidad'),
                    'opcionesMenu' =>  json_encode($opcionesMenu),
                    'Web' => $web,
                    'ver_graficos_organismo' => ($request->get('vergraficos') === null) ? 0 : 1,
                    'ver_equipo' => ($request->get('verequipo') === null) ? 0 : 1,
                    'upload_pdfs' => ($request->get('uploadpdf') === null) ? 0 : 1,
                    'busqueda_financiera' => ($request->get('busquedafinanciera') === null) ? 0 : 1,
                    'featured' => ($request->get('featured') === null) ? 0 : 1,
                    'promotor_proyectos' => ($request->get('promotor_proyectos') === null) ? 0 : 1,
                    'enable_custom_smtp' => ($request->get('enable_custom_smtp') === null) ? 0 : 1,
                    'anios_startup' => ($request->get('anios_startup') === null) ? 5 : $request->get('anios_startup'),
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->with('error', 'Error al actualizar la información de la empresa.');
            }

        }

        if($company->organo !== null){
            try{
                $company->organo->Nombre = $request->get('nombre');
                $company->organo->Acronimo = $request->get('acronimo');
                $company->organo->Web = $request->get('web');
                $company->organo->Descripcion = $request->get('descripcion');
                $company->organo->save();
                $company->Nombre = $request->get('nombre');
                $company->Marca = $request->get('acronimo');
                $company->Web = $request->get('web');
                $company->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->with('error', 'Error al actualizar la información de la empresa.');
            }
        }

        if($company->departamento !== null){
            try{
                $company->departamento->Nombre = $request->get('nombre');
                $company->departamento->Acronimo = $request->get('acronimo');
                $company->departamento->Web = $request->get('web');
                $company->departamento->Descripcion = $request->get('descripcion');
                $company->departamento->save();
                $company->Nombre = $request->get('nombre');
                $company->Marca = $request->get('acronimo');
                $company->Web = $request->get('web');
                $company->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->with('error', 'Error al actualizar la información de la empresa.');
            }
        }

        $check = \App\Models\CompanyNews::where('company_id', $request->get('cif'))->first();

        if($check !== null){
            try{
                $check->mostrar = ($request->get('ultimasnoticias') === null) ? 0 : 1;
                $check->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('No se ha podido actualizar Ultimas noticias en perfil público');
            }
        }else{
            try{
                Artisan::call('calcula:company_news', [
                    'cif' =>  $cif
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());            
            }

            $check = \App\Models\CompanyNews::where('company_id', $cif)->first();
            if($check !== null){
                try{
                    $check->mostrar = ($request->get('ultimasnoticias') === null) ? 0 : 1;
                    $check->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return redirect()->back()->withErrors('No se ha podido actualizar Ultimas noticias en perfil público');
                }
            }
        }

        $vergraficos = ($request->get('vergraficos') !== null) ? "1": "0";

        if($request->get('status_graficos') != $vergraficos){

            if($company->ver_graficos_organismo == 1){
                $graficos = \App\Models\GraficosOrganismos::where('id_organo', $company->idOrganismo)->get();
                if(!$graficos || $graficos->isEmpty()){
                    $graficos = \App\Models\GraficosOrganismos::where('id_departamento', $company->idOrganismo)->get();
                    if(!$graficos || $graficos->isEmpty()){
                        $this->generaGraficosOrganismo($company);
                    }else{
                        \App\Models\GraficosOrganismos::where('id_departamento', $company->idOrganismo)->update([
                            'activo' => 1
                        ]);
                    }
                }else{
                    \App\Models\GraficosOrganismos::where('id_organo', $company->idOrganismo)->update([
                        'activo' => 1
                    ]);
                }
            }elseif($company->ver_graficos_organismo == 0){
                \App\Models\GraficosOrganismos::where('id_departamento', $company->idOrganismo)->update([
                    'activo' => 0
                ]);
                \App\Models\GraficosOrganismos::where('id_organo', $company->idOrganismo)->update([
                    'activo' => 0
                ]);
            }

        }

        if($company->ver_graficos_organismo == 1 && $request->get('status_graficos') == "1"){
            if($company->organo !== null){
                \App\Models\GraficosOrganismos::where('id_organo', $company->idOrganismo)->where('type', 'grafico-1')->update([
                    'activo' => ($request->get('grafico1') === null) ? 0 : 1,
                ]);
                \App\Models\GraficosOrganismos::where('id_organo', $company->idOrganismo)->where('type', 'grafico-2')->update([
                    'activo' => ($request->get('grafico2') === null) ? 0 : 1,
                ]);
                \App\Models\GraficosOrganismos::where('id_organo', $company->idOrganismo)->where('type', 'grafico-3')->update([
                    'activo' => ($request->get('grafico3') === null) ? 0 : 1,
                ]);
                \App\Models\GraficosOrganismos::where('id_organo', $company->idOrganismo)->where('type', 'grafico-4')->update([
                    'activo' => ($request->get('grafico4') === null) ? 0 : 1,
                ]);
            }

            if($company->departamento !== null){
                \App\Models\GraficosOrganismos::where('id_departamento', $company->idOrganismo)->where('type', 'grafico-1')->update([
                    'activo' => ($request->get('grafico1') === null) ? 0 : 1,
                ]);
                \App\Models\GraficosOrganismos::where('id_departamento', $company->idOrganismo)->where('type', 'grafico-2')->update([
                    'activo' => ($request->get('grafico2') === null) ? 0 : 1,
                ]);
                \App\Models\GraficosOrganismos::where('id_departamento', $company->idOrganismo)->where('type', 'grafico-3')->update([
                    'activo' => ($request->get('grafico3') === null) ? 0 : 1,
                ]);
                \App\Models\GraficosOrganismos::where('id_departamento', $company->idOrganismo)->where('type', 'grafico-4')->update([
                    'activo' => ($request->get('grafico4') === null) ? 0 : 1,
                ]);
            }
        }

        Artisan::call('cache:clear');

        $uri = $request->get('uri');

        if($request->get('uri') != $request->get('nuevauri')){
            $uri = $request->get('nuevauri');
        }

        $route = (in_array("6668843", json_decode($company->naturalezaEmpresa, true)) === true) ? "miorganismo" : "miempresa";

        if(isset($message)){
            return redirect()->route($route, $uri)->with('success', 'Datos actualizados con exito, generada solicitud de cambio de CIF');
        }else{
            return redirect()->route($route, $uri)->with('success', 'Datos actualizados con exito.');
        }
    }

    public function createDptoOrgano(Request $request){

        if($request->get('tipo') == "departamento"){
            $url = cleanUriBeforeSave(str_replace(" ","-", mb_strtolower($request->get('acronimo'))));
            try{
                DB::table('departamentos')->insert([
                    'Nombre' => $request->get('nombre'),
                    'Acronimo' => $request->get('acronimo'),
                    'id_ministerio' => $request->get('ministerio'),
                    'url' => $url,
                    'es_interno' => 1,
                ]);
            }catch(Exception $e){
                dd($e->getMessage());
            }

        }
        if($request->get('tipo') == "organo"){
            $url = cleanUriBeforeSave(str_replace(" ","-", mb_strtolower($request->get('acronimo'))));
            try{
                DB::table('organos')->insert([
                    'Nombre' => $request->get('nombre'),
                    'Acronimo' => $request->get('acronimo'),
                    'id_ccaa' => $request->get('ccaa'),
                    'url' => $url,
                    'es_interno' => 1,
                ]);
            }catch(Exception $e){
                dd($e->getMessage());
            }
        }

        return "Creado ".$request->get('tipo');
    }

    public function editarEmpresa(Request $request){

        $tags = array();
        if($request->get('tagsanalisis') !== null && $request->get('tagsanalisis') != ""){
            foreach(explode(",", $request->get('tagsanalisis')) as $tag){
                array_push($tags, $tag);
            }
        }

        $escentro = 0;

        if(in_array("6668838", array($request->get('naturaleza')))){
            $escentro = 1;
        }

        $webs = explode(",",$request->get('web'));

        $uri = $request->get('uri');
        if($request->get('idorganismo') !== null && in_array('6668843', $request->get('naturaleza'))){
            $org = \App\Models\Organos::find($request->get('idorganismo'));
            if(!$org){
               $org = \App\Models\Departamentos::find($request->get('idorganismo'));
            }
            $uri = $org->url;
        }

        if($request->get('noeszoho') == 1){
            try{
                $companyId = DB::table('entidades')->insertGetId([
                    'Nombre' => $request->get('nombre'),
                    'Marca' => $request->get('marca'),
                    'uri' => $uri,
                    'Ccaa' => $request->get('ccaa'),
                    'CIF' => $request->get('cif'),
                    'Web' => $request->get('web'),
                    'TextosLineasTec' => json_encode($tags, JSON_UNESCAPED_UNICODE),
                    'Intereses' => ($request->get('intereses') !== null) ? json_encode($request->get('intereses')) : null,
                    'esConsultoria' => ($request->get('esconsultoria') !== null) ? 1 : 0,
                    'esCentroTecnologico' => $escentro,
                    'naturalezaEmpresa' => json_encode($request->get('naturaleza')),
                    'idOrganismo' => (in_array('6668843', $request->get('naturaleza'))) ? $request->get('idorganismo') : null,
                    'crearBusquedas' => (in_array('6668843', $request->get('naturaleza'))) ? 1 : 0,
                    'simula_empresas' => (in_array('6668843', $request->get('naturaleza'))) ? 1 : 0,
                    'maxProyectos' => $request->get('maxproyectos'),
                    'NumeroLineasTec' => 2,
                    'EntityUpdate' => Carbon::now(),
                    'UpdatedBy' => Auth::user()->email,
                    'created_at' => Carbon::now(),
                ]);

            }catch(Exception $e){
                return redirect()->back()->withErrors("Error en la actualización de la empresa");
            }

            try{
                DB::table('TextosElastic')->updateOrInsert(
                    [
                        'CIF' => $request->get('cif'),
                    ],
                    [
                    'Textos_Documentos' => $request->get('textos_documentos'),
                    'Textos_Proyectos' => $request->get('textos_proyectos'),
                    'Textos_Tecnologia' => $request->get('textos_tecnologia'),
                    'Textos_Tramitaciones' => $request->get('textos_tramitaciones'),
                    'Last_Update' => Carbon::now(),
                    ]
                );
            }catch(Exception $e){
                return redirect()->back()->withErrors("Error en la actualización de los textos elastic de la empresa");
            }

            try{
                DB::table('CifsnoZoho')->where('CIF', $request->get('cif'))->update(
                    [
                    'movidoEntidad' => 1,
                    ]
                );
            }catch(Exception $e){
                return redirect()->back()->withErrors("Error en la actualización de los datos CifsNoZoho");
            }            

        }else{

            try{
                $entity = \App\Models\Entidad::where('CIF', $request->get('cif'))->first();                
                $entity->Nombre = $request->get('nombre');
                $entity->Marca = $request->get('marca');
                $entity->uri = $request->get('uri');
                $entity->Ccaa = $request->get('ccaa');
                $entity->CIF = $request->get('cif');
                $entity->Web = $webs[0];
                $entity->TextosLineasTec = json_encode($tags, JSON_UNESCAPED_UNICODE);
                $entity->Intereses = ($request->get('intereses') !== null) ? json_encode($request->get('intereses')) : null;
                $entity->esConsultoria = ($request->get('esconsultoria') !== null) ? 1 : 0;
                $entity->esCentroTecnologico = $escentro;
                $entity->naturalezaEmpresa = json_encode($request->get('naturaleza'));
                $entity->maxProyectos = $request->get('maxproyectos');
                $entity->NumeroLineasTec = 2;
                $entity->idOrganismo = (in_array('6668843', $request->get('naturaleza'))) ? $request->get('idorganismo') : null;
                $entity->crearBusquedas = (in_array('6668843', $request->get('naturaleza'))) ? 1 : 0;
                $entity->simula_empresas = (in_array('6668843', $request->get('naturaleza'))) ? 1 : 0;
                $entity->EntityUpdate = Carbon::now();
                $entity->UpdatedBy = Auth::user()->email;
                $entity->created_at = Carbon::now();
                $entity->save();

                DB::table('einforma')->where('identificativo', $request->get('cif'))->update([
                    'Web' => json_encode($webs, JSON_UNESCAPED_SLASHES)
                ]);

            }catch(Exception $e){
                return redirect()->back()->withErrors("Error en la actualización de la empresa");
            }

            try{
                DB::table('TextosElastic')->where('CIF', $request->get('cif'))->update([
                    'Textos_Documentos' => $request->get('textos_documentos'),
                    'Textos_Proyectos' => $request->get('textos_proyectos'),
                    'Textos_Tecnologia' => $request->get('textos_tecnologia'),
                    'Textos_Tramitaciones' => $request->get('textos_tramitaciones'),
                    'Last_Update' => Carbon::now(),
                ]);
            }catch(Exception $e){
                return redirect()->back()->withErrors("Error en la actualización de los textos elastic de la empresa");
            }


            if($entity->einforma !== null){
                try{
                    $this->calculaImasD($request->get('cif'));
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    Log::info("Error en el calculo del I+D de la empresa: ".$request->get('cif'));
                }
            }

        }

        $solicitudes = \App\Models\PriorizaEmpresas::where('cifPrioritario', $request->get('cif'))->where('sacadoEinforma', 0)->get();

        if($solicitudes->isNotEmpty()){
            $empresa = \App\Models\Entidad::where('CIF', $request->get('cif'))->first();
            foreach($solicitudes as $solicitud){
                try{
                    $solicitud->sacadoEinforma = 1;
                    $solicitud->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                }    
                $mail = new \App\Mail\AceptaPriorizar($solicitud, $empresa, "");
                try{
                    Mail::to($solicitud->solicitante)->queue($mail);
                }catch(Exception $e){
                    Log::error($e->getMessage());
                }
            }
        }

        return redirect()->back()->withSuccess("Empresa actualizada");

    }

    public function crearEinforma(Request $request){

        if($request->get('update') == "0"){

            $webs = $request->get('web');

            if($request->get('web2') !== null || $request->get('web3') !== null){
                $webs .= ($request->get('web2') !== null) ? ",".$request->get('web2') : '';
                $webs .= ($request->get('web3') !== null) ? ",".$request->get('web3') : '';
            }

            $webs = explode(",",$webs);

            try{
                $newEinforma = new \App\Models\Einforma();            
                $newEinforma->web = json_encode($webs, JSON_UNESCAPED_SLASHES);
                $newEinforma->cnae = $request->get('cnae');
                $newEinforma->ccaa = $request->get('comunidad');
                $newEinforma->identificativo = $request->get('cif');
                $newEinforma->denominacion = $request->get('denominacion');
                $newEinforma->objetoSocial = $request->get('objeto');
                $newEinforma->domicilioSocial = $request->get('direccion');
                $newEinforma->localidad = $request->get('localidad');
                $newEinforma->empleados = $request->get('empleados');
                $newEinforma->fechaConstitucion = Carbon::parse($request->get('fecha'))->format('Y-m-d');
                $newEinforma->categoriaEmpresa = $request->get('categoria');
                $newEinforma->capitalSocial = $request->get('capitalsocial');
                $newEinforma->importeNetoCifraNegocios = $request->get('importeneto');
                $newEinforma->pasivoCorriente = $request->get('pasivocorriente');
                $newEinforma->pasivoNoCorriente = $request->get('pasivonocorriente');
                $newEinforma->activoCorriente = $request->get('activocorriente');
                $newEinforma->activoNoCorriente = $request->get('activonocorriente');
                $newEinforma->patrimonioNeto = $request->get('patrimonio');
                $newEinforma->ebitda = $request->get('ebitda');
                $newEinforma->balanceTotal = $request->get('balance');
                $newEinforma->gastoAnual = $request->get('gasto');
                $newEinforma->situacion =  $request->get('estado');
                $newEinforma->lastEditor = "manual";
                $newEinforma->userEditor = Auth::user()->email;
                $newEinforma->anioBalance = Carbon::now()->format('Y');
                $newEinforma->ultimaActualizacion = Carbon::now()->format('Y-m-d');
                $newEinforma->esMercantil = 0;
                $newEinforma->save();

            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('Error al crear el eInforma Manual.');
            }

            return redirect()->back()->withSuccess('eInforma Manual creado correctamente.');
        }

        if($request->get('update') == "1"){

            try{
                $datosfinancieros = \App\Models\Einforma::find($request->get('id'));
                $editor = ($datosfinancieros->lastEditor == "axesor") ? "axesor" : "manual";                          
                $userEditor = Auth::user()->email;
                
                $updateEinforma = \App\Models\Einforma::where('identificativo', $request->get('cif'))->where('id', $request->get('id'))->first();

                if($updateEinforma){
                                        
                    if($request->get('cnae') !== null && $updateEinforma->cnae != $request->get('cnae')){
                        $cnae = $request->get('cnae');
                        $updateEinforma->cnaeEditado = $cnae;    
                    }                                                 
         
                    $updateEinforma->objetoSocial = $request->get('objeto');
                    $updateEinforma->domicilioSocial = $request->get('direccion');
                    $updateEinforma->localidad = $request->get('localidad');
                    $updateEinforma->empleados = $request->get('empleados');
                    $updateEinforma->fechaConstitucion = Carbon::parse($request->get('fecha'))->format('Y-m-d');
                    $updateEinforma->categoriaEmpresa = $request->get('categoria');
                    $updateEinforma->capitalSocial = $request->get('capitalsocial');
                    $updateEinforma->importeNetoCifraNegocios = $request->get('importeneto');
                    $updateEinforma->pasivoCorriente = $request->get('pasivocorriente');
                    $updateEinforma->pasivoNoCorriente = $request->get('pasivonocorriente');
                    $updateEinforma->activoCorriente = $request->get('activocorriente');
                    $updateEinforma->activoNoCorriente = $request->get('activonocorriente');
                    $updateEinforma->patrimonioNeto = $request->get('patrimonio');
                    $updateEinforma->ebitda = $request->get('ebitda');
                    $updateEinforma->balanceTotal = $request->get('balance');
                    $updateEinforma->gastoAnual = $request->get('gasto');
                    $updateEinforma->situacion =  $request->get('estado');
                    $updateEinforma->lastEditor = $editor;
                    $updateEinforma->userEditor = $userEditor;
                    $updateEinforma->ultimaActualizacion = Carbon::now()->format('Y-m-d');
                    $updateEinforma->updated_at = Carbon::now();     
                    $updateEinforma->esMercantil = 0;                                           
                    $updateEinforma->save();
                }else{
                    return redirect()->back()->withErrors('Error al actualizar el eInforma Manual.');
                }
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('Error al actualizar el eInforma Manual.');
            }

            return redirect()->back()->withSuccess('eInforma Manual actualizado correctamente.');
        }

        return redirect()->back()->withSuccess('No se ha realizado ningún cambio.');

    }

    public function obtenerEinformaActual(Request $request){

        try{
            $response = Artisan::call('sync:einforma', [
                'cif' => $request->get('cif'),
                'tipo' => 'einforma'
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return false;
        }

        if($response == 0){
            return redirect()->back()->withErrors('Einforma no encontrado');
        }

        return redirect()->back()->withSuccess('Einforma encontrado, tienes que actualizar el I+D y el nivel de cooperación de la empresa');

    }

    public function obtenerAxesorActual(Request $request){

        try{
            $response = Artisan::call('get:axesor', [
                'cif' => $request->get('cif')
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return false;
        }

        if($response == 0){
            return redirect()->back()->withErrors('No se han encontrado datos en axesor para esta empresa, prueba a obtener los datos desde einforma.')->withInput(['activareinforma' => 1]);
        }

        #Obtenidos los datos del endpoint secundario de axesor solo informacion mercantil
        if($response == 2){
            return redirect()->back()->with('warning', 'No se han encontrado datos financieros de axesor pero si datos mercantiles, revisa si son validos y sino prueba a obtener los datos desde einforma.')->withInput(['activareinforma' => 1]);
        }

        return redirect()->back()->withSuccess('Axesor encontrado, tienes que actualizar el I+D y el nivel de cooperación de la empresa');

    }

    public function borrarEinforma(Request $request){

        try{
            DB::table('einforma')->where('id', $request->get('id'))->where('lastEditor', '!=', 'einforma')->delete();
        }catch(Exception $e){
            log::error($e->getMessage());
            header('HTTP/1.0 500 Internal server error');
            die(json_encode(array($e->getMessage())));
        }

        return "eInforma manual borrado correctamente";
    }

    public function crearEmpresa(Request $request){

        $uri = str_replace(" ","-",cleanUriBeforeSave(mb_strtolower(trim($request->get('nombre')))));
        $checkentidades = DB::table('entidades')->where('CIF', $request->get('cif'))->first();

        if($checkentidades){
            return redirect()->back()->withErrors('Ese CIF ya ha sido añadido a nuestra lista de empresas, puedes ver la empresa en este enlace: <a class="text-white" href="'.route('empresa', $checkentidades->uri).'">Ver empresa</a>');
        }

        $sedes = new stdClass;
        $sedes->central = $request->get('ccaa');
        $sedes->otrassedes = array();

        try{
            $id = DB::table('entidades')->insertGetId(
                [
                    'CIF' => $request->get('cif'),
                    'Nombre' => $request->get('nombre'),
                    'Web' => $request->get('web'),
                    'Ccaa' => $request->get('ccaa'),
                    'Cnaes' => $request->get('cnaes'),
                    'Sedes' => json_encode($sedes),
                    'uri' => $uri,
                    'naturalezaEmpresa' => json_encode(["6668837"]),
                    'NumeroLineasTec' => 2,
                    'Intereses' => json_encode(["I+D","Innovación","Digitalización","Cooperación","Subcontratación"]),
                    'MinimoSubcontratar' => 0,
                    'MinimoCooperar' => 0,
                    'EntityUpdate' => Carbon::now(),
                    'created_at' => Carbon::now(),
                ]
            );

        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido crear la empresa');
        }


        return redirect()->back()->withSuccess('Empresa creada correctamente, puedes ver sus datos en este enlace: <a class="text-white" href="'.route('viewempresa', [$id,$request->get('cif')]).'">Ver empresa</a>');

    }

    public function avisarUsuarios(Request $request){

        $entity = DB::table('entidades')->where('CIF', $request->get('cif'))->first();
        if(!$entity){
            return redirect()->back()->withErrors('No se ha encontrado la empresa');
        }
        $einforma = DB::table('einforma')->where('identificativo', $entity->CIF)->select('web')->first();

        if($einforma && isset($einforma->web) && $einforma->web !== null 
        && $einforma->web != "" && !empty($einforma->web) && is_array(json_decode($einforma->web, true))){

            $webs = array();
            $einformawebs = json_decode($einforma->web, true);

            foreach($einformawebs as $web){
                if(!empty($web)){
                    $webs[] = $web;
                }
            }
        }

        if(!empty($entity->Web)){
            $webs[] = $entity->Web;
        }

        $users = array();

        foreach($webs as $web){

            $domain = parse_url($web);

            if(isset($domain['host'])){
                $domainname = str_replace("www.","", $domain['host']);
            }else{
                $domainname = str_replace("www.","", $domain['path']);
            }
            $domainusers = DB::table('users')->where('email', 'LIKE', '%'.$domainname)->get();

            foreach($domainusers as $user){
                if(!isset($users[$user->id])){
                    $users[$user->id]['id'] = $user->id;
                    $users[$user->id]['email'] = $user->email;
                }
            }

        }

        foreach($users as $key => $user){

            $checkisInEmpresa = \App\Models\UsersEntidad::where('users_id', $key)->where('entidad_id', $entity->id)->count();

            if($checkisInEmpresa > 0){
                unset($users[$key]);
                continue;
            }

            $notification = DB::table('notifications')->whereJsonContains('data->user_id', $key)
            ->where('entity_id', $entity->id)->count();

            if($notification > 0){
                unset($users[$key]);
                continue;
            }

            $mail = new \App\Mail\EmpresaCreada($entity->Nombre);
            Mail::to($user['email'])->queue($mail);

        }

        $validateCompanies = \App\Models\ValidateCompany::where('cif', $entity->CIF)->where('aceptado', 0)->get();

        if($validateCompanies){
            foreach($validateCompanies as $validation){
                $user = \App\Models\User::find($validation->user_id);
                if($user){
                    $mail = new \App\Mail\EmpresaCreada($entity->Nombre);
                    Mail::to($user->email)->queue($mail);
                }
                try{
                    $validation->aceptado = 1;
                    $validation->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return redirect()->back()->withErrors('Error en la notificacion a los usuarios 2');
                }
            }
        }

        return redirect()->back()->withSuccess('Enviado correo a todos los usuarios con una solicitud pendiente en esta empresa');

    }

    public function DashboardEditProyecto(Request $request){

        try{
            DB::table('proyectos')->where('id', $request->get('id'))->update([
                'Acronimo' => $request->get('acronimo'),
                'Titulo' => $request->get('titulo'),
                'Descripcion' => $request->get('descproyecto'),
                'IdAyuda' => $request->get('ayuda'),
                'Estado' => $request->get('estado')
            ]);
        }catch(Exception $e){
            dd($e->getMessage());
        }

        return "Proyecto actualizado";
    }

    public function updateUmbrales(Request $request, GeneralSettings $settings){

        try{
            $settings->umbral_ayudas = $request->input('umbralayudas');
            $settings->umbral_proyectos = $request->input('umbralproyectos');
            $settings->allow_register = ($request->input('allow') === null) ? false : true;
            $settings->enlace_evento = ($request->input('enlaceevento') === null) ? "" : $request->input('enlaceevento');
            $settings->texto_evento = ($request->input('textoevento') === null) ? "Ver evento" : $request->input('textoevento');
            $settings->enable_einforma = ($request->input('enable_einforma') === null) ? false : true;
            $settings->enable_axesor = ($request->input('enable_axesor') === null) ? false : true;
            $settings->master_featured = ($request->input('master_featured') === null) ? false : true;
            $settings->save();

        }catch(Exception $e){
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Umbrales actualizados');
    }

    public function editarEncaje(Request $request){

        $id = $request->get('id');

        if(!userEntidadSelected()){
            return abort(403);
        }

        $proyecto = DB::table('proyectos')->where('id', $request->get('proyecto_id'))->first();
        $lider = DB::table('entidades')->where('id', userEntidadSelected()->id)->first();

        if($proyecto && ($lider->CIF == $proyecto->empresaPrincipal)){

            try{
                DB::table('Encajes_zoho')->where('id', $id)->update([
                    'Descripcion' => html_entity_decode($request->get('descripcion')),
                    'Encaje_presupuesto' => $request->get('presupuestoparticipantes'),
                    'Encaje_categoria' => json_encode($request->get('tipo')),
                    'Encaje_trl' => ($request->get('trl') === null)? 10 : $request->get('trl'),
                    'TagsTec' => ($request->get('tagstec')) ? $request->get('tagstec') : null,
                ]);

            }catch(Exception $e){
                dd($e->getMessage());
            }

            try{
                Artisan::call('elastic:ayudas', [
                    'id' => $id,
                    'tipo' => 'proyecto'
                ]);
            }catch(Exception $e){
                dd($e->getMessage());
            }
        }

        return "Búsqueda de partner actualizada";
    }

    public function addEncaje(Request $request){

        $proyecto = DB::table('proyectos')->where('id', $request->get('proyecto_id'))->first();
        $lider = DB::table('entidades')->where('id', userEntidadSelected()->id)->first();
        $ayuda = $ayuda = \App\Models\Ayudas::where('id', $proyecto->IdAyuda)->first();

        $ambito = null;
        $ccaas = null;
        $opcioncnae = null;
        $cnaes = null;
        $intereses = null;

        if($ayuda){
            $ambito = $ayuda->Ambito;
            if($ayuda->Ambito == "Comunidad Autónoma"){
                $ccaas = $ayuda->Ccaas;
            }
            $opcioncnae = $ayuda->OpcionCNAE;
            if($ayuda->OpcionCNAE == "Excluidos" || $ayuda->OpcionCNAE == "Válidos"){
                $cnaes = $ayuda->CNAES;
            }
            if($request->get('partneriado') == "Cooperación"){
                $intereses = json_encode(self::CONSORCIOS);
            }
            if($request->get('partneriado') == "Subcontratación"){
                $intereses = json_encode(self::ALLINTERESES);
            }
        }

        if($proyecto && ($lider->CIF == $proyecto->empresaPrincipal)){

            try{
                $id = DB::table('Encajes_zoho')->insertGetId([
                    'Acronimo' => $proyecto->Acronimo,
                    'Titulo' => html_entity_decode($request->get('titulo')),
                    'Descripcion' => html_entity_decode($request->get('descripcion')),
                    'Tipo' => 'Proyecto',
                    'PerfilFinanciacion' => $intereses,
                    'Encaje_presupuesto' => $request->get('presupuestoparticipantes'),
                    'Encaje_categoria' => json_encode($request->get('tipo')),
                    'Encaje_ambito' => $ambito,
                    'Encaje_ccaa' => $ccaas,
                    'Encaje_opcioncnaes' => $opcioncnae,
                    'Encaje_cnaes' => $cnaes,
                    'Ayuda_id' => $request->get('proyecto_id'),
                    'tipoPartner' => $request->get('partneriado'),
                    'naturalezaPartner' => json_encode($request->get('naturaleza')),
                    'TagsTec' => json_encode(explode(",",$request->get('tagstec'))),
                    'Encaje_trl' => ($request->get('trl') === null)? 10 : $request->get('trl'),
                ]);

            }catch(Exception $e){
                die($e->getMessage());
            }

            try{
                Artisan::call('elastic:ayudas', [
                    'id' => $id,
                    'tipo' => 'proyecto'
                ]);
            }catch(Exception $e){
                dd($e->getMessage());
            }

        }

        return "Búsqueda de partner creada";

    }

    public function quitarEncaje(Request $request){

        $id = $request->get('id');
        $proyecto = DB::table('proyectos')->where('id', $request->get('ayudaid'))->first();

        $encaje = DB::table('Encajes_zoho')->where('id', $id)->first();
        $encajes = DB::table('Encajes_zoho')->where('Ayuda_id', $proyecto->id)->count();
        if($encajes <= 1)
        {
            header('HTTP/1.1 500 Internal Server Innovating.works');
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(array('message' => 'Los proyectos han de tener mínimo una búsqueda de partner', 'code' => 1983)));
        }
        try{
            DB::table('Encajes_zoho')->where('id', $encaje->id)->delete();
        }catch(Exception $e){
            die($e->getMessage());
        }


        return "Búsqueda de partner eliminada";
    }

    public function solucionaralarma(Request $request){

        $id = $request->get('id');

        if(!$id){
            return abort(419);
        }

        try{
            DB::table('alarms')->where('id', $id)->update([
                'solucionado' => 1,
                'updated_at' => Carbon::now()
            ]);
        }catch(Exception $e){
            die($e->getMessage());
        }

        return "Alarma solucionada";
    }

    private function calculaImasD($cif){

        if(!$cif){
            return abort(419);
        }

        try{
            Artisan::call('calcula:I+D', [
                'cif' =>  $cif
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return abort(419);
        }

        try{
            Artisan::call('elastic:companies', [
                'cif' =>  $cif
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return abort(419);
        }

        return true;
    }

    public function solicitarEinforma(Request $request){

        try{
            $response = Artisan::call('sync:einforma', [
                'cif' => $request->get('data'),
                'tipo' => 'manual'
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return false;
        }

        if($response == 0){
            return Response::json(['error' => 'Einforma no encontrado'], 404); // Status code here
        }

        $checkEntity = DB::table('entidades')->where('CIF', $request->get('data'))->first();

        if($checkEntity === null){

            try{
                Artisan::call('move:cifsnozoho', [
                    'cif' => $request->get('data'),
                    'force' => 1
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
                return false;
            }
        }

        try{
            Artisan::call('calcula:I+D', [
                'cif' => $request->get('data'),
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return false;
        }

        try{
            Artisan::call('calcula:nivel_cooperacion', [
                'cif' => $request->get('data'),
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return false;
        }

        return Response::json(['success' => 'Einforma encontrado'], 200); // Status code here

    }

    public function solicitarAxesor(Request $request){

        try{
            $response = Artisan::call('get:axesor', [
                'cif' => $request->get('data')
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return false;
        }

        if($response == 0){
            return Response::json(['error' => 'Axesor no encontrado'], 404); // Status code here
        }

        $checkEntity = DB::table('entidades')->where('CIF', $request->get('data'))->first();

        if($checkEntity === null){

            try{
                Artisan::call('move:cifsnozoho', [
                    'cif' => $request->get('data'),
                    'force' => 1
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
                return false;
            }
        }

        try{
            Artisan::call('calcula:I+D', [
                'cif' => $request->get('data'),
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return false;
        }

        try{
            Artisan::call('calcula:nivel_cooperacion', [
                'cif' => $request->get('data'),
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return false;
        }

        return Response::json(['success' => 'Axesor encontrado'], 200); // Status code here

    }

    function mostrarUltimasNoticias(Request $request){

        $check = \App\Models\CompanyNews::where('company_id', $request->get('cif'))->first();

        if($check !== null){
            try{
                $check->mostrar = ($request->get('ultimasnoticias') === null) ? 0 : 1;
                $check->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('No se ha podido actualizar Ultimas noticias en perfil público');
            }
        }

        return redirect()->back()->withSuccess('Ultimas noticias en perfil público actualizado, recuerda actualizar las caches de empresas y generar los datos de este bloque desde el boton "3. Recalculo del I+D y enviar al buscador"');
    }

    function generaGraficosOrganismo($empresa){
        
        $dpto = \App\Models\Organos::find($empresa->idOrganismo);
        $esorgano = false;
        if(!$dpto){
            $dpto = \App\Models\Departamentos::find($empresa->idOrganismo);
            $esorgano = true;
        }
        if(!$dpto){
            return abort(419);
        }

        if($esorgano === true){
            $concesiones = \App\Models\Concessions::where('id_departamento', $empresa->idOrganismo)->where('fecha', '>=', now()->subMonths(6)->format('Y-m-01'))->get();
        }else{
            $concesiones = \App\Models\Concessions::where('id_organo', $empresa->idOrganismo)->where('fecha', '>=', now()->subMonths(6)->format('Y-m-01'))->get();
        }

        if($concesiones->count() > 0){
            
            Carbon::setlocale(config('app.locale'));

            ##DATOS GRAFICO SUMA TOTAL DE CONCESIONES POR MES
            $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(6)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(6)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(5)->endOfMonth()->format('Y-m-d'))->count()];
            $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(5)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(5)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(4)->endOfMonth()->format('Y-m-d'))->count()];
            $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(4)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(4)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(3)->endOfMonth()->format('Y-m-d'))->count()];
            $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(3)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(3)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(2)->endOfMonth()->format('Y-m-d'))->count()];
            $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(2)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(2)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(1)->endOfMonth()->format('Y-m-d'))->count()];
            $totalconcesiones[] = ["mes" => ucfirst(now()->subMonths(1)->translatedFormat('F')), "total" => collect($concesiones)->where('fecha', '>=', now()->subMonths(1)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->endOfMonth()->format('Y-m-d'))->count()];

            $check = 0;
            if(!empty($totalconcesiones)){
                $check = max(array_column($totalconcesiones, "total"));
            }

            try{
                $grafico = new \App\Models\GraficosOrganismos();
                if($esorgano === true){
                    $grafico->id_departamento = $empresa->idOrganismo;
                }else{
                    $grafico->id_organo = $empresa->idOrganismo;
                }
                $grafico->type = "grafico-1";
                $grafico->nombre = "Número de Concesiones Resgistadas en los últimos 6 Meses";
                $grafico->datos = json_encode($totalconcesiones, JSON_UNESCAPED_UNICODE);
                $grafico->activo = ($check > 0) ? 1 : 0;
                $grafico->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('No se ha podido generar el gráfico 1 correctamente');
            }
            
            ##DATOS GRAFICO SUMA TOTAL DE AMOUNTS DE CONCESIONES POR MES
            $totaldinero[] = ["mes" => ucfirst(now()->subMonths(6)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(6)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(5)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
            $totaldinero[] = ["mes" => ucfirst(now()->subMonths(5)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(5)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(4)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
            $totaldinero[] = ["mes" => ucfirst(now()->subMonths(4)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(4)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(3)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
            $totaldinero[] = ["mes" => ucfirst(now()->subMonths(3)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(3)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(2)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
            $totaldinero[] = ["mes" => ucfirst(now()->subMonths(2)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(2)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->subMonths(1)->endOfMonth()->format('Y-m-d'))->sum->amount,0)];
            $totaldinero[] = ["mes" => ucfirst(now()->subMonths(1)->translatedFormat('F')), "total" => intval(collect($concesiones)->where('fecha', '>=', now()->subMonths(1)->startOfMonth()->format('Y-m-d'))->where('fecha', '<=', now()->endOfMonth()->format('Y-m-d'))->sum->amount,0)];

            $check = 0;
            if(!empty($totaldinero)){
                $check = max(array_column($totaldinero, "total"));
            }

            try{
                $grafico = new \App\Models\GraficosOrganismos();
                if($esorgano === true){
                    $grafico->id_departamento = $empresa->idOrganismo;
                }else{
                    $grafico->id_organo = $empresa->idOrganismo;
                }
                $grafico->type = "grafico-2";
                $grafico->nombre = "Importe concesiones registradas últimos 6 meses";
                $grafico->datos = json_encode($totaldinero, JSON_UNESCAPED_UNICODE  );
                $grafico->activo = ($check > 0) ? 1 : 0;
                $grafico->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('No se ha podido generar el gráfico 2 correctamente');
            }

            ##DATOS GRAFICO TAMAÑOS DE EMPRESA CON CONCESIONES
            $empresatipo[] = ['tipo' => 'Micro', "total" => $concesiones->filter(function($concesion){
                if($concesion->entidad !== null && $concesion->entidad->einforma !== null){
                    if($concesion->entidad->einforma->categoriaEmpresa == "Micro"){
                        return true;
                    }
                }
                return false;
            })->count()];
            $empresatipo[] = ['tipo' => 'Pequeña', "total" => $concesiones->filter(function($concesion){
                if($concesion->entidad !== null && $concesion->entidad->einforma !== null){
                    if($concesion->entidad->einforma->categoriaEmpresa == "Pequeña"){
                        return true;
                    }
                }
                return false;
            })->count()];
            $empresatipo[] = ['tipo' => 'Mediana', "total" => $concesiones->filter(function($concesion){
                if($concesion->entidad !== null && $concesion->entidad->einforma !== null){
                    if($concesion->entidad->einforma->categoriaEmpresa == "Mediana"){
                        return true;
                    }
                }
                return false;
            })->count()];
            $empresatipo[] = ['tipo' => 'Grande', "total" => $concesiones->filter(function($concesion){
                if($concesion->entidad !== null && $concesion->entidad->einforma !== null){
                    if($concesion->entidad->einforma->categoriaEmpresa == "Grande"){
                        return true;
                    }
                }
                return false;
            })->count()];
            $empresatipo[] = ['tipo' => 'Desconocido', "total" => $concesiones->filter(function($concesion){
                if($concesion->entidad !== null && $concesion->entidad->einforma !== null){
                    if($concesion->entidad->einforma->categoriaEmpresa == "Desconocido" || $concesion->entidad->einforma->categoriaEmpresa == "Error"){
                        return true;
                    }
                }
                return false;
            })->count()];

            $check = 0;
            if(!empty($empresatipo)){
                $check = max(array_column($empresatipo, "total"));
            }

            try{
                $grafico = new \App\Models\GraficosOrganismos();
                if($esorgano === true){
                    $grafico->id_departamento = $empresa->idOrganismo;
                }else{
                    $grafico->id_organo = $empresa->idOrganismo;
                }
                $grafico->type = "grafico-3";
                $grafico->nombre = "Importe concesiones registradas por categoría de empresa últimos 6 meses";
                $grafico->datos = json_encode($empresatipo, JSON_UNESCAPED_UNICODE);
                $grafico->activo = ($check > 0) ? 1 : 0;
                $grafico->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('No se ha podido generar el gráfico 3 correctamente');
            }

            ##DATOS TABLA CNAES EMPRESAS CON CONCESIONES
            $empresacnaes = array();       
            $cifs = array();     
            foreach ($concesiones as $key => $concesion) {            
                if($concesion->entidad !== null && $concesion->entidad->einforma !== null){                    
                    $cnae = substr($concesion->entidad->einforma->cnae, 0, strripos($concesion->entidad->einforma->cnae,"-") -3);
                    $cnae = str_replace('"', '', $cnae);
                    if(array_search($cnae, array_column($empresacnaes, 'cnaeid')) === false && !in_array($concesion->entidad->CIF,$cifs)){                        
                        $empresacnaes[$cnae]['cnaeid'] = $cnae;
                        $empresacnaes[$cnae]['cnae'] = $concesion->entidad->einforma->cnae;
                        $empresacnaes[$cnae]['amount'] = $concesion->amount;
                        $empresacnaes[$cnae]['total'] = 1;
                        $cifs[] = $concesion->entidad->CIF;
                    }else{
                        if(isset($empresacnaes[$cnae])){
                            $empresacnaes[$cnae]['amount'] += $concesion->amount;
                            $empresacnaes[$cnae]['total'] += 1;
                        }
                    }
                }
            }

            $key_values = array_column($empresacnaes, 'total'); 
            array_multisort($key_values, SORT_DESC, $empresacnaes);
            $empresacnaes = array_values($empresacnaes);

            $check = 0;
            if(!empty($empresacnaes)){
                $check = max(array_column($empresacnaes, "total"));
            }

            try{
                $grafico = new \App\Models\GraficosOrganismos();
                if($esorgano === true){
                    $grafico->id_departamento = $empresa->idOrganismo;
                }else{
                    $grafico->id_organo = $empresa->idOrganismo;
                }
                $grafico->type = "grafico-4";
                $grafico->nombre = "Importe concesiones por CNAE 2 dígitos en los últimos 6 meses";
                $grafico->datos = json_encode($empresacnaes, JSON_UNESCAPED_UNICODE);
                $grafico->activo = ($check > 0) ? 1 : 0;
                $grafico->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('No se ha podido generar el gráfico 4 correctamente');
            }

            ##DATOS TABLA CCAAS EMPRESAS CON CONCESIONES
            $empresaccaas = array();       
            $ccaas = array();     
            foreach ($concesiones as $key => $concesion) {            
                if($concesion->entidad !== null && $concesion->entidad->einforma !== null && $concesion->entidad->einforma->ccaa !== null){        
                    $ccaa = mb_strtolower(str_replace(" ", "", $concesion->entidad->einforma->ccaa));
                    if(array_search($concesion->entidad->einforma->ccaa, array_column($empresaccaas, 'ccaa')) === false && !in_array($concesion->entidad->einforma->ccaa,$ccaas)){                        
                        $empresaccaas[$ccaa]['ccaa'] = $concesion->entidad->einforma->ccaa;
                        $empresaccaas[$ccaa]['amount'] = $concesion->amount;
                        $empresaccaas[$ccaa]['total'] = 1;
                        $ccaas[] = $concesion->entidad->einforma->ccaa;
                    }else{
                        if(isset($empresaccaas[$ccaa])){
                            $empresaccaas[$ccaa]['amount'] += $concesion->amount;
                            $empresaccaas[$ccaa]['total'] += 1;
                        }
                    }
                }
            }

            $key_values = array_column($empresaccaas, 'total'); 
            array_multisort($key_values, SORT_DESC, $empresaccaas);
            $empresaccaas = array_values($empresaccaas);

            $check = 0;
            if(!empty($empresaccaas)){
                $check = max(array_column($empresaccaas, "total"));
            }
  
            try{
                $grafico = new \App\Models\GraficosOrganismos();
                if($esorgano === true){
                    $grafico->id_departamento = $empresa->idOrganismo;
                }else{
                    $grafico->id_organo = $empresa->idOrganismo;
                }
                $grafico->type = "grafico-5";
                $grafico->nombre = "Importe concesiones por Comunidad Autónoma en los últimos 6 meses";
                $grafico->datos = json_encode($empresaccaas, JSON_UNESCAPED_UNICODE, ENT_QUOTES);
                $grafico->activo = ($check > 0) ? 1 : 0;
                $grafico->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('No se ha podido generar el gráfico 5 correctamente');
            }

        }

        return true;
    }

}
