<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class DashboardCnaeController extends Controller
{
    //
    public function viewCnae($id){

        $cnae = DB::table('Cnaes')->where('id', $id)->first();

        return view('dashboard/editcnae', [
            'cnae' => $cnae

        ]);

    }

    public function editarCnae(Request $request){

        try{

            DB::table('Cnaes')->where('id', $request->get('id'))->update([
                'Nombre' => $request->get('nombre'),
                'Tipo' => $request->get('tipo'),
                'TrlMedio' => $request->get('trl'),
            ]);

        }catch(Exception $e){
            dd($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Cnae actualizado');

    }

}
