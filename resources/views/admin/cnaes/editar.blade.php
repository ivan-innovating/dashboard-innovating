@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard CNAEs</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Editar CNAE {{$cnae->Nombre}}</h3>
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
		<form method="post" action="{{route('editarcnae')}}">
            @csrf
            <input type="hidden" name="id" value="{{$cnae->id}}"/>
            <div class="form-group">
                <label for="nombre"><span class="text-danger">*</span> Nombre</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="{{$cnae->Nombre}}" required>
            </div>
            <div class="form-group">
                <label for="tipo"><span class="text-danger">*</span> Tipo</label>
                <input type="text" class="form-control" id="tipo" name="tipo" value="{{$cnae->Tipo}}" required>
            </div>
            <div class="form-group">
                <label for="trl"><span class="text-danger">*</span> Trl</label>
                <input type="number" min="1" class="form-control" id="trl" name="trl" value="{{$cnae->TrlMedio}}" required>
            </div>
            <button type="submit" class="btn btn-primary">Editar CNAE</button>
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
	<script></script>
@stop   