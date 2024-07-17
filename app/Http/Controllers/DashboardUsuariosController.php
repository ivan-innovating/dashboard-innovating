<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardUsuariosController extends Controller
{
    //

    public function users(){

        $users = \App\Models\User::get();      
        return view('admin.users.users', [
            'usuarios' => $users,            
        ]);

    }

    public function usersSinValidar(){

        $usuariossinvalidar = \App\Models\User::whereNull('email_verified_at');
        return view('admin.users.userssinvalidar', [
            'usuarios' => $usuariossinvalidar,            
        ]);

    }

    public function usersSinEmpresa(){

        $userssinempresa = \App\Models\User::whereNotNull('email_verified_at')->withCount('entidades')->get();
        return view('admin.users.userssinempresa', [
            'usuarios' => $userssinempresa,            
        ]);

    }

    public function usersConEmpresa(){
             
        $usersconempresa = 
        \App\Models\User::whereNotNull('email_verified_at')->withCount('entidades')->get();


        return view('admin.users.usersconempresa', [
            'usuarios' => $usersconempresa,            
        ]);

    }

    public function investigadores(){
     
        $investigadores = \App\Models\Investigadores::paginate(100);
        $investigadoresasociados = \App\Models\Investigadores::whereNotNull('id_ultima_experiencia')->leftJoin('entidades', 'entidades.id', '=', 'investigadores.id_ultima_experiencia')->count();
        $investigadoresinsasociar = \App\Models\Investigadores::whereNull('id_ultima_experiencia')->count();
        $invesnoentidades = \App\Models\InvestigadoresEntidades::whereNull('id_entidad')->where('descartado', 0)->count();
        $invesnoentidadesdescartados = \App\Models\InvestigadoresEntidades::whereNull('id_entidad')->where('descartado', 1)->count();
        $invesentidadesauto = \App\Models\InvestigadoresEntidades::whereNotNull('id_entidad')->where('asignadoManual', 0)
        ->leftJoin('entidades', 'entidades.id', '=', 'investigadores_entidades.id_entidad')->select('investigadores_entidades.id as id_investigador_entidad', 'investigadores_entidades.*', 'entidades.*')->count();
        $invesentidadesmanual = \App\Models\InvestigadoresEntidades::whereNotNull('id_entidad')->where('asignadoManual', 1)
        ->leftJoin('entidades', 'entidades.id', '=', 'investigadores_entidades.id_entidad')->select('investigadores_entidades.id as id_investigador_entidad', 'investigadores_entidades.*', 'entidades.*')->count();

        return view('admin.users.investigadores', [
            'investigadores' => $investigadores,
            'investigadoresinsasociar' => $investigadoresinsasociar,
            'investigadoresasociados' => $investigadoresasociados,
            'invesnoentidadesdescartados' => $invesnoentidadesdescartados,
            'invesnoentidades' => $invesnoentidades,
            'invesentidadesauto' => $invesentidadesauto,
            'invesentidadesmanual' => $invesentidadesmanual
        ]);

    }
}