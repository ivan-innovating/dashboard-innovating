@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Envío Datos Beagle</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
        <ul class="nav nav-pills" id="myTab">
            <li class="nav-item"><a class="nav-link @if(isset($totalempresas) && $totalempresas > 0) active @endif" href="#empresas" data-toggle="tab">Enviar empresas Beagle</a></li>
            <li class="nav-item"><a class="nav-link @if(isset($totalproyectos) && $totalproyectos > 0) active @endif" href="#proyectos" data-toggle="tab">Enviar proyectos Beagle</a></li>
        </ul>
		<div class="card-tools">
			<button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
			<i class="fas fa-minus"></i>
			</button>
			<button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
				<i class="fas fa-times"></i>
			</button>
		</div>
	</div>
	<div class="card-body">
		@if(session()->has('success'))
            <div class="alert alert-success">
                {{ session()->get('success') }}
            </div>
        @endif
        @if(session()->has('errors'))
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif
        <div class="tab-content">
            <!-- /.tab-pane -->
            <div class="tab-pane @if(isset($totalempresas) && $totalempresas > 0) active @endif" id="empresas">
                <form method="post" action="{{route('adminsuperadminsearch')}}">
                    @csrf
                    <input type="hidden" name="filter" value="empresas"/>
                    <div class="flex justify-center items-center xl:justify-start pr-2 pl-2 duration-350 ease-in-out">
                        <div class="flex flex-col mb-0 w-100">
                            <input type="hidden" name="filtrolastupdate" value="{{request()->get('filtrolastupdate')}}">                                                                      
                            <div class="mb-2">
                                <select name="pais[]" class="form-control form-control-sm select2" multiple data-placeholder="Selecciona país(es)">
                                    <option></option>
                                    @foreach($paises as $pais)
                                        @if(is_array(request()->get('pais')) && in_array($pais->iso2, request()->get('pais')))
                                            <option value="{{$pais->iso2}}" selected>{{$pais->Nombre_es}}</option>
                                        @elseif(is_string(request()->get('pais')) && in_array($pais->iso2, explode(",", request()->get('pais'))))
                                            <option value="{{$pais->iso2}}" selected>{{$pais->Nombre_es}}</option>
                                        @else
                                            <option value="{{$pais->iso2}}">{{$pais->Nombre_es}}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <select name="comunidad[]" class="form-control form-control-sm select2" multiple data-placeholder="Selecciona CCAA(s)">
                                    <option></option>
                                    @foreach($ccaas as $ccaa)
                                        @if(is_array(request()->get('comunidad')) && in_array($ccaa->id, request()->get('comunidad')))
                                            <option value="{{$ccaa->id}}" selected>{{$ccaa->Nombre}}</option>
                                        @elseif(is_string(request()->get('comunidad')) && in_array($ccaa->id, explode(",", request()->get('comunidad'))))
                                            <option value="{{$ccaa->id}}" selected>{{$ccaa->Nombre}}</option>
                                        @else
                                            <option value="{{$ccaa->id}}">{{$ccaa->Nombre}}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <select name="categoria" class="form-control form-control-sm select2" multiple data-placeholder="Selecciona Categoria(s)">
                                    <option value="Grande" @if(strripos(request()->get('categoria'), "Grande") !== false) selected @endif>Grande</option>
                                    <option value="Mediana" @if(strripos(request()->get('categoria'), "Mediana") !== false) selected @endif>Mediana</option>
                                    <option value="Pequeña" @if(strripos(request()->get('categoria'), "Pequeña") !== false) selected @endif>Pequeña</option>
                                    <option value="Micro" @if(strripos(request()->get('categoria'), "Micro") !== false) selected @endif>Micro</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <select name="trlmax" class="form-control form-control-sm select2" data-placeholder="Selecciona TRL máx">
                                    <option></option>
                                    <option value="9" @if(request()->get('trlmax') == "9") selected @endif> <= TRL9</option>
                                    <option value="8" @if(request()->get('trlmax') == "8") selected @endif> <= TRL8</option>
                                    <option value="7" @if(request()->get('trlmax') == "7") selected @endif> <= TRL7</option>
                                    <option value="6" @if(request()->get('trlmax') == "6") selected @endif> <= TRL6</option>
                                    <option value="5" @if(request()->get('trlmax') == "5") selected @endif> <= TRL5</option>
                                    <option value="4" @if(request()->get('trlmax') == "4") selected @endif> <= TRL4</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <select name="patentes" class="form-control form-control-sm select2" data-placeholder="Num Patentes">
                                    <option></option>
                                    <option value="0" @if(request()->get('patentes') == "0") selected @endif>Sin patentes</option>
                                    <option value="1" @if(request()->get('patentes') == "1") selected @endif>Con patentes</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <select name="ayudas" class="form-control form-control-sm select2" data-placeholder="Num Ayudas concedidas">
                                    <option></option>
                                    <option value="0" @if(request()->get('ayudas') == "0") selected @endif>{{__('Todas')}}</option>
                                    <option value="1" @if(request()->get('ayudas') == "1") selected @endif> >= 1</option>
                                    <option value="2" @if(request()->get('ayudas') == "2") selected @endif> >= 2</option>
                                    <option value="3" @if(request()->get('ayudas') == "3") selected @endif> >= 3</option>
                                    <option value="4" @if(request()->get('ayudas') == "4") selected @endif> >= 4</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <select name="ultimaayuda" class="form-control form-control-sm select2" data-placeholder="Última ayuda concedida">
                                    <option></option>
                                    <option value="1" @if(request()->get('ultimaayuda') == "1") selected @endif>{{__('Hace menos de 1 mes')}}</option>
                                    <option value="6" @if(request()->get('ultimaayuda') == "6") selected @endif>{{__('Hace menos de 6 meses')}}</option>
                                    <option value="12" @if(request()->get('ultimaayuda') == "12") selected @endif>{{__('Hace menos de 1 año')}}</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <select name="sellopyme" class="form-control form-control-sm select2" data-placeholder="Sello Pyme">
                                    <option></option>
                                    <option value="Caducado" @if(request()->get('sellopyme') == "Caducado") selected @endif>{{__('Caducado')}}</option>
                                    <option value="Vigente" @if(request()->get('sellopyme') == "Vigente") selected @endif>{{__('Vigente')}}</option>
                                    <option value="Nunca" @if(request()->get('sellopyme') == "Nunca") selected @endif>{{__('Nunca')}}</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                @if(is_array(request()->get('cooperacion')))
                                <select name="cooperacion[]" class="form-control form-control-sm select2" data-placeholder="Experiencia en Cooperación">
                                    <option></option>
                                    <option value="Nacional" @if(in_array("Nacional", request()->get('cooperacion'))) selected @endif>{{__('Nacional')}}</option>
                                    <option value="Internacional" @if(in_array("Internacional",request()->get('cooperacion'))) selected @endif>{{__('Internacional')}}</option>
                                </select>
                                @else
                                <select name="cooperacion[]" class="form-control form-control-sm select2" data-placeholder="Experiencia en Cooperación">
                                    <option></option>
                                    <option value="Nacional" @if(in_array("Nacional", explode(",",request()->get('cooperacion')))) selected @endif>{{__('Nacional')}}</option>
                                    <option value="Internacional" @if(in_array("Internacional", explode(",",request()->get('cooperacion')))) selected @endif>{{__('Internacional')}}</option>
                                </select>
                                @endif
                            </div>                              
                            <div class="mb-2">
                                <select name="lider" class="form-control form-control-sm select2" data-placeholder="Experiencia en liderar consorcios">
                                    <option></option>
                                    <option value="Si" @if(request()->get('lider') == "Si") selected @endif>{{__('Si')}}</option>
                                    <option value="No" @if(request()->get('lider') == "No") selected @endif>{{__('No')}}</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <select name="empleados" class="form-control form-control-sm select2" data-placeholder="Número de empleados">
                                    <option></option>
                                    <option value="50" @if(request()->get('empleados') == "50") selected @endif>{{__('menos de 50 empleados')}}</option>
                                    <option value="100" @if(request()->get('empleados') == "100") selected @endif>{{__('de 50 a 100 empleados')}}</option>
                                    <option value="150" @if(request()->get('empleados') == "150") selected @endif>{{__('de 100 a 150 empleados')}}</option>
                                    <option value="200" @if(request()->get('empleados') == "200") selected @endif>{{__('de 150 a 200 empleados')}}</option>
                                    <option value="201" @if(request()->get('empleados') == "201") selected @endif>{{__('más de 200 empleados')}}</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <div class="input-group">
                                    <input type="text" maxlength="4" name="codigocnae" value="{{request()->get('codigocnae')}}"
                                    class="form-control form-control-sm f67 txt-azul" placeholder="{{__('Código CNAE')}}" aria-describedby="cnae-addon">
                                    <div class="input-group-append">
                                        <span class="input-group-text clear" id="cnae-addon">
                                            <i class="fa-solid fa-xmark"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="input-group">
                                    <input type="text" maxlength="25" name="descripcioncnae" value="{{request()->get('descripcioncnae')}}"
                                    class="form-control form-control-sm f67 txt-azul" placeholder="{{__('Descripción CNAE')}}" aria-describedby="desccnae-addon"/>
                                    <div class="input-group-append">
                                        <span class="input-group-text clear" id="desccnae-addon">
                                            <i class="fa-solid fa-xmark"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="input-group date input-sm" id="fecha" data-target-input="nearest">
                                    <input type="text" onkeydown="return false" name="fecha" value="{{request()->get('fecha')}}"
                                        class="form-control form-control-sm datetimepicker-input-sm f67 txt-azul" data-target="#fecha" aria-describedby="fechahelp" placeholder="{{__('Fecha mín. constitución: MM/YYYY')}}">
                                    <div class="input-group-append" data-target="#fecha" data-toggle="datetimepicker">
                                        <div class="input-group-text">
                                            <i class="fa fa-calendar fa-sm"></i>
                                        </div>
                                    </div>
                                    <div class="input-group-append" data-target="#fecha" data-toggle="clear">
                                        <div class="input-group-text cleardate" id="cleardate" data-item="fecha">
                                            <i class="fa-solid fa-xmark"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>                                                           
                        </div>
                    </div>   
                    <button type="submit" class="btn btn-primary">Buscar empresas</button>
                </form> 
                <div class="results">
                @if(isset($totalempresas) && $totalempresas > 0)
                    <p class="text-info">* Solo se muestran las 10 primeras empresas como ejemplo de los resultados de búsqueda con los filtros seleccionados</p>
                    Total Empresas encontradas: {{$totalempresas}}
                    @if($totalempresas > 2000)
                        <button type="button" class="btn btn-warning" disabled>Mandar datos a Beagle</button>
                        <small class="text text-warning">* no se pueden mandar más de 2000 líneas de datos de una sola vez a beagle, prueba a añadir más filtros</small>
                    @else
                        <button type="button" class="btn btn-warning">Mandar datos a Beagle</button>
                    @endif            
                    @foreach($empresas->data as $empresa)
                        @if($empresa->LinkInterno == "" || !isset($empresa->LinkInterno) || str_starts_with($empresa->ID, 'XS') || str_starts_with($empresa->ID, 'XA'))
                            @continue
                        @endif               
                        <div class="flex justify-start duration-350 ease-in-out @if(!$loop->last) border-b @endif @if(isset($empresa->Featured) && $empresa->Featured === true && $masterfeatured !== null && $masterfeatured == '1') bg-warning-custom rounded-2xl p-1 @endif">        
                            <div class="flex flex-col m-3">                                           
                                @if(isset($empresa->Naturaleza) && in_array("6668840", $empresa->Naturaleza))                                    
                                    <i class="fa-solid fa-graduation-cap fa-xl text-muted"></i>                                    
                                @elseif(isset($empresa->Naturaleza) && in_array("6668838", $empresa->Naturaleza))                                    
                                    <i class="fa-solid fa-microscope fa-xl text-muted"></i>                                    
                                @elseif(isset($empresa->Naturaleza) && in_array("6668843", $empresa->Naturaleza))
                                    <i class="fa-solid fa-landmark fa-xl text-muted"></i>
                                @elseif(isset($empresa->Naturaleza) && in_array("6668841", $empresa->Naturaleza))
                                    <i class="fa-solid fa-certificate fa-xl text-muted"></i>
                                @elseif(isset($empresa->Naturaleza) && in_array("6668842", $empresa->Naturaleza))
                                    <i class="fa-solid fa-city fa-xl text-muted"></i>
                                @elseif(isset($empresa->Naturaleza) && in_array("6668839", $empresa->Naturaleza))
                                    <i class="fa-solid fa-user-tie fa-xl text-muted"></i>
                                @else
                                    <i class="fa-regular fa-building fa-xl text-muted"></i>
                                @endif           
                                <a href="{{config('app.innovatingurl')}}/empresa/{{$empresa->LinkInterno}}" class="text-uppercase txt-azul hover:text-blue-400 dark:hover:text-blue-400">
                                    <b>
                                        {{\Illuminate\Support\Str::limit($empresa->Nombre, 45, '...')}}
                                    </b>
                                </a> 
                                @if(isset($empresa->Featured) && $empresa->Featured === true && $masterfeatured !== null && $masterfeatured == "1") 
                                    <small class="font-weight-bold"><i>{{ __('Destacado')}}</i></small> 
                                @endif
                                <br/>
                                @if(is_array($empresa->ObjetoSocial))
                                    <small class="description">{{\Illuminate\Support\Str::limit(ucfirst(mb_strtolower(implode("", $empresa->ObjetoSocial))), 140, '...')}}</small>
                                @elseif($empresa->ObjetoSocial != "")
                                    <small class="description">{{\Illuminate\Support\Str::limit(ucfirst(mb_strtolower($empresa->ObjetoSocial)), 140, '...')}}</small>
                                @else
                                    <small class="description">{{__('No se ha especificado una descripción o un objeto social para esta compañía')}}.</small>
                                @endif
                                <br/>
                                <div class="pe-none">
                                    <div class="d-flex text-nowrap mt-1">
                                    @if(isset($empresa->Country))
                                        <img class="d-inline mr-1" src="{{asset('img/flags/countries/'.mb_strtolower($empresa->Country).'.svg')}}" width="20"/>
                                    @else
                                        <img class="d-inline mr-1" src="{{asset('img/flags/countries/eu.svg')}}" width="20"/>
                                    @endif
                                    @if(isset($empresa->TRL))
                                        @if(!in_array("6668840", $empresa->Naturaleza))
                                            @if($empresa->TRL > 0 && $empresa->TRL < 10)                            
                                                <small class="description"><i>{{ __('Perfil tecnológico')}}</i>
                                                    <b>TRL
                                                    @if(isset($empresa->GastoIDI) && $empresa->GastoIDI > 0)
                                                        @if($empresa->TRL <= 9)
                                                            {{$empresa->TRL}}-{{$empresa->TRL+1}}:
                                                        @endif
                                                    @else
                                                        @if($empresa->TRL <= 9)
                                                            {{$empresa->TRL}}-{{$empresa->TRL+1}}
                                                        @endif
                                                    @endif
                                                    @if($empresa->CategoriaEmpresa == "Grande" && $empresa->GastoIDI > 1000000)
                                                        +1M
                                                    @else
                                                        @if(isset($empresa->GastoIDI) && isset($empresa->format_cantidadimasd))
                                                            @if($empresa->GastoIDI > 0)
                                                            {{$empresa->format_cantidadimasd}}
                                                            @endif
                                                        @endif
                                                    @endif
                                                    </b>
                                                </small>                           
                                            @else                            
                                                <small class="description"><i>{{ __('Perfil tecnológico')}}
                                                    {{ __('Pendiente de cálculo')}}</i>
                                                </small>                            
                                            @endif
                                        @elseif(isset($empresa->totalinvestigadores) && $empresa->totalinvestigadores !== null)                       
                                            <small class="description"><i>{{ __('Total investigadores')}}</i>
                                                <b>{{$empresa->totalinvestigadores}}</b>
                                            </small>                       
                                        @else                       
                                            <small class="description"><i>{{ __('Perfil tecnológico')}}
                                                {{ __('Pendiente de cálculo')}}</i>
                                            </small>                       
                                        @endif
                                    @endif
                                    @if(isset($empresa->LastPerfilFin) && $empresa->LastPerfilFin > 1900  && $empresa->EstadoEntidad !== null)
                                        @if($empresa->EstadoEntidad == "extinción")
                                            <small class="description">&bull;</small>
                                            <small class="description text-danger">{{ __('Extinguida')}}</small>
                                        @endif
                                    @endif
                                
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
                </div>
            </div>
            <!-- /.tab-pane -->
            <div class="tab-pane @if(isset($totalproyectos) && $totalproyectos > 0) active @endif" id="proyectos">
                <form method="post" action="{{route('adminsuperadminsearch')}}">
                    @csrf
                    <input type="hidden" name="filter" value="proyectos"/>
                    <div class="flex justify-center items-center xl:justify-start pr-2 pl-2 duration-350 ease-in-out">
                        <div class="flex flex-col mb-0 w-100"> 
                            <div class="mb-2">
                                <select name="estado" class="form-control form-control-sm select2" data-placeholder="Estado" style="width: 100%;">
                                    <option></option>
                                    <option value="Abierto" @if(request()->get('estado') == "Abierto") selected @endif>En Diseño</option>
                                    <option value="Desestimado" @if(request()->get('estado') == "Desestimado") selected @endif>Rechazado</option>
                                    <option value="Cerrado" @if(request()->get('estado') == "Cerrado") selected @endif>Financiado</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <select name="organismo" class="form-control form-control-sm select2" data-placeholder="Organismo" style="width: 100%;">
                                    <option></option>
                                    @foreach($organismos as $org)
                                        @if(request()->get('organismo') !== null && in_array($org->id, explode(",",request()->get('organismo'))))
                                            <option value="{{$org->id}}" selected>
                                                @if(isset($org->Acronimo)) 
                                                    {{$org->Acronimo}}
                                                @else 
                                                    {{\Illuminate\Support\Str::limit($org->Nombre, 45, '...')}}
                                                @endif
                                            </option>
                                        @else
                                            <option value="{{$org->id}}">
                                                @if(isset($org->Acronimo)) 
                                                    {{$org->Acronimo}}
                                                @else
                                                    {{\Illuminate\Support\Str::limit($org->Nombre, 45, '...')}}
                                                @endif
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <select name="linea" class="form-control form-control-sm select2" data-placeholder="Línea de ayuda" style="width: 100%;">
                                    <option></option>
                                    @foreach($ayudasselect as $ayudaproyecto)
                                        @if(request()->get('linea') !== null && in_array($ayudaproyecto->id, explode(",",request()->get('linea'))))
                                            <option value="{{$ayudaproyecto->id}}" selected>
                                                @if(isset($ayudaproyecto->Acronimo)) 
                                                    {{$ayudaproyecto->Acronimo}}
                                                @else 
                                                    {{\Illuminate\Support\Str::limit($ayudaproyecto->Titulo, 45, '...')}}
                                                @endif
                                            </option>
                                        @else
                                            <option value="{{$ayudaproyecto->id}}">
                                                @if(isset($ayudaproyecto->Acronimo)) 
                                                    {{$ayudaproyecto->Acronimo}}
                                                @else
                                                    {{\Illuminate\Support\Str::limit($ayudaproyecto->Nombre, 45, '...')}}
                                                @endif
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>  
                            <div class="mb-2">
                                <input type="number" class="form-control form-control-sm txt-azul" value="{{request()->get('presupuestomin')}}" placeholder="Presupuesto mínimo" name="presupuestomin" steps="10000" min="0" max="99999999">
                            </div>
                            <div class="mb-2">
                                <input type="number" class="form-control form-control-sm txt-azul" value="{{request()->get('presupuestomax')}}" placeholder="Presupuesto máximo" name="presupuestomax" steps="10000" min="0" max="99999999">
                            </div>                                      
                        </div>
                    </div> 
                    <button type="submit" class="btn btn-primary">Buscar proyectos</button>
                </form> 
                <div class="results">
                @if(isset($totalproyectos) && $totalproyectos > 0)
                    <p class="text-info">* Solo se muestran los 10 primeros proyecto como ejemplo de los resultados de búsqueda con los filtros seleccionados</p>
                    Total Proyectos encontrados: {{$totalproyectos}}
                    @if($totalproyectos > 2000)
                        <button type="button" class="btn btn-warning" disabled>Mandar datos a Beagle</button>
                        <small class="text text-warning">* no se pueden mandar más de 2000 líneas de datos de una sola vez a beagle, prueba a añadir más filtros</small>
                    @else
                        <button type="button" class="btn btn-warning">Mandar datos a Beagle</button>
                    @endif
                    @foreach($proyectos->data as $proyecto)
                        <div class="flex justify-start duration-350 mb-3 ease-in-out @if(!$loop->last) border-b @endif">  
                            <div class="flex flex-col m-3">
                                <i class="fa-solid fa-pen-ruler fa-lg text-muted"></i>
                                <a href="{{config('app.innovatingurl')}}/proyectos/{{$proyecto->uri}}" class="txt-azul text-uppercase font-weight-bold">            
                                    <b>
                                        @if($proyecto->Acronimo) 
                                            {{ $proyecto->Acronimo }} 
                                        @else
                                            {{\Illuminate\Support\Str::limit($proyecto->Titulo, 45, '...') }}
                                        @endif
                                    </b>
                                </a>               
                                <br/>
                                @if($proyecto->Titulo != "" && $proyecto->Titulo !== null)
                                <small class="font-weight-bold text-muted">{{\Illuminate\Support\Str::limit($proyecto->Titulo, 60, '...') }}</small>
                                @endif
                                @if($proyecto->TextoHtmlPartners != "")                                            
                                    <!--{!! $proyecto->TextoHtmlPartners !!}-->            
                                @endif
                                <br/>
                                @if($proyecto->Objetivo)
                                <small class="description">
                                    {!! \Illuminate\Support\Str::limit(ucfirst(mb_strtolower($proyecto->Objetivo)), 140, '...') !!}
                                </small>
                                @else
                                <small class="description">
                                    {{ __('No se ha definido un objetivo especifico para este proyecto') }}.
                                </small>
                                @endif
                                <br/>
                                @if($proyecto->Estado == "Tramitado")
                                    @if($proyecto->FechaInicio) 
                                    {{ $proyecto->FechaInicio }} | 
                                    @endif 
                                    <small class="txt-azul">{{ $proyecto->Estado }}</small>
                                @elseif($proyecto->Estado == "Cerrado")
                                    <small class="text-success description">{{ __('Financiado') }}</small>
                                @elseif($proyecto->Estado == "Desestimado")
                                    <small class="text-danger description">{{ __('Rechazado') }}</small>
                                @else
                                    <small class="text-success description">{{ $proyecto->Estado }}</small>
                                @endif
                            </div>                                                                                       
                        </div>
                    @endforeach

                @endif
                </div>
            </div>
        </div>
	</div>
	<div class="card-footer">
		
	</div>
@stop

@section('css')
	<link rel="stylesheet" href="/css/admin_custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
            integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
            crossorigin="anonymous" referrerpolicy="no-referrer" />
	<style>
		.nav-sidebar .menu-open>.nav-treeview {
			margin-left: 0.75rem;
		}   
	</style>
@stop

@section('js')
    <!--DatePicker-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"
    integrity="sha512-qTXRIMyZIFb8iQcfjXWCO8+M5Tbc38Qi5WzdPOYZHIlZpzBHG3L3by84BBBOiRGiEb7KKtAOAs5qYdUiZiQNNQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/js/tempusdominus-bootstrap-4.min.js"
    integrity="sha512-k6/Bkb8Fxf/c1Tkyl39yJwcOZ1P4cRrJu77p83zJjN2Z55prbFHxPs9vN7q3l3+tSMGPDdoH51AEU8Vgo1cgAA==" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/css/tempusdominus-bootstrap-4.min.css"
    integrity="sha512-3JRrEUwaCkFUBLK1N8HehwQgu8e23jTH4np5NHOmQOobuC4ROQxFwFgBLTnhcnQRMs84muMh0PnnwXlPq5MGjg==" crossorigin="anonymous" />
    <!-- jQuery Alerts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<script>
        $(document).ready(function() {
            $('.select2').select2({
                allowClear: true,
                theme: "classic",
            })
        });        
        $("#fecha").datetimepicker({
            format: 'DD/MM/YYYY',
            viewMode: 'days',
            minDate: new Date(),
        });
    </Script>
@stop   