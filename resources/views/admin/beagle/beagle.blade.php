@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Envío Datos Beagle</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
        <ul class="nav nav-pills" id="myTab">
            <li class="nav-item"><a class="nav-link @if(isset($totalempresas) && $totalempresas > 0) active @elseif(!isset($totalempresas) && !isset($totalproyectos)) active @endif "
             href="#empresas" data-toggle="tab">Enviar empresas Beagle</a></li>
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
            <div class="tab-pane @if(isset($totalempresas) && $totalempresas > 0) active @elseif(!isset($totalempresas) && !isset($totalproyectos)) active @endif" id="empresas">
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
                        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#procesoventaModal">Mandar datos a Beagle</button>
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
                                                @endif
                                                {{\Illuminate\Support\Str::limit($org->Nombre, 85, '...')}}                                                
                                            </option>
                                        @else
                                            <option value="{{$org->id}}">
                                                @if(isset($org->Acronimo)) 
                                                    {{$org->Acronimo}}
                                                @endif
                                                {{\Illuminate\Support\Str::limit($org->Nombre, 85, '...')}}                                                
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
                            <div class="mb-2">
                                <div class="input-group date input-sm" id="inicio" data-target-input="nearest">
                                    <input type="text" onkeydown="return false" name="inicio" value="{{request()->get('inicio')}}"
                                        class="form-control form-control-sm datetimepicker-input-sm f67 txt-azul" data-target="#inicio" aria-describedby="iniciohelp" placeholder="{{__('Fecha inicio proyecto: DD/MM/YYYY')}}">
                                    <div class="input-group-append" data-target="#inicio" data-toggle="datetimepicker">
                                        <div class="input-group-text">
                                            <i class="fa fa-calendar fa-sm"></i>
                                        </div>
                                    </div>
                                    <div class="input-group-append" data-target="#inicio" data-toggle="clear">
                                        <div class="input-group-text cleardate" id="cleardate" data-item="inicio">
                                            <i class="fa-solid fa-xmark"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="input-group date input-sm" id="fin" data-target-input="nearest">
                                    <input type="text" onkeydown="return false" name="fin" value="{{request()->get('fin')}}"
                                        class="form-control form-control-sm datetimepicker-input-sm f67 txt-azul" data-target="#fin" aria-describedby="finhelp" placeholder="{{__('Fecha fin proyecto: DD/MM/YYYY')}}">
                                    <div class="input-group-append" data-target="#fin" data-toggle="datetimepicker">
                                        <div class="input-group-text">
                                            <i class="fa fa-calendar fa-sm"></i>
                                        </div>
                                    </div>
                                    <div class="input-group-append" data-target="#fin" data-toggle="clear">
                                        <div class="input-group-text cleardate" id="cleardate" data-item="fin">
                                            <i class="fa-solid fa-xmark"></i>
                                        </div>
                                    </div>
                                </div>
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
                        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#procesoventaProyectosModal">Mandar datos a Beagle</button>
                    @endif
                    @if(empty($proyectos->data))
                        <p class="text-warning font-weight-bold"> * Debido al filtro de fechas que se realiza directamente sobre los resultados de elastic se han filtrado proyectos que se han obtenido desde elastic pero que no encajan con las de inicio o fin, esto es un arreglo provisional hasta que se pueda filtrar por fecha en elastic.</p>
                    @else
                        @foreach($proyectos->data as $proyecto)
                            <div class="flex justify-start duration-350 mb-3 ease-in-out @if(!$loop->last) border-b @endif">  
                                <div class="flex flex-col m-3">
                                    @if($proyecto->uri == "")
                                        <i class="fa-solid fa-pen-ruler fa-lg text-muted"></i> 
                                        <span class="text-muted">            
                                            <b>
                                                @if($proyecto->Acronimo) 
                                                    {{ $proyecto->Acronimo }} 
                                                @else
                                                    {{\Illuminate\Support\Str::limit($proyecto->Titulo, 45, '...') }}
                                                @endif
                                            </b>
                                            filtrado por fecha inicio/fin:
                                            Inicio: {{$proyecto->FechaInicio}} | Fin {{$proyecto->FechaFinal}}
                                        </span>  
                                    @else
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
                                    @endif 
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
                @endif
                </div>
            </div>
        </div>
	</div>
	<div class="card-footer">
		
	</div>
    @if(request()->get('filter') == "empresas" && isset($totalempresas) && $totalempresas > 0 && $totalempresas < 2000) 
        <div class="modal fade" id="procesoventaModal" tabindex="-1" role="dialog" aria-labelledby="procesoventaModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="procesoventaModalLabel">{{__('Generar proceso de venta')}}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{route('adminmandardatosbeagle')}}" class="mandarabeagle" method="post">
                        @csrf
                        <div class="alert alert-warning">
                            <b>{{ __('Este es un proceso costoso a nivel sistema y procesamiento, ten en cuenta que puede tardar varios minutos en ejecutarse')}}</b>
                        </div>
                        <div class="modal-body">                    
                            @if(is_array(request()->get('comunidad')))
                            <input type="hidden" name="comunidad" value="{{implode(',', request()->get('comunidad'))}}">
                            @else
                            <input type="hidden" name="comunidad" value="{{request()->get('comunidad')}}">
                            @endif
                            @if(is_array(request()->get('categoria')))
                            <input type="hidden" name="categoria" value="{{implode(',', request()->get('categoria'))}}">
                            @else
                            <input type="hidden" name="categoria" value="{{request()->get('categoria')}}">
                            @endif
                            <input type="hidden" name="trlmax" value="{{request()->get('trlmax')}}">
                            <input type="hidden" name="patentes" value="{{request()->get('patentes')}}">
                            <input type="hidden" name="ayudas" value="{{request()->get('ayudas')}}">
                            <input type="hidden" name="ultimaayuda" value="{{request()->get('ultimaayuda')}}">
                            <input type="hidden" name="sellopyme" value="{{request()->get('sellopyme')}}">
                            @if(is_array(request()->get('cooperacion')))
                            <input type="hidden" name="cooperacion" value="{{implode(',', request()->get('cooperacion'))}}">
                            @else
                            <input type="hidden" name="cooperacion" value="{{request()->get('cooperacion')}}">
                            @endif
                            <input type="hidden" name="lider" value="{{request()->get('lider')}}">
                            <input type="hidden" name="empleados" value="{{request()->get('empleados')}}">
                            <input type="hidden" name="codigocnae" value="{{request()->get('codigocnae')}}">
                            <input type="hidden" name="descripcioncnae" value="{{request()->get('descripcioncnae')}}">
                            <input type="hidden" name="fecha" value="{{request()->get('fecha')}}">
                            <input type="hidden" name="soloclientes" value="{{request()->get('soloclientes')}}">
                            <input type="hidden" name="patrimonionetomin" value="{{request()->get('patrimonionetomin')}}">
                            <input type="hidden" name="patrimonionetomax" value="{{request()->get('patrimonionetomax')}}">
                            <input type="hidden" name="activocorrientemin" value="{{request()->get('activocorrientemin')}}">
                            <input type="hidden" name="activocorrientemax" value="{{request()->get('activocorrientemax')}}">
                            <input type="hidden" name="activofijomin" value="{{request()->get('activofijomin')}}">
                            <input type="hidden" name="activofijomax" value="{{request()->get('activofijomax')}}">
                            <input type="hidden" name="beneficioanualmin" value="{{request()->get('beneficioanualmin')}}">
                            <input type="hidden" name="beneficioanualmax" value="{{request()->get('beneficioanualmax')}}">
                            <input type="hidden" name="circulantemin" value="{{request()->get('circulantemin')}}">
                            <input type="hidden" name="circulantemax" value="{{request()->get('circulantemax')}}">
                            <input type="hidden" name="gastoanualmin" value="{{request()->get('gastoanualmin')}}">
                            <input type="hidden" name="gastoanualmax" value="{{request()->get('gastoanualmax')}}">
                            <input type="hidden" name="ingresosmin" value="{{request()->get('ingresosmin')}}">
                            <input type="hidden" name="ingresosmax" value="{{request()->get('ingresosmax')}}">
                            <input type="hidden" name="margenendeudamientomin" value="{{request()->get('margenendeudamientomin')}}">
                            <input type="hidden" name="margenendeudamientomax" value="{{request()->get('margenendeudamientomax')}}">
                            <input type="hidden" name="pasivocorrientemin" value="{{request()->get('pasivocorrientemin')}}">
                            <input type="hidden" name="pasivocorrientemax" value="{{request()->get('pasivocorrientemax')}}">
                            <input type="hidden" name="pasivocorrientemin" value="{{request()->get('pasivocorrientemin')}}">
                            <input type="hidden" name="pasivonocorrientemin" value="{{request()->get('pasivonocorrientemin')}}">
                            <input type="hidden" name="pasivonocorrientemax" value="{{request()->get('pasivonocorrientemax')}}">
                            <input type="hidden" name="trabajosinmovilizadosmin" value="{{request()->get('trabajosinmovilizadosmin')}}">
                            <input type="hidden" name="trabajosinmovilizadosmax" value="{{request()->get('trabajosinmovilizadosmax')}}">
                            <input type="hidden" name="gastoidmin" value="{{request()->get('gastoidmin')}}">
                            <input type="hidden" name="gastoidmax" value="{{request()->get('gastoidmax')}}">
                            <input type="hidden" name="isfilter" value="1">
                            <input type="hidden" name="isfilterfinanciero" value="{{request()->get('isfilterfinanciero')}}">
                            <div class="form-group">
                                <span class="text-danger">*</span> <label for="titulo">{{ __('Título proceso de venta') }}</label>
                                <input type="text" name="titulo" id="titulo" class="form-control" required maxlength="300">
                                <small>* máximo 300 caracteres</small>
                            </div>
                            <div class="form-group">
                                <span class="text-danger">*</span> <label for="mensaje">{{ __('Explicación targetización de empresa') }}</label>
                                <textarea name="mensaje" id="mensaje" rows="5" class="form-control" required="required" maxlength="1000"></textarea>
                                <small>* máximo 1000 caracteres</small>
                            </div>
                            <div class="form-group">
                                <label for="ayuda">{{ __('Selecciona ayuda de innovating.works') }}</label><br/>
                                <select name="ayuda" class="form-control modal1" title="{{__('Ayudas')}}" style="width: 100%;">
                                    <option></option>
                                    @foreach($selectayudas as $option)
                                        @if($option->organo !== null)
                                        <option value="{{$option->id}}">
                                            @if($option->Acronimo) {{$option->Acronimo}}: @endif {{ \Illuminate\Support\Str::limit($option->Titulo, 40, '...')}}
                                        </option>
                                        @elseif($option->departamento !== null)
                                        <option value="{{$option->id}}">
                                            @if($option->Acronimo) {{$option->Acronimo}}: @endif {{ \Illuminate\Support\Str::limit($option->Titulo, 40, '...')}}
                                        </option>
                                        @else
                                        <option value="{{$option->id}}">
                                            @if($option->Acronimo) {{$option->Acronimo}}: @endif {{ \Illuminate\Support\Str::limit($option->Titulo, 40, '...')}}
                                        </option>
                                        @endif
                                    @endforeach
                                </select>                            
                            </div>
                            <div class="form-group">
                                <label for="link">{{ __('Link innovating.works') }}</label>
                                <input type="url" name="link" id="link" class="form-control" maxlength="300">
                                <small>* máximo 300 caracteres</small>
                            </div>
                            <div class="form-group">
                                <label for="speech">{{ __('Speech proceso de televenta') }}</label>
                                <textarea name="speech" id="speech" rows="5" class="form-control" maxlength="5000"></textarea>
                                <small>* máximo 5000 caracteres</small>
                            </div>
                            <div class="form-group">
                                <label for="prioridad">{{ __('Prioridad') }}</label><br/>
                                <select class="select2 form-control-sm pl-0 ml-0"  style="width: 100%;" name="prioridad" title="Prioridad">
                                    <option value="Alta">Alta</option>
                                    <option value="Media">Media</option>
                                    <option value="Baja">Baja</option>
                                </select>
                                <small>* por defecto si no se selecciona prioridad se mandará con prioridad "Baja"</small>
                            </div>
                            <div class="form-group">
                                <label for="fechamax">{{ __('Fecha máxima') }}</label>
                                <div class="input-group date" id="fechamax" data-target-input="nearest">
                                    <input type="text" name="fechamax" class="form-control-xs datetimepicker-input" data-target="#fechamax"  aria-describedby="fechahelp" placeholder="Fecha máxima" onkeydown="return false"/>
                                    <div class="input-group-append" data-target="#fechamax" data-toggle="datetimepicker">
                                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                    </div>
                                    <div class="input-group-append">
                                        <div class="input-group-text"><button type="button" class="btn btn-link btn-xs text-muted clearbutton" data-item="fechamax"><i class="fa fa-times"></i></button></div>
                                    </div>
                                </div>
                                <small>* por defecto si no se selecciona fecha se mandará la fecha de hoy más 7 días</small>
                            </div>     
                        </div>
                        <div class="modal-footer">
                            <span class="ajaxloader" style="display:none"><img src="{{asset('img/ajax-loader.gif')}}" width="30" height="30" /></span>
                            <button type="submit" class="btn btn-outline-warning btn-sm">Enviar a Beagle</button>
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">{{__('Cerrar')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    @if(request()->get('filter') == "proyectos" && isset($totalproyectos) && $totalproyectos > 0 && $totalproyectos < 2000) 
        <div class="modal fade" id="procesoventaProyectosModal" tabindex="-1" role="dialog" aria-labelledby="procesoventaProyectosModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="procesoventaProyectosModalLabel">{{__('Generar proceso de venta proyectos')}}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{route('adminmandardatosbeagle')}}" class="mandarabeagle" method="post">
                        @csrf
                        <input type="hidden" name="esproyectos" value="1">
                        <input type="hidden" name="organismo" value="{{request()->get('organismo')}}">
                        <input type="hidden" name="linea" value="{{request()->get('linea')}}">
                        <input type="hidden" name="estado" value="{{request()->get('estado')}}">
                        <input type="hidden" name="presupuestomin" value="{{request()->get('presupuestomin')}}">
                        <input type="hidden" name="presupuestomax" value="{{request()->get('presupuestomax')}}">
                        <div class="alert alert-warning">
                            <b>{{ __('Este es un proceso costoso a nivel sistema y procesamiento, ten en cuenta que puede tardar varios minutos en ejecutarse')}}</b>
                        </div>
                        <div class="modal-body">                           
                            <div class="form-group">
                                <span class="text-danger">*</span> <label for="titulo">{{ __('Título proceso de venta') }}</label>
                                <input type="text" name="titulo" id="titulo" class="form-control" required maxlength="300"/>
                                <small>* máximo 300 caracteres</small>
                            </div>
                            <div class="form-group">
                                <span class="text-danger">*</span> <label for="mensaje">{{ __('Explicación targetización de empresa') }}</label>
                                <textarea name="mensaje" id="mensaje" rows="5" class="form-control" required maxlength="1000"></textarea>
                                <small>* máximo 1000 caracteres</small>
                            </div>
                            <div class="form-group">
                                <label for="ayuda">{{ __('Selecciona ayuda de innovating.works') }}</label><br/>
                                <select name="ayuda" class="form-control modal2" title="{{__('Ayudas')}}" style="width: 100%;">
                                    <option></option>
                                    @php
                                        $url = null;
                                    @endphp
                                    @if($ayudasselect !== null)
                                        @foreach($ayudasselect as $item)
                                            @if(request()->get('linea') !== null && request()->get('linea') == $item->id)
                                                <option value="{{$item->id}}" selected>
                                            @else
                                                <option value="{{$item->id}}">
                                            @endif
                                            @if($item->Acronimo) {{$item->Acronimo}}: @endif {{ \Illuminate\Support\Str::limit($item->Titulo, 40, '...')}}
                                                </option>
                                        @endforeach
                                    @endif
                                </select>                            
                            </div>
                            <div class="form-group">
                                <label for="link">{{__('Link innovating.works') }}</label>                                
                                <input type="url" name="link" id="link" class="form-control" maxlength="300"/>                                
                                <small>* máximo 300 caracteres</small>
                            </div>
                            <div class="form-group">
                                <label for="speech">{{ __('Speech proceso de televenta') }}</label>
                                <textarea name="speech" id="speech" rows="5" class="form-control" maxlength="5000"></textarea>
                                <small>* máximo 5000 caracteres</small>
                            </div>
                            <div class="form-group mt-4">
                                <label for="prioridad">{{ __('Prioridad') }}</label><br/>
                                <select class="select2 form-control-sm pl-0 ml-0"  style="width: 100%;" name="prioridad" title="Prioridad">
                                    <option value="Alta">Alta</option>
                                    <option value="Media">Media</option>
                                    <option value="Baja">Baja</option>
                                </select>
                                <small>* por defecto si no se selecciona prioridad se mandará con prioridad "Baja"</small>
                            </div>
                            <div class="form-group">
                                <label for="fechamax">{{ __('Fecha máxima') }}</label>
                                <div class="input-group date" id="fechamax" data-target-input="nearest">
                                    <input type="text" name="fechamax" class="form-control-xs datetimepicker-input" data-target="#fechamax"  aria-describedby="fechahelp" placeholder="Fecha máxima" onkeydown="return false"/>
                                    <div class="input-group-append" data-target="#fechamax" data-toggle="datetimepicker">
                                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                    </div>
                                    <div class="input-group-append">
                                        <div class="input-group-text"><button type="button" class="btn btn-link btn-xs text-muted clearbutton" data-item="fechamax"><i class="fa fa-times"></i></button></div>
                                    </div>
                                </div>
                                <small>* por defecto si no se selecciona fecha se mandará la fecha de hoy más 7 días</small>
                            </div>                     
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-outline-warning btn-sm">Enviar a Beagle</button>
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">{{__('Cerrar')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
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
            $('.modal1').select2({
                allowClear: true,
                placeholder: "Selecciona una ayuda",
                theme: "classic",
                dropdownParent: $('#procesoventaModal')
            });
            $('.modal2').select2({
                allowClear: true,
                placeholder: "Selecciona una ayuda",
                theme: "classic",
                dropdownParent: $('#procesoventaProyectosModal')
            });
        });        
        $("#fecha").datetimepicker({
            format: 'DD/MM/YYYY',
            viewMode: 'days',
            minDate: new Date(),
        });
        $("#fechamax").datetimepicker({
            format: 'DD/MM/YYYY',
            viewMode: 'days',
            minDate: new Date(),
        });

        $("#inicio").datetimepicker({
            format: 'DD/MM/YYYY',
            viewMode: 'days',
        });

        $("#fin").datetimepicker({
            format: 'DD/MM/YYYY',
            viewMode: 'days',
        });

        $('form').on('submit', function(){
            var $preloader = $('.preloader');
            $preloader.css('height', '100%');
            setTimeout(function () {
                $preloader.children().show();
            });
        });
    </Script>
@stop   