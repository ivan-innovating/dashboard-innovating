@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Ayudas/Convocatorias</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Crear Ayuda</h3>
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
        {{ html()->form('POST', route('adminsaveayuda'))->open()}}            
            <div class="form-group">
                {{ html()->label( '<span class="text-danger">*</span> Acrónimo', 'acronimo') }}
                {{ html()->text('acronimo', null)->class('form-control')->required()->maxlength(250) }}
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Título', 'titulo') }}
                {{ html()->text('titulo', null)->class('form-control')->required()->maxlength(250) }}
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Descripción', 'descripcion') }}
                {{ html()->textarea('descripcion', null)->class('form-control')->required()->maxlength(250)->rows(5) }}
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Mes apertura 1', 'mes_1') }}
                {{ html()->select('mes_1', $meses, 0)->class('form-control')->required() }}
            </div>
            <div class="form-group">
                {{ html()->label('Mes apertura 2', 'mes_2') }}
                {{ html()->select('mes_2', $meses, null)->class('form-control')->placeholder('Selecciona uno si es necesario') }}
            </div>
            <div class="form-group">
                {{ html()->label('Mes apertura 3', 'mes_3') }}
                {{ html()->select('mes_3', $meses, null)->class('form-control')->placeholder('Selecciona uno si es necesario') }}
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Duración convocatorias(nº de meses)', 'duracion') }}
                {{ html()->select('duracion', $meses, 0)->class('form-control')->required() }}
            </div>
            <div class="form-check pl-1">
                <input type="checkbox" name="esindefinida" id="esindefinida"/>
                <label for="esindefinida">Es una ayuda indefinida?</label>
            </div>
            <button type="submit" class="btn btn-primary">Crear ayuda</button>
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
	<script></script>
@stop   