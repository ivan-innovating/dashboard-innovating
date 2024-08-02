<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CondicionesFinancierasController extends Controller
{
    //
    public function condicionesFinancieras(){

        $condiciones = \App\Models\CondicionesFinancieras::all();
        $analisis = \App\Models\AnalisisFinancieros::all();
       
        return view('admin.condicionesfinancieras.condicionesfinancieras', [
            'condiciones' => $condiciones,
            'analisisfinancieros' => $analisis
        ]);
    }

    public function saveCondicionFinanciera(Request $request){

        $checkcondicion = \App\Models\CondicionesFinancieras::where('var1', $request->get('var1'))->where('var2', $request->get('var2'))->where('condicion', $request->get('condicion'))->first();

        if($checkcondicion){
            return redirect()->back()->withErrors('Ya existe una condicion con las mismas condiciones que las que has introducido');
        }

        $condicion = new \App\Models\CondicionesFinancieras();

        try{
            $condicion->var1 = $request->get('var1');
            $condicion->var2 = $request->get('var2');
            $condicion->condicion = $request->get('condicion');
            $condicion->valor = ($request->get('valor') !== null) ? $request->get('valor') : null;
            $condicion->coeficiente = $request->get('coeficiente');
            $condicion->comentario_cumple = ($request->get('comentario_cumple') !== null) ? $request->get('comentario_cumple') : null;
            $condicion->color_cumple = ($request->get('color_cumple') !== 'no') ? $request->get('color_cumple') : null;
            $condicion->comentario_incumple = $request->get('comentario_incumple');
            $condicion->color_incumple = ($request->get('color_incumple') !== 'no') ? $request->get('color_incumple') : null;
            $condicion->orden = $request->get('orden');
            $condicion->todasconvocatorias = ($request->get('todasconvocatorias') !== null) ? 1 : 0;
            $condicion->idsconvocatorias = ($request->get('idsconvocatorias') !== null) ? json_encode($request->get('idsconvocatorias')) : null;
            $condicion->save();
        }catch(Exception $e){
            log::error($e->getMessage());
            return redirect()->back()->withErrors('Ha ocurrido un error al crear la condicion');
        }

        return redirect()->back()->withSuccess('Se ha creado la condicion correctamente');

    }

    public function editarCondicionFinanciera($id){

        $condicion = \App\Models\CondicionesFinancieras::find($id);

        if(!$condicion){
            return abort(404);
        }

        $colors = [
            'danger' => 'Rojo',
            'success' => 'Verde',
            'orange' => 'Naranja',
            'no' => 'No mostrar'
        ];

        $variables = [
            'Gastos Anuales' => 'Gastos Anuales',
            'Presupuesto del proyecto' => 'Presupuesto del proyecto',
            'Fondos propios' => 'Fondos propios',
            'Circulante' => 'Circulante',
            'Beneficios reales' => 'Beneficios reales',
            'Margen de endeudamiento' => 'Margen de endeudamiento',
            'Gasto Medio I+D' => 'Gasto Medio I+D',
            'Gasto Min I+D' => 'Gasto Mínimo I+D',
            'Gasto Max I+D' => 'Gasto Máximo I+D',
            'Trabajos inmovilizado' => 'Trabajos inmovilizado'
        ];
        $variables2 = [
            'Fijo' => 'Fijo',
            'Presupuesto Total del proyecto' => 'Presupuesto Total del proyecto',
            'Presupuesto Mínimo de la ayuda' => 'Presupuesto mínimo de la ayuda',
            'Presupuesto Máximo de la ayuda' => 'Presupuesto Máximo de la ayuda',
            'Gastos Anuales' => 'Gastos Anuales',
            'Presupuesto del proyecto' => 'Presupuesto del proyecto',
            'Fondos propios' => 'Fondos propios',
            'Circulante' => 'Circulante',
            'Beneficios reales' => 'Beneficios reales',
            'Margen de endeudamiento' => 'Margen de endeudamiento'
        ];

        $convocatorias = \App\Models\Ayudas::all()->pluck('Titulo','id')->toArray();

        return view('admin.condicionesfinancieras.editar', [
            'colors' => $colors,
            'condicion' => $condicion,
            'variables' => $variables,
            'variables2' => $variables2,
            'convocatorias' => $convocatorias,
            'return' => null
        ]);

    }

    public function editCondicionFinanciera(Request $request){

        try{
            $condicion = \App\Models\CondicionesFinancieras::find($request->get('id'));
            if(!$condicion){
                return redirect()->back()->withErrors('Error al actualizar la condicion financiera');
            }
            $condicion->var1 = $request->get('var1');
            $condicion->condicion = $request->get('condicion');
            $condicion->var2 = $request->get('var2');
            $condicion->comentario_cumple = ($request->get('comentario_cumple') !== null) ? $request->get('comentario_cumple') : null;
            $condicion->color_cumple = ($request->get('color_cumple') !== 'no') ? $request->get('color_cumple') : null;
            $condicion->comentario_incumple = $request->get('comentario_incumple');
            $condicion->color_incumple = ($request->get('color_incumple') !== 'no') ? $request->get('color_incumple') : null;
            $condicion->coeficiente = $request->get('coeficiente');
            $condicion->orden = $request->get('orden');
            $condicion->valor = ($request->get('valor') !== null) ? $request->get('valor') : null;
            $condicion->link = ($request->get('link') !== null) ? $request->get('link') : null;
            if($request->get('todasconvocatorias') !== null){
                $condicion->todasconvocatorias = 1;
                $condicion->idsconvocatorias = null;
            }else{
                $condicion->todasconvocatorias = 0;
                $condicion->idsconvocatorias = json_encode($request->get('idsconvocatorias'));;
            }

            $condicion->save();

        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('Error al actualizar la condicion financiera');
        }

        return redirect()->back()->withSuccess('Condicion financiera actualizada');
    }

    public function borrarCondicionFinanciera(Request $request){

        try{
            \App\Models\CondicionesFinancieras::find($request->get('id'))->delete();
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido borrar la condición.');
        }

        return redirect()->back()->withSuccess('La condición se ha borrado de la base de datos.');
    }

    public function crearCondicionFinanciera(){

        $convocatorias = \App\Models\Ayudas::all()->pluck('Titulo','id')->toArray();
        
        $colors = [
            'danger' => 'Rojo',
            'success' => 'Verde',
            'orange' => 'Naranja',
            'no' => 'No mostrar'
        ];

        $variables = [
            'Gastos Anuales' => 'Gastos Anuales',
            'Presupuesto del proyecto' => 'Presupuesto del proyecto',
            'Fondos propios' => 'Fondos propios',
            'Circulante' => 'Circulante',
            'Beneficios reales' => 'Beneficios reales',
            'Margen de endeudamiento' => 'Margen de endeudamiento',
            'Gasto Medio I+D' => 'Gasto Medio I+D',
            'Gasto Min I+D' => 'Gasto Mínimo I+D',
            'Gasto Max I+D' => 'Gasto Máximo I+D',
            'Trabajos inmovilizado' => 'Trabajos inmovilizado'
        ];
        $variables2 = [
            'Fijo' => 'Fijo',
            'Gastos Anuales' => 'Gastos Anuales',
            'Presupuesto Total del proyecto' => 'Presupuesto Total del proyecto',
            'Presupuesto Mínimo de la ayuda' => 'Presupuesto mínimo de la ayuda',
            'Presupuesto Máximo de la ayuda' => 'Presupuesto Máximo de la ayuda',
            'Gastos Anuales' => 'Gastos Anuales',
            'Presupuesto del proyecto' => 'Presupuesto del proyecto',
            'Fondos propios' => 'Fondos propios',
            'Circulante' => 'Circulante',
            'Beneficios reales' => 'Beneficios reales',
            'Margen de endeudamiento' => 'Margen de endeudamiento'
        ];


        return view('admin.condicionesfinancieras.crear', [
            'colors' => $colors,
            'convocatorias' => $convocatorias,
            'variables' => $variables,
            'variables2' => $variables2,
        ]);
    }

}

