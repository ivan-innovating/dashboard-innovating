<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Imports\ExcelImportProyects;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use stdClass;

class DashboardProyectosController extends Controller
{
    //

    public function viewPeoyectoImportado($id){

        $proyecto = \App\Models\Proyectos::find($id);

        if(!$proyecto){
            return abort(404);
        }

        $participantes = \App\Models\Participantes::where('id_proyecto', $proyecto->id)->get();
        $ayudasselect = \App\Models\Ayudas::whereNotNull('IdConvocatoriaStr')->where('IdConvocatoriaStr', '!=', '')->select('IdConvocatoriaStr', 'Acronimo', 'Titulo')->get();

        return view('dashboard/viewproyectoimportado', [
            'proyecto' => $proyecto,
            'participantes' => $participantes,
            'ayudasselect' => $ayudasselect
        ]);
    }

    public function viewProyectoCreado($id){

        $proyecto = \App\Models\Proyectos::find($id);

        if(!$proyecto){
            return abort(404);
        }

        $participantes = \App\Models\Participantes::where('id_proyecto', $proyecto->id)->get();

        return view('dashboard/viewproyectocreado', [
            'proyecto' => $proyecto,
            'participantes' => $participantes,
        ]);
    }

    public function viewBusquedaCreada($id){

        $busqueda = \App\Models\Encaje::where('id', $id)->whereNotNull('Proyecto_id')->first();

        if(!$busqueda){
            return abort(404);
        }

        return view('dashboard/viewbusquedacreada', [
            'busqueda' => $busqueda,
        ]);

    }


    public function deleteProyectos(Request $request){

        try{
            \App\Models\Proyectos::where('fromFile', $request->get('archivo'))->delete();
            \App\Models\Participantes::where('from_file', $request->get('archivo'))->delete();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se han podido borrar los proyectos del archivo de importación: '.$request->get('archivo'))->with('option','ok');
        }

        return redirect()->back()->withSuccess('Borrados todos los proyectos del archivo de importación: '.$request->get('archivo'))->with('option','ok');
    }

    public function deleteArchivos(Request $request){

        if($request->get('archivo') !== null && !empty($request->get('archivo'))){
            $files = Storage::disk('s3_files')->files('proyectos/import');

            foreach($files as $file){
                $filedata =  Storage::disk('s3_files')->lastModified($file);
                $date = Carbon::createFromTimestamp($filedata)->toDateTimeString();
                if($date > Carbon::now()->subDays(7)){
                    if(in_array($file, $request->get('archivo'))){
                        Storage::disk('s3_files')->delete($file);
                    }
                }
            }
        }

        return redirect()->back()->withSuccess('Se han borrado y quitado de la importación los archivos seleccionados.')->with('option','ok');

    }


    public function subirArchivoProyectos(Request $request){

        $file = $request->file('excel');
        //$file->storeAs('proyectos', $file->getClientOriginalName(), ['disk' => 'excelproyectos']);
        Storage::disk('s3_files')->put("proyectos/import/".$file->getClientOriginalName(), $file, ['visibility' => "public-read"]);

        return redirect()->back()->withSuccess('El archivo : '.$file->getClientOriginalName().' es correcto')->with('option', 'ok');
    }

    public function subirArchivoProyectosActualizados(Request $request){

        $file = $request->file('excel');
        $file->storeAs('excelcifs', $file->getClientOriginalName(), ['disk' => 'excelcompletados']);

        $uploadExcel = new \App\Models\UploadExcels();
        $uploadExcel->filename =  $file->getClientOriginalName();
        $uploadExcel->user_id = Auth::user()->id;
        $uploadExcel->procesado = 0;
        $uploadExcel->upload_at = Carbon::now();
        $uploadExcel->save();

        return redirect()->back()->withSuccess('El archivo : '.$file->getClientOriginalName().' es correcto')->with('option', 'ok');
    }

    public function importarProyectos(Request $request){

        $file = $request->file('excel');
        $fallos = array();

        try{
            $import = new ExcelImportProyects;
            Excel::import($import, $file);
        }catch(\Maatwebsite\Excel\Validators\ValidationException $e) {
            dd($e->failures());
        }
        $total = 0;
        foreach ($import->failures() as $key => $failure) {
            if($failure->row() > 200){
                $fallos[98] = 'El archivo tiene más de 200 líneas';
            }
            if($key < 10 && $failure->row() != 2 && $failure->row() <= 200){
                $error = $failure->errors();
                $fallos[$key] = "línea: ".$failure->row().": ".$error[0];
            }elseif($key >= 10){
                $total++;
                $fallos[99] = "...y otros ".$total." errores más";
            }
        }

        if($request->get('crearnoticia') !== null 
        && $request->get('texto') !== null && $request->get('fecha') !== null && $request->get('organismo') !== null){

            try{
                $noticia = new \App\Models\Noticias();
                $noticia->id_ayuda = null;
                $noticia->id_organo = $request->get('organismo');
                $noticia->link = ($request->get('link') === null) ? null : $request->get('link');
                $noticia->texto = $request->get('texto');
                $noticia->fecha = Carbon::createFromFormat('d/m/Y', $request->get('fecha'))->format('Y-m-d');
                $noticia->user = Auth::user()->email;
                $noticia->created_at = Carbon::now();
                $noticia->save();
            }catch(Exception $e){
                Log::error($e->getMessage());                
            }

        }

        ksort($fallos);
        return redirect()->back()->withSuccess('Primeras líneas importadas: '.$import->getRowsCount().' del archivo:'.$file->getClientOriginalName())
        ->with('fallos', $fallos)->with('option', 'ok');
    }

    public function matchProyectosConcesiones(){

        $proyectos = \App\Models\Proyectos::where('importado', 1)->where('esEuropeo', 0)->where('matchConcesion', 0)->whereNull('idConcesion')
        ->where('Estado', 'Cerrado')->where('empresaPrincipal', '!=', 'XXXXXXXXX')->get();
        $matchs = 0;
        $totalmatchs = 5;
        $totalnomatch = 5;
        $nomatch = array();
        $match = array();

        if($proyectos->count() > 0){

            foreach($proyectos as $proyecto){

                foreach($proyecto->participantes as $participante){

                    $concesion = null;

                    if($proyecto->organo !== null){
                        $total = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_organo', $proyecto->organo->id)->where('fecha', $proyecto->Fecha)->count();
                        if($total == 1){
                            $concesion = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_organo', $proyecto->organo->id)->where('fecha', $proyecto->Fecha)->first();
                        }elseif($total > 1){
                            $ayudaequivalente = null;
                            if($participante->ayuda_eq_socio !== null){
                                $ayudaequivalente = $participante->ayuda_eq_socio;
                            }elseif($proyecto->AyudaEqSocio !== null){
                                $ayudaequivalente = $proyecto->AyudaEqSocio;
                            }
                            if($ayudaequivalente){
                                $concesion = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_organo', $proyecto->organo->id)->where('fecha', $proyecto->Fecha)
                                ->where('equivalent_aid', $ayudaequivalente)->first();
                                if(!$concesion){
                                    $qty1 = $ayudaequivalente*1.05;
                                    $qty2 = $proyecto->AyudaEqSocio/1.05;
                                    $concesion = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_organo', $proyecto->organo->id)->where('fecha', $proyecto->Fecha)
                                    ->where('equivalent_aid', '>=', $qty1)->where('equivalent_aid', '<=', $qty2)->first();
                                }
                            }
                        }
                    }elseif($proyecto->departamento !== null){
                        $total = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_departamento', $proyecto->departamento->id)->where('fecha', $proyecto->Fecha)->count();
                        if($total == 1){
                            $concesion = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_departamento', $proyecto->departamento->id)->where('fecha', $proyecto->Fecha)->first();
                        }elseif($total > 1){
                            $ayudaequivalente = null;
                            if($participante->ayuda_eq_socio !== null){
                                $ayudaequivalente = $participante->ayuda_eq_socio;
                            }elseif($proyecto->AyudaEqSocio !== null){
                                $ayudaequivalente = $proyecto->AyudaEqSocio;
                            }
                            if($ayudaequivalente){
                                $concesion = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_departamento', $proyecto->departamento->id)->where('fecha', $proyecto->Fecha)
                                ->where('equivalent_aid', $ayudaequivalente)->first();
                                if(!$concesion){
                                    $qty1 = $ayudaequivalente*1.05;
                                    $qty2 = $proyecto->AyudaEqSocio/1.05;
                                    $concesion = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_departamento', $proyecto->departamento->id)->where('fecha', $proyecto->Fecha)
                                    ->where('equivalent_aid', '>=', $qty1)->where('equivalent_aid', '<=', $qty2)->first();
                                }
                            }
                        }
                    }

                    ##SI no hay concesion por fecha, cif y id organismo, relajamos filtro solo ha si hay 1 sola concesion para ese cif, id organismo con ayuda eq igual que la del proeycto
                    if($concesion === null){
                        if($proyecto->departamento !== null){
                            $total = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_departamento', $proyecto->departamento->id)->count();
                        }elseif($proyecto->organo !== null){
                            $total = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_organo', $proyecto->organo->id)->count();
                        }
                        if($total == 1){
                            $ayudaequivalente = null;
                            if($participante->ayuda_eq_socio !== null){
                                $ayudaequivalente = $participante->ayuda_eq_socio;
                            }elseif($proyecto->AyudaEqSocio !== null){
                                $ayudaequivalente = $proyecto->AyudaEqSocio;
                            }
                            if($ayudaequivalente){
                                if($proyecto->departamento !== null){
                                    $concesion = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_departamento', $proyecto->departamento->id)->where('equivalent_aid', $ayudaequivalente)->first();
                                }elseif($proyecto->organo !== null){
                                    $concesion = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_organo', $proyecto->organo->id)->where('equivalent_aid', $ayudaequivalente)->first();
                                }
                            }
                            #Si no encaja por ayuda equivalente, revisamos por fecha de proyecto - 7 dias >= fecha concesion 
                            if($concesion === null){
                                $fechaInit = Carbon::createFromFormat("Y-m-d" ,$proyecto->Fecha)->subDays(7)->format('Y-m-d');
                                $fechaFin = Carbon::createFromFormat("Y-m-d" ,$proyecto->Fecha)->format('Y-m-d');
                                if($proyecto->departamento !== null){
                                    $concesion = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_departamento', $proyecto->departamento->id)->where('fecha', '>=', $fechaInit)->where('fecha', '<=', $fechaFin)->first();
                                }elseif($proyecto->organo !== null){
                                    $concesion = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_organo', $proyecto->organo->id)->where('fecha', '>=', $fechaInit)->where('fecha', '<=', $fechaFin)->first();
                                }
                            }
                        }elseif($total > 1){
                            $ayudaequivalente = null;
                            if($participante->ayuda_eq_socio !== null){
                                $ayudaequivalente = $participante->ayuda_eq_socio;
                            }elseif($proyecto->AyudaEqSocio !== null){
                                $ayudaequivalente = $proyecto->AyudaEqSocio;
                            }
                            if($ayudaequivalente){
                                if($proyecto->departamento !== null){
                                    $total = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_departamento', $proyecto->departamento->id)->where('equivalent_aid', $ayudaequivalente)->count();
                                }elseif($proyecto->organo !== null){
                                    $total = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_organo', $proyecto->organo->id)->where('equivalent_aid', $ayudaequivalente)->count();
                                }
                            }
                            if($total < 1 || $total > 1){
                                $fechaInit = Carbon::createFromFormat("Y-m-d" ,$proyecto->Fecha)->subDays(7)->format('Y-m-d');
                                $fechaFin = Carbon::createFromFormat("Y-m-d" ,$proyecto->Fecha)->format('Y-m-d');
                                if($proyecto->departamento !== null){
                                    $total = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_departamento', $proyecto->departamento->id)->where('fecha', '>=', $fechaInit)->where('fecha', '<=', $fechaFin)->count();
                                }elseif($proyecto->organo !== null){
                                    $total = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_organo', $proyecto->organo->id)->where('fecha', '>=', $fechaInit)->where('fecha', '<=', $fechaFin)->count();
                                }
                            }
                            if($total == 1){
                                if($proyecto->departamento !== null){
                                    $concesion = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_departamento', $proyecto->departamento->id)->where('fecha', '>=', $fechaInit)->where('fecha', '<=', $fechaFin)->first();
                                }elseif($proyecto->organo !== null){
                                    $concesion = \App\Models\Concessions::where('custom_field_cif', $participante->cif_participante)->where('id_organo', $proyecto->organo->id)->where('fecha', '>=', $fechaInit)->where('fecha', '<=', $fechaFin)->first();
                                }
                            }
                        }
                    }

                    #dump($participante);
                    #dd($proyecto);
                    
                    if($concesion){

                        $matchs++;
                        try{
                            $participanteupdate = \App\Models\Participantes::find($participante->id);
                            $participanteupdate->id_concesion = $concesion->id;
                            $participanteupdate->save();
                            $proyecto->matchConcesion = 1;
                            $proyecto->idConcesion = $concesion->id;
                            $proyecto->save();
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                            continue;
                        }
                        if(count($match) > 5){
                            $totalmatchs++;
                            $match[10] = " y otras ".$totalmatchs++." lineas más ...";
                        }else{
                            $match[] = $proyecto->empresaPrincipal." - ".$proyecto->organismo." - ".$proyecto->Fecha. " con: ".$concesion->id;
                        }
                    }else{                       
                        if(count($nomatch) > 5){
                            $totalnomatch++;
                            $nomatch[10] = " y otras ".$totalnomatch++." lineas más ...";
                        }else{
                            $nomatch[] = $proyecto->empresaPrincipal." - ".$proyecto->organismo." - ".$proyecto->Fecha;
                        }                       
                    }

                }
            }

        }

        return redirect()->back()->withSuccess('Se ha hecho match de los proyectos importados con un total de concesiones de: '.$matchs)
        ->with('warning', $nomatch)->with('matchs', $match)->with('option', 'ok');

    }

    ###PENDIENTE REVISAR
    public function crearEmpresasProyectos(){

        $proyectos = \App\Models\Proyectos::whereNotNull('fromFile')->where('importado', 1)->where('esEuropeo', 0)->where('movidoEntidad', 0)
        ->where('empresaPrincipal', '!=', 'XXXXXXXXX')->where(function ($query) {
                $query->where('Estado', '=', 'Cerrado')
                  ->orWhere('Estado', '=', 'Desestimado');
                }
            )->orderBy('id', 'DESC')->skip(0)->take(1000)->get();

        $totalempresas = 0;

        if($proyectos->count() > 0){

            foreach($proyectos as $proyecto){

                if($proyecto->participantes !== null && $proyecto->participantes->isNotEmpty()){
                    foreach($proyecto->participantes as $participante){
                        
                        $entidad = \App\Models\Entidad::where('CIF', $participante->cif_participante)->first();

                        if($entidad){
                            
                            try{
                                $proyecto->movidoEntidad = 2;
                                $proyecto->save();
                            }catch(Exception $e){
                                Log::error($e->getMessage());
                                continue;
                            }
                            $textos = \App\Models\TextosElastic::where('CIF', $participante->cif_participante)->first();
                            if($textos && $proyecto){
                                $check = mb_substr($proyecto->Titulo,0,50);
                                if(stripos($textos->Textos_Proyectos, $check) === false){
                                    try{
                                        $textos->Textos_Proyectos .= $proyecto->Titulo;
                                        $textos->save();
                                    }catch(Exception $e){
                                        Log::error($e->getMessage());
                                    }
                                }
                            }elseif($proyecto && !$textos){
                                $textos = new \App\Models\TextosElastic;
                                try{
                                    $textos->CIF = $participante->cif_participante;
                                    $textos->Textos_Proyectos = $proyecto->Titulo;
                                    $textos->Last_Update = Carbon::now();
                                    $textos->save();
                                }catch(Exception $e){
                                    Log::error($e->getMessage());
                                }
                            }   
                            continue;
                        }

                        $cifnoZoho = \App\Models\CifsNoZoho::where('CIF', $participante->cif_participante)->first();
                        ##Actualizamos campos de movido a entidad de proyecto y cifsnozoho si existe
                        try{
                            if($cifnoZoho){
                                $cifnoZoho->movidoEntidad = 1;
                                $cifnoZoho->save();
                            }
                            ##campo movioEntidad = 1//se crea nueva la empresa en innovating
                            $proyecto->movidoEntidad = 1;
                            $proyecto->save();
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                            continue;
                        }

                        ##Creamos la empresa en innovating
                        $uri = cleanUriBeforeSave(str_replace(" ","-",mb_strtolower(quitar_tildes($participante->nombre_participante))));
                        $uri = preg_replace('/([^a-zA-Z0-9\-]+)/i', '', $uri);
                                    
                        try{
                            $totalempresas++;

                            $entidad = new \App\Models\Entidad();
                            $entidad->CIF = $participante->cif_participante;
                            $entidad->Nombre = $participante->nombre_participante;
                            $entidad->Web = '';
                            $entidad->Ccaa = null;
                            $entidad->Cnaes = '';
                            $entidad->Sedes = null;
                            $entidad->uri = $uri;
                            $entidad->Web = '';
                            $entidad->naturalezaEmpresa = json_encode(["6668837"]);
                            $entidad->NumeroLineasTec = 2;
                            $entidad->Intereses = json_encode(["I+D","Innovación","Digitalización","Cooperación","Subcontratación"], JSON_UNESCAPED_UNICODE);
                            $entidad->MinimoSubcontratar = 0;
                            $entidad->MinimoCooperar = 0;
                            $entidad->EntityUpdate = Carbon::now();
                            $entidad->save();

                            $textos = \App\Models\TextosElastic::where('CIF', $participante->cif_participante)->first();
                            if($textos && $proyecto){
                                $check = mb_substr($proyecto->Titulo,0,50);
                                if(stripos($textos->Textos_Proyectos, $check) === false){
                                    try{
                                        $textos->Textos_Proyectos .= $proyecto->Titulo;
                                        $textos->Last_Update = Carbon::now();
                                        $textos->save();
                                    }catch(Exception $e){
                                        Log::error($e->getMessage());
                                    }
                                }
                            }elseif($proyecto && !$textos){
                                $textos = new \App\Models\TextosElastic;
                                try{
                                    $textos->CIF = $participante->cif_participante;
                                    $textos->Textos_Proyectos = $proyecto->Titulo;
                                    $textos->Last_Update = Carbon::now();
                                    $textos->save();
                                }catch(Exception $e){
                                    Log::error($e->getMessage());
                                }
                            }
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                            continue;
                        }

                    }
                }else{
                    $entidad = \App\Models\Entidad::where('CIF', $proyecto->empresaPrincipal)->first();

                    if($entidad){
                        
                        try{
                            $proyecto->movidoEntidad = 2;
                            $proyecto->save();
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                            continue;
                        }
                        $textos = \App\Models\TextosElastic::where('CIF', $proyecto->empresaPrincipal)->first();
                        if($textos && $proyecto){
                            $check = mb_substr($proyecto->Titulo,0,50);
                            if(stripos($textos->Textos_Proyectos, $check) === false){
                                try{
                                    $textos->Textos_Proyectos .= $proyecto->Titulo;
                                    $textos->save();
                                }catch(Exception $e){
                                    Log::error($e->getMessage());
                                }
                            }
                        }elseif($proyecto && !$textos){
                            $textos = new \App\Models\TextosElastic;
                            try{
                                $textos->CIF = $proyecto->empresaPrincipal;
                                $textos->Textos_Proyectos = $proyecto->Titulo;
                                $textos->Last_Update = Carbon::now();
                                $textos->save();
                            }catch(Exception $e){
                                Log::error($e->getMessage());
                            }
                        }   
                        continue;
                    }

                    $cifnoZoho = \App\Models\CifsNoZoho::where('CIF', $proyecto->empresaPrincipal)->first();
                    ##Actualizamos campos de movido a entidad de proyecto y cifsnozoho si existe
                    try{
                        if($cifnoZoho){
                            $cifnoZoho->movidoEntidad = 1;
                            $cifnoZoho->save();
                        }
                        ##campo movioEntidad = 1//se crea nueva la empresa en innovating
                        $proyecto->movidoEntidad = 1;
                        $proyecto->save();
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        continue;
                    }

                    ##Creamos la empresa en innovating
                    $uri = cleanUriBeforeSave(str_replace(" ","-",mb_strtolower(quitar_tildes($proyecto->nombreEmpresa))));
                    $uri = preg_replace('/([^a-zA-Z0-9\-]+)/i', '', $uri);
                                
                    try{
                        $totalempresas++;

                        $entidad = new \App\Models\Entidad();
                        $entidad->CIF = $proyecto->empresaPrincipal;
                        $entidad->Nombre = $proyecto->nombreEmpresa;
                        $entidad->Web = '';
                        $entidad->Ccaa = null;
                        $entidad->Cnaes = '';
                        $entidad->Sedes = null;
                        $entidad->uri = $uri;
                        $entidad->Web = '';
                        $entidad->naturalezaEmpresa = json_encode(["6668837"]);
                        $entidad->NumeroLineasTec = 2;
                        $entidad->Intereses = json_encode(["I+D","Innovación","Digitalización","Cooperación","Subcontratación"], JSON_UNESCAPED_UNICODE);
                        $entidad->MinimoSubcontratar = 0;
                        $entidad->MinimoCooperar = 0;
                        $entidad->EntityUpdate = Carbon::now();
                        $entidad->save();

                        $textos = \App\Models\TextosElastic::where('CIF', $proyecto->empresaPrincipal)->first();
                        if($textos && $proyecto){
                            $check = mb_substr($proyecto->Titulo,0,50);
                            if(stripos($textos->Textos_Proyectos, $check) === false){
                                try{
                                    $textos->Textos_Proyectos .= $proyecto->Titulo;
                                    $textos->Last_Update = Carbon::now();
                                    $textos->save();
                                }catch(Exception $e){
                                    Log::error($e->getMessage());
                                }
                            }
                        }elseif($proyecto && !$textos){
                            $textos = new \App\Models\TextosElastic;
                            try{
                                $textos->CIF = $proyecto->empresaPrincipal;
                                $textos->Textos_Proyectos = $proyecto->Titulo;
                                $textos->Last_Update = Carbon::now();
                                $textos->save();
                            }catch(Exception $e){
                                Log::error($e->getMessage());
                            }
                        }
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        continue;
                    }
                }
            }
        }

        return redirect()->back()->withSuccess('Empresas creadas desde proyectos importados correctamente: '.$totalempresas)->with('option', '3');

    }

    public function recalculanivelcooperacion(){

        $entidades = \App\Models\Entidad::where('created_at', '>', Carbon::now()->format('Y-m-d')." 00:00:00")->whereColumn('created_at', '=', 'updated_at')->take(20)->get();
        $total = \App\Models\Entidad::where('created_at', '>', Carbon::now()->format('Y-m-d')." 00:00:00")->whereColumn('created_at', '=', 'updated_at')->count();

        if($entidades->count() > 0){

            foreach($entidades as $entidad){

                try{
                    ##recalculamos el nivel de cooperacion de la empresa creada
                    Artisan::call('calcula:nivel_cooperacion', [
                        'cif' => $entidad->CIF
                    ]);
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    continue;
                }

                $entidad->updated_at = Carbon::now();
                $entidad->save();

                if($entidad->einforma === null){

                    #Obtenemos el perfil financiero de la empresa de axesor
                    try{
                        Artisan::call('get:axesor', [
                            'cif' => $entidad->CIF
                        ]);
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        continue;
                    }
                }
            }

        }else{
            $entidades = \App\Models\Entidad::where('EntityUpdate', '>=', Carbon::now()->format('Y-m-d')." 00:00:00")->where('checkeomasivocooperacion', 0)->take(20)->get();
            $total = \App\Models\Entidad::where('EntityUpdate', '>=', Carbon::now()->format('Y-m-d')." 00:00:00")->where('checkeomasivocooperacion', 0)->count();
            if($entidades->count() > 0){

                foreach($entidades as $entidad){
    
                    try{
                        ##recalculamos el nivel de cooperacion de la empresa creada
                        Artisan::call('calcula:nivel_cooperacion', [
                            'cif' => $entidad->CIF
                        ]);
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        continue;
                    }
    
                    $entidad->updated_at = Carbon::now();
                    $entidad->EntityUpdate = Carbon::now()->addDays(1);
                    $entidad->save();
                }
            }

        }

        return redirect()->back()->withSuccess('Se ha actualizado el nivel de cooperacion de un total de: '.$entidades->count().' empresas quedan por calcular: '.$total-$entidades->count())->with('option', 'ok');

    }

    public function matchProyectosEmpresas(){
        
        $proyectosNoCif = \App\Models\Proyectos::where('esEuropeo', 0)->where('importado', 1)->where('movidoEntidad', 0)->where('empresaPrincipal', 'XXXXXXXXX')->orderByDesc('Fecha')->get();

        $find = 
        array('/ SLNE$/', '/ SCoop$/', '/ SLL$/', '/ SLU$/', '/ SAU$/', '/ SPA$/', '/ SLA$/', '/ SL$/', '/ SA$/', '/ S L$/', '/ S A$/', '/ S L U$/', '/ S A U$/', '/ S.L.L$/', '/ S P A$/', '/ S L L$/', 
        '/ S.L.U.$/', '/ S.L.$/', '/ S.A.$/', '/ S. COOP.$/', '/ SOCIEDAD LIMITADA$/', '/ SOCIEDAD ANONIMA$/', '/, SOCIEDAD ANONIMA$/', '/, SOCIEDAD LIMITADA$/', '/ S.A.T.$/', '/ SCOOP$/', '/ S.COOP.$/',
        '/ S.A.$/', '/ S.A$/', '/ S.L.$/', '/ S.L$/', '/ SLP$/', '/ SOCIEDAD ANONIMA OPERADORA$/', '/ S.L.U$/', '/ SOCIEDAD DE RESPONSABILIDAD LIMITADA$/', '/ S.A.E.$/', '/ SL PROFESIONAL$/', '/ S.L.N.E.$/',
        '/ S.L.N.E$/', '/ S.COOP.LTDA.$/');

        $replace = [''];
        $total = 0;

        foreach($proyectosNoCif as $proyecto){

            if($proyecto->nombreEmpresa === null || $proyecto->nombreEmpresa == ""){
                continue;
            }

            $name = trim($proyecto->nombreEmpresa);
            $name = preg_replace($find, $replace, $name, 1, $count);
            $name = rtrim($name, ",");
            $name = preg_replace('/[^ \w-]/u', '', $name);
            $name = str_replace(' ','%', $name);

            if($count > 1){
                Log::error('Encontrada más de una coincidencia con posibles empresas no se hace el match');
                continue;
            }
            
            $entidad = \App\Models\Entidad::where('Nombre', 'LIKE', $name."%")->first();

            if($entidad){
                try{
                    $total++;
                    $proyecto->movidoEntidad = 1;
                    $proyecto->empresaPrincipal = $entidad->CIF;
                    $proyecto->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    continue;
                }
                
                $participantes = \App\Models\Participantes::where('id_proyecto', $proyecto->id)->get();
                
                foreach($participantes as $participante){

                    $name = trim($participante->nombre_participante);
                    $name = preg_replace($find, $replace, $name, 1, $count);
                    $name = rtrim($name, ",");
                    $name = preg_replace('/[^ \w-]/u', '', $name);
                    $name = str_replace(' ','%', $name);

                    if($count > 1){
                        Log::error('Encontrada más de una coincidencia con posibles empresas no se hace el match');
                        continue;
                    }
                    
                    $entidad = \App\Models\Entidad::where('Nombre', 'LIKE', $name."%")->first();

                    if($entidad){
                        try{
                            $total++;
                            $participante->cif_participante = $entidad->CIF;
                            $participante->save();
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                            continue;
                        }
                        continue;
                    }

                    $entidad = \App\Models\CifsNoZoho::where('Nombre', 'LIKE', $name."%")->first();

                    if($entidad){
                        try{
                            $total++;
                            $participante->cif_participante = $entidad->CIF;
                            $participante->save();
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                            continue;
                        }
                        continue;
                    }
                }
                
                continue;
            }

            $entidad = \App\Models\CifsNoZoho::where('Nombre', 'LIKE', $name."%")->first();

            if($entidad){
                try{
                    $total++;
                    $proyecto->movidoEntidad = 1;
                    $proyecto->empresaPrincipal = $entidad->CIF;
                    $proyecto->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    continue;
                }

                $participantes = \App\Models\Participantes::where('id_proyecto', $proyecto->id)->get();
                    
                foreach($participantes as $participante){

                    $name = trim($participante->nombre_participante);
                    $name = preg_replace($find, $replace, $name, 1, $count);
                    $name = rtrim($name, ",");
                    $name = preg_replace('/[^ \w-]/u', '', $name);
                    $name = str_replace(' ','%', $name);
                    if($count > 1){
                        Log::error('Encontrada más de una coincidencia con posibles empresas no se hace el match');
                        continue;
                    }
                    
                    $entidad = \App\Models\Entidad::where('Nombre', 'LIKE', $name."%")->first();

                    if($entidad){
                        try{
                            $total++;
                            $participante->cif_participante = $entidad->CIF;
                            $participante->save();
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                            continue;
                        }
                        continue;
                    }

                    $entidad = \App\Models\CifsNoZoho::where('Nombre', 'LIKE', $name."%")->first();

                    if($entidad){
                        try{
                            $total++;
                            $participante->cif_participante = $entidad->CIF;
                            $participante->save();
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                            continue;
                        }
                        continue;
                    }

                }

                continue;
            }

            if($total >= 2000){
                return redirect()->back()->withSuccess($total.": limite de empresas por ejecución, es necesario seguir matcheando empresas, ".$total." empresas matcheadas con proyectos");        
            }
        }

        return redirect()->back()->withSuccess($total.": empresas encontradas en innovating y matcheadas con los proyectos");
    }

    public function exportarExcel(){

        $proyectosNoCif = \App\Models\Proyectos::where('esEuropeo', 0)->where('importado', 1)->where('movidoEntidad', 0)->where('empresaPrincipal', 'XXXXXXXXX')->orderByDesc('Fecha')->skip(0)->take(1000)->get();
        $name = "proyectos-nocifs-".Carbon::now()->format('i-s').".xlsx";
        $filePath = public_path('proyectos')."/".$name;
        
        $defaultStyle = (new StyleBuilder())
                ->setFontName('Arial')
                ->setFontSize(11)
                ->build();
        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->setDefaultRowStyle($defaultStyle)->openToFile($filePath);

        $row = WriterEntityFactory::createRowFromArray(
            ['id','Expediente','idAyuda','NombreAyuda', 'empresaPrincipal','organismo', 'empresasParticipantes','Titulo','Acronimo',
            'presupuestoTotal','AyudaEqSocio','AyudaEq','nombreEmpresa','Fecha','Estado','Tramitación']
        );
        $writer->addRow($row);

        foreach($proyectosNoCif as $proyecto){

            if($proyecto->ayudaAcronimo !== null){
            
                $data = [
                    $proyecto->id,
                    $proyecto->Expediente,
                    $proyecto->ayudaAcronimo->Acronimo,
                    $proyecto->ayudaAcronimo->Titulo,
                    '',
                    $proyecto->organismo,
                    '',
                    $proyecto->Titulo,
                    $proyecto->Acronimo,
                    $proyecto->presupuestoTotal,
                    $proyecto->AyudaEqSocio,
                    $proyecto->AyudaEq,
                    $proyecto->nombreEmpresa,
                    $proyecto->Fecha,
                    $proyecto->Estado,
                    $proyecto->tipoConvocatoria
                ];
            }else{

                $data = [
                    $proyecto->id,
                    $proyecto->Expediente,
                    '',
                    $proyecto->tituloAyuda,
                    '',
                    $proyecto->organismo,
                    '',
                    $proyecto->Titulo,
                    $proyecto->Acronimo,
                    $proyecto->presupuestoTotal,
                    $proyecto->AyudaEqSocio,
                    $proyecto->AyudaEq,
                    $proyecto->nombreEmpresa,
                    $proyecto->Fecha,
                    $proyecto->Estado,
                    $proyecto->tipoConvocatoria
                ];
            }

            $row = WriterEntityFactory::createRowFromArray($data);
            $writer->addRow($row);
        }

        $writer->close();

        return redirect()->back()->withSuccess("Archivo generado correctamente, puedes descargar el archivo desde este enlace: <a href='public/proyectos/".$name."' target='_blank' download='".$name."'>Descargar</a>.");

    }

    public function importarExcel(REquest $request){

        $file = $request->file('excel');

        if($file !== null){

            $checkUpload = \App\Models\uploadExcels::where('procesado', 0)->where('filename', $file->getClientOriginalName())->where('user_id', Auth::user()->id)->first();

            if($checkUpload){
                try{
                    $checkUpload->upload_at = Carbon::now();
                    $checkUpload->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                }
                return redirect()->back()->withErrors('Ya existe un archivo con este nombre: '.$file->getClientOriginalName().' para este usuario: '.Auth::user()->email)->with('option', 'ok');
            }else{

                try{
                    $uploadExcel = new \App\Models\uploadExcels();
                    $uploadExcel->user_id = Auth::user()->id;
                    $uploadExcel->filename = $file->getClientOriginalName();
                    $uploadExcel->procesado = 0;
                    $uploadExcel->upload_at = Carbon::now();
                    $uploadExcel->save();
                    $file->storeAs('excelcifs', $file->getClientOriginalName(), ['disk' => 'excelcompletados']);
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return redirect()->back()->withErrors('No se ha podido subir el archivo');
                }
            }
        }

        return redirect()->back()->withSuccess('El archivo : '.$file->getClientOriginalName().' es correcto')->with('option', 'ok');

    }

    public function recalculoimasdmandarelastic(Request $request){

        if($request->get('filtroarchivo') !== null && $request->get('filtroarchivo') != ""){
            $entidades = \App\Models\Entidad::where('created_at', '>', Carbon::now()->format('Y-m-d')." 00:00:00")->whereColumn('created_at', '=', 'updated_at')->take(50)->get();
            $total = \App\Models\Entidad::where('created_at', '>', Carbon::now()->format('Y-m-d')." 00:00:00")->whereColumn('created_at', '=', 'updated_at')->count();
        }else{
            $entidades = \App\Models\Entidad::where('created_at', '>', Carbon::now()->format('Y-m-d')." 00:00:00")->whereColumn('created_at', '=', 'updated_at')->take(50)->get();
            $total = \App\Models\Entidad::where('created_at', '>', Carbon::now()->format('Y-m-d')." 00:00:00")->whereColumn('created_at', '=', 'updated_at')->count();
        }

        if($entidades->count() > 0){

            foreach($entidades as $entidad){

                try{
                    Artisan::call('calcula:I+D', [
                        'cif' =>  $entidad->CIF
                    ]);
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    continue;
                }

                try{
                    Artisan::call('elastic:companies', [
                        'cif' =>  $entidad->CIFF
                    ]);
                }catch(Exception $e){
                    log::error($e->getMessage());
                   continue;
                }

            }

        }else{
            $entidades = \App\Models\Entidad::where('EntityUpdate', '>=', Carbon::now()->format('Y-m-d')." 00:00:00")->where('checkeomasivoimasd', 0)->take(20)->get();
            $total = \App\Models\Entidad::where('EntityUpdate', '>=', Carbon::now()->format('Y-m-d')." 00:00:00")->where('checkeomasivoimasd', 0)->count();
            if($entidades->count() > 0){

                foreach($entidades as $entidad){
    
                    try{
                        Artisan::call('calcula:I+D', [
                            'cif' =>  $entidad->CIF
                        ]);
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        continue;
                    }
    
                    try{
                        Artisan::call('elastic:companies', [
                            'cif' =>  $entidad->CIFF
                        ]);
                    }catch(Exception $e){
                        log::error($e->getMessage());
                       continue;
                    }

                    $entidad->updated_at = Carbon::now();
                    $entidad->checkeomasivoimasd = 1;
                    $entidad->save();
                }
            }
        }

        return redirect()->back()->withSuccess('Se ha actualizado el nivel de I+D y mandado a elastic un total de: '.$entidades->count().' empresas quedan por calcular: '.$total-$entidades->count())->with('option', 'ok');

    }

    public function getConcesionesCif(Request $request){

        $concesiones = \App\Models\Concessions::where('custom_field_cif', $request->get('cif'))->where('id_organo', $request->get('dpto'))->orWhere('id_departamento', $request->get('dpto'))->get();

        if($concesiones->isNotEmpty()){
            return response()->json($concesiones);
        }

        return response()->json('No se han encontrado concesiones para el CIF:'.$request->get('cif'), 423);
    }

    public function setConcesionParticipante(Request $request){

        $concesion = \App\Models\Concessions::find($request->get('concesion'));

        try{
            $participante = \App\Models\Participantes::where('cif_participante', $request->get('cif'))->where('id_proyecto', $request->get('id_proyecto'))->first();
            $participante->id_concesion = (int)$request->get('concesion');
            if($participante->ayuda_eq_socio == 0 || $participante->ayuda_eq_socio === null){
                if($concesion->amount !== null){
                    $participante->presupuesto_socio = $concesion->amount;
                }
                if($concesion->equivalent_aid !== null){
                    $participante->ayuda_eq_socio = $concesion->equivalent_aid;
                }
            }
            $participante->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('Error al asociar la concesion a la empresa indicada');
        }

        $proyecto = \App\Models\Proyectos::where('empresaPrincipal', $request->get('cif'))->where('id', $request->get('id_proyecto'))->first();

        if($proyecto){
            try{
                $proyecto->matchConcesion = 1;
                $proyecto->idConcesion = $request->get('concesion');
                $proyecto->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('Error al asociar la concesion a la empresa indicada');
            }
        }

        return redirect()->back()->withSuccess('Asociada correctamente la concesion a la empresa indicada');

    }

    public function removeConcesionParticipante(Request $request){

        try{
            $participante = \App\Models\Participantes::where('cif_participante', $request->get('cif'))->where('id_proyecto', $request->get('id_proyecto'))->first();
            $participante->id_concesion = null;
            $participante->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('Error quitar la asociación de la concesion con la empresa indicada');
        }

        $proyecto = \App\Models\Proyectos::where('empresaPrincipal', $request->get('cif'))->where('id', $request->get('id_proyecto'))->first();

        if($proyecto){
            try{
                $proyecto->matchConcesion = 0;
                $proyecto->idConcesion = null;
                $proyecto->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->back()->withErrors('Error al asociar la concesion a la empresa indicada');
            }
        }

        return redirect()->back()->withSuccess('Quitada la asociación correctamente de la concesion con la empresa indicada');

    }

    public function updateProyectoImportado(Request $request){

        $entity = \App\Models\Entidad::where('CIF', $request->get('empresaPrincipal'))->first();

        if($entity){

            $proyecto = \App\Models\Proyectos::find($request->get('id'));

            if($proyecto){

                try{
                    $participantes = json_decode($proyecto->empresasParticipantes);
                    array_push($participantes, $request->get('empresaPrincipal'));

                    $proyecto->empresasParticipantes = json_encode(array_unique($participantes));
                    $proyecto->empresaPrincipal = $request->get('empresaPrincipal');
                    $proyecto->Descripcion = $request->get('descripcion');
                    $proyecto->Titulo = $request->get('titulo');
                    $proyecto->nombreEmpresa = $entity->Nombre;
                    $proyecto->idAyudaAcronimo = $request->get('idayudaacronimo');
                    $proyecto->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return redirect()->back()->withErrors('Error al actualizar el proyecto.');
                }

                $check = \App\Models\Participantes::where('cif_participante', $entity->CIF)->where('id_proyecto', $proyecto->id)->first();

                if(!$check){

                    try{
                        $participante = new \App\Models\Participantes();
                        $participante->cif_participante = $entity->CIF;
                        $participante->nombre_participante = $entity->Nombre;
                        $participante->id_proyecto = $proyecto->id;
                        $participante->save();
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        return redirect()->back()->withErrors('Error al actualizar el proyecto.');
                    }

                }


                return redirect()->back()->withSuccess('Proyecto actualizado.');
            }

        }


        return redirect()->back()->withErrors('Error al actualizar el proyecto.');

    }

    public function asociarEmpresaProyecto(Request $request){

        $proyecto = \App\Models\Proyectos::where('empresaPrincipal', 'XXXXXXXXX')->where('id', $request->get('proyectoid'))->first();

        if($proyecto){
            try{
                $proyecto->empresaPrincipal = $request->get('cif');
                $proyecto->movidoEntidad = 2;
                $proyecto->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return response()->json('No se ha podido asociar al proyecto esa empresa', 423);
            }
        }

        $participante = \App\Models\Participantes::where('cif_participante', 'XXXXXXXXX')->where('id_proyecto', $request->get('proyectoid'))->first();

        if($participante){
            try{
                $participante->cif_participante = $request->get('cif');
                $participante->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return response()->json('No se ha podido asociar al proyecto esa empresa', 423);
            }
        }

        $textos = \App\Models\TextosElastic::where('CIF', $request->get('cif'))->first();

        if($textos && $proyecto){
            $check = mb_substr($proyecto->Titulo,0,50);
            if(stripos($textos->Textos_Proyectos, $check) === false){
                try{
                    $textos->Textos_Proyectos .= $proyecto->Titulo;
                    $textos->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                }
            }
        }elseif($proyecto && !$textos){
            $textos = new \App\Models\TextosElastic;
            try{
                $textos->CIF = $request->get('cif');
                $textos->Textos_Proyectos = $proyecto->Titulo;
                $textos->Last_Update = Carbon::now();
                $textos->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
            }
        }

        return response()->json('Asociada correctamente el proyecto a esa empresa', 200);
    }
}
