<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

use Exception;

class ConvocatoriaController extends Controller
{
    //
    public function editarConvocatoria(Request $request){

        $id = $request->get('id');
        $estado = $request->get('estado');
        $analisis = $request->get('analisis');
        $idayudas = $request->get('ayudas');

        try{
            DB::table('convocatorias')->where('IDConvocatoria', $id)->update([
                'Estado' => $estado,
                'Analisis' => $analisis,
                'id_ayudas' => $idayudas
            ]);

        }catch(Exception $e){
            die($e->getMessage());
        }

        return "Actualizada convocatoria";
    }


}
