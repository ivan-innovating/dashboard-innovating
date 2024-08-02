@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Stats generales</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Editar Condición recompensa</h3>
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
            <form action="{{route('adminsendtestmail')}}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{$correo->id}}"/>                
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-paper-plane"></i> Enviame un correo de prueba</button>                
            </form>
        </div>
        <form action="{{route('admineditmail')}}" method="post">
            @csrf                                         
            <input type="hidden" name="id" value="{{$correo->id}}"/>  
            <div class="form-group">
                <span class="text-danger">*</span> <label for="asunto">Asunto del mail</label>
                <input type="text" name="asunto" class="form-control" required maxlength="250" value="{{$correo->asunto_mail}}"/>
            </div>
            <div class="form-group">
                <span class="text-danger">*</span> <label for="cabecera">Cabecera del mail</label>
                <textarea name="cabecera" class="form-control" required cols="5">{{$correo->cabecera_mail}}</textarea>
            </div>
            <div class="form-group">
                <span class="text-danger">*</span> <label for="cuerpo">Cuerpo del mail</label>
                <textarea name="cuerpo" class="form-control" required cols="5">{{$correo->cuerpo_mail}}</textarea>
            </div>
            <div class="form-group">
                <span class="text-danger">*</span> <label for="pie">Pie del mail</label>
                <textarea name="pie" class="form-control" required cols="5">{{$correo->pie_mail}}</textarea>
            </div>
            <div class="form-group">
                <label for="url">Url del botón ir a Innovating del mail, formato: https://....'</label>
                <input tupe="text" name="url" class="form-control" maxlength="250" value="{{$correo->url_innovating}}"/>
            </div>    
            <button type="submit" class="btn btn-primary btn-sm">Editar email</button>
        </form>
	</div>
	<div class="card-footer">
		
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
            $('.select2').select2({
                placeholder: "Selecciona...",
                allowClear: true,
                theme: "classic",
            });
        });
    </script>
@stop   