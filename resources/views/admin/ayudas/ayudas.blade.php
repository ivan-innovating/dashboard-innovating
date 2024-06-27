@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Ayudas</h3>
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
        <div class="text-left mt-4 mb-4">
            <a href="{{route('admincrearayuda')}}" class="btn btn-primary btn-sm">
                Crear nueva ayuda
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table2">
                <thead>
                    <tr>
                        <th></th>
                        <th class="text-center">Esta Cerrada/Extinguida</th>
                        <th>Acronimo</th>
                        <th>Duración(meses)</th>
                        <th>Es indefinida</th>
                        <th>Mes Conv. 1</th>
                        <th>Mes Conv. 2</th>
                        <th>Mes Conv. 3</th>
                    </tr>
                </thead>
                <tbody>
                @if($ayudas->count() > 0)
                    @foreach($ayudas as $ayuda)
                    <tr>
                        <td><a href="{{route('admineditarayuda', $ayuda->id)}}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pen-to-square"></i></a></td>
                        <td class="text-center">
                            @if($ayuda->extinguida == 1)
                                <span class="text-success">Sí</span>
                            @else
                                <span class="text-danger">No</span>
                            @endif
                            <span class="d-none">{{$ayuda->extinguida}}</span>
                        </td>
                        <td>{{$ayuda->acronimo}}</td>
                        <td>
                            @if($ayuda->duracion_convocatorias != "") {{$ayuda->duracion_convocatorias}} @else N.D. @endif
                        </td>
                        <td>
                            @if($ayuda->es_indefinida == 1)
                                <span class="text-success">Sí</span>
                            @else
                                <span class="text-danger">No</span>
                            @endif
                            <span class="d-none">{{$ayuda->es_indefinida}}</span>
                        </td>
                        <td>{{ (isset($ayuda->mes_apertura_1)) ? $ayuda->mes_apertura_1 : "N.D" }}</td>
                        <td>{{ (isset($ayuda->mes_apertura_2)) ? $ayuda->mes_apertura_2 : "N.D" }}</td>
                        <td>{{ (isset($ayuda->mes_apertura_3)) ? $ayuda->mes_apertura_3 : "N.D" }}</td>

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