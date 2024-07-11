@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Organismos</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Crear Organo</h3>
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
        {{ html()->form('POST', route('adminsaveorgano'))->open()}}                                              
            <div class="alert alert-warning">Antes de crear un Organo/Departamento asegurate de que no lo has encontrado en la lista correspondiente(puedes editar en caso de necesitar el Organo/Departamento), la creación y asignación errónea de un Organo/Departamento puede traer perdida de datos e información de la plataforma</div>
            <div class="form-group">
                <label for="descripcion">Acronimo</label>
                <input type="text" class="form-control" name="acronimo" required>
            </div>
            <div class="form-group">
                <label for="descripcion">Nombre</label>
                <input type="text" class="form-control" name="nombre" required>
            </div>              
            <div class="form-group mb-3" id="ministerio">
                <label for="ministerio">Ministerio</label>
                <br/>
                <select name="ministerio" class="multiple-select form-control" title="Selecciona uno..." required>
                    @foreach($ministerios as $ministerio)
                        <option value="{{$ministerio->id}}">{{$ministerio->Nombre}}</option>
                    @endforeach
                </select>
            </div>            
            <button type="submit" class="btn btn-primary">Crear Organo</button>
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
            });
        });
    </script>
@stop   