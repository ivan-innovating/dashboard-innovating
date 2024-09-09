@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Ayudas/Convocatorias</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Crear Encaje para la Convocatoria {{$ayuda->Titulo}} <a href="{{route('admineditarconvocatoria', $ayuda->id)}}" class="btn btn-primary btn-sm"><i class="fa-solid fa-share"></i> Volver a editar convocatoria</a></h3>
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
        <form method="post" action="{{route('adminsaveencaje')}}" class="addencajes">
            @csrf
            <input type="hidden" name="ayuda_id" value="{{$ayuda->id}}"/>
                <div class="form-group">
                    <label for="acronimo"><span class="text-danger">*</span> Acronimo</label>
                    <input type="text" class="form-control" id="acronimo" name="acronimo" value="" required>
                </div>
                <div class="form-group">
                    <label for="titulo"><span class="text-danger">*</span> Titulo</label>
                    <input type="text" class="form-control" id="titulo" name="titulo" value="" required>
                </div>
                <div class="form-group">
                    <label for="estado"><span class="text-danger">*</span> Tipo</label><br/>
                    <select name="tipo" class="selectpicker" data-width="100%" title="Selecciona uno..." required aria-describedby="tipoHelp" id="addtipoencaje">
                        <option value="Linea">Linea</option>
                        <option value="Interna">Interna</option>
                        <option value="Target">Target</option>
                    </select>
                    <small id="tipoHelp">* No es posible crear un encaje tipo target si la ayuda no tiene ningún encaje de tipo línea o interna ya creado</small>
                </div>
                <div id="addcnaesencajes" class="form-group">
                    <div class="form-group">
                        <label for="opcionCNAEEncaje"><span class="text-danger">*</span> Opcion CNAE</label><br/>
                        <select name="opcionCNAEEncaje" class="selectpicker" data-width="100%" title="Selecciona uno...">         
                            <option value="Todos">Todos</option>
                            <option value="Válidos">Válidos</option>
                            <option value="Excluidos">Excluidos</option>                        
                        </select><br/>
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
                    </div>
                </div>
                <div class="form-group">
                    <label for="naturaleza"><span class="text-danger">*</span> Naturaleza Empresa</label><br/>
                    <select name="naturaleza[]" class="selectpicker" multiple data-width="100%" title="Selecciona..." required>
                        @foreach($naturalezas as $naturaleza)
                            <option value="{{$naturaleza->id}}">{{$naturaleza->NombreNaturaleza}}</option>
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
                            @if($ayuda->PerfilFinanciacion && $ayuda->PerfilFinanciacion != "null")
                                @if(in_array($interes->Id_zoho, json_decode($ayuda->PerfilFinanciacion, true)))
                                    <option value="{{$interes->Id_zoho}}" selected>{{$interes->Nombre}}</option>
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
                    <label for="descripcionadd">Descripción</label>
                    <textarea class="form-control descripcionadd" id="descripcionadd" rows="5" name="descripcion"></textarea>
                </div>
                <div class="form-group">
                    <label for="palabrases">Palabras clave ES</label>
                    <textarea class="form-control palabrases" id="palabrases" name="palabrases" aria-describedby="pcesHelp"></textarea>
                    <small id="pcesHelp" class="form-text text-muted">Las palabras clave van separadas cada uno por una coma.</small>
                </div>
                <div class="form-group">
                    <label for="palabrasen">Palabras clave EN</label>
                    <textarea class="form-control" id="palabrasen" name="palabrasen" aria-describedby="pcenHelp"></textarea>
                    <small id="pcenHelp" class="form-text text-muted">Las palabras clave van separadas cada uno por una coma.</small>
                </div>
                <div class="form-group">
                    <label for="tags">Tags Tecnología</label>
                    <select class="tags-encaje" multiple="multiple" name="tags[]" style="width:100%"></select>
                    <small id="tagsHelp" class="form-text text-muted">* Máximo 20 tags tecnología por encaje.</small>
                </div>

            <button type="submit" class="btn btn-primary enviarencaje">Crear Encaje</button>                
        </form>
	</div>
	<div class="card-footer">
		
	</div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-autocomplete/1.0.7/jquery.auto-complete.min.js"
    integrity="sha512-TToQDr91fBeG4RE5RjMl/tqNAo35hSRR4cbIFasiV2AAMQ6yKXXYhdSdEpUcRE6bqsTiB+FPLPls4ZAFMoK5WA=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!--Bootstrap Select-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
    <!--Select2-->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <!-- Bootstrap4 Duallistbox -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap4-duallistbox/4.0.2/jquery.bootstrap-duallistbox.min.js" integrity="sha512-l/BJWUlogVoiA2Pxj3amAx2N7EW9Kv6ReWFKyJ2n6w7jAQsjXEyki2oEVsE6PuNluzS7MvlZoUydGrHMIg33lw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap4-duallistbox/4.0.2/bootstrap-duallistbox.min.css" integrity="sha512-BcFCeKcQ0xb020bsj/ZtHYnUsvPh9jS8PNIdkmtVoWvPJRi2Ds9sFouAUBo0q8Bq0RA/RlIncn6JVYXFIw/iQA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script>  
        $(document).ready(function() {
            $(".tags-encaje").select2({
                tags: true,
                maximumSelectionLength: 20,
                width: 'resolve' 
            });       
        });
        $('.duallistbox').bootstrapDualListbox();

        $('.addencajes select[name="opcionCNAEEncaje"]').on("changed.bs.select", function(e){
            if($(this).val() == "Todos"){
                $('#cnaesaddencajes').addClass('d-none');
                $('.addencajes select[name="cnaesencaje"]').attr('required', false);
            }else{
                $('#cnaesaddencajes').removeClass('d-none');
                $('.addencajes select[name="cnaesencaje"]').attr('required', true);
                $('.addencajes .duallistbox').bootstrapDualListbox('refresh', true);
            }
        });
    </script>
@stop   