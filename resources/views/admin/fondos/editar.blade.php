@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Fondos</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Editar fondo {{$fondo->nombre}}</h3>
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
            <small class="text-muted">
                @if($graficos !== null) {{__('Útlima actualización')}}: {{ $graficos->updated_at }} @endif                                                  
            </small>
            @if($fondo->matches_budget_application !== null)
            {{ html()->form('POST', route('actualizargraficosfondo'))->open() }}
                {{html()->hidden('id', $fondo->id)}}
                <button type="submit" class="btn btn-warning btn-sm"><i class="fa-solid fa-chart-simple"></i> Actualizar Datos Gráficos</button>
            {{ html()->form()->close()}}
            @else
                <button type="submit" class="btn btn-warning btn-sm" disabled><i class="fa-solid fa-chart-simple"></i> Actualizar Datos Gráficos</button>
                <small class="text-muted">* Para poder crear o actualizar los datos de un gráficos es necesario completar el campo "Palabras budget application"</small>
            @endif
        </div>
        {{ html()->form('POST', route('admineditfondo'))->class('submitfondo')->open() }}
            {{ html()->hidden('id', $fondo->id)}}          
            {{ html()->hidden('old_name', $fondo->nombre)}}          
                <div class="form-group">
                    {{ html()->label('<span class="text-danger">*</span> Nombre','nombre') }}
                    {{ html()->text('nombre', $fondo->nombre)->class('form-control')->required()->maxlength(100) }}
                </div>
                <div class="form-group">
                    {{ html()->label('Descripción del fondo','descripcion') }}
                    {{ html()->textarea('descripcion', $fondo->descripcion)->class('form-control')->required()->maxlength(250)->rows(5) }}
                </div>
                <div class="form-group">                                                  
                    <label for="tags"><span class="text-danger">*</span> {{__('Palabras budget application para Match fondo->concesiones')}}</label>
                    <select class="form-control js-example-tags" multiple="multiple" name="tags[]">
                        @if($fondo->matches_budget_application !== null)
                            @foreach(json_decode($fondo->matches_budget_application, true) as $linea)                        
                                <option value="{{$linea}}" selected="selected">{{$linea}}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="form-group">
                    {{ html()->label('<span class="text-danger">*</span> Estado','estado') }}
                    {{ html()->checkbox('estado', $fondo->status, $fondo->status) }}
                    <small class="text-muted">* marcar para hacer el fondo seleccionable en el admin de ayudas y visible en la página de ayuda pública.</small>
                </div>
                <div class="form-group">
                    {{ html()->label('<span class="text-danger">*</span> Mostrar Gráficos','mostrar_graficos') }}
                    {{ html()->checkbox('mostrar_graficos', $fondo->mostrar_graficos, $fondo->mostrar_graficos) }}
                    <small class="text-muted">* marcar para hacer visible la página de estadísticas de los ùltimos 12 meses.</small>
                </div>
                <button type="submit" class="btn btn-primary">Actualizar fondo</button>
            {{ html()->form()->close() }}
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