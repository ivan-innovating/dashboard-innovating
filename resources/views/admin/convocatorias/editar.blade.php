@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Ayudas/Convocatorias</h1>
    <button class="btn btn-primary" type="button" onclick="scrollBottom()">Ir a Encajes</button>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title cursor-pointer" data-card-widget="collapse" title="Collapse">Editar Convocatoria {{$ayuda->Titulo}}</h3>
		<div class="card-tools">
			<button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
			<i class="fas fa-minus"></i>
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
        @if($ayuda->organo !== null)
            <a href="{{config('app.innovatingurl')}}/ayuda/{{$ayuda->organo->url}}/{{$ayuda->Uri}}?preview=1" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-share"></i> Ver convocatoria</a>
        @elseif($ayuda->departamento !== null)
            <a href="{{config('app.innovatingurl')}}/ayuda/{{$ayuda->deparamento->url}}/{{$ayuda->Uri}}?preview=1" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-share"></i> Ver convocatoria</a>
        @endif
        <div class="text-right">
            <form method="post" action="{{route('adminpublicarconvocatoria')}}" class="publicarconvocatoria">
                @csrf
                <input type="hidden" name="id" value="{{$ayuda->id}}"/>            
                @if($ayuda->Publicada == 1)                
                    <input type="hidden" name="publicada" value="0"/>
                    <button type="submit" class="btn btn-warning btn-md"><i class="fa-solid fa-eye-slash"></i> {{__('Pasar a borrador')}}</button><br/>
                    <small class="text-muted">* {{__('Pasar a borrador una convocatoria, significa que la ayuda dejara de ser accesible publicamente(menos para superAdmin) y no aparecerá en los resultados de las búsquedas y en los perfiles financieros y simulados de las empresas')}}</small>
                @else                
                    <input type="hidden" name="publicada" value="1"/>
                    <button type="submit" class="btn btn-primary btn-md"><i class="fa-solid fa-eye"></i> {{__('Publicar convocatoria')}}</button><br/>
                    <small class="text-muted">* {{__('Pasar a publicada una convocatoria, significa que aparecerá en los resultados de las búsquedas y en los perfiles financieros y simulados de las empresas en caso de encajar')}}</small>
                @endif
            </form>
        </div>      
        <hr/>
        <form method="post" action="{{route('admineditconvocatoria')}}" class="editarconvocatoria">
            <h3>Información Principal</h3>
            <hr/>
            <input type="hidden" name="id" value="{{$ayuda->id}}"/>
            <input type="hidden" name="old_tpa" value="{{$ayuda->type_of_action_id}}"/>
            <input type="hidden" name="old_subfondos" value="{{$ayuda->subfondos}}"/>
            @csrf
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="infodefinitiva" name="infodefinitiva" ariala-describedby="definitivaHelp"
                @if($ayuda->InformacionDefinitiva == 1) checked @endif>
                <label for="infodefinitiva" class="form-check-label">Información no definitiva</label>
                <small id="definitivaHelp"  class="form-text text-muted">* Marcando este campo indicarás en la ayuda públicada, que los datos pueden estar sujetos a cambios y no son definitivos.</small>
            </div>
            <hr/>
            <div class="alert alert-info mt-3 ml-3">
                <p>* Los campos acrónimo y titulo son obligatorios, si estan vaciós hay que asignar la ayuda previamente creada o crear una ayuda nueva a partir de esta convocatoria.</p>
            </div>
            @if(isset($ayuda_convocatoria))
            <div class="form-group ml-3">
                <label for="acronimo_ayuda"><span class="text-danger">*</span> Acronimo Ayuda</label>
                <input type="text" class="form-control" id="acronimo_ayuda" name="acronimo_ayuda" value="{{$ayuda_convocatoria->acronimo}}" disabled>
            </div>
            <div class="form-group ml-3">
                <label for="titulo_ayuda"><span class="text-danger">*</span> Titulo Ayuda</label>
                <input type="text" class="form-control" id="titulo_ayuda" name="titulo_ayuda" value="{{$ayuda_convocatoria->titulo}}" disabled>
            </div>
            @else
            <div class="form-group ml-3">
                <label for="acronimo_ayuda"><span class="text-danger">*</span> Acronimo Ayuda</label>
                <input type="text" class="form-control" id="acronimo_ayuda" name="acronimo_ayuda" value="" disabled>
            </div>
            <div class="form-group ml-3">
                <label for="titulo_ayuda"><span class="text-danger">*</span> Titulo Ayuda</label>
                <input type="text" class="form-control" id="titulo_ayuda" name="titulo_ayuda" value="" disabled>
            </div>
            @endif
            <div class="form-group ml-3">
                <label for="id_ayuda"><span class="text-danger">*</span> Ayuda a la que pertenece la convocatoria</label>
                <select name="id_ayuda" class="form-control selectpicker" placeholder="selecciona una" required>
                    @foreach($ayudasconv as $ayudaconv)
                        <option value="{{$ayudaconv->id}}" @if($ayudaconv->id == $ayuda->id_ayuda) selected @endif>{{$ayudaconv->acronimo}}: {{$ayudaconv->titulo}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group ml-3 text-left">
                <!-- Button trigger modal -->
                <span class="ml-3 text-danger">* Puede que la ayuda que intentes asociar este como extinguida, revisalo en este listado antes de crear una nueva ayuda
                    <a href="{{route('dashboardayudas')}}" target="_blank" class="btn btn-primary btn-sm">Ayudas</a>
                </span><br/><br/>

                <span class="ml-3 text-primary">* Si no has encontrado la ayuda para esta convocatoria en el selector anterior puedes crearla desde el siguiente botón:</span>
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#crerAyudaConvocatoria">
                    Crear ayuda a partir de convocatoria
                </button>
            </div>
            <hr/>
            <div class="form-group mt-3">
                <label for="acronimo"><span class="text-danger">*</span> Acronimo</label>
                <input type="text" class="form-control" id="acronimo" name="acronimo" value="{{$ayuda->Acronimo}}">
            </div>
            <div class="form-group mt-3">
                <label for="titulo"><span class="text-danger">*</span> Titulo</label>
                <input type="text" class="form-control" id="titulo" name="titulo" value="{{$ayuda->Titulo}}">
            </div>
            <div class="form-group mt-3">
                <label for="uri"><span class="text-danger">*</span> Url</label>
                <input type="text" class="form-control" id="uri" name="uri" value="{{$ayuda->Uri}}" aria-describedby="urihelp">
                <small id="urihelp" class="form-text text-muted">Solo cambiar en el caso de que haya varias ayudas con el mismo título, utilizar solo, letras, números y guiones, consejo: añadir al final la ccaa, año o similar para no perder SEO.</small>
            </div>
            <h3>Información ayuda</h3>
            <hr/>
            <div class="form-group">
                <label for="presentacion"><span class="text-danger">*</span> Presentación</label><br/>
                <select name="presentacion" class="selectpicker" data-width="100%" title="Selecciona uno...">
                    <option value="Consorcio" @if($ayuda->Presentacion == "Consorcio") selected @endif>Consorcio</option>
                    <option value="Individual" @if($ayuda->Presentacion == "Individual") selected @endif>Individual</option>
                </select>
            </div>
            <div class="form-group">
                <label for="link"><span class="text-danger">*</span> Link</label>
                <input type="text" class="form-control" id="link" name="link" value="{{$ayuda->Link}}">
            </div>
            <div class="form-group">
                <label for="naturaleza_convocatoria"><span class="text-danger">*</span> Naturaleza Convocatoria</label><br/>
                <select name="naturaleza_convocatoria[]" class="selectpicker" multiple required data-width="100%" title="Selecciona...">
                    @foreach($naturalezas as $naturaleza)
                        @if(isset($ayuda->naturalezaConvocatoria) && !empty($ayuda->naturalezaConvocatoria) && in_array($naturaleza->id, json_decode($ayuda->naturalezaConvocatoria, true)))
                            <option value="{{$naturaleza->id}}" selected>{{$naturaleza->NombreNaturaleza}}</option>
                        @else
                            <option value="{{$naturaleza->id}}">{{$naturaleza->NombreNaturaleza}}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            @if(isset($ayuda->naturalezaConvocatoria) && !empty($ayuda->naturalezaConvocatoria) && in_array("6668837", json_decode($ayuda->naturalezaConvocatoria, true)))
            <div class="form-group categoria-convocatoria">
            @else
            <div class="form-group categoria-convocatoria d-none">
            @endif
                <label for="categoria"><span class="text-danger">*</span> Categoría</label><br/>
                <select name="categoria[]" class="selectpicker" multiple data-width="100%" title="Selecciona...">
                @if($categorias)
                    @if(in_array("Micro", $categorias))
                        <option value="Micro" selected="selected">Micro</option>
                    @else
                        <option value="Micro">Micro</option>
                    @endif
                    @if(in_array("Pequeña", $categorias))
                        <option value="Pequeña" selected="selected">Pequeña</option>
                    @else
                        <option value="Pequeña">Pequeña</option>
                    @endif
                    @if(in_array("Mediana", $categorias))
                        <option value="Mediana" selected="selected">Mediana</option>
                    @else
                        <option value="Mediana">Mediana</option>
                    @endif
                    @if(in_array("Grande", $categorias))
                        <option value="Grande" selected="selected">Grande</option>
                    @else
                        <option value="Grande">Grande</option>
                    @endif
                @else
                    <option value="Micro">Micro</option>
                    <option value="Pequeña">Pequeña</option>
                    <option value="Mediana">Mediana</option>
                    <option value="Grande">Grande</option>
                @endif;
                </select>
            </div>
            <div class="form-group">
                <label for="intereses"><span class="text-danger">*</span> Perfil Financiación</label><br/>
                <select name="intereses[]" class="selectpicker" multiple data-width="100%" data-live-search="true" title="Selecciona...">
                    @foreach($intereses as $interes)
                        @if($interes->id == 1 || $interes->id == 10 || $interes->id == 11)
                            @continue
                        @endif
                        @if($ayuda->PerfilFinanciacion && $ayuda->PerfilFinanciacion != "null")
                            @if(in_array($interes->Id_zoho, json_decode($ayuda->PerfilFinanciacion, true)))
                                <option value="{{$interes->Id_zoho}}" selected="selected">{{$interes->Nombre}}</option>
                            @else
                                <option value="{{$interes->Id_zoho}}">{{$interes->Nombre}}</option>
                            @endif
                        @else
                            <option value="{{$interes->Id_zoho}}">{{$interes->Nombre}}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="ambito"><span class="text-danger">*</span> Ambito</label><br/>
                <select name="ambito" class="selectpicker" data-width="100%" title="Selecciona uno..." required>
                    <option value="Europea" @if($ayuda->Ambito == "Europea") selected @endif>Europea</option>
                    <option value="Nacional" @if($ayuda->Ambito == "Nacional") selected @endif>Nacional</option>
                    <option value="Comunidad Autónoma" @if($ayuda->Ambito == "Comunidad Autónoma") selected @endif>Comunidad Autónoma</option>
                </select>
            </div>
            <div id="ccaas" @if($ayuda->Ambito != "Comunidad Autónoma") class="form-group d-none" @else class="form-group" @endif>
                <label for="ccaas">CCAA</label><br/>
                <select name="ccaas[]" class="selectpicker" data-width="100%" title="Selecciona..." multiple>
                @if($ayuda->Ccaas && $ayuda->Ccaas != "null")
                    @foreach($ccaas as $ccaa)
                        @if(in_array($ccaa->Nombre, json_decode($ayuda->Ccaas, true)))
                            <option value="{{$ccaa->Nombre}}" selected>{{$ccaa->Nombre}}</option>
                        @else
                            <option value="{{$ccaa->Nombre}}">{{$ccaa->Nombre}}</option>
                        @endif
                    @endforeach
                @else
                    @foreach($ccaas as $ccaa)
                        <option value="{{$ccaa->Nombre}}">{{$ccaa->Nombre}}</option>
                    @endforeach
                @endif
                </select>
            </div>
            <div class="form-group">
                <label for="opcionCNAE"><span class="text-danger">*</span> Opcion CNAE</label><br/>
                <select name="opcionCNAE" class="selectpicker" data-width="100%" title="Selecciona uno..." required>
                    <option value="Todos" @if($ayuda->OpcionCNAE == "Todos") selected @endif>Todos</option>
                    <option value="Válidos" @if($ayuda->OpcionCNAE == "Válidos") selected @endif>Válidos</option>
                    <option value="Excluidos" @if($ayuda->OpcionCNAE == "Excluidos") selected @endif>Excluidos</option>
                </select>
            </div>
            <div id="cnaes" @if($ayuda->OpcionCNAE != "Todos") class="form-group" @else class="form-group d-none" @endif>
                <div class="form-group">
                    <label for="cnaes">CNAES</label><br/>
                    <select name="cnaes[]" multiple class="duallistbox" style="height: 250px !important;">
                        @if($ayuda->CNAES != "null" && !empty($ayuda->CNAES))
                            @foreach($cnaes as $cnae)
                                @if($ayuda->CNAES)
                                    @if(in_array($cnae->Id_zoho, json_decode($ayuda->CNAES, true)))
                                        <option value="{{$cnae->Id_zoho}}" selected="selected">{{$cnae->Nombre}}</option>
                                    @else
                                        <option value="{{$cnae->Id_zoho}}">{{$cnae->Nombre}}</option>
                                    @endif
                                @else
                                    <option value="{{$cnae->Id_zoho}}">{{$cnae->Nombre}}</option>
                                @endif
                            @endforeach
                        @else
                            @foreach($cnaes as $cnae)
                                <option value="{{$cnae->Id_zoho}}">{{$cnae->Nombre}}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="form-group mb-3" id="organismo">
                <label for="organo"><span class="text-danger">*</span> Organismo</label>
                <br/>
                <button type="button" data-toggle="modal" data-target="#organismoModal" class="btn btn-primary">{{__('Asignar/Editar Organismo')}}</button>
                @if($ayuda->organo)
                    <span class="txt-azul">{{__('El organismo asignado es')}}: {{$ayuda->organo->Acronimo}} {{$ayuda->organo->Nombre}}</span>
                    <input type="hidden" name="organo" value="{{$ayuda->organo->id}}" required/>
                @elseif($ayuda->departamento)
                    <span class="txt-azul">{{__('El organismo asignado es')}}: {{$ayuda->departamento->Acronimo}} {{$ayuda->departamento->Nombre}}</span>
                    <input type="hidden" name="organo" value="{{$ayuda->departamento->id}}" required/>
                @else
                    <span class="text-danger">{{__('Esta convocatoria no tiene organismo asignado')}}</span>
                    <input type="hidden" name="organo" value="" required/>
                @endif                                                        
            </div>
            <div class="form-group">
                <label for="desccorta">Descripción corta</label>
                <textarea type="text" class="form-control" id="desccorta" name="desccorta" rows="5">{{$ayuda->DescripcionCorta}}</textarea>
            </div>
            <div class="form-group">
                <label for="desclarga">Descripción larga</label>
                <textarea type="text" class="form-control" id="desclarga" name="desclarga" rows="10">{{$ayuda->DescripcionLarga}}</textarea>
            </div>
            <div class="form-group">
                <label for="requisitos">Requisitos</label>
                <textarea type="text" class="form-control" id="requisitos" name="requisitos" rows="10">{{$ayuda->RequisitosTecnicos}}</textarea>
            </div>
            <div class="form-group">
                <label for="requisitos_participantes">Requisitos Participantes</label>
                <textarea type="text" class="form-control" id="requisitos_participantes" name="requisitos_participantes" rows="10">{{$ayuda->RequisitosParticipante}}</textarea>
            </div>
            <div class="form-row mb-3">
                <label for="trl">Trl</label><br/>
                <select name="trl" class="selectpicker" data-width="100%" title="Selecciona uno...">
                    @foreach($trls as $trl)
                        @if($ayuda->Trl == $trl->id)
                            <option value="{{$trl->id}}" selected>{{$trl->nivel}}: {{$trl->titulo}}</option>
                        @else
                            <option value="{{$trl->id}}">{{$trl->nivel}}: {{$trl->titulo}}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="presupuesto"><span class="text-danger">*</span> Presupuesto</label>
                <input type="number" class="form-control" id="presupuesto" name="presupuesto" value="{{$ayuda->Presupuesto}}" required>
            </div>
            <div class="form-group">
                <label for="fondos">Fondos</label>
                @if($fondos->count() > 0)
                <select name="fondos[]" class="selectpicker" data-width="100%" title="Selecciona uno..." multiple>
                    @foreach($fondos as $fondo)
                        @if(isset($ayuda->FondosEuropeos))
                            <option value="{{$fondo->id}}" @if(in_array($fondo->id, json_decode($ayuda->FondosEuropeos, true))) selected @endif>{{$fondo->nombre}}</option>
                        @else
                            <option value="{{$fondo->id}}">{{$fondo->nombre}}</option>
                        @endif
                    @endforeach
                </select>
                @else
                    <br/>
                    <span class="text-danger">* Para poder asignar tipos de fondos a esta convocatoria tienes que crearlos <a href="{{route('dashboardayudasfondos')}}">aquí</a></span>
                @endif
            </div>
            <div class="form-group divsubfondos">
                <label for="subfondos">Subfondos</label>
                @if($subfondos->count() > 0)
                <select name="subfondos[]" id="subfondos" class="selectpicker" data-width="100%" title="Selecciona uno..." multiple data-live-search="true" @if($checksubfondos === false) disabled @endif>
                    @foreach($subfondos as $subfondo)
                        @if(isset($ayuda->subfondos) && $ayuda->subfondos !== null)
                            <option value="{{$subfondo->external_id}}" @if(in_array($subfondo->external_id, json_decode($ayuda->subfondos, true))) selected @endif>{{$subfondo->nombre}}</option>
                        @else
                            <option value="{{$subfondo->external_id}}">{{$subfondo->nombre}}</option>
                        @endif
                    @endforeach
                </select>
                @endif
            </div>
            <div class="form-group">
                <label for="type_of_action_id">Type Of Action</label>
                @if($actions->count() > 0)
                <select name="type_of_action_id" class="selectpicker" data-width="100%" title="Selecciona uno..." data-live-search="true">     
                    @foreach($actions as $action)                                                       
                        @if(isset($ayuda->type_of_action_id) && $ayuda->type_of_action_id !== null)
                            <option value="{{$action->id}}" @if($action->id == $ayuda->type_of_action_id) selected @endif>{{$action->nombre}}</option>                                                                
                        @else
                            <option value="{{$action->id}}">{{$action->nombre}}</option>                                                                
                        @endif                                                            
                    @endforeach
                </select>                                                      
                @endif
                @if($ayuda->type_of_action_id !== null && $ayuda->type_of_action_id > 0)
                <small class="text-muted">
                    El Type Of Action seleccionado para esta ayuda antes de cambiarlo es: 
                    <b>{{ $actions->where('id', $ayuda->type_of_action_id)->first()->nombre }}</b>
                </small>
                @endif
            </div>
            <h3>Estado de la ayuda</h3>
            <hr/>
            <div class="form-group">
                <label for="estado">Estado convocatoria</label><br/>
                <select name="estado" class="selectpicker" data-width="100%" title="Selecciona uno...">
                    <option value="Abierta" @if($ayuda->Estado == "Abierta") selected @endif>Abierta</option>
                    <option value="Cerrada" @if($ayuda->Estado == "Cerrada") selected @endif>Cerrada</option>
                    <option value="Próximamente" @if($ayuda->Estado == "Próximamente") selected @endif>Próximamente</option>
                </select>
            </div>
            <div class="form-group">
                <label for="update_extinguida_ayuda">Estado extinguida de la ayuda de la convocatoria</label><br/>
                <select name="update_extinguida_ayuda" class="selectpicker" data-width="100%" title="Selecciona uno...">
                    <option value="1" @if($ayuda->update_extinguida_ayuda == "1") selected @endif>La ayuda pasaría a "extinguida" cuando la ayuda pase a "Cerrada"</option>
                    <option value="2" @if($ayuda->update_extinguida_ayuda == "2" || $ayuda->update_extinguida_ayuda === null) selected @endif>No realizar ninguna acción sobre la ayuda cuando la convocatoria cierre</option>
                </select>
            </div>
            <div class="form-group m-4">                                                        
                <input class="form-check-input" type="checkbox" name="hastafinfondos" id="hastafinfondos" @if($ayuda->HastaFinFondos == 1) checked @endif>
                <label class="form-check-label font-weight-bold text-danger" for="hastafinfondos">Ayuda abierta hasta el fin de Fondos</label>                                                        
            </div>
            <div class="form-group">
                <label for="competitiva">Es Competitiva</label><br/>
                <select name="competitiva" class="selectpicker" data-width="100%">
                    <option value="Muy Competitiva" @if($ayuda->Competitiva == "Muy Competitiva") selected @endif>Muy Competitiva</option>
                    <option value="Competitiva" @if($ayuda->Competitiva == "Competitiva") selected @endif>Competitiva</option>
                    <option value="No competitiva" @if($ayuda->Competitiva == "No competitiva") selected @endif>No competitiva</option>
                </select>
            </div>
            <div class="form-row mb-3">
                <div class="col">
                    <label for="inicio">Fecha Inicio</label><br/>
                    <div class="input-group date" id="inicio" data-target-input="nearest">
                        <input type="text" name="inicio" class="form-control datetimepicker-input" data-target="#inicio"  aria-describedby="fechainiciohelp"/>
                        <div class="input-group-append" data-target="#inicio" data-toggle="datetimepicker">
                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                        </div>
                    </div>
                    <small id="fechainiciohelp" class="form-text text-muted">Formato fecha: dd/mm/yyyy</small>
                </div>
                <div class="col">
                    <label for="fin">Fecha Fin</label><br/>
                    <div class="input-group date" id="fin" data-target-input="nearest">
                        <input type="text" name="fin" class="form-control datetimepicker-input" data-target="#fin"  aria-describedby="fechafinhelp"/>
                        <div class="input-group-append" data-target="#fin" data-toggle="datetimepicker">
                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                        </div>
                    </div>
                    <small id="fechafinhelp" class="form-text text-muted">Formato fecha: dd/mm/yyyy</small>
                </div>
            </div>
            <div class="form-row mb-3">
                <div class="col-sm-6">
                    <label for="fechaemails">Fecha Emails Ayuda Abierta Encaja</label><br/>
                    <div class="input-group date" id="fechaemails" data-target-input="nearest">
                        <input type="text" name="fechaemails" class="form-control datetimepicker-input" data-target="#fechaemails"  aria-describedby="fechaemailshelp"/>
                        <div class="input-group-append" data-target="#fechaemails" data-toggle="datetimepicker">
                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                        </div>
                    </div>
                    <small id="fechaemailshelp" class="form-text text-danger">* Este campo es el que se revisa para mandar correos de "ayuda abierta encaja" a empresas, por defecto si no se añade se tomará como valor la fecha de inicio, ejemplo: añades una ayuda que lleva abierta unos dias, en este campo deberias poner la fecha de hoy o de mañana.</small>
                </div>
            </div>
            <div class="form-row mb-3">
                <div class="form-group w-50">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="checkmesesmin" @if($ayuda->MesesMin !== null) checked @endif>
                        <label class="form-check-label" for="checkmesesmin">
                            Fecha Min. Constitucion es nº de meses
                        </label>
                    </div>
                    <div id="mesesmincontent" @if(!$ayuda->MesesMin) class="d-none" @endif>
                        <label for="mesesmin">Nº Meses</label>
                        <input type="number" class="form-control" id="mesesmin" name="mesesmin" min="1" value="{{$ayuda->MesesMin}}">
                    </div>
                </div>
                <div class="form-group w-50">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="checkfechamin" @if($ayuda->FechaMinConstitucion !== null) checked @endif>
                        <label class="form-check-label" for="checkfechamin">
                            Fecha Min. Constitucion es una Fecha
                        </label>
                    </div>
                    <div id="fechamincontent" @if($ayuda->FechaMinConstitucion === null) class="d-none" @endif>
                        <label for="fechamin">Fecha Min. Constitucion</label><br/>
                        <div class="input-group date" id="fechamin" data-target-input="nearest">
                            <input type="text" name="fechamin" class="form-control datetimepicker-input" data-target="#fechamin"  aria-describedby="fechaminhelp"/>
                            <div class="input-group-append" data-target="#fechamin" data-toggle="datetimepicker">
                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                            </div>
                        </div>
                        <small id="fechaminhelp" class="form-text text-muted">Formato fecha: dd/mm/yyyy</small>
                    </div>
                </div>
            </div>
            <div class="form-row mb-3">
                <div class="form-group w-50">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="checkmeses" @if($ayuda->Meses !== null) checked @endif>
                        <label class="form-check-label" for="checkmeses">
                            Fecha Max. Constitucion es nº de meses
                        </label>
                    </div>
                    <div id="mesescontent" @if(!$ayuda->Meses) class="d-none" @endif>
                        <label for="meses">Nº Meses</label>
                        <input type="number" class="form-control" id="meses" name="meses" min="1" value="{{$ayuda->Meses}}">
                    </div>
                </div>
                <div class="form-group w-50">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="checkfecha" @if($ayuda->FechaMaxConstitucion !== null) checked @endif>
                        <label class="form-check-label" for="checkfecha">
                            Fecha Max. Constitucion es una Fecha
                        </label>
                    </div>
                    <div id="fechamaxcontent" @if($ayuda->FechaMaxConstitucion === null) class="d-none" @endif>
                        <label for="fechamax">Fecha Max. Constitucion</label><br/>
                        <div class="input-group date" id="fechamax" data-target-input="nearest">
                            <input type="text" name="fechamax" class="form-control datetimepicker-input fechamax" data-target="#fechamax"  aria-describedby="fechamaxhelp"/>
                            <div class="input-group-append" data-target="#fechamax" data-toggle="datetimepicker">
                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                            </div>
                        </div>
                        <small id="fechamaxhelp" class="form-text text-muted">Formato fecha: dd/mm/yyyy</small>
                    </div>
                </div>
            </div>
            <h3>Condiciones de Financiación</h3>
            <hr/>
            <div class="form-row mb-3">
                <div class="form-group w-100">
                    <label for="tipofinanciacion"><span class="text-danger">*</span> Tipo Financiación</label><br/>
                    <select name="tipofinanciacion[]" class="selectpicker" data-width="100%" multiple required>
                        @if($ayuda->TipoFinanciacion)
                            <option value="Fondo perdido" @if(in_array('Fondo perdido', json_decode($ayuda->TipoFinanciacion, true))) selected @endif>Fondo perdido</option>
                            <option value="Crédito" @if(in_array('Crédito', json_decode($ayuda->TipoFinanciacion, true))) selected @endif>Crédito</option>
                        @else
                            <option value="Fondo perdido">Fondo perdido</option>
                            <option value="Crédito">Crédito</option>
                        @endif
                    </select>
                </div>
            </div>
            <div class="form-row mb-3">
                <div class="form-group w-100">
                    <label for="objetivoFinanciacion"><span class="text-danger">*</span> Objetivo Financiación</label><br/>
                    <select name="objetivoFinanciacion" class="selectpicker" data-width="100%" required>
                        <option value="Proyectos" @if($ayuda->objetivoFinanciacion == "Proyectos") selected @endif>Proyectos</option>
                        <option value="Empresas" @if($ayuda->objetivoFinanciacion == "Empresas") selected @endif>Empresas</option>
                        <option value="Personas" @if($ayuda->objetivoFinanciacion == "Personas") selected @endif>Personas</option>
                    </select>
                </div>
            </div>
            <div class="form-row mb-3">
                <div class="form-group w-100">
                    <label for="capitulos">Capitulos Financiables</label><br/>
                    <select name="capitulos[]" class="selectpicker" data-width="100%" multiple>
                        @foreach($capitulosFinanciacion as $capitulo)
                            @if($ayuda->CapitulosFinanciacion !== null)
                                @if(in_array($capitulo->id, json_decode($ayuda->CapitulosFinanciacion, true)) 
                                || in_array($capitulo->nombre, json_decode($ayuda->CapitulosFinanciacion, true)) ) 
                                    <option value="{{$capitulo->id}}" selected>{{$capitulo->nombre}}</option>
                                @else
                                    <option value="{{$capitulo->id}}">{{$capitulo->nombre}}</option>
                                @endif
                            @else
                            <option value="{{$capitulo->id}}">{{$capitulo->nombre}}</option>
                            @endif
                        @endforeach                                                                                                                         
                    </select>
                </div>
            </div>
            <div class="form-row mb-3">
                <label for="condicionesfinanciacion">Condiciones de financiación</label>
                <textarea type="text" class="form-control" id="condicionesfinanciacion" name="condicionesfinanciacion" rows="10">{{ $ayuda->CondicionesFinanciacion }}</textarea>
            </div>
            <div class="form-group">
                <label for="presupuesto">Presupuesto Consorcio</label>
                <input type="number" class="form-control" id="presupuestoconsorcio" name="presupuestoconsorcio" value="{{$ayuda->PresupuestoConsorcio}}">
            </div>
            <div class="form-group">
                <label for="presupuesto">Presupuesto Participantes</label>
                <input type="number" class="form-control" id="presupuestoparticipante" name="presupuestoparticipante" value="{{$ayuda->PresupuestoParticipante}}">
            </div>
            <div class="form-group">
                <label for="numeroparticipantes">Número mínimo de participantes</label>
                <input type="number" class="form-control" id="numeroparticipantes" name="numeroparticipantes" value="{{$ayuda->NumeroParticipantes}}">
            </div>
            <div class="form-row mb-3">
                <div class="col">
                    <label for="presupuestomin">Presupuesto Minimo </label><br/>
                    <input type="number" class="form-control" id="presupuestomin" name="presupuestomin" value="{{$ayuda->PresupuestoMin}}">
                </div>
                <div class="col">
                    <label for="presupuestomax">Presupuesto Máximo</label><br/>
                    <input type="number" class="form-control" id="presupuestomax" name="presupuestomax" value="{{$ayuda->PresupuestoMax}}">
                </div>
            </div>                                                   
            <div class="form-row mb-3">
                <div class="col">
                    <label for="duracionmin">Duración mínima del proyecto en meses</label><br/>
                    <input type="number" class="form-control" id="duracionmin" name="duracionmin" value="{{$ayuda->DuracionMin}}">
                </div>
                <div class="col">
                    <label for="duracionmax">Duración máxima del proyecto en meses</label><br/>
                    <input type="number" class="form-control" id="duracionmax" name="duracionmax" value="{{$ayuda->DuracionMax}}">
                </div>
            </div>
            <div class="form-group">
                <label for="textocondicionesespeciales">Texto Condiciones Especiales</label>
                <textarea class="form-control" id="textocondicionesespeciales" name="textocondicionesespeciales" rows="10">{{$ayuda->CondicionesEspeciales}}</textarea>
            </div>
            <div class="form-group">
                <label for="textoconsorcio">Texto Consorcio</label>
                <textarea class="form-control" id="textoconsorcio" name="textoconsorcio" rows="10">{{$ayuda->TextoConsorcio}}</textarea>
            </div>
            <h3>Datos gráfico financiación</h3>
            <hr/>
            <small>*La suma de los 3 campos no debe superar el 100%</small>
            <div class="form-row mb-3">
                <div class="col">
                    <label for="porcentajefondoperdido">% fondo perdido o tramo no reembolsable máximo</label>
                    <input type="number" class="form-control" step="0.01" name="porcentajefondoperdido" value="{{$ayuda->PorcentajeFondoPerdido}}"/>
                    <small>Número con dos decimales como máxima precisión.</small>
                </div>
                <div class="col">
                    <label for="porcentajecreditomax">% crédito máximo</label>
                    <input type="number" class="form-control" step="0.01" name="porcentajecreditomax" value="{{$ayuda->PorcentajeCreditoMax}}"/>
                    <small>Número con dos decimales como máxima precisión.</small>
                </div>
                <div class="col">
                    <label for="deduccionmax">Deducción máxima</label>
                    <input type="number" class="form-control" step="0.01" name="deduccionmax" value="{{$ayuda->DeduccionMax}}"/>
                    <small>Número con dos decimales como máxima precisión.</small>
                </div>
            </div>
            <div class="form-row mb-3">
                <div class="col-sm-4">
                    <label for="porcentajefondoperdido">% fondo perdido o tramo no reembolsable mínimo</label>
                    <input type="number" class="form-control" step="0.01" name="porcentajefondoperdidominimo" value="{{$ayuda->FondoPerdidoMinimo}}"/>
                    <small>Número con dos decimales como máxima precisión.</small>
                </div>
                <div class="col-sm-4">
                    <label for="porcentajecreditomax">% crédito mínimo</label>
                    <input type="number" class="form-control" step="0.01" name="porcentajecreditominimo" value="{{$ayuda->CreditoMinimo}}"/>
                    <small>Número con dos decimales como máxima precisión.</small>
                </div>
            </div>
            <div class="form-check form-check-inline mb-3">
                <input class="form-check-input" type="radio" name="fondotramo" id="fondotramo1" value="fondo" @if($ayuda->FondoTramo == "fondo") checked @endif>
                <label class="form-check-label" for="fondotramo1">Es fondo perdido</label>
                </div>
                <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="fondotramo" id="fondotramo2" value="tramo" @if($ayuda->FondoTramo == "tramo") checked @endif>
                <label class="form-check-label" for="fondotramo2">Es tramo no reembolsable</label>
            </div>
            <div class="form-row mb-3">
                <div class="col">
                    <label for="tiempomedioresolucion">Tiempo medio resolución</label>
                    <input type="number" class="form-control" name="tiempomedioresolucion" value="{{$ayuda->TiempoMedioResolucion}}"/>
                    <small>Número de meses.</small>
                </div>
            </div>
            <div class="form-row mb-3">
                <div class="form-group col-sm-6">
                    <label for="aplicacionintereses">Aplicación intereses</label><br/>
                    <select name="aplicacionintereses" class="selectpicker" data-width="100%" title="Selecciona una...">
                        <option value="No" @if($ayuda->AplicacionIntereses == "No") selected @endif>No</option>
                        <option value="Euribor" @if($ayuda->AplicacionIntereses == "Euribor") selected @endif>Euribor</option>
                        <option value="Fijo" @if($ayuda->AplicacionIntereses == "Fijo") selected @endif>Fijo</option>
                    </select>
                </div>
                <div class="form-group col-sm-6">
                    <label for="porcentajeintereses">Porcentaje intereses</label><br/>
                    <input type="number" step="0.01" min="0" class="form-control" id="porcentajeintereses" name="porcentajeintereses" value="{{$ayuda->PorcentajeIntereses}}">
                </div>
            </div>
            <div class="form-row mb-3">
                <div class="form-group col-sm-6">
                    <label for="anosamortizacion">Años amortización</label><br/>
                    <input type="number" step="1" min="0" class="form-control" id="anosamortizacion" name="anosamortizacion" value="{{$ayuda->AnosAmortizacion}}">
                </div>
                <div class="form-group col-sm-6">
                    <label for="mesescarencia">Meses carencia</label><br/>
                    <input type="number" step="1" min="0" class="form-control" id="mesescarencia" name="mesescarencia" value="{{$ayuda->MesesCarencia}}">
                </div>
            </div>
            <h3>Condiciones adicionales</h3>
            <hr/>
            <div class="row mb-3 ml-3">
                <div class="form-check col-6 mt-3">
                    <input class="form-check-input" type="checkbox" id="featured" name="featured"
                    @if($ayuda->Featured == 1) checked @endif>
                    <label class="form-check-label" for="featured">
                        Es una ayuda destacada
                    </label>
                </div>
                <div class="form-check col-6 mt-3">
                    <input class="form-check-input" type="checkbox" name="tematicaobligatoria" id="tematicaobligatoria" @if($ayuda->TematicaObligatoria == 1) checked @endif>
                    <label class="form-check-label" for="tematicaobligatoria">Es de temática obligatoria?</label>
                </div>
                <div class="form-check col-6 mt-3">
                    <input class="form-check-input" type="checkbox" id="sellopyme" name="sellopyme"
                    @if($ayuda->SelloPyme == 1) checked @endif>
                    <label class="form-check-label" for="sellopyme">
                        Permite obtener el Sello Pyme Innovadora
                    </label>
                </div>
                <div class="form-check col-6 mt-3">
                    <input class="form-check-input" type="checkbox" id="informemotivado" name="informemotivado"
                    @if($ayuda->InformeMotivado == 1) checked @endif>
                    <label class="form-check-label" for="informemotivado">
                        Emite Informe Motivado
                    </label>
                </div>
                <div class="form-check col-6 mt-3">
                    <input class="form-check-input" type="checkbox" id="empresacrisis" name="empresacrisis"
                    @if($ayuda->EmpresaCrisis == 1) checked @endif>
                    <label class="form-check-label" for="empresacrisis">
                        Sólo empresas que no estén en crisis
                    </label>
                </div>
                <div class="form-check col-6 mt-3">
                    <input class="form-check-input" type="checkbox" id="minimis" name="minimis"
                    @if($ayuda->Minimis == 1) checked @endif>
                    <label class="form-check-label" for="minimis">
                        Minimis
                    </label>
                </div>
                <div class="form-check col-6 mt-3">
                    <input class="form-check-input" type="checkbox" id="efectoincentivador" name="efectoincentivador"
                    @if($ayuda->EfectoIncentivador == 1) checked @endif>
                    <label class="form-check-label" for="efectoincentivador">
                        Efecto incentivador
                    </label>
                </div>
            </div>
            <div class="form-check col-12 mt-3 mb-3">
                <label for="tiposobligatorios">Para tramitar esta ayuda es obligatorio subcontrar:</label><br/>                                                        
                <select name="tiposobligatorios[]" class="selectpicker" data-width="100%" title="Selecciona..." multiple>                                                            
                    @if($ayuda->tiposObligatorios !== null)
                    <option value="cti" @if(in_array('cti', json_decode($ayuda->tiposObligatorios, true))) selected @endif>Obligatorio Centro Tecnológico</option>
                    <option value="uni" @if(in_array('uni', json_decode($ayuda->tiposObligatorios, true))) selected @endif>Obligatorio Universidad</option>
                    @else
                    <option value="cti">Obligatorio Centro Tecnológico</option>
                    <option value="uni">Obligatorio Universidad</option>
                    @endif
                </select>
            </div>
            <div class="form-row mb-3">
                <div class="col-sm-6">
                    <label for="minempleados"><span class="text-danger">*</span> Número mínimo empleados</label><br/>
                    <input type="number" step="1" min="1" class="form-control" id="minempleados" min="0" step="1" name="minempleados" value="{{$ayuda->minEmpleados}}">
                </div>
                <div class="col-sm-6">
                    <label for="maxempleados"><span class="text-danger">*</span> Número máximo empleados</label><br/>
                    <input type="number" step="1" min="1" class="form-control" id="maxempleados" min="1" step="1" name="maxempleados" value="{{$ayuda->maxEmpleados}}">
                </div>
            </div>
            <div class="form-row mb-3">
                <div class="col-sm-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="esdegenero" name="esdegenero" @if($ayuda->esDeGenero == 1) checked @endif>
                        <label class="form-check-label" for="esdegenero">
                            Es una ayuda de género?
                        </label>
                    </div>
                </div>
                <div class="col-sm-12 mt-3">
                    <div class="form-group col-sm-12 @if($ayuda->esDeGenero == 0) d-none @endif" id="textodegenero">
                        <label for="textodegenero"><span class="text-danger">*</span> Mensaje a mostrar en el campo ayuda de género</label><br/>
                        <textarea class="form-control" name="textodegenero" rows="5" maxlength="250" @if($ayuda->esDeGenero == 1) required @endif>{{ $ayuda->textoGenero }}</textarea>
                        <span class="text-muted">̣Máximo 250 carácteres</span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="dnsh">DNSH</label><br/>
                <select name="dnsh" class="selectpicker" data-width="100%" title="Selecciona uno...">
                    <option value="no definido" @if($ayuda->Dnsh == "no definido") selected @endif>Sin DNSH</option>
                    <option value="opcional" @if($ayuda->Dnsh == "opcional") selected @endif>Opcional</option>
                    <option value="obligatorio" @if($ayuda->Dnsh == "obligatorio") selected @endif>Obligatorio</option>
                </select>
            </div>
            @if($ayuda->Dnsh == "opcional")
            <div class="form-group" id="mensajednsh">
            @else
            <div class="form-group d-none" id="mensajednsh">
            @endif
                <label for="mensajednsh"><span class="text-danger">*</span> Mensaje a mostrar en el campo DNSH</label><br/>
                <textarea type="text" class="form-control" name="mensajednsh" rows="5" maxlength="600">{{ $ayuda->MensajeDnsh }}</textarea>
                <span class="text-muted">̣Máximo 600 carácteres</span>
            </div>
            <div class="form-row mb-3">
                <div class="form-group col-sm-12">
                    <label for="garantias">Garantías</label><br/>
                    <select name="garantias" class="selectpicker" data-width="100%" title="Selecciona una...">
                        <option value="Si" @if($ayuda->Garantias == "Si") selected @endif>Si</option>
                        <option value="No" @if($ayuda->Garantias == "No") selected @endif>No</option>
                        <option value="Evaluación" @if($ayuda->Garantias == "Evaluación") selected @endif>Evaluación</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="textocondiciones">Texto Condiciones Garantías</label>
                <textarea class="form-control" id="textocondiciones" name="textocondiciones" rows="10">{{$ayuda->TextoCondiciones}}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Editar Convocatoria</button>
        </form>
	</div>
	<div class="card-footer">
		
	</div>
</div>

<h5 class="mt-3 mb-1">Encajes de la Convocatoria {{$ayuda->Titulo}}</h5>
<a href="{{route('admincrearencaje', $ayuda->id)}}" class="btn btn-primary mt-1 mb-3">{{__('Añadir nuevo encaje')}}</a>
<br/>
@foreach($encajes as $encaje)
    <div class="card collapsed-card">
        <div class="card-header">
            <h3 class="card-title cursor-pointer" data-card-widget="collapse" title="Collapse">  
                @if($encaje->Tipo == "Target")
                    <i class="fa-solid fa-bullseye fa-xs text-info"></i>
                @else
                    <i class="fa-solid fa-tags fa-xs text-info"></i>
                @endif
                {{$encaje->Titulo}}  
                
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-plus"></i>
                </button>			
            </div>
        </div>
        <div class="card-body">
            <div class="text-right">
                <form method="post" action="{{route('admindeleteencaje')}}" class="deleteencaje">
                    @csrf
                    <input type="hidden" name="id" value="{{$encaje->id}}"/>
                    <input type="hidden" name="ayuda_id" value="{{$encaje->Ayuda_id}}"/> 
                    <button class="btn btn-danger" type="submit"><i class="fas fa-trash"></i> {{__('Borrar encaje')}}</button>
                </form>
            </div>
            <form method="post" action="{{route('admineditencaje')}}" class="editencajes">
                @csrf
                <input type="hidden" name="id" value="{{$encaje->id}}"/>
                <input type="hidden" name="ayuda_id" value="{{$encaje->Ayuda_id}}"/>             
                <div class="form-group">
                    <label for="acronimo"><span class="text-danger">*</span> Acronimo</label>
                    <input type="text" class="form-control" id="acronimo" name="acronimo" value="{{$encaje->Acronimo}}" required>
                </div>
                <div class="form-group">
                    <label for="titulo"><span class="text-danger">*</span> Titulo</label>
                    <input type="text" class="form-control" id="titulo" name="titulo" value="{{$encaje->Titulo}}" required>
                </div>
                <div class="form-group">
                    <label for="tipo">Tipo</label><br/>
                    <select name="tipo" class="selectpicker" data-width="100%" title="Selecciona uno..." id="tipoencaje"  aria-describedby="tipoHelp">
                        <option value="Linea" @if($encaje->Tipo == "Linea") selected @endif>Linea</option>
                        <option value="Interna" @if($encaje->Tipo == "Interna") selected @endif>Interna</option>
                        <option value="Target" @if($encaje->Tipo == "Target") selected @endif>Target</option>
                    </select>
                    <small id="tipoHelp">* No es posible crear un encaje tipo target si la ayuda no tiene ningún encaje de tipo línea o interna ya creado</small>
                </div>
                <div class="form-group">
                    <label for="encajefechamax">Fecha Max. Constitucion</label><br/>                    
                    <input type="date" class="form-control" id="encajefechamax" name="encajefechamax" value="{{$encaje->Encaje_fechamax}}" min="{{\Carbon\Carbon::now()->format('Y-m-d')}}" max="{{$ayuda->FechaMaxConstitucion}}" />
                    <small id="encajefechamaxhelp" class="form-text text-muted">Un campo vaćio indica que la ayuda no tiene Fecha Max. Constitución, Formato fecha: dd/mm/yyyy</small>
                </div>
                <div class="form-group">
                <label for="cate">Categorías</label><br/>
                    <select name="cate" class="selectpicker" multiple data-width="100%" title="Selecciona uno..." disabled>
                        @if($ayuda->Categoria !== null && is_array(json_decode($ayuda->Categoria)))
                            @if(in_array("Micro", json_decode($ayuda->Categoria)))
                                <option value="Micro" selected="selected">Micro</option>
                            @else
                                <option value="Micro">Micro</option>
                            @endif
                            @if(in_array("Pequeña", json_decode($ayuda->Categoria)))
                                <option value="Pequeña" selected="selected">Pequeña</option>
                            @else
                                <option value="Pequeña">Pequeña</option>
                            @endif
                            @if(in_array("Mediana", json_decode($ayuda->Categoria)))
                                <option value="Mediana" selected="selected">Mediana</option>
                            @else
                                <option value="Mediana">Mediana</option>
                            @endif
                            @if(in_array("Grande", json_decode($ayuda->Categoria)))
                                <option value="Grande" selected="selected">Grande</option>
                            @else
                                <option value="Grande">Grande</option>
                            @endif
                        @else
                            <option value="Micro">Micro</option>
                            <option value="Pequeña">Pequeña</option>
                            <option value="Mediana">Mediana</option>
                            <option value="Grande">Grande</option>
                        @endif
                    </select>
                    <small id="catehelp" class="form-text text-muted">Los encajes tienen las categorías asignadas en la convocatoria, no son editables</small>
                </div>
                <div id="cnaesencajes" class="form-group">
                    <div class="form-group">
                        <label for="opcionCNAEEncaje"><span class="text-danger">*</span> Opcion CNAE</label><br/>
                        <select name="opcionCNAEEncaje" class="selectpicker" data-width="100%" title="Selecciona uno..." required>
                            @if($encaje->Encaje_opcioncnaes !== null)
                                <option value="Todos" @if($encaje->Encaje_opcioncnaes == "Todos") selected @endif>Todos</option>
                                <option value="Válidos" @if($encaje->Encaje_opcioncnaes == "Válidos") selected @endif>Válidos</option>
                                <option value="Excluidos" @if($encaje->Encaje_opcioncnaes == "Excluidos") selected @endif>Excluidos</option>
                            @elseif($ayuda->OpcionCNAE == "Válidos")
                                <option value="Válidos" selected>Válidos</option>
                                <option value="Excluidos">Excluidos</option>
                            @elseif($ayuda->OpcionCNAE == "Excluidos")
                                <option value="Válidos">Válidos</option>
                                <option value="Excluidos" selected>Excluidos</option>
                            @else
                                <option value="Todos" selected>Todos</option>
                                <option value="Válidos">Válidos</option>
                                <option value="Excluidos">Excluidos</option>
                            @endif
                        </select><br/>
                        <div id="cnaeseditencajes" @if($encaje->Encaje_opcioncnaes !== null && $encaje->Encaje_opcioncnaes == "Todos") class="d-none" @elseif($encaje->Encaje_opcioncnaes === null && $ayuda->OpcionCNAE == 'Todos') class="d-none" @endif>
                            <label for="cnaesencaje">CNAES</label><br/>
                            
                            <select name="cnaesencaje[]" multiple class="duallistbox" style="height: 250px !important;" id="cnaesencaje">
                                @if($encaje->Encaje_opcioncnaes !== null && $encaje->Encaje_opcioncnaes != "Todos")
                                    @foreach($cnaes as $cnae)
                                        @if($encaje->Encaje_cnaes !== null && in_array($cnae->Id_zoho, json_decode($encaje->Encaje_cnaes, true)))
                                            <option value="{{$cnae->Id_zoho}}" selected="selected">{{$cnae->Nombre}}</option>
                                        @else
                                            <option value="{{$cnae->Id_zoho}}">{{$cnae->Nombre}}</option>
                                        @endif
                                    @endforeach
                                @elseif($ayuda->OpcionCNAE != "Todos")
                                    @if($ayuda->CNAES != "null" && !empty($ayuda->CNAES))
                                        @foreach($cnaes as $cnae)
                                            @if($ayuda->CNAES)
                                                @if(in_array($cnae->Id_zoho, json_decode($ayuda->CNAES, true)))
                                                    <option value="{{$cnae->Id_zoho}}" selected="selected">{{$cnae->Nombre}}</option>
                                                @else
                                                    <option value="{{$cnae->Id_zoho}}">{{$cnae->Nombre}}</option>
                                                @endif
                                            @else
                                                <option value="{{$cnae->Id_zoho}}">{{$cnae->Nombre}}</option>
                                            @endif
                                        @endforeach
                                    @else
                                        @foreach($cnaes as $cnae)
                                            @if(in_array($cnae->Id_zoho, json_decode($encaje->Encaje_cnaes, true)))
                                                <option value="{{$cnae->Id_zoho}}">{{$cnae->Nombre}}</option>
                                            @else
                                                <option value="{{$cnae->Id_zoho}}">{{$cnae->Nombre}}</option>
                                            @endif
                                        @endforeach
                                    @endif
                                @else
                                    @foreach($cnaes as $cnae)
                                        <option value="{{$cnae->Id_zoho}}">{{$cnae->Nombre}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="naturaleza"><span class="text-danger">*</span> Naturaleza Empresa</label><br/>
                    <select name="naturaleza[]" id="naturaleza" class="selectpicker" multiple data-width="100%" title="Selecciona..." required>
                        @foreach($naturalezas as $naturaleza)
                            <option value="{{$naturaleza->id}}" @if(in_array($naturaleza->id, json_decode($encaje->naturalezaPartner))) selected @endif>{{$naturaleza->NombreNaturaleza}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="encajeintereses"><span class="text-danger">*</span> Perfil Financiación</label><br/>
                    <select name="encajeintereses[]" id="encajeintereses" class="selectpicker" multiple data-width="100%" data-live-search="true" title="Selecciona..." required>
                        @foreach($intereses as $interes)
                            @if($interes->id == 1 || $interes->id == 10 || $interes->id == 11)
                                @continue
                            @endif
                            <option value="{{$interes->Id_zoho}}" @if(in_array($interes->Id_zoho, json_decode($encaje->PerfilFinanciacion))) selected @endif>{{$interes->Nombre}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea class="form-control descripcion" id="descripcion" rows="5" name="descripcion">{{strip_tags($encaje->Descripcion)}}</textarea>
                </div>
                <div class="form-group">
                    <label for="palabrases">Palabras clave ES</label>
                    <textarea class="form-control" id="palabrases" name="palabrases" aria-describedby="pcesHelp">{{$encaje->PalabrasClaveES}}</textarea>
                    <small id="pcesHelp" class="form-text text-muted">Las palabras clave van separadas cada uno por una coma.</small>
                </div>
                <div class="form-group">
                    <label for="palabrasen">Palabras clave EN</label>
                    <textarea class="form-control" id="palabrasen" name="palabrasen" aria-describedby="pcenHelp">{{$encaje->PalabrasClaveEN}}</textarea>
                    <small id="pcenHelp" class="form-text text-muted">Las palabras clave van separadas cada uno por una coma.</small>
                </div>
                <div class="form-group">
                    <label for="tags">Tags Tecnología</label><br/>
                    <select class="tags-encaje" multiple="multiple" name="tags[]" style="width:100%">
                        @if($encaje->TagsTec !== null && $encaje->TagsTec != "")
                            @if(is_array(json_decode($encaje->TagsTec)))
                                @foreach(json_decode($encaje->TagsTec) as $tag)
                                    <option value="{{$tag}}" class="text-dark" selected>{{$tag}}</option>
                                @endforeach
                            @elseif(is_array(explode(",", $encaje->TagsTec)))
                                @foreach(explode(",", $encaje->TagsTec) as $tag)
                                    <option value="{{$tag}}" class="text-dark" selected>{{$tag}}</option>
                                @endforeach
                            @endif
                        @endif
                    </select>
                    <small id="tagsHelp" class="form-text text-muted">Máximo 20 tags tecnología por encaje.</small>
                </div>
                @if($encaje->keywords !== null)
                <div class="form-group chatgptkeywords">
                    <p>{{__('Chat GPT Keywords')}}</p>
                    <label>{{$encaje->keywords->keywords}}</label>
                </div>
                @endif            
                <button type="submit" class="btn btn-primary">Editar Encaje {{$encaje->Acronimo}}</button>              
            </form>
        </div>
        <div class="card-footer">
            
        </div>
    </div>
@endforeach
<div class="modal fade" id="organismoModal" tabindex="-1" role="dialog" aria-labelledby="organismoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="crerAyudaConvocatoriaLabel">Selecciona orgnaismo para esta convocatoria</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @if($ayuda->organo)
                    <span class="txt-azul">{{__('El organismo asignado es')}}: {{$ayuda->organo->Acronimo}} {{$ayuda->organo->Nombre}}</span>
                @elseif($ayuda->departamento)
                    <span class="txt-azul">{{__('El organismo asignado es')}}: {{$ayuda->departamento->Acronimo}} {{$ayuda->departamento->Nombre}}</span>
                @else
                    <span class="text-danger">{{__('Esta convocatoria no tiene organismo asignado')}}</span>
                @endif
                <label for="cifNombre">Buscar por nombre o por Acrónimo del organismo</label>
                <input type="text" class="form-control mb-3" id="textoOrganismos" placeholder="Buscar por título o Acrónimo"  aria-describedby="nombreHelp"/>
                <small id="nombreHelp" class="form-text text-muted">*La búsqueda se hará por el texto introducido siempre que aparezca esa parte de texto en el nombre o acrónimo de un organismo ayuda será develto.</small>
                <button class="btn btn-primary buscarorganismos">Buscar</button>                
            </div>
            <div class="modal-footer justify-content-start d-none" id="footer-dnone">
               <h4 class="w-100">Resultados de la búsqueda</h4><br/>
               <div class="resultados-busqueda"></div>
            </div>  
        </div>
    </div>
</div>
@stop
@section('css')
	<link rel="stylesheet" href="/css/admin_custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
            integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
            crossorigin="anonymous" referrerpolicy="no-referrer" />
@stop

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-autocomplete/1.0.7/jquery.auto-complete.min.js"
    integrity="sha512-TToQDr91fBeG4RE5RjMl/tqNAo35hSRR4cbIFasiV2AAMQ6yKXXYhdSdEpUcRE6bqsTiB+FPLPls4ZAFMoK5WA=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!--Bootstrap Select-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
    <!--DatePicker-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js" integrity="sha512-qTXRIMyZIFb8iQcfjXWCO8+M5Tbc38Qi5WzdPOYZHIlZpzBHG3L3by84BBBOiRGiEb7KKtAOAs5qYdUiZiQNNQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/js/tempusdominus-bootstrap-4.min.js" integrity="sha512-k6/Bkb8Fxf/c1Tkyl39yJwcOZ1P4cRrJu77p83zJjN2Z55prbFHxPs9vN7q3l3+tSMGPDdoH51AEU8Vgo1cgAA==" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/css/tempusdominus-bootstrap-4.min.css" integrity="sha512-3JRrEUwaCkFUBLK1N8HehwQgu8e23jTH4np5NHOmQOobuC4ROQxFwFgBLTnhcnQRMs84muMh0PnnwXlPq5MGjg==" crossorigin="anonymous" />
    <!-- Bootstrap4 Duallistbox -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap4-duallistbox/4.0.2/jquery.bootstrap-duallistbox.min.js" integrity="sha512-l/BJWUlogVoiA2Pxj3amAx2N7EW9Kv6ReWFKyJ2n6w7jAQsjXEyki2oEVsE6PuNluzS7MvlZoUydGrHMIg33lw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap4-duallistbox/4.0.2/bootstrap-duallistbox.min.css" integrity="sha512-BcFCeKcQ0xb020bsj/ZtHYnUsvPh9jS8PNIdkmtVoWvPJRi2Ds9sFouAUBo0q8Bq0RA/RlIncn6JVYXFIw/iQA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- jQuery Alerts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<script>  
        $(document).ready(function() {
            $(".tags-encaje").select2({
                tags: true,
                maximumSelectionLength: 20,
                width: 'resolve' 
            });       
        });
        $(".buscarorganismos").on('click', function(e){
            var text = $("#textoOrganismos").val();

            if(text == "" || text.length < 3){
                $.alert(
                    {
                        title: 'Texto mínimo',
                        content: 'Mínimo 3 carácteres para poder buscar un organismo'
                    }
                );
                return false;
            }
            $.ajax({
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                url: "{{ route('buscarorganismos') }}",
                type:'POST',
                data: {text: text},
                success: function(resp){
                    $("#footer-dnone").removeClass('d-none');
                    $(".resultados-busqueda").empty();
                    $(".resultados-busqueda").append(resp);
                },
                error: function(resp){
                    $("#footer-dnone").addClass('d-none');
                    $(".resultados-busqueda").empty();
                    $.alert(
                        {
                            title: 'Texto no encontrado',
                            content: resp.responseText+' prueba modificando la búsqueda para ver si encuentras la convocatoria'
                        }
                    );
                    return false;
                }
            });

            return false;
        });

        $(document).on('click', '.setorganismo', function(e){
            var value = $(this).attr('data-item');
            $.confirm({
                title: 'Cambio en Organismo Convocatoria',
                content: 'Vas a Asignar/Cambiar el organismo de una convocatoria,¿Estás seguro?',
                buttons: {
                    SI: function(){
                        $("#organismoModal").modal('toggle');
                        $('input[name="organo"]').val(value);
                    },
                    NO: function(){}
                }
            });        
        });
        $("#inicio").datetimepicker({
            format: 'DD/MM/YYYY',
            viewMode: 'years',
            defaultDate: "{{$ayuda->Inicio}}"
        });
        $("#fin").datetimepicker({
            format: 'DD/MM/YYYY',
            viewMode: 'years',
            defaultDate: "{{$ayuda->Fin}}"
        });
        $("#fechaemails").datetimepicker({
            format: 'DD/MM/YYYY',
            viewMode: 'years',
            defaultDate: "{{$ayuda->fechaEmails}}"
        });
        $("#fechamax").datetimepicker({
            format: 'DD/MM/YYYY',
            viewMode: 'years',
            defaultDate: "{{$ayuda->FechaMaxConstitucion}}"
        });
        $("#fechamin").datetimepicker({
            format: 'DD/MM/YYYY',
            viewMode: 'years',
            defaultDate: "{{$ayuda->FechaMinConstitucion}}"
        });
        $(".editarconvocatoria input").each(function() {
            var element = $(this);
            if (element.val() == "") {
                $(element).addClass('bg-amarillo');
            }
        });
        $('select[name="naturaleza_convocatoria[]"]').on("changed.bs.select", function(e){
            if($.inArray("6668837", $(this).val()) > -1){
                $('.categoria-convocatoria').removeClass('d-none');
            }else{
                $('.categoria-convocatoria').addClass('d-none');
            }
        });

        $('select[name="opcionCNAE"]').on("changed.bs.select", function(e){
            if($(this).val() == "Todos"){
                $("#cnaes").addClass('d-none');
                $("#cnaes").attr('required', false);
            }else{
                $("#cnaes").removeClass('d-none');
                $("#cnaes").attr('required', true);
            }
        });

        $('input[name="esdegenero"]').on('change', function(e){
            if($('#textodegenero').is(':visible')){
                $('#textodegenero').addClass('d-none');
                $('textarea[name="textodegenero"]').prop('required', false);
            }else{
                $('#textodegenero').removeClass('d-none');
                $('textarea[name="textodegenero"]').prop('required', true);
            }
        });

        $('#limitarempleados').on('click', function(e){
            if($('#numempleados').is(':visible')){
                $('#numempleados').addClass('d-none');
                $("#minempleados").attr('required', false);
                $("#maxempleados").attr('required', false);
                $("#minempleados").val(null);
                $("#maxempleados").val(null);
            }else{
                $('#numempleados').removeClass('d-none');
                $("#minempleados").attr('required', true);
                $("#maxempleados").attr('required', true);
            }
        })

        $('select[name="ambito"]').on("changed.bs.select", function(e){
            if($(this).val() == "Comunidad Autónoma"){
                $("#ccaas").removeClass('d-none');
                $("#ccaas").attr('required', true);
            }else{
                $("#ccaas").addClass('d-none');
                $("#ccaas").attr('required', false);
            }
        });

        $('select[name="departamento"]').on('changed.bs.select', function(){
            $('select[name="organo"]').val('');
            $('select[name="organo"]').selectpicker('render');
        });

        $('select[name="organo"]').on('changed.bs.select', function(){
            $('select[name="departamento"]').val('');
            $('select[name="departamento"]').selectpicker('render');
        });


        $('.editarconvocatoria').on('submit', function(e){
            e.preventDefault();
            var form = $(this);
            var organismo = $('.editarconvocatoria').find('input[name="organo"]').val();
            if(organismo == "" || organismo === undefined){
                $.confirm({
                    title: 'Convocatoria sin Organismo',
                    content: 'Esta convocatoria no tiene Organismo asignado',                                                                                                                                                
                    buttons: {
                        ok: function(){
                        }
                    },             
                    onDestroy: function () {
                        document.getElementById('organismo').scrollIntoView({behavior: "smooth"});  
                    },
                });
                
                return false;
            }

            $(form).unbind('submit').submit();    
            
        });

        $('.duallistbox').bootstrapDualListbox();
        $(".publicarconvocatoria").on('submit', function(e){
            e.preventDefault();
            var form = $(this);
            $.confirm({
                title: 'Cambiar estado de publicación',
                content: 'Vas a cambiar el estado de publicación de esta ayuda ¿estas seguro?',
                buttons: {
                    ok:  function(){
                        $(form).unbind('submit').submit();    
                    },
                    cancel: function(){}
                }
            });
        });
 
        $('.editencajes select[name="opcionCNAEEncaje"]').on("changed.bs.select", function(e){
            if($(this).val() == "Todos"){
                $('#cnaeseditencajes').addClass('d-none');
                $('.editencajes select[name="cnaesencaje"]').attr('required', false);
            }else{
                $('#cnaeseditencajes').removeClass('d-none');
                $('.editencajes select[name="cnaesencaje"]').attr('required', true);
                $('.editencajes .duallistbox').bootstrapDualListbox('refresh', true);
            }
        });

        function scrollBottom(){             
            window.scroll({
                top:  $(document).height(),
                behavior: 'smooth'
            });          
        }
    </script>
@stop   