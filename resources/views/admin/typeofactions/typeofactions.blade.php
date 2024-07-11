@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Fondos</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Listado de Type of Actions</h3>
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
            <a href="{{route('admincreartypeofaction')}}" class="btn btn-primary btn-sm">
                Crear nuevo Type of Action
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table4">
                <thead>
                    <tr>
                        <th></th>
                        <th>Nombre</th>
                        <th>Publicar ayudas?</th>
                        <th>Presentación</th>
                        <th>Categoría</th>
                        <th>Naturaleza</th>                                                    
                        <th>Tipo Financiación</th>
                        <th>Objetivo Financiación</th>
                        <th>TRL</th>
                    </tr>
                </thead>
                <tbody>
                @if($actions->count() > 0)
                    @foreach($actions as $action)
                    <tr>
                        <td><a href="{{route('admineditartypeofaction', $action->id)}}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pen-to-square"></i></a></td>
                        <td>
                            @if($action->acronimo !== null) {{$action->acronimo}}: @endif {{$action->nombre}}
                        </td>
                        <td>
                            @if($action->publicar_ayudas == 1) <span class="text-success">Si</span> @else <span class="text-danger">No</span> @endif 
                            <span class="d-none">{{$action->publicar_ayudas}}</span>
                        </td>
                        <td>
                            @if($action->presentacion !== null)
                                @foreach(json_decode($action->presentacion) as $presentacion)
                                    @if($loop->last)
                                        {{$presentacion}}
                                    @else
                                        {{$presentacion}},
                                    @endif
                                @endforeach
                            @endif
                        </td>
                        <td>
                            @if($action->naturaleza !== null)
                                @php 
                                    echo "En ".count(json_decode($action->naturaleza))." Naturalezas diferentes";                                                                
                                @endphp
                            @endif
                        </td>
                        <td>
                            @if($action->categoria !== null)
                                @foreach(json_decode($action->categoria) as $categoria)
                                    @if($loop->last)
                                        {{$categoria}}
                                    @else
                                        {{$categoria}},
                                    @endif
                                @endforeach
                            @endif
                        </td>
                        <td>
                            @if($action->tipo_financiacion !== null)
                                @foreach(json_decode($action->tipo_financiacion) as $tipo_financiacion)
                                    @if($loop->last)
                                        {{$tipo_financiacion}}
                                    @else
                                        {{$tipo_financiacion}},
                                    @endif
                                @endforeach
                            @endif
                        </td>
                        <td>
                            @if($action->objetivo_financiacion !== null)
                                @foreach(json_decode($action->objetivo_financiacion) as $objetivo_financiacion)
                                    @if($loop->last)
                                        {{$objetivo_financiacion}}
                                    @else
                                        {{$objetivo_financiacion}},
                                    @endif
                                @endforeach
                            @endif
                        </td>
                        <td>
                            {{$action->trl}}
                        </td>
                    </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
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