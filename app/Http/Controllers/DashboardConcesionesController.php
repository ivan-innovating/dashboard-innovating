<?php

namespace App\Http\Controllers;

use App\Models\Imports\ExcelImportConcessions;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class DashboardConcesionesController extends Controller
{
    //

    public function concesionesImportadas(){

        $concesiones = \App\Models\Concessions::where('type', 'uploadexcel')->where('importada', 1)->whereNotNull('file_name')->orderBy('created_at', 'DESC')->limit(200)->get();

        $archivosimportacion = collect(null);
        if($concesiones->count() > 0){
            $archivosimportacion = $concesiones->pluck('file_name','file_name')->toArray();
        }

        return view('dashboard/concesionesimportadas', [
            'concesiones' => $concesiones,
            'archivosimportacion' => $archivosimportacion
        ]);
    }

    public function importarConcesiones(Request $request){

        $file = $request->file('excel');
        $fallos = array();

        try{
            $import = new ExcelImportConcessions;
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

        ksort($fallos);
        return redirect()->back()->withSuccess('Líneas importadas: '.$import->getRowsCount().', líneas existentes: '.$import->getRows2Count().', del archivo:'.$file->getClientOriginalName())
        ->with('fallos', $fallos)->with('option', 'ok');
    }

    public function deleteConcesiones(Request $request){

        try{
            \App\Models\Concessions::where('file_name', $request->get('archivo'))->delete();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se han podido borrar las concesiones del archivo de importación: '.$request->get('archivo'))->with('option','ok');
        }

        return redirect()->back()->withSuccess('Borradas las concesiones del archivo de importación: '.$request->get('archivo'))->with('option','ok');

    }


    public function programarScrapperConcesiones(){

        $organosScrapper = \App\Models\Organos::where('scrapper', 1)->orderBy('Nombre', 'ASC')->pluck('Nombre', 'id')->toArray();
        $departamentosScrapper = \App\Models\Departamentos::where('scrapper', 1)->orderBy('Nombre', 'ASC')->pluck('Nombre', 'id')->toArray();
        $scrapperspendientes = \App\Models\ScrappersOrganismos::orderBy('updated_at', 'DESC')->limit(100)->get();

        return view('dashboard/programarscrapper', [
            'organos' => $organosScrapper,
            'departamentos' => $departamentosScrapper,
            'scrapperprogramados' => $scrapperspendientes
        ]);
    }

    public function createProgramScrapper(Request $request){

        if(\Carbon\Carbon::createFromFormat('d/m/Y', $request->get('desde')) >= \Carbon\Carbon::createFromFormat('d/m/Y', $request->get('hasta'))){
            return redirect()->back()->withErrors('La fecha desde es mayor o igual a la fecha hasta');
        }

        if($request->get('organo') !== null){
            $type = "organo";
            $organo = \App\Models\Organos::find($request->get('organo'));
            $idorganismo = $organo->id;
            $superior = $organo->ministerios;
        }else if($request->get('dpto') !== null){
            $type = "departamento";
            $dpto = \App\Models\Departamentos::find($request->get('dpto'));
            $idorganismo = $dpto->id;
            $superior = $dpto->ccaa;
        }else{
            return redirect()->back()->withErrors('No se ha podido crear la tarea programada');
        }

        $checkscrapper = \App\Models\ScrappersOrganismos::where('ejecutado', 0)->where('id_organismo', $idorganismo)->where('type', $type)->first();

        if($checkscrapper){
            return redirect()->back()->withErrors('Ya existe una tarea programada pendiente de ejecución para este organismo, si no es correcta puedes borrarla y crear una nueva.');
        }

        try{
            $scrapper = new \App\Models\ScrappersOrganismos();
            $scrapper->id_user = Auth::user()->id;
            $scrapper->id_organismo = $idorganismo;
            $scrapper->id_ministerio = ($type == "departamento") ? $superior->external_id : $superior->id;
            $scrapper->type = $type;
            $scrapper->desde = \Carbon\Carbon::createFromFormat('d/m/Y', $request->get('desde'))->format('Y-m-d');
            $scrapper->hasta = \Carbon\Carbon::createFromFormat('d/m/Y', $request->get('hasta'))->format('Y-m-d');
            $scrapper->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido crear la tarea programada');
        }

        return redirect()->back()->withSuccess('Se ha creado la tarea programada correctamente');
        
    }

    public function deleteProgramScrapper(Request $request){

        try{
            \App\Models\ScrappersOrganismos::find($request->get('id'))->delete();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido borra la tarea programada');
        }

        return redirect()->back()->withSuccess('Se ha borrado la tarea programada correctamente');
    }
}

