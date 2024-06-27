<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Settings\GeneralSettings;
use App\Models\ValidateCompany;
use App\Models\OldEinforma;
use App\Models\Einforma;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\Ministerios;
use App\Models\Departamentos;
use App\Models\Organos;
use stdClass;

class DashboardEmpresasController extends Controller
{

    public function empresas(Request $request){

        if($request->get('tipo') !== null){
            switch($request->get('tipo')){
                case "perfilcompleto":
                    $empresas = \App\Models\Entidad::where('esCentroTecnologico',0)
                    ->where('perfilCompleto', 1)
                    ->orWhereJsonContains('efectoWow->perfilcompleto', 1)
                    ->orderBy('updated_at', 'DESC')
                    ->paginate(20);
                break;
                case "efectowow":
                    $empresas = \App\Models\Entidad::where('esCentroTecnologico',0)
                    ->whereJsonContains('efectoWow->perfilcompleto', 1)
                    ->whereJsonContains('efectoWow->perfilentrayuda', 1)
                    ->whereJsonContains('efectoWow->enviarelastic', 1)
                    ->orderBy('updated_at', 'DESC')
                    ->paginate(20);
                break;
                case "envioelastic":
                    $empresas = \App\Models\Entidad::where('esCentroTecnologico',0)
                    ->where('firstElastic', '>', 0)
                    ->orWhereJsonContains('efectoWow->enviarelastic', 1)
                    ->orderBy('updated_at', 'DESC')
                    ->paginate(20);
                break;
                default:
                    $empresas = \App\Models\Entidad::where('esCentroTecnologico',0)->orderBy('updated_at', 'DESC')->paginate(20);
                break;
            }

        }else{
            $empresas = \App\Models\Entidad::where('esCentroTecnologico',0)->orderBy('updated_at', 'DESC')->paginate(20);
        }

        $totalempresas = \App\Models\Entidad::where('esCentroTecnologico',0)->count();
        return view('admin.empresas.empresas', [
            'totalempresas' => $totalempresas,
            'empresas' => $empresas,
        ]);
    }

    public function centros(){

        $centros = \App\Models\Entidad::where('esCentroTecnologico',1)->get();
     
        return view('admin.empresas.centros', [
            'totalempresas' => $centros->count(),
            'empresas' => $centros,
        ]);
    }

    public function buscarEmpresas(){

        $totalempresas = \App\Models\Entidad::count();

        return view('admin.empresas.buscar', [
            'totalempresas' => $totalempresas
        ]);
    }

    public function validarEmpresas(){
    
        $validar = ValidateCompany::orderBy('updated_at', 'DESC')->paginate(20);
        return view('admin.empresas.validar', [
            'empresas' => $validar
        ]);
    
    }

    public function priorizarEmpresas(){

        $priorizar = \App\Models\PriorizaEmpresas::where('esOrgano',0)->orderBy('created_at', 'DESC')->paginate(20);
        return view('admin.empresas.priorizar', [
            'priorizar' => $priorizar
        ]);
    
    }

    public function crearEmpresas(){

        $naturalezas = \App\Models\Naturalezas::where('Activo', 1)->get();
        $ccaas = getAllCcaas();
        $paises = \App\Models\Paises::all();
        $cnaes = \App\Models\Cnaes::all();

        return view('admin.empresas.crear', [
            'naturalezas' => $naturalezas,
            'ccaas' => $ccaas,
            'paises' => $paises,
            'cnaes' => $cnaes,
        ]);

    }

    public function editarEmpresas($cif, $id){


        $empresa = \App\Models\Entidad::where('CIF', $cif)->where('id', $id)->first();
        $nozoho = 0;
        if(!$empresa){
            $empresa = DB::table('CifsnoZoho')->where('id', $id)->where('CIF', $cif)->first();
            $nozoho = 1;
            if(!$empresa){
                return abort(404);
            }
        }

        if($nozoho == 1){
            $empresa->Marca = null;
            $empresa->Web = null;
            $empresa->Ccaa = null;
            $empresa->Intereses = json_encode(array());
            $empresa->naturalezaEmpresa = json_encode(array());
            $empresa->esConsultoria = 0;
            $empresa->maxProyectos = 10;
            $empresa->TextosLineasTec = json_encode(array());
            $empresa->NumeroLineasTec = 2;
        }
        
        $intereses = \App\Models\Intereses::where('defecto', 'true')->get();
        $naturalezas = \App\Models\Naturalezas::where('Activo', 1)->get();
        $ccaas = getAllCcaas();
        $paises = \App\Models\Paises::all();
        $cnaes = \App\Models\Cnaes::all();

        if($empresa->esCentroTecnologico == 1){

            $organos = \App\Models\Organos::all()->toArray();
            $departamentos = \App\Models\Departamentos::all()->toArray();
            $organismos = array_merge($organos, $departamentos);
            usort($organismos, function($a, $b) {
                return $a['Nombre'] <=> $b['Nombre'];
            });
        }else{
            $organismos = array();
        }

        $solicitudes = \App\Models\PriorizaEmpresas::where('cifPrioritario', $cif)->get();

        return view('admin.empresas.editar', [
            'empresa' => $empresa,
            'nozoho' => $nozoho,
            'naturalezas' => $naturalezas,
            'ccaa' => $ccaas,
            'paises' => $paises,
            'cnaes' => $cnaes,
            'intereses' => $intereses,
            'organismos' => $organismos,
            'solicitudes' => $solicitudes
        ]);

    }

    public function newEmpresa(Request $request){

        $uri = str_replace(" ","-",cleanUriBeforeSave(mb_strtolower(trim($request->get('nombre')))));
        $checkentidades = \App\Models\Entidad::where('CIF', $request->get('cif'))->first();

        if($checkentidades){
            return redirect()->back()->withErrors('Ese CIF ya ha sido añadido a nuestra lista de empresas, puedes ver la empresa en este enlace: <a class="text-white" href="'.route('empresa', $checkentidades->uri).'">Ver empresa</a>');
        }

        $sedes = new stdClass;
        $sedes->central = $request->get('ccaa');
        $sedes->otrassedes = array();

        try{
            $entidad = new \App\Models\Entidad();            
            $entidad->CIF = $request->get('cif');
            $entidad->Nombre = $request->get('nombre');
            $entidad->Web = $request->get('web');
            $entidad->Ccaa = $request->get('ccaa');
            $entidad->Cnaes = $request->get('cnaes');
            $entidad->Sedes = json_encode($sedes);
            $entidad->uri = $uri;
            $entidad->naturalezaEmpresa = json_encode(["6668837"]);
            $entidad->NumeroLineasTec = 2;
            $entidad->Intereses = json_encode(["I+D","Innovación","Digitalización","Cooperación","Subcontratación"]);
            $entidad->MinimoSubcontratar = 0;
            $entidad->MinimoCooperar = 0;
            $entidad->EntityUpdate = Carbon::now();
            $entidad->created_at = Carbon::now();
            $entidad->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido crear la empresa');
        }

        return redirect()->route('admineditarempresa', ['cif' => $entidad->CIF, 'id' => $entidad->id])->withSuccess('Empresa creada correctamente');

    }

    public function editEmpresa(Request $request){

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
                $entidad = new \App\Models\Entidad();                
                $entidad->Nombre = $request->get('nombre');
                $entidad->Marca = $request->get('marca');
                $entidad->uri = $uri;
                $entidad->Ccaa = $request->get('ccaa');
                $entidad->CIF = $request->get('cif');
                $entidad->Web = $request->get('web');
                $entidad->TextosLineasTec = json_encode($request->get('tagsanalisis'), JSON_UNESCAPED_UNICODE);
                $entidad->Intereses = ($request->get('intereses') !== null) ? json_encode($request->get('intereses')) : null;
                $entidad->esConsultoria = ($request->get('esconsultoria') !== null) ? 1 : 0;
                $entidad->esCentroTecnologico = $escentro;
                $entidad->naturalezaEmpresa = json_encode($request->get('naturaleza'));
                $entidad->idOrganismo = (in_array('6668843', $request->get('naturaleza'))) ? $request->get('idorganismo') : null;
                $entidad->crearBusquedas = (in_array('6668843', $request->get('naturaleza'))) ? 1 : 0;
                $entidad->simula_empresas = (in_array('6668843', $request->get('naturaleza'))) ? 1 : 0;
                $entidad->maxProyectos = $request->get('maxproyectos');
                $entidad->NumeroLineasTec = 2;
                $entidad->EntityUpdate = Carbon::now();
                $entidad->UpdatedBy = Auth::user()->email;
                $entidad->created_at = Carbon::now();
            }catch(Exception $e){
                return redirect()->back()->withErrors("Error en la actualización de la empresa");
            }

            try{
                $textos = \App\Models\TextosElastic::where('CIF', $request->get('cif'))->first();
                
                if(!$textos){
                    $textos = new \App\Models\TextosElastic();
                }
                
                $textos->Textos_Documentos = $request->get('textos_documentos');
                $textos->Textos_Proyectos = $request->get('textos_proyectos');
                $textos->Textos_Tecnologia = $request->get('textos_tecnologia');
                $textos->Textos_Tramitaciones = $request->get('textos_tramitaciones');
                $textos->Last_Update = Carbon::now();
                $textos->save();
            }catch(Exception $e){
                return redirect()->back()->withErrors("Error en la actualización de los textos elastic de la empresa");
            }

            try{
                \App\Models\CifsNoZoho::where('CIF', $request->get('cif'))->update(
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
                $entity->TextosLineasTec = json_encode($request->get('tagsanalisis'), JSON_UNESCAPED_UNICODE);
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

                $entity->einforma->Web = json_encode($webs, JSON_UNESCAPED_SLASHES);
                $entity->einforma->save();                

            }catch(Exception $e){
                return redirect()->back()->withErrors("Error en la actualización de la empresa");
            }

            try{
                $textos = \App\Models\TextosElastic::where('CIF', $request->get('cif'))->first();
                
                if(!$textos){
                    $textos = new \App\Models\TextosElastic();
                }
                
                $textos->Textos_Documentos = $request->get('textos_documentos');
                $textos->Textos_Proyectos = $request->get('textos_proyectos');
                $textos->Textos_Tecnologia = $request->get('textos_tecnologia');
                $textos->Textos_Tramitaciones = $request->get('textos_tramitaciones');
                $textos->Last_Update = Carbon::now();
                $textos->save();
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

    public function viewPriorizar($id){

        $priorizar = \App\Models\PriorizaEmpresas::where('id', $id)->first();

        if(!$priorizar){
            return abort(404);
        }

        $solicitante = \App\Models\Entidad::where('CIF', $priorizar->solicitante)->first();

        if($priorizar->esOrgano == 1){
            $organodpto = \App\Models\Organos::where('id', $priorizar->idOrgano)->first();
            if(!$organodpto){
                $organodpto = \App\Models\Departamentos::where('id', $priorizar->idOrgano)->first();
            }
            $priorizar->NombreOrgano = $organodpto->Nombre;
        }

        if(!$solicitante){
            $solicitante = \App\Models\User::where('email', $priorizar->solicitante)->first();
        }

        if(!$solicitante){
            return abort(404);
        }

        $ccaas = getAllCcaas();
        //$paises = \App\Models\Paises::all();
        $cnaes = \App\Models\Cnaes::all();

        return view('admin.empresas.viewpriorizar', [
            'priorizar' => $priorizar,
            'solicitante' => $solicitante,
            'ccaas' => $ccaas,
            'cnaes' => $cnaes
        ]);
    }

    public function aceptaPriorizar(Request $request){

        try{
            $priorizar = \App\Models\PriorizaEmpresas::find($request->get('id'));
            if(!$priorizar){
                return response()->json(['error' => 'No se ha encontrado la solicitud']);
            }
            $priorizar->sacadoEinforma = 1;
            $priorizar->save();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return response()->json(['error' => 'No se ha procesado correctamente la petición']);
        }

        return response()->json(['success'=> 'Actualizada solicitud.']);
        
    }

    public function rechazaPriorizar(Request $request){

        $priorizar = \App\Models\PriorizaEmpresas::where('id', $request->get('id'))->first();

        if($priorizar->esOrgano == 1){
            $organodpto = \App\Models\Organos::where('id', $priorizar->idOrgano)->first();
            if(!$organodpto){
                try{
                    \App\Models\Departamentos::where('id', $priorizar->idOrgano)->update([
                        'scrapper' => 0,
                        'updated_at' => Carbon::now(),
                    ]);
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return response()->json(['error' => 'Error al actualizar el departamento']);
                }
            }else{
                try{
                    \App\Models\Organos::where('id', $priorizar->idOrgano)->update([
                        'scrapper' => 1,
                        'updated_at' => Carbon::now(),
                    ]);
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return response()->json(['error' => 'Error al actualizar el organo']);
                }
            }

            $empresa = \App\Models\Entidad::where('CIF', $priorizar->solicitante)->first();

            $userentidades = \App\Models\UsersEntidad::where('entidad_id', $empresa->id)->where('role','admin')->Join('users','users_entidades.users_id', '=', 'users.id')
                ->get();

            if($userentidades){

                $dpto =\App\Models\Organos::where('id', $priorizar->idOrgano)->first();

                if(!$dpto){
                    $dpto = \App\Models\Departamentos::where('id', $priorizar->idOrgano)->first();
                }

                foreach($userentidades  as $user){

                    $mail = new \App\Mail\RechazaPriorizar($priorizar, $dpto, $request->get('message'));

                    try{
                        Mail::to($user->email)->queue($mail);
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        return response()->json(['error' => 'Error al enviar el correo al usuario']);
                    }

                }

            }
        }else{

            try{
                \App\Models\PriorizaEmpresas::where('id', $request->get('id'))->update([
                    'updated_at' => Carbon::now(),
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
                return redirect()->json(['error' => 'Error al actualizar la solicitud']);
            }

            $empresa = \App\Models\Entidad::where('CIF', $priorizar->solicitante)->first();

            if($empresa){

                $empresa = \App\Models\Entidad::where('CIF', $priorizar->solicitante)->first();

                $userentidades = \App\Models\UsersEntidad::where('entidad_id', $empresa->id)->where('role','admin')->Join('users','users_entidades.users_id', '=', 'users.id')
                    ->get();

                if($userentidades){

                    if($priorizar->esOrgano == 0){

                        $empresapriorizar = \App\Models\Entidad::where('CIF', $priorizar->cifPrioritario)->first();

                        if(!$empresapriorizar){
                            $empresapriorizar =\App\Models\CifsNoZoho::where('CIF', $priorizar->cifPrioritario)->first();
                        }

                        foreach($userentidades  as $user){

                            $mail = new \App\Mail\RechazaPriorizar($priorizar, $empresapriorizar, $request->get('message'));

                            try{
                                Mail::to($user->email)->queue($mail);
                            }catch(Exception $e){
                                Log::error($e->getMessage());
                                return response()->json(['error' => 'Error al enviar el correo al usuario']);
                            }

                        }

                    }

                }
            }

        }

        return response()->json(['success'=> 'Solicitud rechazada.']);
    }


}
