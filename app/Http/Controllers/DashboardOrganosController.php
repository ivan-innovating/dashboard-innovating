<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardOrganosController extends Controller
{
    //
    public function organos(){

        $organos = \App\Models\Organos::orderByDesc('updated_at')->orderByDesc('created_at')->get();
        
        return view('admin.organos.organos', [
            'organos' => $organos
        ]);
    }

    public function editarOrgano($id){

        if($id == "" || $id === null){
            return abort(419);
        }

        $organismo = \App\Models\Organos::find($id);

        if($organismo === null){
            return abort(419);
        }

        $ministerios = \App\Models\Ministerios::get();

        return view('admin.organos.editar', [
            'organismo' => $organismo,
            'ministerios' => $ministerios
        ]);
    }
}
