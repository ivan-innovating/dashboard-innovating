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
            {{ html()->form('GET', route('adminscrappers'))->class('mb-3')->open()}}               
            <select name="organismo" class="form-control multiple-select" title="Selecciona uno si es necesario">
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
        @endif
        @if($scrapperdata->isNotEmpty())
            @if(request()->get('organismo') !== null && request()->get('organismo') !== "")
            <div class="text-right">
                <a href="{{route('adminscrapperreglas', $organismos->first()->id_organismo)}}" class="btn btn-primary btn-sm">Reglas scrapper
                    @if($organismos->first()->organo !== null)
                        @if($organismos->first()->organo->Acronimo !== null && $organismos->first()->organo->Acronimo !== "")
                            {{ \Illuminate\Support\Str::limit($organismos->first()->organo->Acronimo, 25, '...') }}
                        @else
                            {{ \Illuminate\Support\Str::limit($organismos->first()->organo->Nombre, 25, '...') }}
                        @endif 
                    @elseif($organismos->first()->departamento !== null)
                        @if($organismos->first()->organo->Acronimo !== null && $organismos->first()->organo->Acronimo !== "")
                            {{ \Illuminate\Support\Str::limit($organismos->first()->organo->Acronimo, 25, '...') }}
                        @else
                            {{ \Illuminate\Support\Str::limit($organismos->first()->organo->Nombre, 25, '...') }}
                        @endif 
                    @endif
                </a>
            </div>
            @endif           
            <div class="table-responsive mt-3">
                <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table2">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Organismo</th>
                            <th>Id innovating</th>
                            <th>Datos extraidos</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($scrapperdata as $data)
                        <tr>
                            <td>
                                <a href="#" class="btn btn-primary btn-sm">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                            </td>
                            <td>
                                @if($data->organo !== null)
                                    @if($data->organo->Acronimo !== null && $data->organo->Acronimo !== "")
                                        {{ \Illuminate\Support\Str::limit($data->organo->Acronimo, 25, '...') }}
                                    @else
                                        {{ \Illuminate\Support\Str::limit($data->organo->Nombre, 25, '...') }}
                                    @endif 
                                @elseif($data->departamento !== null)
                                    @if($data->organo->Acronimo !== null && $data->organo->Acronimo !== "")
                                        {{ \Illuminate\Support\Str::limit($data->organo->Acronimo, 25, '...') }}
                                    @else
                                        {{ \Illuminate\Support\Str::limit($data->organo->Nombre, 25, '...') }}
                                    @endif 
                                @endif
                            </td>
                            <td>
                                {{$data->proyecto_string}}
                            </td>
                            <td>
                                @php
                                    $datos = json_decode($data->jsondata, true);
                                @endphp
                                @if(!empty($datos))
                                    {{$datos['RazonSocial']}}({{$datos['CodigoEntidad']}}), {{$datos['Provincia']}} | {{$datos['IdTipologia']}}: "{{$datos['Tipologia']}}" 
                                @else
                                    No se han encontrado datos o error en scrapper
                                @endif
                            </td>
                        </tr>
                    @endforeach        
                    </tbody>
                </table>
            </div>            
        @else         
            <p class="text-muted">No se han encontrado datos de scrappers de proyectos importados</p>
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
            $('.multiple-select').select2({
                placeholder: "Selecciona organismo...",
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