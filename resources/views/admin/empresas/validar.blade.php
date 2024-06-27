@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Empresas</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Listado de empresas para validar</h3>
		<div class="card-tools">
			<button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
			    <i class="fas fa-minus"></i>
			</button>
		</div>
	</div>
	<div class="card-body">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-3">
            Validar usuarios en empresas
        </h2>
        <div class="row">
            <div class="table-responsive">
                <table class="table table-hover text-nowrap f-14" id="table2" style="overflow-x:scroll">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Usuario</th>
                            <th>CIF</th>
                            <th>Documento</th>
                            <th>Fecha</th>
                            <th>Existe en Entidades</th>
                            <th>Validado / Usuario validación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($validaciones))
                            @foreach($validaciones as $validacion)
                                <tr>
                                    <td>
                                        <a href="{{route('viewvalidacion', $validacion->id)}}" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i></a>
                                        {{Form::open(['url' => route('aceptarvalidacion'), 'class' => 'aceptarvalidacion d-inline', 'method' => 'POST'])}}
                                            {{Form::hidden('id', $validacion->id)}}                                                                            
                                            <button class="btn btn-warning btn-sm" title="Aceptar validación"><i class="fa-solid fa-building-circle-check"></i></button>
                                        {{Form::close()}}
                                    </td>
                                    <td>
                                        {{$validacion->user_id}}: {{$validacion->solicitante->email}}
                                    </td>
                                    <td>
                                        {{$validacion->cif}}
                                    </td>
                                    <td>
                                        <a class="txt-azul" href="{{asset('uploadfiles'.'/'.$validacion->doc)}}" target="_blank">Ver documento adjunto</a>
                                    </td>
                                    <td>
                                        {{\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $validacion->created_at)}}
                                    </td>
                                    <td>
                                        @if($validacion->esEntidad == 1)
                                            <i class="fa-solid fa-check text-success"></i>
                                        @else
                                            <i class="fa-solid fa-xmark text-danger"></i>
                                        @endif
                                        <span class="d-none">{{$validacion->esEntidad}}</span>
                                    </td>
                                    <td>
                                        @if($validacion->aceptado == 1)
                                            <i class="fa-solid fa-check text-success"></i> @if($validacion->usuariovalidacion !== null) / {{$validacion->usuariovalidacion}} @endif
                                        @else
                                            <i class="fa-solid fa-xmark text-danger"></i>
                                        @endif
                                        <span class="d-none">{{$validacion->aceptado}}</span>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
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
	<!-- jQuery Alerts -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>    
    <!--Datatables-->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap4.min.css"/>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"> </script>
    <script src="https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap4.min.js"> </script>
    <!--Bootstrap Select-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-autocomplete/1.0.7/jquery.auto-complete.min.js"
 integrity="sha512-TToQDr91fBeG4RE5RjMl/tqNAo35hSRR4cbIFasiV2AAMQ6yKXXYhdSdEpUcRE6bqsTiB+FPLPls4ZAFMoK5WA=="
 crossorigin="anonymous" referrerpolicy="no-referrer"></script>
 	<script type="text/javascript">
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