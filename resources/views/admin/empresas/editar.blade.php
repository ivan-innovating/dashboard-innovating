@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard Empresas</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Editar empresa {{$empresa->Nombre}}</h3>
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
        {{ html()->form('POST', route('admineditempresa'))->class('editempresa')->open()}}  
            <input type="hidden" name="id" value="{{$empresa->id}}"/>
            <input type="hidden" name="noeszoho" value="{{$nozoho}}"/>
            <p class="text-muted">LastEditor: @if(isset($empresa->updatedBy)) {{$empresa->updatedBy}} @else "No editada" @endif</p>
            <p class="text-muted">Ver página pública: <a href="https://innovating.works/empresa/{{$empresa->uri}}">{{$empresa->Nombre}}</a></p>
            <div class="form-group">
                <label for="nombre"><span class="text-danger">*</span> Nombre</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="{{$empresa->Nombre}}" required>
            </div>
            <div class="form-group">
                <label for="marca"><span class="text-danger">*</span> Marca</label>
                <input type="text" class="form-control" id="marca" name="marca" value="{{$empresa->Marca}}" required>
            </div>
            <div class="form-group">
                <label for="nombre"><span class="text-danger">*</span> Url página pública</label>
                <input type="text" class="form-control" id="uri" name="uri" value="{{$empresa->uri}}" required>
                <small>* cambiar solo en caso de que la empresa no sea accesilbe</small>
            </div>
            <label for="cif"><span class="text-danger">*</span> CIF</label><br/>
            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <div class="input-group-text">
                        <input type="checkbox" class="habilitacif">
                    </div>
                </div>
                <input type="text" class="form-control" id="cif" name="cif" value="{{$empresa->CIF}}" readonly required>
            </div>
            <div class="form-group">
                <label for="web">Sitio web</label><br/>
                <input type="text" class="form-control" id="web" name="web" value="{{$empresa->Web}}">
                <small>* Sitio web de la empresa, formato(http://... o https://...)</small>
            </div>
            <div class="form-group">
                <label for="nombre"><span class="text-danger">*</span> CCAA</label><br/>
                <select name="ccaa" class="selectpicker" data-width="100%" title="Selecciona uno..." required>
                    @foreach($ccaa as $ca)
                        @if($ca->Nombre == $empresa->Ccaa)
                            <option value="{{$ca->Nombre}}" selected="selected">{{$ca->Nombre}}</option>
                        @else
                            <option value="{{$ca->Nombre}}">{{$ca->Nombre}}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="nombre"><span class="text-danger">*</span> Intereses</label><br/>
                <select name="intereses[]" class="selectpicker" multiple data-width="100%" title="Selecciona uno..." required>
                    @foreach($intereses as $interes)
                        @if($empresa->Intereses !== null && $empresa->Intereses !== "null")
                            @if(in_array($interes->Nombre, json_decode($empresa->Intereses, true)))
                                <option value="{{$interes->Nombre}}" selected="selected">{{$interes->Nombre}}</option>
                            @else
                                <option value="{{$interes->Nombre}}">{{$interes->Nombre}}</option>
                            @endif
                        @else
                            <option value="{{$interes->Nombre}}">{{$interes->Nombre}}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="naturaleza"><span class="text-danger">*</span> Naturaleza</label><br/>
                <select name="naturaleza[]" class="selectpicker" data-width="100%" title="Selecciona uno..." required multiple>
                    @foreach($naturalezas as $naturaleza)
                        @if(in_array($naturaleza->id, json_decode($empresa->naturalezaEmpresa, true)))
                            <option value="{{$naturaleza->id}}" selected="selected">{{$naturaleza->NombreNaturaleza}}</option>
                        @else
                            <option value="{{$naturaleza->id}}">{{$naturaleza->NombreNaturaleza}}</option>
                        @endif

                    @endforeach
                </select>
            </div>                                                    
            <div class="form-group @if(!in_array('6668843', json_decode($empresa->naturalezaEmpresa, true))) d-none @endif" id="idOrganismo">
                <label for="idorganismo"><span class="text-danger">*</span> Organismo público</label><br/>
                <select name="idorganismo" class="selectpicker" data-width="100%"  data-live-search="true" title="Selecciona uno..." @if(in_array('6668843', json_decode($empresa->naturalezaEmpresa, true))) required @endif>
                    @foreach($organismos as $org)
                        @if(isset($empresa->idOrganismo) && $empresa->idOrganismo == $org['id'])
                            <option value="{{$org['id']}}" selected="selected">@if($org['Acronimo'] !== null) {{$org['Acronimo']}} @endif {{$org['Nombre']}}</option>
                        @else
                            <option value="{{$org['id']}}">@if($org['Acronimo'] !== null) {{$org['Acronimo']}} @endif {{$org['Nombre']}}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            @if(in_array("6668840", json_decode($empresa->naturalezaEmpresa, true)))
            <div class="form-check" id="checkuniversidadprivada">
            @else
            <div class="form-check d-none" id="checkuniversidadprivada">
            @endif
                <input class="form-check-input" type="checkbox" id="universidadprivada" name="universidadprivada" @if(isset($empresa->esUniversidadPrivada) && $empresa->esUniversidadPrivada == 1) checked @endif>
                <label class="form-check-label" for="universidadprivada">
                    Marcar si es una universidad privada
                </label>
            </div>
            @if(isset($textos))
            <div class="form-group">
                <label for="nombre">Textos Documentos</label><br/>
                <textarea name="textos_documentos" rows="4" class="form-control">{{$textos->Textos_Documentos}}</textarea>
            </div>
            <div class="form-group">
                <label for="nombre">Textos Proyectos</label><br/>
                <textarea name="textos_proyectos" rows="4" class="form-control">{{$textos->Textos_Proyectos}}</textarea>
            </div>
            <div class="form-group">
                <label for="nombre">Textos Tecnología</label><br/>
                <textarea name="textos_tecnologia" rows="4" class="form-control">{{$textos->Textos_Tecnologia}}</textarea>
            </div>
            <div class="form-group">
                <label for="nombre">Textos Tramitaciones</label><br/>
                <textarea name="textos_tramitaciones" rows="4" class="form-control">{{$textos->Textos_Tramitaciones}}</textarea>
            </div>
            @else
            <div class="form-group">
                <label for="nombre">Textos Documentos</label><br/>
                <textarea name="textos_documentos" rows="4" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <label for="nombre">Textos Proyectos</label><br/>
                <textarea name="textos_proyectos" rows="4" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <label for="nombre">Textos Tecnología</label><br/>
                <textarea name="textos_tecnologia" rows="4" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <label for="nombre">Textos Tramitaciones</label><br/>
                <textarea name="textos_tramitaciones" rows="4" class="form-control"></textarea>
            </div>
            @endif
            <div class="form-row mb-3">
                <div class="col">
                    <div class="form-check">
                        @if($empresa->esConsultoria == 1)
                            <input class="form-check-input" type="checkbox" name="esconsultoria" id="esconsultoria" checked>
                        @else
                            <input class="form-check-input" type="checkbox" name="esconsultoria" id="esconsultoria">
                        @endif
                        <label for="esconsultoria">¿Tiene acceso a Consultoría?</label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="maxproyectos">Número máximo de proyectos</label><br/>
                @if(in_array("6668838", json_decode($empresa->naturalezaEmpresa)) || in_array("6668840", json_decode($empresa->naturalezaEmpresa)))
                <input type="number" min="3" max="1000" class="form-control" id="maxproyectos" name="maxproyectos" value="{{$empresa->maxProyectos}}" required>
                @else
                <input type="number" min="3" max="20" class="form-control" id="maxproyectos" name="maxproyectos" value="{{$empresa->maxProyectos}}" required>
                @endif
                <small>* Número máximo de proyectos que esta empresa puede crear, por defecto son 3.</small>
            </div>
            <div class="form-group mt-3">
                @if($empresa->TextosLineasTec)
                    @php
                        $lineas = json_decode($empresa->TextosLineasTec, true);
                    @endphp
                @endif                
                <label><span class="text-danger">*</span> {{__('Líneas Tecnológicas')}}:</label>
                <select class="form-control js-example-tags" multiple="multiple" name="tagsanalisis[]" required>
                    @if(isset($lineas) && !empty($lineas))
                        @foreach($lineas as $linea)
                            <option value="{{$linea}}" selected="selected">{{$linea}}</option>
                        @endforeach
                    @endif
                </select> 
                <small id="help" class="form-text text-muted">{{__('Máximo 20 palabras tecnológicas')}}</small>                                      
                <small id="help" class="form-text text-info">* Escribe palabras tecnológicas que esten vinculadas con el trabajo a realizar por el partner. Recuerda "Inteligencia artificial" no es lo mismo que "Inteligencia", "Artificial"</small>
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
        {{ html()->form()->close() }}
    </div>
</div>
@stop

@section('css')
	<!--<link rel="stylesheet" href="/css/admin_custom.css">-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
            integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
            crossorigin="anonymous" referrerpolicy="no-referrer" />	
@stop

@section('js')
	<!-- jQuery Alerts -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>    
    <!--Bootstrap Select-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-autocomplete/1.0.7/jquery.auto-complete.min.js"
 integrity="sha512-TToQDr91fBeG4RE5RjMl/tqNAo35hSRR4cbIFasiV2AAMQ6yKXXYhdSdEpUcRE6bqsTiB+FPLPls4ZAFMoK5WA=="
 crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
 	<script type="text/javascript">
        $('.habilitacif').on('click', function(e){
            e.preventDefault();
            $.confirm({
                title: 'Editar CIF',
                content: 'Vas a habilitar la edición del CIF de esta empresa, ¿estas seguro?.',
                buttons: {
                    confirm: function(){
                        $('input[name="cif"]').attr('readonly', false);
                    },
                    cancel: function(){
                        return false;
                    }
                }
            });
        });
        // In your Javascript (external .js resource or <script> tag)
        $(document).ready(function() {
            $(".js-example-tags").select2({
                tags: true,
                maximumSelectionLength: 20
            });
        });
	</script>
@stop   