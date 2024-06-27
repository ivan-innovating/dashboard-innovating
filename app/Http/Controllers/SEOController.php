<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use stdClass;

class SEOController extends Controller
{
    //
    const EMPRESAS = "empresas";
    const AYUDAS = "ayudas";

    public function index(){

        $paginas = \App\Models\SEOPages::orderBy('updated_at', 'DESC')->where('page_type', 'ayudas')->get();

        return view('seo.index',[           
            'paginas' => $paginas
        ]);

    }

    public function indexCompanies(){
        $paginas = \App\Models\SEOPages::orderBy('updated_at', 'DESC')->where('page_type', 'empresas')->get();

        return view('seo.index-companies',[           
            'paginas' => $paginas
        ]);
    }

    public function create(){

        $ccaas = \App\Models\Ccaa::orderBy('Nombre', 'ASC')->get();
        $intereses = \App\Models\Intereses::where('Defecto', 'true')->orderBy('Nombre', 'ASC')->get();
        $fondos = \App\Models\Fondos::where('status', 1)->get();

        //dump($intereses);

        return view('seo.create',[           
            'ccaas' => $ccaas,
            'intereses' => $intereses,
            'fondos' => $fondos
        ]);

    }

    public function createCompanies(){

        $ccaas = \App\Models\Ccaa::orderBy('Nombre', 'ASC')->get();
        $intereses = \App\Models\Intereses::where('Defecto', 'true')->orderBy('Nombre', 'ASC')->get();
        $trls = \App\Models\Trl::all();

        //dump($intereses);

        return view('seo.create-company',[           
            'ccaas' => $ccaas,
            'intereses' => $intereses,
            'trls' => $trls
        ]);

    }


    public function createBlock(){

        $ccaas = \App\Models\Ccaa::orderBy('Nombre', 'ASC')->get();
        $intereses = \App\Models\Intereses::where('Defecto', 'true')->orderBy('Nombre', 'ASC')->get();
        $fondos = \App\Models\Fondos::where('status', 1)->get();
        //dump($intereses);

        return view('seo.create-block',[           
            'ccaas' => $ccaas,
            'intereses' => $intereses,
            'fondos' => $fondos
        ]);

    }

    public function edit($id){

        if($id === null || $id == 0){
            return abort(404);
        }

        $pagina = \App\Models\SEOPages::find($id);

        if($pagina === null){
            return abort(404);
        }

        $ccaas = \App\Models\Ccaa::orderBy('Nombre', 'ASC')->get();
        $intereses = \App\Models\Intereses::where('Defecto', 'true')->orderBy('Nombre', 'ASC')->get();
        $fondos = \App\Models\Fondos::where('status', 1)->get();

        $ayudas = collect(null);

        foreach(json_decode($pagina->resultados, true) as $resultado){            
            $ayuda = \App\Models\Ayudas::find($resultado['id']);            
            if(isset($ayuda) && $ayuda !== null){
                $ayuda->visibilidad = $resultado['visibilidad'];
                $ayudas->push($ayuda);
            }
        }

        return view('seo.edit',[           
            'pagina' => $pagina,
            'ccaas' => $ccaas,
            'intereses' => $intereses,
            'fondos' => $fondos,
            'ayudas' => $ayudas
        ]);

    }

    public function editCompany($id){

        if($id === null || $id == 0){
            return abort(404);
        }

        $pagina = \App\Models\SEOPages::find($id);

        if($pagina === null){
            return abort(404);
        }

        $ccaas = \App\Models\Ccaa::orderBy('Nombre', 'ASC')->get();
        $intereses = \App\Models\Intereses::where('Defecto', 'true')->orderBy('Nombre', 'ASC')->get();
        $trls = \App\Models\Trl::all();

        $empresas = collect(null);

        foreach(json_decode($pagina->resultados, true) as $resultado){            
            $empresa = \App\Models\Entidad::where('CIF', $resultado['id'])->first();            
            if(isset($empresa) && $empresa !== null){
                $empresa->visibilidad = $resultado['visibilidad'];
                $empresas->push($empresa);
            }
        }

        return view('seo.edit-company',[           
            'pagina' => $pagina,
            'ccaas' => $ccaas,
            'intereses' => $intereses,
            'trls' => $trls,
            'empresas' => $empresas
        ]);

    }


    public function getSeoAyudas(Request $request){

        if($request->get('ccaa') === null && $request->get('area') === null && $request->get('tipo') === null && $request->get('fondos') === null && $request->get('financiacion') === null){
            return response()->json("");
        }

        $ayudas = \App\Models\Ayudas::where('Publicada', 1)->orderBy('Estado', 'ASC')->get();

        if($request->get('ccaa') !== null){
            $ayudas = collect($ayudas)->where('Ambito', 'Comunidad Autónoma');
            $ccaa = $request->get('ccaa');
            $ayudas = $ayudas->filter(function($ayuda) use($ccaa){
                return (count(array_intersect(json_decode($ayuda->Ccaas,false, 512, JSON_UNESCAPED_UNICODE), [$ccaa])) > 0) ? true : false;
            });
        }

        if($request->get('area') !== null){
            $ayudas = collect($ayudas)->whereNotNull('PerfilFinanciacion');
            $interes = \App\Models\Intereses::where('Nombre', $request->get('area'))->first();
            $id = $interes->Id_zoho;         
            $ayudas = $ayudas->filter(function($ayuda) use($id){
                if(json_decode($ayuda->PerfilFinanciacion) !== null){
                    $result = array_intersect([$id], json_decode($ayuda->PerfilFinanciacion, true));
                    return (!empty($result)) ? true: false;   
                }
            });
        }

        if($request->get('tipo') !== null){
            $tipo = $request->get('tipo');
            $ayudas = $ayudas->filter(function($ayuda) use($tipo){
                if(json_decode($ayuda->Categoria) !== null){
                    $result = array_intersect([$tipo], json_decode($ayuda->Categoria, true));
                    return (!empty($result)) ? true: false;  
                }
            });
        }

        if($request->get('fondos') !== null){
            $fondos = $request->get('fondos');
            $ayudas = $ayudas->filter(function($ayuda) use($fondos){
                if(json_decode($ayuda->FondosEuropeos) !== null){
                    $result = array_intersect([$fondos], json_decode($ayuda->FondosEuropeos, true));
                    return (!empty($result)) ? true: false;  
                }
            });
        }

        if($request->get('financiacion') !== null){
            $financiacion = $request->get('financiacion');
            $ayudas = $ayudas->filter(function($ayuda) use($financiacion){
                if(json_decode($ayuda->TipoFinanciacion) !== null){
                    $result = array_intersect([$financiacion], json_decode($ayuda->TipoFinanciacion, true));
                    return (!empty($result)) ? true: false;  
                }
            });
        }

        if($ayudas->isNotEmpty()){
            $response = array();
            $i = 0;
            foreach($ayudas as $data){
                $response[$i]['id_ayuda'] = (string)$data->id;
                $response[$i]['Nombre'] = $data->Acronimo." ".$data->Titulo;
                $response[$i]['Visible'] = 1;
                $response[$i]['Ccaa'] = $request->get('ccaa');
                $response[$i]['Intereses'] = $request->get('area');
                $response[$i]['Categoria'] = $request->get('tipo');
                $i++;
            }

            return response()->json($response);
        }


        return response()->json("");
    }

    public function getSeoEmpresas(Request $request){


        if($request->get('comunidad') === null && $request->get('categoria') === null && $request->get('trlmax') === null  && $request->get('ayudas') === null && $request->get('patentes') === null){
            return response()->json("");
        }

        $empresas = getElasticCompanies("", $request, 1, "empresas", 200);

        if(!empty($empresas->data)){
            $response = array();
            $i = 0;
            foreach($empresas->data as $empresa){
                $response[$i]['CIF'] = $empresa->NIF;
                $response[$i]['Nombre'] = $empresa->Nombre;
                $response[$i]['Visible'] = 1;
                $response[$i]['Ccaa'] = $empresa->ComunidadAutonoma[0];
                $response[$i]['Trl'] = $empresa->TRL;
                $response[$i]['Categoria'] = $empresa->CategoriaEmpresa;
                if($request->get('ayudas') !== null && $request->get('ayudas') != ""){
                    $response[$i]['NumAyudas'] = $request->get('ayudas');
                }else{
                    $response[$i]['NumAyudas'] = null;
                }
                if($request->get('patentes') !== null && $request->get('patentes') != ""){
                    $response[$i]['NumPatentes'] = $request->get('patentes');
                }else{
                    $response[$i]['NumPatentes'] = null;
                }
                $i++;
            }

            return response()->json($response);
        }

        return response()->json("");

    }

    public function save(Request $request){

        $seopage = new \App\Models\SEOPages();

        try{

            $url = "ayudas-subvenciones-innovacion";
            if($request->get('ccaa') !== null){
                if(strripos($request->get('ccaa'),"/") !== false){
                    $string = str_replace("/","-",$request->get('ccaa'));
                }else{
                    $string = $request->get('ccaa');
                }
                $url .= "/".seo_quitar_tildes($string);                
            }

            if($request->get('area') !== null){  
                if(strripos($request->get('area'),"/") !== false){
                    $string = str_replace("/","-",$request->get('area'));
                }else{
                    $string = $request->get('area');
                }
                $url .= "/".seo_quitar_tildes($string); 
            }

            if($request->get('tipo') !== null){
                if(strripos($request->get('tipo'),"/") !== false){
                    $string = "empresas_".str_replace("/","-",$request->get('tipo'));
                }else{
                    $string = "empresas_".$request->get('tipo');
                }
                $url .= "/".seo_quitar_tildes($string); 
            }

            if($request->get('sector') !== null){
                if(strripos($request->get('sector'),"/") !== false){
                    $string = str_replace("/","-",$request->get('sector'));
                }else{
                    $string = $request->get('sector');
                }
                $url .= "/".seo_quitar_tildes($string); 
            }

            if($request->get('fondos') !== null){
                $fondo = \App\Models\Fondos::find($request->get('fondos'));
                if(strripos($fondo->nombre,"/") !== false){
                    $string = str_replace("/","-",$fondo->nombre);
                }else{
                    $string = $fondo->nombre;
                }
                $url .= "/fondos-".seo_quitar_tildes($string);                
            }

            if($request->get('financiacion') !== null){
                if(strripos($request->get('financiacion'),"/") !== false){
                    $string = str_replace("/","-",$request->get('financiacion'));
                }else{
                    $string = $request->get('financiacion');
                }
                $url .= "/financiacion-".seo_quitar_tildes($string);                
            }

            $url = preg_replace('#[^\pL\pN./-]+#', "-", $url);

            $checkpage = \App\Models\SEOPages::where('url', $url)->first();
            if($checkpage !== null){
                return redirect()->back()->withErrors('Ya existe una pagina con esos filtros');
            }

            $seopage->url = mb_strtolower($url);
            $seopage->creator = Auth::user()->name;
            $seopage->creator_id = Auth::user()->id;
            $seopage->page_type = self::AYUDAS;
            $seopage->tipo = ($request->get('tipo') !== null) ? $request->get('tipo') : null;
            $seopage->ccaa = ($request->get('ccaa') !== null) ? $request->get('ccaa') : null;
            $seopage->sector = ($request->get('sector') !== null) ? $request->get('sector') : null;
            $seopage->area = ($request->get('area') !== null) ? $request->get('area') : null;     
            $seopage->fondos = ($request->get('fondos') !== null) ? $request->get('fondos') : null;
            $seopage->financiacion = ($request->get('financiacion') !== null) ? $request->get('financiacion') : null;      
            $seopage->url = mb_strtolower($url);
            $seopage->title = $request->get('title');
            $seopage->description = $request->get('description');
            $seopage->h1 = $request->get('h1');
            $seopage->h2 = $request->get('h2');
            $seopage->h3 =  ($request->get('h3') !== null) ? $request->get('h3') : null;
            $seopage->texto_introduccion =  $request->get('texto_introduccion');
            $resultados = array();
            foreach($request->get('data') as $key => $data){
                if($request->get('visibilidad-'.$data) !== null){
                    array_push($resultados, ['id' => $data, 'visibilidad' => 1]);
                }else{
                    array_push($resultados, ['id' => $data, 'visibilidad' => 0]);
                }
            }

            $seopage->resultados = json_encode($resultados); 
            $seopage->save();

            return redirect()->route('seo-pages')->withSuccess('Se ha creado la página SEO correctamente');

        }catch(Exception $e){
            log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido crear la página, revisa que todos los datos son correctos');
        }

    }

    public function saveBlock(Request $request){        

        $bloque = $request->get('bloque');

        if($bloque == "ccaa"){
            $filtros = \App\Models\Ccaa::all();
        }elseif($bloque == "intereses"){
            $filtros = \App\Models\Intereses::where('Defecto', 'true')->get();
        }elseif($bloque == "categoria"){    
            $filtros = collect(null);
            $filtros->push(['Nombre' => 'Micro'],['Nombre' => 'Pequeña'],['Nombre' => 'Mediana'],['Nombre' => 'Grande']);
        }elseif($bloque == "fondos"){  
            $filtros = \App\Models\Fondos::where('status',1)->get();
        }elseif($bloque == "financiacion"){  
            $filtros = collect(null);
            $filtros->push(['Nombre' => 'Fondo perdido'],['Nombre' => 'Crédito']);
        }else{
            return redirect()->back()->withErrors('No se ha seleccionado un filtro valido');
        }

        $i = 0;

        try{

            if($filtros->isNotEmpty()){

                foreach($filtros as $filtro){

                    if($bloque == "ccaa"){
                        $ayudas =  \App\Models\Ayudas::where('Ccaas', 'LIKE', '%'.$filtro->Nombre.'%')->orderBy('Estado', 'ASC')->get();
                        $name = " en ".$filtro->Nombre;
                    }elseif($bloque == "intereses"){                        
                        $ayudas =  \App\Models\Ayudas::where('PerfilFinanciacion', 'LIKE', '%'.$filtro->Id_zoho.'%')->orderBy('Estado', 'ASC')->get();
                        $name = "para el Área de ".$filtro->Nombre;
                    }elseif($bloque == "categoria"){    
                        $ayudas =  \App\Models\Ayudas::all();
                        $tipo = $filtro['Nombre'];
                        $ayudas = $ayudas->filter(function($ayuda) use($tipo){
                            if(json_decode($ayuda->Categoria) !== null){
                                $result = array_intersect([$tipo], json_decode($ayuda->Categoria, true));
                                return (!empty($result)) ? true: false;  
                            }
                        });
                        $name = "para empresas con tamaño ". $filtro['Nombre'];
                    }elseif($bloque == "fondos"){    
                        $ayudas =  \App\Models\Ayudas::all();
                        $fondo = $filtro->id;
                        $ayudas = $ayudas->filter(function($ayuda) use($fondo){
                            if(json_decode($ayuda->FondosEuropeos) !== null){
                                $result = array_intersect([strval($fondo)], json_decode($ayuda->FondosEuropeos, true));
                                return (!empty($result)) ? true: false;  
                            }
                        });
                        $name = "con fondos del tipo ". $filtro->nombre;
                    }elseif($bloque == "financiacion"){    
                        $ayudas =  \App\Models\Ayudas::all();
                        $financiacion = $filtro['Nombre'];
                        $ayudas = $ayudas->filter(function($ayuda) use($financiacion){
                            if(json_decode($ayuda->TipoFinanciacion) !== null){
                                $result = array_intersect([$financiacion], json_decode($ayuda->TipoFinanciacion, true));
                                return (!empty($result)) ? true: false;  
                            }
                        });
                        $name = "con financiacíon del tipo ". $filtro['Nombre'];
                    }else{
                        return redirect()->back()->withErrors('No se ha seleccionado un filtro valido');
                    }
                    
                    $url = "ayudas-subvenciones-innovacion";
                    if($bloque == "categoria"){   
                        if(strripos($filtro['Nombre'],"/") !== false){
                            $string = "empresas_".str_replace("/","-",$filtro['Nombre']);
                        }else{
                            $string = "empresas_".$filtro['Nombre'];
                        }
                        $url .= "/".seo_quitar_tildes($string); 
                    }elseif($bloque == "fondos"){    
                        if(strripos($filtro->nombre,"/") !== false){
                            $string = str_replace("/","-",$filtro->nombre);
                        }else{
                            $string = $filtro->nombre;
                        }
                        $url .= "/fondos-".seo_quitar_tildes($string);   
                    }elseif($bloque == "financiacion"){    
                        if(strripos($filtro['Nombre'],"/") !== false){
                            $string = str_replace("/","-",$filtro['Nombre']);
                        }else{
                            $string = $filtro['Nombre'];
                        }
                        $url .= "/financiacion-".seo_quitar_tildes($string);    
                    }else{
                        if(strripos($filtro->Nombre,"/") !== false){
                            $string = str_replace("/","-",$filtro->Nombre);
                        }else{
                            $string = $filtro->Nombre;
                        }
                        $url .= "/".seo_quitar_tildes($string);
                    }
                    
                    $url = preg_replace('#[^\pL\pN./-]+#', "-", $url);

                    $checkpage = \App\Models\SEOPages::where('url', $url)->first();
                    if($checkpage !== null){
                        continue;
                    }

                    $seopage = new \App\Models\SEOPages();
                    $seopage->creator = Auth::user()->name;
                    $seopage->creator_id = Auth::user()->id;
                    $seopage->page_type = self::AYUDAS;
                    $seopage->tipo = ($bloque == "categoria") ? $filtro['Nombre'] : null;
                    $seopage->ccaa = ($bloque == "ccaa") ? $filtro->Nombre : null;
                    $seopage->sector = null;
                    $seopage->area = ($bloque == "intereses") ? $filtro->Nombre : null;
                    $seopage->fondos = ($bloque == "fondos") ? $filtro->id : null;
                    $seopage->financiacion = ($bloque == "financiacion") ? $filtro['Nombre'] : null;                    
                    $seopage->url = mb_strtolower($url);
                    $seopage->title = $request->get('title')." ".$name;
                    $seopage->description = $request->get('description')." ".$name;
                    $seopage->h1 = $request->get('h1')." ".$name;
                    $seopage->h2 = $request->get('h2')." ".$name;
                    $seopage->h3 = ($request->get('h3') !== null) ? $request->get('h3')." ".$name : null;
                    $seopage->texto_introduccion =  $request->get('texto_introduccion');
                    $resultados = array();
                    if($ayudas->isNotEmpty()){
                        foreach($ayudas as $key => $data){                            
                            array_push($resultados, ['id' => $data->id, 'visibilidad' => 1]);
                        }
                    }
                    $i++;
                    $seopage->resultados = json_encode($resultados); 
                    $seopage->save();        

                }
            }

        }catch(Exception $e){
            log::error($e->getMessage());
            return redirect()->back()->withErrors('No se han podido crear las páginas SEO, revisa que todos los datos son correctos');
        }

        return redirect()->route('seo-pages')->withSuccess('Se han creado las páginas SEO correctamente, total páginas nuevas: '.$i);

    }

    public function saveCompanyPage(Request $request){

        $seopage = new \App\Models\SEOPages();

        try{

            $url = "empresas";

            if($request->get('comunidad') !== null){
                $comunidad = \App\Models\Ccaa::find($request->get('comunidad'));
                if(strripos($comunidad->Nombre,"/") !== false){
                    $string = str_replace("/","-",$comunidad->Nombre);
                }else{
                    $string = $comunidad->Nombre;
                }
                $url .= "/".seo_quitar_tildes($string);                
            }

            if($request->get('categoria') !== null){
                if(strripos($request->get('categoria'),"/") !== false){
                    $string = "tamaño_".str_replace("/","-",$request->get('categoria'));
                }else{
                    $string = "tamaño_".$request->get('categoria');
                }
                $url .= "/".seo_quitar_tildes($string); 
            }

            if($request->get('trlmax') !== null){
                if(strripos($request->get('trlmax'),"/") !== false){
                    $string = "trl_".str_replace("/","-",$request->get('trlmax'));
                }else{
                    $string = "trl_".$request->get('trlmax');
                }
                $url .= "/".seo_quitar_tildes($string); 
            }

            if($request->get('ayudas') !== null){
                if($request->get('patentes') == "0"){
                    $string = "sin_ayudas_recibidas";            
                }else{
                    $string = "con_mas_de".$request->get('ayuda')."_ayudas_recibidas";            
                }
                $url .= "/".seo_quitar_tildes($string); 
            }

            if($request->get('patentes') !== null){
                if($request->get('patentes') == "0"){
                    $string = "sin_patentes_recibidas";  
                }else{
                    $string = "con_patentes_recibidas";  
                }          
                $url .= "/".seo_quitar_tildes($string); 
            }

            $url = preg_replace('#[^\pL\pN./-]+#', "-", $url);

            $checkpage = \App\Models\SEOPages::where('url', $url)->first();
            if($checkpage !== null){
                return redirect()->back()->withErrors('Ya existe una pagina con esos filtros');
            }

            $seopage->url = mb_strtolower($url);
            $seopage->creator = Auth::user()->name;
            $seopage->creator_id = Auth::user()->id;
            $seopage->page_type = self::EMPRESAS;
            $seopage->tipo = ($request->get('categoria') !== null) ? $request->get('categoria') : null;
            $seopage->ccaa = ($request->get('comunidad') !== null) ? $request->get('comunidad') : null;
            $seopage->sector = ($request->get('trlmax') !== null) ? $request->get('trlmax') : null;
            $seopage->total_ayudas = ($request->get('ayudas') !== null) ? $request->get('ayudas') : null;
            $seopage->total_patentes = ($request->get('patentes') !== null) ? $request->get('patentes') : null;
            $seopage->area = null;     
            $seopage->fondos = null;
            $seopage->financiacion = null;                  
            $seopage->url = mb_strtolower($url);
            $seopage->title = $request->get('title');
            $seopage->description = $request->get('description');
            $seopage->h1 = $request->get('h1');
            $seopage->h2 = $request->get('h2');
            $seopage->h3 =  ($request->get('h3') !== null) ? $request->get('h3') : null;
            $seopage->texto_introduccion =  $request->get('texto_introduccion');
            $resultados = array();
            foreach($request->get('data') as $key => $data){
                if($request->get('visibilidad-'.$data) !== null){
                    array_push($resultados, ['id' => $data, 'visibilidad' => 1]);
                }else{
                    array_push($resultados, ['id' => $data, 'visibilidad' => 0]);
                }
            }

            $seopage->resultados = json_encode($resultados); 
            $seopage->save();

            return redirect()->route('seo-company-pages')->withSuccess('Se ha creado la página SEO correctamente');

        }catch(Exception $e){
            log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido crear la página, revisa que todos los datos son correctos');
        }

    }

    public function update(Request $request){

        $seopage = \App\Models\SEOPages::find($request->get('id'));

        if($request->get('id') === null || $seopage === null){
            return abort(404);
        }

        try{

            if($seopage->url != $request->get('url')){
                $url = $request->get('url');
            }else{
                $url = "ayudas-subvenciones-innovacion";
                if($request->get('ccaa') !== null){
                    if(strripos($request->get('ccaa'),"/") !== false){
                        $string = str_replace("/","-",$request->get('ccaa'));
                    }else{
                        $string = $request->get('ccaa');
                    }
                    $url .= "/".seo_quitar_tildes($string);  
                }
                if($request->get('area') !== null){   
                    if(strripos($request->get('area'),"/") !== false){
                        $string = str_replace("/","-",$request->get('area'));
                    }else{
                        $string = $request->get('area');
                    }
                    $url .= "/".seo_quitar_tildes($string); 
                }
                if($request->get('tipo') !== null){
                    if(strripos($request->get('tipo'),"/") !== false){
                        $string = "empresas_".str_replace("/","-",$request->get('tipo'));
                    }else{
                        $string = "empresas_".$request->get('tipo');
                    }
                    $url .= "/".seo_quitar_tildes($string); 
                }
                if($request->get('sector') !== null){
                    if(strripos($request->get('sector'),"/") !== false){
                        $string = str_replace("/","-",$request->get('sector'));
                    }else{
                        $string = $request->get('sector');
                    }
                    $url .= "/".seo_quitar_tildes($string); 
                }   

                if($request->get('fondos') !== null){
                    $fondo = \App\Models\Fondos::find($request->get('fondos'));
                    if(strripos($fondo->nombre,"/") !== false){
                        $string = str_replace("/","-",$fondo->nombre);
                    }else{
                        $string = $fondo->nombre;
                    }
                    $url .= "/fondos-".seo_quitar_tildes($string);                
                }
    
                if($request->get('financiacion') !== null){
                    if(strripos($request->get('financiacion'),"/") !== false){
                        $string = str_replace("/","-",$request->get('financiacion'));
                    }else{
                        $string = $request->get('financiacion');
                    }
                    $url .= "/financiacion-".seo_quitar_tildes($string);                
                }

            }

            $url = preg_replace('#[^\pL\pN./-]+#', "-", $url);  
            
            if($seopage->url != $request->get('old_url')){                
                $checkpage = \App\Models\SEOPages::where('url', $url)->first();
                if($checkpage !== null) {
                    return redirect()->back()->withErrors('Ya existe una pagina con esos filtros');
                }
            }

            $seopage->editor = Auth::user()->name;
            $seopage->editor_id = Auth::user()->id;
            $seopage->tipo = ($request->get('tipo') !== null) ? $request->get('tipo') : null;
            $seopage->ccaa = ($request->get('ccaa') !== null) ? $request->get('ccaa') : null;
            $seopage->sector = ($request->get('sector') !== null) ? $request->get('sector') : null;
            $seopage->area = ($request->get('area') !== null) ? $request->get('area') : null;     
            $seopage->fondos = ($request->get('fondos') !== null) ? $request->get('fondos') : null;
            $seopage->financiacion = ($request->get('financiacion') !== null) ? $request->get('financiacion') : null;
               
            $seopage->url = mb_strtolower($url);
            $seopage->title = $request->get('title');
            $seopage->description = $request->get('description');
            $seopage->h1 = $request->get('h1');
            $seopage->h2 = $request->get('h2');
            $seopage->h3 = ($request->get('h3') !== null) ? $request->get('h3') : null;
            $seopage->texto_introduccion =  $request->get('texto_introduccion');
            $resultados = array();
            foreach($request->get('data') as $key => $data){
                if($request->get('visibilidad-'.$data) !== null){
                    array_push($resultados, ['id' => $data, 'visibilidad' => 1]);
                }else{
                    array_push($resultados, ['id' => $data, 'visibilidad' => 0]);
                }
            }

            $seopage->resultados = json_encode($resultados); 
            $seopage->visibilidad = ($request->get('visibilidad') !== null) ? 1 : 0;
            $seopage->view_calendar = ($request->get('view_calendar') !== null) ? 1 : 0;
            $seopage->save();

            return redirect()->route('seo-pages')->withSuccess('Se ha actualizado la página SEO correctamente');

        }catch(Exception $e){
            log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido crear la página, revisa que todos los datos son correctos');
        }

    }

    public function updateCompanyPage(Request $request){

        $seopage = \App\Models\SEOPages::find($request->get('id'));

        if($request->get('id') === null || $seopage === null){
            return abort(404);
        }
        try{

            if($seopage->url != $request->get('url')){
                $url = $request->get('url');
            }else{
                $url = "empresas";

                if($request->get('comunidad') !== null){
                    $comunidad = \App\Models\Ccaa::find($request->get('comunidad'));
                    if(strripos($comunidad->Nombre,"/") !== false){
                        $string = str_replace("/","-",$comunidad->Nombre);
                    }else{
                        $string = $comunidad->Nombre;
                    }
                    $url .= "/".seo_quitar_tildes($string);                
                }

                if($request->get('categoria') !== null){
                    if(strripos($request->get('categoria'),"/") !== false){
                        $string = "tamaño_".str_replace("/","-",$request->get('categoria'));
                    }else{
                        $string = "tamaño_".$request->get('categoria');
                    }
                    $url .= "/".seo_quitar_tildes($string); 
                }

                if($request->get('trlmax') !== null){
                    if(strripos($request->get('trlmax'),"/") !== false){
                        $string = "trl_".str_replace("/","-",$request->get('trlmax'));
                    }else{
                        $string = "trl_".$request->get('trlmax');
                    }
                    $url .= "/".seo_quitar_tildes($string); 
                }

                if($request->get('ayudas') !== null){
                    if($request->get('ayudas') == "0"){
                        $string = "sin_ayudas_recibidas";            
                    }else{
                        $string = "con_mas_de_".$request->get('ayudas')."_ayudas_recibidas";            
                    }
                    $url .= "/".seo_quitar_tildes($string); 
                }

                if($request->get('patentes') !== null){
                    if($request->get('patentes') == "0"){
                        $string = "sin_patentes_recibidas";  
                    }else{
                        $string = "con_patentes_recibidas";  
                    }          
                    $url .= "/".seo_quitar_tildes($string); 
                }
            }

            $url = preg_replace('#[^\pL\pN./-]+#', "-", $url);

            if($seopage->url != $request->get('old_url')){                
                $checkpage = \App\Models\SEOPages::where('url', $url)->first();
                if($checkpage !== null) {
                    return redirect()->back()->withErrors('Ya existe una pagina con esos filtros');
                }
            }

            $seopage->editor = Auth::user()->name;
            $seopage->editor_id = Auth::user()->id;
            $seopage->page_type = self::EMPRESAS;
            $seopage->tipo = ($request->get('categoria') !== null) ? $request->get('categoria') : null;
            $seopage->ccaa = ($request->get('comunidad') !== null) ? $request->get('comunidad') : null;
            $seopage->sector = ($request->get('trlmax') !== null) ? $request->get('trlmax') : null;
            $seopage->total_ayudas = ($request->get('ayudas') !== null) ? $request->get('ayudas') : null;
            $seopage->total_patentes = ($request->get('patentes') !== null) ? $request->get('patentes') : null;
            $seopage->area = null;     
            $seopage->fondos = null;
            $seopage->financiacion = null;                  
            $seopage->url = mb_strtolower($url);
            $seopage->title = $request->get('title');
            $seopage->description = $request->get('description');
            $seopage->h1 = $request->get('h1');
            $seopage->h2 = $request->get('h2');
            $seopage->h3 =  ($request->get('h3') !== null) ? $request->get('h3') : null;
            $seopage->texto_introduccion =  $request->get('texto_introduccion');
            $resultados = array();
            foreach($request->get('data') as $key => $data){
                if($request->get('visibilidad-'.$data) !== null){
                    array_push($resultados, ['id' => $data, 'visibilidad' => 1]);
                }else{
                    array_push($resultados, ['id' => $data, 'visibilidad' => 0]);
                }
            }

            $seopage->resultados = json_encode($resultados); 
            $seopage->save();

            return redirect()->route('seo-company-pages')->withSuccess('Se ha creado la página SEO correctamente');

        }catch(Exception $e){
            log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido crear la página, revisa que todos los datos son correctos');
        }
    }

    public function view(Request $request){

        $seopage = \App\Models\SEOPages::where('url', $request->route('url'))->first();

        if($seopage === null){
            return abort(404);
        }
        
        $ayudas = collect(null);
        $empresas = collect(null);

        if($seopage->page_type == "ayudas"){
            if(json_decode($seopage->resultados, true) !== null && !empty(json_decode($seopage->resultados, true))){
                foreach(json_decode($seopage->resultados, true) as $resultado){
                    if($resultado['visibilidad'] == 1){
                        $ayuda = \App\Models\Ayudas::find($resultado['id']);
                        if(isset($ayuda) && $ayuda !== null){
                            $ayudas->push($ayuda);
                        }                    
                    }
                }
            }
            $checkSimilarPages = collect(null);
            if($ayudas->isEmpty()){
                if($seopage->ccaa !== null && $seopage->area !== null && $seopage->tipo === null){
                    $checkSimilarPages = \App\Models\SEOPages::where('ccaa', $seopage->ccaa)->where('area', $seopage->area)->where('page_type', 'ayudas')->get();
                }elseif($seopage->ccaa !== null && $seopage->area === null && $seopage->tipo !== null){
                    $checkSimilarPages = \App\Models\SEOPages::where('ccaa', $seopage->ccaa)->where('tipo', $seopage->tipo)->where('page_type', 'ayudas')->get();
                }elseif($seopage->ccaa !== null && $seopage->area === null && $seopage->tipo === null){
                    $checkSimilarPages = \App\Models\SEOPages::where('ccaa', $seopage->ccaa)->get();
                }elseif($seopage->ccaa === null && $seopage->area !== null && $seopage->tipo !== null){
                    $checkSimilarPages = \App\Models\SEOPages::where('area', $seopage->area)->where('tipo', $seopage->tipo)->where('page_type', 'ayudas')->first();
                }elseif($seopage->ccaa === null && $seopage->area !== null && $seopage->tipo === null){
                    $checkSimilarPages = \App\Models\SEOPages::where('area', $seopage->area)->where('page_type', 'ayudas')->first();
                }elseif($seopage->ccaa === null && $seopage->area === null && $seopage->tipo !== null){
                    $checkSimilarPages = \App\Models\SEOPages::where('tipo', $seopage->tipo)->where('page_type', 'ayudas')->first();
                }
            }

            if($ayudas->isNotEmpty()){
                foreach($ayudas as $ayuda){
                    $ayuda->extinguida = 0;
                    $ayuda->indefinida = 0;
                    $ayuda->proxima_apertura = null;
    
                    if($ayuda->convocatoria->extinguida == 1){
                        $ayuda->extinguida = 1;
                        continue;
                    }
                    
                    if($ayuda->convocatoria->es_indefinida == 1){
                        $ayuda->indefinida = 1;
                        continue;
                    }
    
                    if($ayuda->Estado == "Cerrada" && $ayuda->Fin !== null){
    
                        if($ayuda->convocatoria->mes_apertura_1 !== null && $ayuda->convocatoria->mes_apertura_2 === null && $ayuda->convocatoria->mes_apertura_3 === null){
                            $ayuda->proxima_apertura = $ayuda->convocatoria->mes_apertura_1;
                        }
    
                        if($ayuda->convocatoria->mes_apertura_1 !== null && $ayuda->convocatoria->mes_apertura_2 !== null && $ayuda->convocatoria->mes_apertura_3 === null){
    
                            if((int)$ayuda->convocatoria->mes_apertura_1 > (int)Carbon::parse($ayuda->Fin)->format('m')){
                                $ayuda->proxima_apertura = $ayuda->convocatoria->mes_apertura_1;
                            }
    
                            if((int)$ayuda->convocatoria->mes_apertura_2 > (int)Carbon::parse($ayuda->Fin)->format('m')){
                                $ayuda->proxima_apertura = $ayuda->convocatoria->mes_apertura_2;
                            }
    
                            if((int)$ayuda->convocatoria->mes_apertura_1 <= (int)Carbon::parse($ayuda->Fin)->format('m') && 
                            (int)$ayuda->convocatoria->mes_apertura_2 <= (int)Carbon::parse($ayuda->Fin)->format('m')){
                                $ayuda->proxima_apertura = $ayuda->convocatoria->mes_apertura_1;
                            }
    
                        }
    
                        if($ayuda->convocatoria->mes_apertura_1 !== null && $ayuda->convocatoria->mes_apertura_2 !== null && $ayuda->convocatoria->mes_apertura_3 !== null){
    
                            if((int)$ayuda->convocatoria->mes_apertura_1 > (int)Carbon::parse($ayuda->Fin)->format('m')){
                                $ayuda->proxima_apertura = $ayuda->convocatoria->mes_apertura_1;
                            }
    
                            if((int)$ayuda->convocatoria->mes_apertura_2 > (int)Carbon::parse($ayuda->Fin)->format('m')){
                                $ayuda->proxima_apertura = $ayuda->convocatoria->mes_apertura_2;
                            }
    
                            if((int)$ayuda->convocatoria->mes_apertura_3 > (int)Carbon::parse($ayuda->Fin)->format('m')){
                                $ayuda->proxima_apertura = $ayuda->convocatoria->mes_apertura_3;
                            }
    
                            if((int)$ayuda->convocatoria->mes_apertura_1 <= (int)Carbon::parse($ayuda->Fin)->format('m') && 
                            (int)$ayuda->convocatoria->mes_apertura_2 <= (int)Carbon::parse($ayuda->Fin)->format('m') && 
                            (int)$ayuda->convocatoria->mes_apertura_3 > (int)Carbon::parse($ayuda->Fin)->format('m')){
                                $ayuda->proxima_apertura = $ayuda->convocatoria->mes_apertura_1;
                            }
    
                        }
    
                    }
                }
            }
        }
        if($seopage->page_type == "empresas"){
            if(json_decode($seopage->resultados, true) !== null && !empty(json_decode($seopage->resultados, true))){
                foreach(json_decode($seopage->resultados, true) as $resultado){
                    if($resultado['visibilidad'] == 1){
                        $empresa = \App\Models\Entidad::where('CIF', $resultado['id'])->first();
                        if(isset($empresa) && $empresa !== null){
                            $empresa->LinkInterno = route('empresa', $empresa->uri);
                            $empresa->NIF = $empresa->CIF;
                            $empresa->Naturaleza = json_decode($empresa->naturalezaEmpresa, true);
                            $empresa->ObjetoSocial = $empresa->einforma->objetoSocial;
                            $empresa->TRL = $empresa->valorTrl;
                            $empresas->push($empresa);
                        }                    
                    }
                }
            }
            $checkSimilarPages = collect(null);
            if($empresas->isEmpty()){
                if($seopage->ccaa !== null && $seopage->sector !== null && $seopage->tipo === null){
                    $checkSimilarPages = \App\Models\SEOPages::where('ccaa', $seopage->ccaa)->where('sector', $seopage->sector)->where('page_type', 'empresas')->get();
                }elseif($seopage->ccaa !== null && $seopage->sector === null && $seopage->tipo !== null){
                    $checkSimilarPages = \App\Models\SEOPages::where('ccaa', $seopage->ccaa)->where('tipo', $seopage->tipo)->where('page_type', 'empresas')->get();
                }elseif($seopage->ccaa !== null && $seopage->sector === null && $seopage->tipo === null){
                    $checkSimilarPages = \App\Models\SEOPages::where('ccaa', $seopage->ccaa)->get();
                }elseif($seopage->ccaa === null && $seopage->sector !== null && $seopage->tipo !== null){
                    $checkSimilarPages = \App\Models\SEOPages::where('sector', $seopage->sector)->where('tipo', $seopage->tipo)->where('page_type', 'empresas')->first();
                }elseif($seopage->ccaa === null && $seopage->sector !== null && $seopage->tipo === null){
                    $checkSimilarPages = \App\Models\SEOPages::where('sector', $seopage->sector)->where('page_type', 'empresas')->first();
                }elseif($seopage->ccaa === null && $seopage->sector === null && $seopage->tipo !== null){
                    $checkSimilarPages = \App\Models\SEOPages::where('tipo', $seopage->tipo)->where('page_type', 'empresas')->first();
                }
            }
        }

        $lastnews = $this->getLastNews();
        $ccaas = getAllCcaas();
        $categories = getAllCategories();
        $intereses = \App\Models\Intereses::where('Defecto', 'true')->orderBy('Nombre', 'ASC')->get();
        $fondos = \App\Models\Fondos::where('status',1)->get();

        $ayudasConvocatorias = collect(null);
        if($ayudas->isNotEmpty()){
            $ayudasConvocatorias = getAyudasCalendario($ayudas);
        }

        $meses = new stdClass();

        if($ayudasConvocatorias->isNotEmpty()){
            #$init = Carbon::now()->subMonths(1)->format('m/y');
            #$mes_inicio = Carbon::now()->subMonths(1);
            $init = Carbon::now()->format('m/y');
            $mes_inicio = Carbon::now();
            $diff = 10 - Carbon::now()->firstOfYear()->diffInMonths($mes_inicio);
            $meses->diff = $diff;
            $meses->inicio = $mes_inicio->format('m');
            $meses->current =  Carbon::now()->format('m');
            $meses->{"0"} = $init;
            $meses->{"1"} = Carbon::now()->addMonths(1)->format('m/y');
            for ($i = 3; $i < 11; $i++) {
                $meses->{$i} = Carbon::now()->addMonths($i-1)->format('m/y');
            }
        }

        return view('seo.view',[           
            'pagina' => $seopage,
            'ayudas' => $ayudas,
            'empresas' => $empresas,
            'lastnews' => $lastnews, 
            'ccaas' => $ccaas,
            'categories' => $categories,
            'intereses' => $intereses,
            'fondos' => $fondos,
            'checkSimilarPages' => $checkSimilarPages,
            'url' => route('search'),
            'showsubheader' => 1,
            'convocatorias' => $ayudasConvocatorias,
            'meses' => $meses
        ]);

    }

    public function getLastnews(){
        $lastnews = \App\Models\Noticias::where('fecha', '>=', Carbon::now()->subdays(60))->where('status', 1)
        ->orderBy('fecha', 'desc')->orderBy('user', 'asc')->take(3)->leftJoin('convocatorias_ayudas', 'convocatorias_ayudas.id', '=', 'noticias.id_ayuda')->get();

        if($lastnews->isNotEmpty()){
            foreach($lastnews as $noticia){
                $noticiadpto = \App\Models\Organos::where('id', $noticia->id_organo)->first();
                if(!$noticiadpto){
                    $noticiadpto = \App\Models\Departamentos::where('id', $noticia->id_organo)->first();
                }
                if($noticiadpto){
                    $noticia->dpto = ($noticiadpto->Acronimo)
                    ? mb_strtolower(str_replace(" ","-", $noticiadpto->Acronimo))
                    : mb_strtolower(str_replace(" ","-", $noticiadpto->Nombre));
                    $noticia->dptourl = $noticiadpto->url;
                }
            }
        }

        return $lastnews;
    }
    
}
