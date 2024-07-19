@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Proyectos</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Editar proyecto {{$proyecto->Titulo}}</h3>
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
        <div class="text-right">
            @if($proyecto->projectRawData !== null)
            <a href="{{route('adminviewdatoscordis', $proyecto->id_raw_data)}}">Ver Datos extraídos de CORDIS</a>
            @endif
        </div>        
        <form method="post" action="{{route('dashboardeditproyecto')}}" class="editaproyecto">
            @csrf
            <input type="hidden" name="id" value="{{$proyecto->id}}"/>
            <input type="hidden" name="id_encaje" value="{{$proyecto->IdEncaje}}"/>
            <div class="form-group">
                <label for="acronimo"><span class="text-danger">*</span> Acronimo</label>
                <input type="text" class="form-control" id="acronimo" name="acronimo" maxlength="12" aria-describedby="acronimoHelp" placeholder="Nombre del proyecto" required value="{{$proyecto->Acronimo}}">
                <small id="acronimoHelp" class="form-text text-muted">Acronimo del proyecto(max: 12 carácteres).</small>
            </div>
            <div class="form-group">
                <label for="titulo"><span class="text-danger">*</span> Titulo</label>
                <input type="text" class="form-control" id="titulo" name="titulo" aria-describedby="tituloHelp" placeholder="Título del proyecto" required  value="{{$proyecto->Titulo}}">
                <small id="tituloHelp" class="form-text text-muted">Titulo identificativo del proyecto.</small>
            </div>
            <div class="form-group">
                <label for="descproyecto"><span class="text-danger">*</span> Descripción el proyecto</label>
                <textarea class="form-control" maxlength="300" id="descproyecto" name="descproyecto" aria-describedby="descproyectoHelp" placeholder="Descripción del proyecto a realizar" required>{{$proyecto->Descripcion}}</textarea>
                <small id="descproyectoHelp" class="form-text text-muted">Breve descripción del proyecto(max: 300 carácteres).</small>
            </div>
            <div class="form-group">
                <label for="ayuda"><span class="text-danger">*</span> Ayuda asociada al proyecto</label>
                <select class="form-control selectpicker" id="ayuda" name="ayuda" required data-width="100%" data-live-search="true" title="Selecciona una...">
                    @foreach($ayudas as $ayu)
                        <option value="{{$ayu->Id}}" @if($ayu->IdConvocatoriaStr == $proyecto->idAyudaAcronimo) selected @endif>{{$ayu->Titulo}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mt-3 mb-3">
                <label for="ayuda"><span class="text-danger">*</span> Estado del proyecto</label>
                <select class="form-control selectpicker" id="estado" name="estado" required data-width="100%" data-live-search="true" title="Selecciona uno...">
                    <option value="Abierto" @if($proyecto->Estado == "Abierto") selected @endif>Abierto</option>
                    <option value="Cerrado" @if($proyecto->Estado == "Cerrado") selected @endif>Cerrado</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
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
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $(".js-example-tags").select2({
            tags: true,
            maximumSelectionLength: 40
        });
    });
    $('form').on('submit', function(){
        var $preloader = $('.preloader');
        $preloader.css('height', '100%');
        setTimeout(function () {
            $preloader.children().show();
        });
    });
</script>
@stop   