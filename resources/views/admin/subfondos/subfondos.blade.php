@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Fondos</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Listado de Subfondos</h3>
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
        <div class="text-right mb-3">
                <!-- Button trigger modal -->
                <a href="{{route('admincrearsubfondo')}}" class="btn btn-primary btn-sm">
                    Crear nuevo Subfondo
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table3">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Nombre</th>
                            <th>Nivel</th>
                            <th>Padre del subfondo</th>
                        </tr>
                    </thead>
                    <tbody>
                    @if($subfondos->count() > 0)
                        @foreach($subfondos as $subfondo)
                        <tr>
                            <td>
                                <a href="{{route('admineditarsubfondo', $subfondo->id)}}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pen-to-square"></i></a>
                            </td>
                            <td>
                                @if($subfondo->acronimo !== null) {{$subfondo->acronimo}}: @endif {{$subfondo->nombre}}
                            </td>
                            <td>
                                @if($subfondo->nivelsuperior !== null)
                                    {{$subfondo->nivelsuperior->nivel}}
                                @else
                                    1
                                @endif
                            </td>
                            <td>
                                @if($subfondo->nivelsuperior !== null && $subfondo->nivelsuperior->padre !== null)
                                    {{$subfondo->nivelsuperior->padre->nombre}}
                                @else
                                    "No tiene nivel superior"
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    @endif
                    </tbody>
                </table>
            </div>
        </div>  
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