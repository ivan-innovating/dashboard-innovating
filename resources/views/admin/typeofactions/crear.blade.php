@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Ayudas/Convocatorias</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Crear Type of Action</h3>
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

        {{ html()->form('POST', route('adminsavetypeofaction'))->open()}} 
        <div class="form-group">
            {{ html()->label('<span class="text-danger">*</span> Nombre', 'nombre') }}
            {{ html()->text('nombre', null)->class('form-control')->required()->maxlength(100) }}
        </div>
        <div class="form-group">
            {{ html()->label('<span class="text-danger">*</span> Acronimo', 'acronimo') }}
            {{ html()->text('acronimo', null)->class('form-control')->required()->maxlength(100) }}
        </div>
        <div class="form-group">
            {{ html()->label('<span class="text-danger">*</span> Presentación', 'presentacion') }}
            {{ html()->select('presentacion[]', ['Individual' => 'Individual', 'Consorcio' => 'Consorcio'], null)->class('form-control multiple-select')->required()->multiple() }}
        </div>
        <div class="form-group">
            {{ html()->label('<span class="text-danger">*</span> Naturaleza Empresas', 'naturaleza') }}
            {{ html()->select('naturaleza[]', $naturalezas, null)->class('form-control multiple-select')->required()->multiple() }}
        </div>
        <div class="form-group">
            {{ html()->label('<span class="text-danger">*</span> Categoria Empresas', 'categoria') }}
            {{ html()->select('categoria[]', ['Micro' => 'Micro','Pequeña' => 'Pequeña','Mediana' => 'Mediana', 'Grande' => 'Grande'], null)->class('form-control multiple-select')->required()->multiple() }}
        </div>
        <div class="form-group">
            {{ html()->label('<span class="text-danger">*</span> TRL', 'trl') }}
            {{ html()->select('trl', $trls, null)->class('form-control')->required() }}
        </div>
        <div class="form-group">
            {{ html()->label('<span class="text-danger">*</span> Perfil de financiación', 'perfil_financiacion') }}
            {{ html()->select('perfil_financiacion[]', $intereses, null)->class('form-control multiple-select')->required()->multiple() }}                                                        
        </div>
        <div class="form-group">
            {{ html()->label('<span class="text-danger">*</span> Tipo de financiacion', 'tipo_financiacion') }}
            {{ html()->select('tipo_financiacion[]', ['Crédito' => 'Crédito', 'Fondo perdido' => 'Fondo perdido'], null)->class('form-control multiple-select')->required()->multiple() }}
        </div>
        <div class="form-group">
            {{ html()->label('<span class="text-danger">*</span> Objetivo de financiacion', 'objetivo_financiacion') }}
            {{ html()->select('objetivo_financiacion[]', ['Proyectos' => 'Proyectos', 'Empresas' => 'Empresas', 'Personas' => 'Personas'],  null)->class('form-control multiple-select')->required()->multiple() }}
        </div>
        <div class="form-group">
            {{ html()->label('<span class="text-danger">*</span> Fondo perdido mínimo', 'fondo_perdido_minimo') }}
            {{ html()->number('fondo_perdido_minimo', null, 0)->class('form-control')->required() }}
        </div>
        <div class="form-group">
            {{ html()->label('<span class="text-danger">*</span> Fondo perdido máximo', 'fondo_perdido_maximo') }}
            {{ html()->number('fondo_perdido_maximo', null, 0)->class('form-control')->required() }}
        </div>

        <button type="submit" class="btn btn-primary">Crear Type of Action</button>
            
        {{ html()->form()->close()}}
        
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
            $('.multiple-select').select2({
                placeholder: "Selecciona...",
            });
        });
    </script>
@stop   