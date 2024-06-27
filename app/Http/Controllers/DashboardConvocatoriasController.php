<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardConvocatoriasController extends Controller
{
    //
    public function convocatorias(Request $request){

        if($request->query('Estado')){
            if($request->query('Estado') == "publicadas"){
                $ayudas =\App\Models\Ayudas::where('Publicada', 1)->orderBy('updated_at','desc')->orderBy('created_at','desc')->paginate(50);
            }
            elseif($request->query('Estado') == "nopublicadas"){
                $ayudas =\App\Models\Ayudas::where('Publicada', 0)->orderBy('updated_at','desc')->orderBy('created_at','desc')->paginate(50);
            }elseif($request->query('Estado') == "nuevas"){
                $ayudas =\App\Models\Ayudas::where('created_at', '>=', Carbon::now()->subDays(15))->orderBy('updated_at','desc')->orderBy('created_at','desc')->paginate(50);
            }else{
                $ayudas = \App\Models\Ayudas::orderBy('updated_at','desc')->orderBy('created_at','desc')->paginate(50);
            }
        }else{
            $ayudas = \App\Models\Ayudas::orderBy('updated_at','desc')->orderBy('created_at','desc')->paginate(50);
        }

        foreach($ayudas as $ayuda){
            if($ayuda->organo !== null){                                
                $ayuda->dpto = $ayuda->organo->url;
                $ayuda->dptoNombre = $ayuda->organo->Nombre;                
            }
            if($ayuda->departamento !== null){                                
                $ayuda->dpto = $ayuda->departamento->url;
                $ayuda->dptoNombre = $ayuda->departamento->Nombre;            
            }
            $ayuda->totalencajes = \App\Models\Encaje::where('Ayuda_id', $ayuda->id)->count();
        }

        $naturalezas = \App\Models\Naturalezas::where('Activo', 1)->get();

        return view('admin.convocatorias.convocatorias', [
            'ayudas' => $ayudas,
            'naturalezas' => $naturalezas
        ]);
    }


    public function buscarConvocatorias(Request $request){

        $text = $request->get('text');
        
        $convocatorias = \App\Models\Ayudas::where('Acronimo', 'LIKE', '%'.$text.'%')->orWhere('Titulo', 'LIKE', '%'.$text.'%')->get();

        if($convocatorias->isEmpty()){

            header('HTTP/1.0 404 Not Found');
            die(json_encode(array('No se han encontrado convocatorias')));        
        }

        $html = '';

        if($convocatorias->isNotEmpty()){
            $html = "<ul>";
        }

        foreach($convocatorias as $convocatoria){                        
            $html .= "<li><a href=".route('admineditarconvocatoria', $convocatoria->id)." target='_blank'>(".$convocatoria->Acronimo."): ".$convocatoria->Titulo."</a></li>";            
        }

        $html .= "</ul>";

        return $html;

    }


    public function editarConvocatoria($id){

        if($id === null){
            return abort(419);
        }

        $convocatoria = \App\Models\Ayudas::where('id', $id)->first();

        if(!$convocatoria){
            return abort(419);
        }

        $intereses = \App\Models\Intereses::where('defecto', 'true')->get();
        
        $ccaas = \App\Models\Ccaa::orderBy('Nombre')->get();
        $cnaes = \App\Models\Cnaes::all();        
        $encajes = \App\Models\Encaje::where('Ayuda_id', $id)->get();

        $org = null;
        $org = \App\Models\Organos::where('id', $convocatoria->Organismo)->first();
        if(!$org){
            $org = \App\Models\Departamentos::where('id', $convocatoria->Organismo)->first();
        }

        $convocatoria->dpto = null;
        if($org){
            $convocatoria->dpto = $org->url;
        }

        #$convocatorias = DB::table('convocatorias')->whereJsonContains('id_ayudas', $id)->get();

        foreach($encajes as $encaje){
            if(is_array($encaje->TagsTec)){
                $encaje->TagsTec = json_decode($encaje->TagsTec, true);
            }
        }

        $trls = \App\Models\Trl::all();
        $naturalezas = \App\Models\Naturalezas::where('Activo', 1)->get();
        $ayuda = \App\Models\Convocatorias::where('id', $convocatoria->id_ayuda)->first();
        $ayudas = \App\Models\Convocatorias::where('extinguida', 0)->get();
        $fondos = \App\Models\Fondos::where('status', 1)->get();

        $condiciones = \App\Models\CondicionesFinancieras::where('idsconvocatorias', 'LIKE', '%'.$convocatoria->id.'%')->get();
        $variables = [
            'Gastos Anuales' => 'Gastos Anuales',
            'Presupuesto del proyecto' => 'Presupuesto del proyecto',
            'Fondos propios' => 'Fondos propios',
            'Circulante' => 'Circulante',
            'Beneficios reales' => 'Beneficios reales',
            'Margen de endeudamiento' => 'Margen de endeudamiento'
        ];
        $variables2 = [
            'Fijo' => 'Fijo',
            'Presupuesto Total del proyecto' => 'Presupuesto Total del proyecto',
            'Presupuesto Mínimo de la ayuda' => 'Presupuesto mínimo de la ayuda',
            'Presupuesto Máximo de la ayuda' => 'Presupuesto Máximo de la ayuda',
        ];
      
        $solicitudespriorizarcashflow = \App\Models\PriorizaAnalisisTesoreria::where('convocatoria_id', $convocatoria->id)->count();

        $subfondos = \App\Models\Subfondos::all();
        $actions = \App\Models\TypeOfActions::all();
        $checksubfondos = false;
        if(isset($convocatoria->FondosEuropeos) && $convocatoria->FondosEuropeos !== null){
            foreach(json_decode($convocatoria->FondosEuropeos, true) as $fondo_id){
                $fondossubfondos = \App\Models\FondosSubfondos::where('fondo_id', $fondo_id)->get();
                if($fondossubfondos->count() > 0){
                    $checksubfondos = true;
                }
            }
            
        }
        
        $capitulosFinanciacion = \App\Models\CapitulosPaises::where('pais', 'ES')->where('activo', 1)->get();

        return view('admin.convocatorias.editar', [
            'ayuda_convocatoria' => $ayuda,
            'ayuda' => $convocatoria,
            'org' => $org,
            'condiciones' => $condiciones,
            'variables' => $variables,
            'variables2' => $variables2,
            'intereses' => $intereses,
            'ayudasconv' => $ayudas,
            'cnaes' => $cnaes,
            'ccaas' => $ccaas,
            'trls' => $trls,
            'fondos' => $fondos,
            'subfondos' => $subfondos,
            'checksubfondos' => $checksubfondos,
            'actions' => $actions,
            'categorias' => json_decode($convocatoria->Categoria, true),
            'naturalezas' => $naturalezas,
            'encajes' => $encajes,
            'solicitudespriorizarcashflow' => $solicitudespriorizarcashflow,
            'capitulosFinanciacion' => $capitulosFinanciacion
        ]);

    }

    public function editConvocatoria(Request $request){

        $id = $request->get('id');
        $ayuda = \App\Models\Ayudas::where('id', $id)->first();

        if(!$ayuda){
            return redirect()->back()->withErrors('No se ha encontrado la convocatoria que estas intentando actualizar');
        }

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
                return redirect()->back()->withErrors('No se puede guardar una ayuda de temática obligatoria que no tenga un encaje de tipo línea');
            }
        }

        $convocatoriasenayuda = \App\Models\Ayudas::where('id_ayuda', $request->get('id_ayuda'))->where('Estado', '!=', 'Cerrada')
        ->where('es_europea', 0)->get();

        if($convocatoriasenayuda->count() > 1){
            foreach($convocatoriasenayuda as $convoca){
                if($convoca->id != $id){
                    if($convoca->Inicio !== null && $convoca->Fin !== null 
                        && $request->get('inicio') !== null &&  $request->get('fin') !== null && $request->get('inicio') != "" &&  $request->get('fin') != ""){
                        if(Carbon::createFromFormat('d/m/Y', $request->get('inicio')) >= Carbon::createFromFormat('Y-m-d', $convoca->Inicio)
                            && Carbon::createFromFormat('d/m/Y', $request->get('fin')) <= Carbon::createFromFormat('Y-m-d', $convoca->Fin)){
                            return redirect()->back()->withErrors('No se puede guardar una convocatoria que tiene fechas de inicio o fin entre las fechas de otra convocatoria de la ayuda seleccionada');
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

        if($fechaEmails === null && $request->get('inicio') !== null && $request->get('inicio') != ""){
            $fechaEmails = Carbon::createFromFormat('d/m/Y', $request->get('inicio'))->format('Y-m-d');
        }

        $typeOfAction = null;
        if($request->get('old_tpa') != $request->get('type_of_action_id')){
            if($ayuda->type_of_action_id !== $request->get('type_of_action_id')){
                $typeOfAction = \App\Models\TypeOfActions::find($request->get('type_of_action_id'));
            }            
        }

        try{

            if($typeOfAction === null){
                $ayuda->Categoria = $categoria;
                $ayuda->naturalezaConvocatoria = (!empty($request->get('naturaleza_convocatoria'))) ? json_encode($request->get('naturaleza_convocatoria')) : null;
                $ayuda->Presentacion = $request->get('presentacion');
                $ayuda->TipoFinanciacion = json_encode($request->get('tipofinanciacion'));
                $ayuda->Trl = ($request->get('trl') != "") ? $request->get('trl') : null;
                $ayuda->objetivoFinanciacion = $request->get('objetivoFinanciacion');
                $ayuda->PorcentajeFondoPerdido = $request->get('porcentajefondoperdido');
                $ayuda->FondoPerdidoMinimo = $request->get('porcentajefondoperdidominimo');
            }else{
                $ayuda->Categoria = $typeOfAction->categoria;
                $ayuda->naturalezaConvocatoria = $typeOfAction->naturaleza;
                $ayuda->Presentacion = $typeOfAction->presentacion;
                $ayuda->TipoFinanciacion = $typeOfAction->tipo_financiacion;
                $ayuda->Trl = $typeOfAction->trl;
                $ayuda->objetivoFinanciacion = $typeOfAction->objetivo_financiacion;
                $ayuda->FondoPerdidoMinimo = $typeOfAction->fondo_perdido_minimo;
                $ayuda->PorcentajeFondoPerdido = $typeOfAction->fondo_perdido_maximo;
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
                        $ayuda->FondoPerdidoMaximoNominal = (isset($array[$indice][0])) ? $array[$indice][0] : null;
                    }
                    if($ayuda->rawdataEU->maxContribution !== null){
                        $array = json_decode($ayuda->rawdataEU->maxContribution, true);
                        $ayuda->FondoPerdidoMinimoNominal = (isset($array[$indice][0])) ? $array[$indice][0] : null;
                    }    
                }
            }

            $ayuda->id_ayuda = $request->get('id_ayuda');
            $ayuda->IdConvocatoriaStr = $idAcronimo;
            $ayuda->InformacionDefinitiva = $infodefinitiva;
            $ayuda->Acronimo = $request->get('acronimo');
            $ayuda->Titulo = $request->get('titulo');
            $ayuda->Uri = $uri;
            $ayuda->Link = $request->get('link');          
            $ayuda->Intensidad = $intensidad;
            $ayuda->PerfilFinanciacion = $intereses;
            $ayuda->Presupuesto = $request->get('presupuesto');
            $ayuda->PresupuestoConsorcio = ($request->get('presupuestoconsorcio') === "") ? 0 : $request->get('presupuestoconsorcio');
            $ayuda->PresupuestoParticipante = ($request->get('presupuestoparticipante') === "" || $request->get('presupuestoparticipante') === null) ? null : $request->get('presupuestoparticipante');
            $ayuda->NumeroParticipantes = ($request->get('numeroparticipantes') === null || $request->get('numeroparticipantes') === "") ? 1 : $request->get('numeroparticipantes'); 
            $ayuda->Ambito = $request->get('ambito');
            $ayuda->opcionCNAE = $request->get('opcionCNAE');
            $ayuda->CNAES = $cnaes;
            $ayuda->DescripcionCorta = $descCorta;
            $ayuda->DescripcionLarga = $descLarga;
            $ayuda->RequisitosTecnicos = $requisitos;
            $ayuda->RequisitosParticipante = ($request->get('requisitos_participantes') !== null && $request->get('requisitos_participantes') != "") ? strip_tags($request->get('requisitos_participantes')) : null;
            $ayuda->Estado = $estado;
            $ayuda->Competitiva = $request->get('competitiva');
            $ayuda->Organismo = $organismo;
            $ayuda->Ccaas = $ccaas;
            $ayuda->Featured = $featured;           
            $ayuda->CapitulosFinanciacion = $capitulos;
            $ayuda->CondicionesFinanciacion = $condicinesfinanciacion;
            $ayuda->CondicionesEspeciales = $condicionesespeciales;
            $ayuda->PresupuestoMin = $presupuestomin;
            $ayuda->PresupuestoMax = $presupuestomax;
            $ayuda->DuracionMin = $duracionmin;
            $ayuda->DuracionMax = $duracionmax;
            $ayuda->Garantias = $garantias;
            $ayuda->Inicio = ($request->get('inicio') != "") ? Carbon::createFromFormat('d/m/Y', $request->get('inicio'))->format('Y-m-d') : null;
            $ayuda->Fin = ($request->get('fin') != "") ? Carbon::createFromFormat('d/m/Y', $request->get('fin'))->format('Y-m-d') : null;
            $ayuda->fechaEmails = $fechaEmails;
            $ayuda->MesesMin = ($mesesmin) ? $mesesmin : null;
            $ayuda->FechaMinConstitucion = ($fechamin) ? Carbon::createFromFormat('d/m/Y', $fechamin)->format('Y-m-d') : null;
            $ayuda->Meses = ($meses) ? $meses : null;
            $ayuda->FechaMaxConstitucion = ($fechamax) ? Carbon::createFromFormat('d/m/Y', $fechamax)->format('Y-m-d') : null;            
            $ayuda->PorcentajeCreditoMax = $request->get('porcentajecreditomax');            
            $ayuda->CreditoMinimo = $request->get('porcentajecreditominimo');
            $ayuda->DeduccionMax = $request->get('deduccionmax');
            $ayuda->NivelCompetitivo = ($request->get('nivelcompetitivo') && $request->get('nivelcompetitivo') != "") ? $request->get('nivelcompetitivo') : null;
            $ayuda->TiempoMedioResolucion = ($request->get('tiempomedioresolucion') && $request->get('tiempomedioresolucion') != "") ? $request->get('tiempomedioresolucion') : null;
            $ayuda->SelloPyme = ($request->get('sellopyme') && $request->get('sellopyme') != "") ? 1 : 0;
            $ayuda->EmpresaCrisis = ($request->get('empresacrisis') && $request->get('empresacrisis') != "") ? 1 : 0;
            $ayuda->InformeMotivado = ($request->get('informemotivado') && $request->get('informemotivado') != "") ? 1 : 0;
            $ayuda->TextoCondiciones = ($request->get('textocondiciones') && $request->get('textocondiciones') != "") ? $request->get('textocondiciones') : null;
            $ayuda->TextoConsorcio = ($request->get('textoconsorcio') && $request->get('textoconsorcio') != "") ? $request->get('textoconsorcio') : null;
            $ayuda->FondoTramo = $request->get('fondotramo');
            $ayuda->LastEditor = Auth::user()->email;
            $ayuda->updated_at = Carbon::now();
            $ayuda->AplicacionIntereses = $request->get('aplicacionintereses');
            $ayuda->PorcentajeIntereses = $request->get('porcentajeintereses');
            $ayuda->AnosAmortizacion = $request->get('anosamortizacion');
            $ayuda->MesesCarencia = $request->get('mesescarencia');
            $ayuda->Minimis = ($request->get('minimis') && $request->get('minimis') != "") ? 1 : 0;
            $ayuda->TematicaObligatoria = ($request->get('tematicaobligatoria') && $request->get('tematicaobligatoria') != "") ? 1 : 0;
            $ayuda->EfectoIncentivador = ($request->get('efectoincentivador') && $request->get('efectoincentivador') != "") ? 1 : 0;
            $ayuda->Dnsh = $dnsh;
            $ayuda->MensajeDnsh = ($dnsh == "opcional") ? $request->get('mensajednsh') : null;
            $ayuda->FondosEuropeos = $fondos;
            $ayuda->esDeGenero = ($request->get('esdegenero') && $request->get('esdegenero') != "") ? 1 : 0;
            $ayuda->textoGenero = ($request->get('esdegenero') && $request->get('esdegenero') != "") ? $request->get('textodegenero') : null;
            $ayuda->minEmpleados = ($request->get('minempleados') && $request->get('minempleados') != "") ? $request->get('minempleados') : null;
            $ayuda->maxEmpleados = ($request->get('maxempleados') && $request->get('maxempleados') != "") ? $request->get('maxempleados') : null;
            $ayuda->tiposObligatorios = (empty($request->get('tiposobligatorios'))) ? null : json_encode($request->get('tiposobligatorios'));
            $ayuda->update_extinguida_ayuda = ($request->get('update_extinguida_ayuda') !== null) ? $request->get('update_extinguida_ayuda') : null;
            $ayuda->subfondos = ($request->get('subfondos') === null) ? null :  json_encode($request->get('subfondos'));
            $ayuda->type_of_action_id = ($request->get('type_of_action_id') === null || $request->get('type_of_action_id') === "") ? null : $request->get('type_of_action_id');
            $ayuda->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha encontrado la ayuda que quieres editar');
        }

        $encajes = \App\Models\Encaje::where('Ayuda_id', $id)->get();

        if($encajes){
            foreach($encajes as $encaje){
                try{
                    Artisan::call('elastic:ayudas', [
                        'id' =>  $encaje->id
                    ]);
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return redirect()->back()->withErrors('Error al actualizar los encajes en elastic');
                }
            }
        }
        
        try{
            Artisan::call('calcula:ayudas_parecidas', [
                'id' =>  $id
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('Error al actualizar las ayudas parecidas');
        }
            
        return redirect()->back()->withSuccess("Ayuda actualizada");
    }
}
