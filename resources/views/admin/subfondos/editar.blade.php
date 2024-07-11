@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Fondos</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Editar Subfondo {{$subfondo->nombre}}</h3>
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
        {{ html()->form('POST', route('admineditsubfondo'))->class('submitfondo')->open() }}
            <input type="hidden" name="id" value="{{$subfondo->id}}"/>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Nombre', 'nombre') }}
                {{ html()->text('nombre', $subfondo->nombre)->class('form-control')->required() }}
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Acrónimo', 'acronimo') }}
                {{ html()->text('acronimo', $subfondo->acronimo)->class('form-control')->required() }}                                                        
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Nivel', 'nivel') }}
                @if($subfondo->nivelsuperior !== null)
                    {{  html()->select('nivel', ['1' => '1', '2' => '2'], $subfondo->nivelsuperior->nivel)->class('form-control')->placeholder('Selecciona uno...')->required() }}
                @else
                    {{  html()->select('nivel', ['1' => '1', '2' => '2'], 1)->class('form-control')->placeholder('Selecciona uno...')->required() }}
                @endif
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Actualizar Subfondo</button>
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
</script>
@stop   