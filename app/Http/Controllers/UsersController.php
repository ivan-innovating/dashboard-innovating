<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UsersController extends Controller
{
    //
    public function users(){

        $users = DB::table('users')->get();
        $usuariossinvalidar = collect($users)->whereNull('email_verified_at');
        $usersconempresa = DB::table('users_entidades')->leftJoin('users', 'users.id', '=', 'users_entidades.users_id')
        ->whereNotNull('users.id')->whereNotNull('users.email_verified_at')->select('users.id as id_ok','users.*',)->groupBy('id_ok')->get();

        foreach($users as $user){
            $user->totalempresas = DB::table('users_entidades')->where('users_id', $user->id)->count();
        }

        foreach($usersconempresa as $user){
            $user->totalempresas = DB::table('users_entidades')->where('users_id', $user->id_ok)->count();
        }
        $userssinempresa = DB::table('users')->leftJoin('users_entidades', 'users.id', '=', 'users_entidades.users_id')
        ->where('users_entidades.users_id', '=', null)->whereNotNull('users.email_verified_at')->select('users.*')->get();

        return view('dashboard/usuarios', [
            'usuarios' => $users,
            'usuariossinvalidar' => $usuariossinvalidar,
            'userssinempresa' => $userssinempresa,
            'usersconempresa' => $usersconempresa
        ]);

    }

    public function investigadores(){

        $totalinvestigadores = \App\Models\Investigadores::count();
        $investigadoresasociados = \App\Models\Investigadores::whereNotNull('id_ultima_experiencia')->leftJoin('entidades', 'entidades.id', '=', 'investigadores.id_ultima_experiencia')->paginate(500);
        $investigadoresinsasociar = \App\Models\Investigadores::whereNull('id_ultima_experiencia')->paginate(500);
        $invesnoentidades = \App\Models\InvestigadoresEntidades::whereNull('id_entidad')->where('descartado', 0)->paginate(500);
        $invesnoentidadesdescartados = \App\Models\InvestigadoresEntidades::whereNull('id_entidad')->where('descartado', 1)->paginate(500);
        $invesentidadesauto = \App\Models\InvestigadoresEntidades::whereNotNull('id_entidad')->where('asignadoManual', 0)
        ->leftJoin('entidades', 'entidades.id', '=', 'investigadores_entidades.id_entidad')->select('investigadores_entidades.id as id_investigador_entidad', 'investigadores_entidades.*', 'entidades.*')->paginate(500);
        $invesentidadesmanual = \App\Models\InvestigadoresEntidades::whereNotNull('id_entidad')->where('asignadoManual', 1)
        ->leftJoin('entidades', 'entidades.id', '=', 'investigadores_entidades.id_entidad')->select('investigadores_entidades.id as id_investigador_entidad', 'investigadores_entidades.*', 'entidades.*')->paginate(500);

        return view('dashboard/investigadores', [
            'totalinvestigadores' => $totalinvestigadores,
            'investigadoresinsasociar' => $investigadoresinsasociar,
            'investigadoresasociados' => $investigadoresasociados,
            'invesnoentidadesdescartados' => $invesnoentidadesdescartados,
            'invesnoentidades' => $invesnoentidades,
            'invesentidadesauto' => $invesentidadesauto,
            'invesentidadesmanual' => $invesentidadesmanual
        ]);

    }

    public function viewInvestigador($id){

        $investigador = \App\Models\Investigadores::find($id);
        $organos = \App\Models\Organos::all();
        $departamentos = \App\Models\Departamentos::all();
        $organismos = $organos->merge($departamentos);

        $entity = null;

        if($investigador->id_ultima_experiencia !== null){
            $entity = \App\Models\Entidad::find($investigador->id_ultima_experiencia);
        }

        return view('dashboard/edit-investigador', [
            'investigador' => $investigador,
            'organismos' => $organismos,
            'entidadasociado' => $entity
        ]);

    }

    public function saveInvestigador(Request $request){

        $investigador = \App\Models\Investigadores::find($request->get('id'));

        $keywords = array_filter(explode(",",$request->get('tags')));

        try{

            $investigador->investigador = $request->get('investigador');
            $investigador->descripcion = $request->get('descripcion');
            $investigador->keywords = (!empty($keywords)) ? $keywords : array();
            $investigador->ultima_experiencia = $request->get('ultima_experiencia');
            $investigador->total_works = $request->get('total_works');
            $investigador->save();

        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('Error al actualizar los datos del investigador');
        }

        return redirect()->back()->withSuccess('Investigador actualizado correctamente.');
    }

    public function updateInvestigadores(Request $request){

        try{
            \Artisan::call('assign:investigadores_universidades', [
                'tipo' => $request->get('tipo')
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido actualizar los investigadores de manera masiva.');
        }

        return redirect()->back()->withSuccess('Actualizando investigadores de manera masiva.');
    }

    public function updateInvestigadorEntidades(Request $request){

        try{
            \App\Models\InvestigadoresEntidades::where('id', $request->get('id'))->update([
                'id_entidad' => $request->get('identidad'),
                'asignadoManual' => 1,
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return response()->json('Error al actualizar el listado de entidades investigadores', 419);
        }

        $entity = \App\Models\Entidad::find($request->get('id'));

        if($entity){
            try{
                \Artisan::call('calcula:I+D', [
                    'cif' => $entity->CIF
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
                return response()->json('Error al recalcular I+d y TRL de la empresa', 419);
            }

            try{
                \Artisan::call('elastic:companies', [
                    'cif' => $entity->CIF
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
                return response()->json('Error al actualizar la empresa en elastic', 419);
            }
        }

        return response()->json('Listado de entidades investigadores actualizado correctamente.', 200);
    }

    public function descartarInvestigadorEmpresa(Request $request){
        try{
            \App\Models\InvestigadoresEntidades::where('id', $request->get('id'))->update([
                'descartado' => $request->get('opcion')
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return response()->json('Error al descartar la empresa de investigadores', 419);
        }

        return response()->json('Empresa descartada correctamente de investigadores.', 200);
    }

    public function asociarInvestigadorEmpresa(Request $request){

        try{
            \App\Models\Investigadores::where('id', $request->get('idinvestigador'))->update([
                'id_ultima_experiencia' => $request->get('id')
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return response()->json('Error al actualizar los datos del investigador', 419);
        }

        $entity = \App\Models\Entidad::find($request->get('id'));

        if($entity){
            try{
                \Artisan::call('calcula:I+D', [
                    'cif' => $entity->CIF
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
                return response()->json('Error al recalcular I+d y TRL de la empresa', 419);
            }

            try{
                \Artisan::call('elastic:companies', [
                    'cif' => $entity->CIF
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
                return response()->json('Error al actualizar la empresa en elastic', 419);
            }
        }

        return response()->json('Investigador actualizado correctamente.', 200);
    }

    public function updateOrcid(Request $request){

        try{
            $resp = \Artisan::call('get:orcid', [
                'id' => $request->get('orcid_id')
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido actualizar el perfil desde ORCID.');
        }

        if($resp == 1){
            return redirect()->back()->withErrors('No se ha podido actualizar el perfil desde ORCID.');
        }

        return redirect()->back()->withSuccess('Actualizado el perfil desde ORCID, revisa si los datos son correctos.');
    }

    public function validateUser(Request $request){

        try{
            DB::table('users')->where('id', $request->get('id'))->update([
                'email_verified_at' => Carbon::now()
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('Error al validar el usuario: '.$request->get('id'));
        }

        return redirect()->back()->withSuccess('usuario '.$request->get('id').' validado');
    }

    public function roles(Request $request){

        $id = $request->route('id');

        if(!$id){
            return abort(404);
        }

        $user = DB::table('users')->where('id', $id)->first();

        if(!$user){
            return abort(404);
        }

        $roles = array('tecnico' => 'Tecnico', 'manager' => 'Manager', 'admin' => 'Admin');

        $userRoles = DB::table('users_entidades')->where('users_entidades.users_id', $id)
        ->leftJoin('users', 'users.id', '=', 'users_entidades.users_id')
        ->leftJoin('entidades', 'users_entidades.entidad_id', '=', 'entidades.id')->get();

        return view('dashboard/roles', [
            'usuario' => $user,
            'roles' => $roles,
            'userRoles' => $userRoles
        ]);

    }

    public function userUpdate(Request $request){

        $id = $request->route('id');

        if(!$id){
            return abort(404);
        }

        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$id,
            'password' => 'same:confirm-password',
        ]);


        $input = $request->all();
        if(!empty($input['password']) && !(empty($input['"confirm-password']))){
            $input['password'] = Hash::make($input['password']);
        }else{
            $input = Arr::except($input,array('password'));
        }

        try{
            $user = \App\Models\User::find($id);
            if(isset($input['is_ban'])){
                $user->is_ban = 1;
            }else{
                $user->is_ban = 0;
            }
            if(isset($input['super_admin'])){
                $user->super_admin = 1;
            }else{
                $user->super_admin = 0;
            }

            if(isset($input['superadmin_access'])){
                $user->superadmin_access = 1;
            }else{
                $user->superadmin_access = 0;
            }

            if(!empty($input['password']) && !(empty($input['"confirm-password']))){
                $user->password = Hash::make($input['password']);
            }

            $user->name = $input['name'];
            $user->email = $input['email'];
            $user->save();
        }catch(Exception $e){
            dd($e->getMessage());
        }


        foreach($request->all() as $key => $value){

            $match = preg_match('/role-/i', $key);

            if($match){
                $entidad_id = str_replace("role-","", $key);
                try{
                    DB::table('users_entidades')->where('users_id', $id)->where('entidad_id', $entidad_id)->update([
                        'role' => $value
                    ]);
                }catch(Exception $e){
                    dd($e->getMessage());
                }
            }

        }

        return redirect()->back()->with('success','User updated successfully');

    }
}
