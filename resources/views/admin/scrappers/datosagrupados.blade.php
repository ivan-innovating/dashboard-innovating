@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Scrappers</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Datos Scrappers No Importados</h3>
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
        @if($organismos->isNotEmpty())
            <p class="text-muted">Paso 1: selecciona el organismo del cual quieres agrupar datos:</p>
            {{ html()->form('GET', route('adminsdatosagrupados'))->class('mb-3')->open()}}               
            <select name="organismo" class="form-control select" title="Selecciona uno si es necesario">
                <option></option>
                @foreach($organismos as $organismo)
                    @if($organismo->organo !== null)
                    <option value="{{$organismo->organo->id}}" @if($organismo->organo->id == request()->get('organismo')) selected @endif>{{$organismo->organo->Acronimo}}</option>
                    @elseif($organismo->departamento !== null)
                    <option value="{{$organismo->departamento->id}}" @if($organismo->departamento->id == request()->get('organismo')) selected @endif>{{$organismo->departamento->Acronimo}}</option>
                    @endif
                @endforeach       
            </select>
            <button type="submit" class="btn btn-primary btn-sm mt-3">Filtrar por organismo</button>
            {{html()->form()->close()}}
            @if(request()->get('organismo') !== null && request()->get('organismo') != "")
                <p class="text-muted">Paso 2: selecciona los campos de los cuales quieres ver los datos agrupados:</p>
                {{ html()->form('GET', route('adminsdatosagrupados'))->class('mb-3')->open()}}      
                {{ html()->hidden('organismo', request()->get('organismo')) }}
                <select name="columnas[]" class="form-control multiple-select" multiple="multiple">
                    <option></option>
                    @foreach($columnas as $columna)
                        @if(request()->get('columnas') !== null)
                        <option value="{{$columna}}" @if(in_array($columna, request()->get('columnas'))) selected @endif>{{$columna}}</option>
                        @else
                        <option value="{{$columna}}">{{$columna}}</option>
                        @endif                                        
                    @endforeach       
                </select>
                <button type="submit" class="btn btn-primary btn-sm mt-3">Obtener datos agrupados</button>
                {{html()->form()->close()}}
            @endif
            @if(count($datosagrupados) > 0 && !empty($datosagrupados))
                <p class="text-muted">Resultados:</p>
                <ul>
                @foreach($datosagrupados as $key => $datos)
                    @if(count($datos) > 75)
                        <li>{{$key}}: no es un campo valido para ver datos agrupados tiene más de 75 valores diferentes, ej: {{$datos[0]}}....</li>   
                    @else
                        <li>{{$key}}:
                            <ol>
                                @foreach($datos as $ind => $value)
                                    <li>{{$value}}</li>
                                @endforeach
                            </ol>
                        </li>
                    @endif
                @endforeach
                </ul>
            @else
                <p class="text-muted">No hay datos para poder verlos agrupados</p>
            @endif
        @else
            <p class="text-muted">No hay datos para poder verlos agrupados</p>
        @endif       
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
    <!--Datatables-->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap4.min.css"/>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"> </script>
    <script src="https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap4.min.js"> </script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<script>
        $(document).ready(function() {
            $('.select').select2({
                placeholder: "Selecciona organismo...",
                allowClear: true,
                theme: "classic"
            });
            $('.multiple-select').select2({
                placeholder: "Selecciona columnas...",
                allowClear: true,
                theme: "classic"
            });
        });
        $('#table2').DataTable({
            "paging": true,
            "pageLength": 100,
            "lengthChange": false,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": false,
            "columnDefs": [

            ],
		});
    </script>
@stop   