<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class DashboardAyudaController extends Controller
{
    //
    public function quitarEncaje(Request $request){

        $id = $request->get('id');

        $idayuda = $request->get('ayudaid');

        try{
            Artisan::call('elastic:deleteayuda', [
                'id' => $id
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return "Error en el borrado del encaje del motor inteligente, intentalo de nuevo";
        }

        $encajes = DB::table('Encajes_zoho')->where('Ayuda_id', $idayuda)->get();

        foreach($encajes as $encaje){

            if($encaje->id == $id){
                try{
                    DB::table('Encajes_zoho')->where('id', $encaje->id)->delete();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return "Error en el borrado del encaje de nuestro sistema, intentalo de nuevo";
                }
            }
        }

        return "Encaje eliminado";
    }

    public function addEncaje(Request $request){

        $palabrases = null;
        if(!empty($request->get('palabrases'))){
            $palabrases = strip_tags($request->get('palabrases'));
        }
        $palabrasen = null;
        if(!empty($request->get('palabrases'))){
            $palabrasen = strip_tags($request->get('palabrasen'));
        }
        $tags = null;
        if(!empty($request->get('tags'))){
            $tags = strip_tags($request->get('tags'));
        }
        $titulo = null;
        if(!empty($request->get('titulo'))){
            $titulo = $request->get('titulo');
        }
        $acronimo = null;
        if(!empty($request->get('acronimo'))){
            $acronimo = $request->get('acronimo');
        }
        $intereses = json_encode($request->get('encajeintereses'));
        $desc = $request->get('descripcion');

        $opcioncnae = null;
        $cnaes = null;

        if($request->get('tipo') == "Interna" || $request->get('tipo') == "Target"){

            $opcioncnae = ($request->get('opcionCNAEEncaje') === null) ? null : $request->get('opcionCNAEEncaje') ;
            if($request->get('opcionCNAEEncaje') == "Todos"){
                $cnaes = null;
            }else{
                $cnaes = ($request->get('cnaesencaje') === null) ? null : json_encode($request->get('cnaesencaje'));
            }
        }

        try{

            $id = DB::table('Encajes_zoho')->insertGetId([
                'Acronimo' => $acronimo,
                'Titulo' => html_entity_decode($titulo),
                'Tipo' => $request->get('tipo'),
                'Descripcion' => html_entity_decode($desc),
                'PalabrasClaveES' => html_entity_decode($palabrases),
                'PalabrasClaveEN' => html_entity_decode($palabrasen),
                'PerfilFinanciacion' => html_entity_decode($intereses),
                'TagsTec' => html_entity_decode($tags),
                'Encaje_cnaes' => $cnaes,
                'Encaje_opcioncnaes' => $opcioncnae,
                'naturalezaPartner' => json_encode($request->get('naturaleza')),
                'Ayuda_id' => $request->get('ayuda_id')
            ]);

            try{
                Artisan::call('elastic:ayudas', [
                    'id' => $id
                ]);
            }catch(Exception $e){
                dd($e->getMessage());
            }

        }catch(Exception $e){
            die($e->getMessage());
        }

        return "Encaje creado";

    }

    public function quitarConvocatoria(Request $request){

        dd($request->all());

    }

    public function editarEncaje(Request $request){

        $id = $request->get('id');
        $palabrases = null;
        if(!empty($request->get('palabrases'))){
            $palabrases = strip_tags($request->get('palabrases'));
        }
        $palabrasen = null;
        if(!empty($request->get('palabrases'))){
            $palabrasen = strip_tags($request->get('palabrasen'));
        }

        $tags = $request->get('tags');

        $titulo = null;
        if(!empty($request->get('titulo'))){
            $titulo = $request->get('titulo');
        }
        $acronimo = null;
        if(!empty($request->get('acronimo'))){
            $acronimo = $request->get('acronimo');
        }
        $intereses = json_encode($request->get('encajeintereses'));
        $desc = $request->get('descripcion');

        $opcioncnae = null;
        $cnaes = null;

        $opcioncnae = ($request->get('opcionCNAEEncaje') === null) ? null : $request->get('opcionCNAEEncaje') ;
        if($request->get('opcionCNAEEncaje') == "Todos"){
            $cnaes = null;
        }else{
            $cnaes = ($request->get('cnaesencaje') === null) ? null : json_encode($request->get('cnaesencaje'));
        }

        $fechamax = ($request->get('encajefechamax') === null) ? null : $request->get('encajefechamax');

        if($fechamax){
            $fechamax = Carbon::createFromFormat('d/m/Y', $fechamax)->format('Y-m-d');
        }

        try{
            $encaje = \App\Models\Encaje::where('id', $id)->first();           
            $encaje->Acronimo = $acronimo;
            $encaje->Titulo = html_entity_decode($titulo);
            $encaje->Tipo = $request->get('tipo');
            $encaje->Descripcion = $desc;
            $encaje->PalabrasClaveES = $palabrases;
            $encaje->PalabrasClaveEN = $palabrasen;
            $encaje->PerfilFinanciacion = $intereses;
            $encaje->Encaje_cnaes = $cnaes;
            $encaje->Encaje_opcioncnaes = $opcioncnae;
            $encaje->Encaje_fechamax = $fechamax;
            $encaje->naturalezaPartner = json_encode($request->get('naturaleza'));
            $encaje->TagsTec = ($tags) ? $tags : null;
            $encaje->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            die("Error al actualizar el encaje");
        }

        /*
            "id" => "231435000089743106"
            "ayuda_id" => "231435000089743102"
        */

        try{
            Artisan::call('elastic:ayudas', [
                'id' => $id
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            die("Error al enviar el encaje a elastic");
        }

        return "Encaje actualizado";
    }

    public function getEncaje(Request $request){

        $id = $request->get('id');
        $encaje = \App\Models\Encaje::where('id', $id)->first();
        $encaje->chatgptkeywords = null;
        if($encaje->keywords !== null){
            $encaje->chatgptkeywords = $encaje->keywords->keywords;
        }

        return json_encode($encaje);
    }

    public function getEmpresasProyecto(Request $request){

        $id = $request->get('id');

        if($request->get('page')){
            $result = getElasticEmpresasProyecto($id, 200, $request->get('page'));
        }else{
            $result = getElasticEmpresasProyecto($id, 200, 1);
        }

        $empresas = array(
            'totalPages' => 0,
            'totalItems' => 0,
            'currentPage' => 1,
            'encajeId' => null,
        );

        if($result){

            $empresas = $result['data'];
            //$response = collect($empresas)->only(['ID']);
            $empresas['totalPages'] = $result['pagination']->numTotalPages;
            $empresas['totalItems'] = $result['pagination']->totalItems;
            $empresas['currentPage'] = $result['pagination']->currentPage;
            $empresas['encajeId'] = $id;

        }


        return Response::json($empresas);
    }

    public function updatePublicadaAyuda(Request $request){

        $publicada = ($request->get('publicada') == "publicada") ? 1: 0;

        $ayuda = \App\Models\Ayudas::find($request->get('id'));

        if($ayuda->Organismo === null || empty($ayuda->Organismo) || !is_int($ayuda->Organismo)){
            if($publicada == 1){
                return response()->json("No se puede publicar una ayuda que no tiene un Organo o Departamento seleccionado", 403);
            }
        }

        try{
            $ayuda->Publicada = $publicada;
            $ayuda->LastEditor = Auth::user()->email;
            $ayuda->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return response()->json("Error al publicar la ayuda", 403);
        }

        if($publicada == 1){
            $encajes = \App\Models\Encaje::where('Ayuda_id',$request->get('id'))->select(['id'])->get();
            foreach($encajes as $encaje){
                try{
                    Artisan::call('elastic:ayudas', [
                        'id' => $encaje->id
                    ]);
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return response()->json("Error al publicar la ayuda", 403);
                }
            }

            if($ayuda->Fin !== null && Carbon::createFromFormat("Y-m-d", $ayuda->Fin) < Carbon::now()){
                return response()->json("Ayuda guardada y publicada correctamente pero no se genera noticia de publicación, la fecha de fin es menor al dia de hoy: ".$ayuda->Fin, 403);
            }

            try{

                if(isset($ayuda->Organismo)){

                    $checknoticia = \App\Models\Noticias::where('id_ayuda', $ayuda->id)->where('id_organo', $ayuda->Organismo)->where('user', 'system_publica')->first();

                    if($checknoticia){

                        $checknoticia->user = Auth::user()->email;
                        $checknoticia->fecha = Carbon::now();
                        $checknoticia->updated_at = Carbon::now();
                        $checknoticia->save();

                    }else{

                        if(isset($ayuda->Organismo)){
                            $dpto = DB::table('departamentos')->where('id', $ayuda->Organismo)->select(['Nombre','id','Acronimo'])->first();
                            if(!$dpto){
                                $dpto = DB::table('organos')->where('id', $ayuda->Organismo)->select(['Nombre','id','Acronimo'])->first();
                            }
                        }

                        $noticia = new \App\Models\Noticias();
                        $noticia->id_ayuda = $ayuda->id;
                        $noticia->id_organo = (!isset($ayuda->Organismo)) ? null : $ayuda->Organismo;
                        $noticia->texto = (!isset($ayuda->Organismo)) ?
                            'Publicada nueva línea de ayuda pública: '.$ayuda->Titulo :
                            'Publicada nueva línea de ayuda pública: '.$ayuda->Titulo. ' para el organismo:'.$dpto->Acronimo;
                        $noticia->fecha = Carbon::now();
                        $noticia->user = 'system_publica';
                        $noticia->created_at = Carbon::now();
                        $noticia->save();

                    }
                }

            }catch(Exception $e){
                Log::error($e->getMessage());
                return response()->json('Error al crear la noticia de publicación de esta ayuda', 403);
            }

            try{
                Artisan::call('check:seo_pages', [
                    'id' => $ayuda->id,
                    'remove' => 1,
                    'publicar' => 1
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
            }

        }else{

            try{
                Artisan::call('check:seo_pages', [
                    'id' => $ayuda->id,
                    'remove' => 1,
                    'publicar' => 0
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
            }
        }

        return response()->json('Ayuda en estado de publicación: '.$request->get('publicada'), 200);
    }

    public function duplicarAyuda(Request $request){

        $ayudabase = \App\Models\Ayudas::where('id', $request->get('ayuda'))->first();

        if($ayudabase){

            if($ayudabase->Acronimo !== null){
                $acronimo = cleanUriBeforeSave($ayudabase->Acronimo);
            }else{
                $acronimo = cleanUriBeforeSave($ayudabase->Titulo);
            }

            $idAcronimo = rtrim(mb_strtoupper(mb_substr(str_replace(" ","",$acronimo),0,6)));
            $idAcronimo .= Carbon::now()->format('Y')."#";

            $checkIdConvStr = \App\Models\Ayudas::where('IdConvocatoriaStr', 'LIKE', $idAcronimo."%")->count();

            if($checkIdConvStr > 0){
                $idAcronimo .= $checkIdConvStr+1;
            }else{
                $idAcronimo .= "1";
            }

            try{
                $id = \App\Models\Ayudas::insertGetId([
                    'Acronimo' => $ayudabase->Acronimo,
                    'Titulo' => $ayudabase->Titulo,
                    'Presentacion' => $ayudabase->Presentacion,
                    'Link' => $ayudabase->Link,
                    'Organismo' => $ayudabase->Organismo,
                    'PerfilFinanciacion' => $ayudabase->PerfilFinanciacion,
                    'FechaMax' => $ayudabase->FechaMax,
                    'Meses' => $ayudabase->Meses,
                    'FechaMaxConstitucion' => $ayudabase->FechaMaxConstitucion,
                    'Categoria' => $ayudabase->Categoria,
                    'Presupuesto' => $ayudabase->Presupuesto,
                    'Ambito' => $ayudabase->Ambito,
                    'OpcionCNAE' => $ayudabase->OpcionCNAE,
                    'CNAES' => $ayudabase->CNAES,
                    'DescripcionCorta' => $ayudabase->DescripcionCorta,
                    'DescripcionLarga' => $ayudabase->DescripcionLarga,
                    'RequisitosTecnicos' => $ayudabase->RequisitosTecnicos,
                    'Convocatoria' => $ayudabase->Convocatoria,
                    'Inicio' => $ayudabase->Inicio,
                    'Fin' => $ayudabase->Fin,
                    'fechaEmails' => $ayudabase->fechaEmails,
                    'Estado' => $ayudabase->Estado,
                    'Competitiva' => $ayudabase->Competitiva,
                    'Uri' => (string) rand(0,9999),
                    'Ccaas' => $ayudabase->Ccaas,
                    'Featured' => $ayudabase->Featured,
                    'TipoFinanciacion' => $ayudabase->TipoFinanciacion,
                    'CapitulosFinanciacion' => $ayudabase->CapitulosFinanciacion,
                    'CentroTecnologico' => $ayudabase->CentroTecnologico,
                    'CondicionesFinanciacion' => $ayudabase->CondicionesFinanciacion,
                    'PresupuestoMin' => $ayudabase->PresupuestoMin,
                    'PresupuestoMax' => $ayudabase->PresupuestoMax,
                    'DuracionMin' => $ayudabase->DuracionMin,
                    'DuracionMax' => $ayudabase->DuracionMax,
                    'Garantias' => $ayudabase->Garantias,
                    'IDInnovating' => $ayudabase->IDInnovating,
                    'Trl' => $ayudabase->Trl,
                    'objetivoFinanciacion' => $ayudabase->objetivoFinanciacion,
                    'PorcentajeFondoPerdido' => $ayudabase->PorcentajeFondoPerdido,
                    'PorcentajeCreditoMax' => $ayudabase->PorcentajeCreditoMax,
                    'FondoPerdidoMinimo' => $ayudabase->FondoPerdidoMinimo,
                    'CreditoMinimo' => $ayudabase->CreditoMinimo,
                    'DeduccionMax' => $ayudabase->DeduccionMax,
                    'NivelCompetitivo' => $ayudabase->NivelCompetitivo,
                    'TiempoMedioResolucion' => $ayudabase->TiempoMedioResolucion,
                    'SelloPyme' => $ayudabase->SelloPyme,
                    'EmpresaCrisis' => $ayudabase->EmpresaCrisis,
                    'InformeMotivado' => $ayudabase->InformeMotivado,
                    'TextoCondiciones' => $ayudabase->TextoCondiciones,
                    'TextoConsorcio' => $ayudabase->TextoConsorcio,
                    'FondoTramo' => $ayudabase->FondoTramo,
                    'created_at' => Carbon::now(),
                    'updated_at' => null,
                    'LastEditor' => Auth::user()->email,
                    'Publicada' => 0,
                    'AplicacionIntereses' => $ayudabase->AplicacionIntereses,
                    'PorcentajeIntereses' => $ayudabase->PorcentajeIntereses,
                    'MesesCarencia' => $ayudabase->MesesCarencia,
                    'AnosAmortizacion' => $ayudabase->AnosAmortizacion,
                    'IdConvocatoriaStr' => $idAcronimo,
                    'id_ayuda' => $ayudabase->id_ayuda
                ]);

            }catch(Exception $e){
                dd($e->getMessage());
            }

            if(!$id){
                return redirect()->back();
            }

            $encajes = DB::table('Encajes_zoho')->where('Ayuda_id', $ayudabase->id)->get();

            foreach($encajes as $encaje){

                try{

                    DB::table('Encajes_zoho')->insert([
                        'Acronimo' => $encaje->Acronimo,
                        'Titulo' => html_entity_decode($encaje->Titulo),
                        'Tipo' => $encaje->Tipo,
                        'Descripcion' => html_entity_decode($encaje->Descripcion),
                        'PalabrasClaveES' => $encaje->PalabrasClaveES,
                        'PalabrasClaveEN' => $encaje->PalabrasClaveEN,
                        'PerfilFinanciacion' => $encaje->PerfilFinanciacion,
                        'Ayuda_id' => $id,
                        'naturalezaPartner' => $encaje->naturalezaPartner,
                        'TagsTec' => $encaje->TagsTec,
                        'created_at' => Carbon::now()
                    ]);

                }catch(Exception $e){
                    die($e->getMessage());
                }
            }

            return redirect()->route('editconvocatoria', $id);//$id);
        }

        return redirect()->back();
    }

    public function updatePreguntas(Request $request){

        try{
            $ayuda = \App\Models\Ayudas::where('id', $request->get('ayuda'))->update([
                'pregunta1' => $request->get('pregunta1'),
                'instrucciones1' => $request->get('instrucciones1'),
                'pregunta2' => $request->get('pregunta2'),
                'instrucciones2' => $request->get('instrucciones2'),
                'LastEditor' => Auth::user()->email,
                'updated_at' => Carbon::now(),
            ]);

        }catch(Exception $e){
            Log::error($e->getMessage());
            return Response::json(['error' => 'Error al actualizar las preguntas'], 424);
        }

        return Response::json(['msg' => 'Preguntas actualizadas correctamente'], 200);
    }

    public function deletePregunta(Request $request){

        try{
            if($request->get('tipo') == "pregunta1"){
                $ayuda = \App\Models\Ayudas::where('id', $request->get('id'))->update([
                    'pregunta1' => null,
                    'instrucciones1' => null,
                    'LastEditor' => Auth::user()->email,
                    'updated_at' => Carbon::now(),
                ]);
            }

            if($request->get('tipo') == "pregunta2"){
                $ayuda = \App\Models\Ayudas::where('id', $request->get('id'))->update([
                    'pregunta2' => null,
                    'instrucciones2' => null,
                    'LastEditor' => Auth::user()->email,
                    'updated_at' => Carbon::now(),
                ]);
            }

        }catch(Exception $e){
            Log::error($e->getMessage());
            return Response::json(['error' => 'Error al actualizar las preguntas'], 424);
        }

        return Response::json(['msg' => 'Preguntas actualizadas correctamente'], 200);
    }

    public function updateAyudaAnalisis(Request $request){

        try{
            $ayuda = \App\Models\Ayudas::where('id', $request->get('id'))->update([
                'Analisis' => $request->get('value'),
                'LastEditor' => Auth::user()->email,
                'updated_at' => Carbon::now(),
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return Response::json(['error' => 'Error al actualizar el estado de analisis'], 424);
        }

        return Response::json(['msg' => 'Analisis actualizado correctamente'], 200);
    }


    public function updateGraficosAyuda(Request $request){

        if($request->get('id') === null){
            return abort(404);
        }

        $checkayuda = \App\Models\Ayudas::where('Estado', 'Cerrada')->where('id', $request->get('id'))->first();

        if(!$checkayuda){
            return abort(404);
        }

        try{
            \Artisan::call('create:ayudas_graficos',[
                'id' => $request->get('id')
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido actualizar/Crear los graficos para esta ayuda');
        }

        return redirect()->back()->withSuccess('Actualizados/Creados los graficos para esta ayuda correctamente');
    }

    public function maasiveConvocatoriaUpdate(Request $request){
        
        if($request->get('idayudas') === null){
            return abort(404);
        }

        foreach(explode(",",$request->get('idayudas')) as $idayuda){

            $ayuda = \App\Models\Ayudas::find($idayuda);
            if($ayuda){
                try{
                    $ayuda->update_extinguida_ayuda = ($request->get('estado_extinguida') === null) ? '2' : $request->get('estado_extinguida');
                    $ayuda->LastEditor = Auth::user()->email;
                    $ayuda->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return redirect()->back()->withErrors('No se han podido actualizar estas ayudas');
                }

            }

        }

        return redirect()->back()->withSuccess('Actualizadas las ayudas correctamente');

    }

    public function createAyuda(Request $request){

        $organismo = ($request->get('organo') !== null) ? $request->get('organo') : $request->get('departamento');

        if($organismo === null){
            return redirect()->back()->withErrors('No se ha seleccionado un organismo');
        }
        $name = $request->get('titulo');

        $arraycoincidences = array(",", ".", "(",")", "/", "|", "\\");
        $uriArray = explode(' ', mb_strtolower($name));
        $uri = '';

        if(count($uriArray) > 15){
            for($i = 0; $i < 14; $i++){
                $uri .= $uriArray[$i]."-";
            }
            $uri = substr($uri, 0, -1);
        }else{
            $uri = mb_strtolower(preg_replace('/\s+/', '-', trim($name)));
        }

        $uri = str_replace($arraycoincidences, '', $uri);
        $checkUri = \App\Models\Ayudas::where('Uri', $uri)->first();

        if($checkUri){
            $uri .= (string) rand(0,9999);
        }

        $uri = quitar_tildes($uri);

        try{
            $convocatoria = new \App\Models\Ayudas();                            
            $convocatoria->Organismo = $organismo;
            $convocatoria->Titulo = $name;
            $convocatoria->Uri = iconv("UTF-8", "ASCII//TRANSLIT", $uri);
            $convocatoria->TipoFinanciacion = json_encode(array());
            $convocatoria->Publicada = 0;
            $convocatoria->IdConvocatoriaStr = '';
            $convocatoria->naturalezaConvocatoria = '';
            $convocatoria->esMetricable = 0;
            $convocatoria->FondosAgotados = 0;
            $convocatoria->LastEditor = Auth::user()->email;
            $convocatoria->created_at = Carbon::now();
            $convocatoria->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('Error en la creación de la ayuda');
        }

        return redirect()->route('admineditarconvocatoria', $convocatoria->id);//$id);

    }
}

