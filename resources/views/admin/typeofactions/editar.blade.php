@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Fondos</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Editar Type of Action {{$actions->nombre}}</h3>
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
        {{ html()->form('POST', route('adminedittypeofaction'))->class('submitaction')->open() }}
            <input type="hidden" name="id" value="{{$actions->id}}"/>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Nombre', 'nombre') }}
                {{ html()->text('nombre', $actions->nombre)->class('form-control')->required() }}
            </div>
            <div class="form-group">
                {{ html()->label('Acrónimo', 'acronimo') }}
                {{ html()->text('acronimo', $actions->acronimo)->class('form-control') }}                                                        
            </div>
            <div class="form-group">
                {{ html()->label('Subfondo', 'subfondo_id') }}
                @if($actions->subfondo_id !== null && $actions->subfondo_id > 0)
                    {{ html()->select('subfondo_id', $subfondos, $actions->subfondo_id)->class('form-control')->placeholder('Selecciona uno...') }}
                @else
                    {{ html()->select('subfondo_id', $subfondos,  null)->class('form-control')->placeholder('Selecciona uno...') }}
                @endif
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Presentación', 'presentacion') }}
                @if($actions->presentacion !== null && is_array(json_decode($actions->presentacion, true)))
                    {{ html()->select('presentacion[]', ['Individual' => 'Individual', 'Consorcio' => 'Consorcio'], json_decode($actions->presentacion, true))->class('form-control multiple-select')->required()->multiple() }}
                @else
                    {{ html()->select('presentacion[]', ['Individual' => 'Individual', 'Consorcio' => 'Consorcio'],  null,)->class('form-control multiple-select')->required()->multiple() }}
                @endif
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Naturaleza Empresas', 'naturaleza') }}
                @if($actions->naturaleza !== null && is_array(json_decode($actions->naturaleza, true)))
                    {{ html()->select('naturaleza[]', $naturalezas, json_decode($actions->naturaleza, true))->class('form-control multiple-select')->required()->multiple() }}
                @else
                    {{ html()->select('naturaleza[]', $naturalezas,  null)->class('form-control multiple-select')->required()->multiple() }}
                @endif
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Categoria Empresas', 'categoria') }}
                @if($actions->categoria !== null && is_array(json_decode($actions->categoria, true)))
                    {{ html()->select('categoria[]', ['Micro' => 'Micro','Pequeña' => 'Pequeña','Mediana' => 'Mediana', 'Grande' => 'Grande'], json_decode($actions->categoria))->class('form-control multiple-select')->required()->multiple() }}
                @else
                    {{ html()->select('categoria[]', ['Micro' => 'Micro','Pequeña' => 'Pequeña','Mediana' => 'Mediana', 'Grande' => 'Grande'],  null)->class('form-control multiple-select')->required()->multiple() }}
                @endif
            </div>
            <div class="form-group">
                {{  html()->label('<span class="text-danger">*</span> TRL', 'trl') }}
                @if($actions->trl !== null)
                    {{ html()->select('trl', $trls, $actions->trl)->class('form-control')->placeholder('Selecciona uno...')->required() }}
                @else
                    {{ html()->select('trl', $trls, null)->class('form-control')->placeholder('Selecciona uno...')->required() }}
                @endif
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Perfil de financiación', 'perfil_financiacion') }}
                @if($actions->perfil_financiacion !== null && is_array(json_decode($actions->perfil_financiacion, true)))
                    {{ html()->select('perfil_financiacion[]', $intereses, json_decode($actions->perfil_financiacion))->class('form-control multiple-select')->required()->multiple() }}
                @else
                    {{ html()->select('perfil_financiacion[]', $intereses, null)->class('form-control multiple-select')->required()->multiple() }}
                @endif
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Tipo de financiacion', 'tipo_financiacion') }}
                @if($actions->tipo_financiacion !== null && is_array(json_decode($actions->tipo_financiacion, true)))
                    {{ html()->select('tipo_financiacion[]', ['Crédito' => 'Crédito', 'Fondo perdido' => 'Fondo perdido'], json_decode($actions->tipo_financiacion, true))->class('form-control multiple-select')->required()->multiple() }}
                @else
                    {{ html()->select('tipo_financiacion[]', ['Crédito' => 'Crédito', 'Fondo perdido' => 'Fondo perdido'], null)->class('form-control multiple-select')->required()->multiple() }}
                @endif
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Objetivo de financiacion', 'objetivo_financiacion') }}
                @if($actions->objetivo_financiacion !== null && is_array(json_decode($actions->objetivo_financiacion, true)))
                    {{ html()->select('objetivo_financiacion[]', ['Proyectos' => 'Proyectos', 'Empresas' => 'Empresas', 'Personas' => 'Personas'], json_decode($actions->objetivo_financiacion, true))->class('form-control multiple-select')->required()->multiple() }}
                @else
                    {{ html()->select('objetivo_financiacion[]', ['Proyectos' => 'Proyectos', 'Empresas' => 'Empresas', 'Personas' => 'Personas'],  null)->class('form-control multiple-select')->required()->multiple() }}
                @endif
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Fondos/Capítulos de financiacion', 'capitulos_financiacion') }}
                @if($actions->capitulos_financiacion !== null && is_array(json_decode($actions->capitulos_financiacion, true)))
                    {{ html()->select('capitulos_financiacion[]', ['Personnel costs' => 'Personnel costs', 'Subcontracting costs' => 'Subcontracting costs', 'Purchase costs' => 'Purchase costs', 'Other cost categories' => 'Other cost categories', 'Indirect costs' => 'Indirect costs'], json_decode($actions->capitulos_financiacion, true))->class('form-control multiple-select')->required()->multiple() }}
                @else
                    {{ html()->select('capitulos_financiacion[]', ['Personnel costs' => 'Personnel costs', 'Subcontracting costs' => 'Subcontracting costs', 'Purchase costs' => 'Purchase costs', 'Other cost categories' => 'Other cost categories', 'Indirect costs' => 'Indirect costs'],  null)->class('form-control multiple-select')->required()->multiple() }}
                @endif
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Fondo perdido mínimo', 'fondo_perdido_minimo') }}
                {{ html()->number('fondo_perdido_minimo', $actions->fondo_perdido_minimo, 0)->class('form-control')->required() }}
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Fondo perdido máximo', 'fondo_perdido_maximo') }}
                {{ html()->number('fondo_perdido_maximo', $actions->fondo_perdido_maximo, 0)->class('form-control')->required() }}
            </div>
            <div class="form-group">
                {{ html()->label('Condiciones de financiación', 'condiciones_financiacion') }}
                {{ html()->textarea('condiciones_financiacion', $actions->condiciones_financiacion)->class('form-control')->rows(5) }}
            </div>
            <div class="form-group">
                {{ html()->label('Texto Consorcio', 'texto_consorcio') }}
                {{ html()->textarea('texto_consorcio', $actions->texto_consorcio)->class('form-control')->rows(5) }}
            </div>
            <div class="form-group">
                @if($actions->publicar_ayudas == 1)
                {{ html()->checkbox('publicar_ayudas', true, $actions->publicar_ayudas) }}
                @else
                {{ html()->checkbox('publicar_ayudas', false, $actions->publicar_ayudas) }}
                @endif
                {{ html()->label('¿Publicar las ayudas asociadas a este type of action?', 'publicar_ayudas') }}    
            </div>
            <p class="text-danger">* Al actualizar, las <b>Convocatorias</b> que esten <b>asociadas a este Type Of Action se actualizaran</b> con la información de este Type of Action.</p>
            <button type="submit" class="btn btn-primary">Actualizar Type Of Action</button>
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
            $('.multiple-select').select2(
                placeholder: "Selecciona...",
            );
        });
    </script>
@stop   