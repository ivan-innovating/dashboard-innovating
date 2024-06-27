@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Empresas</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Listado de empresas para priorizar</h3>
		<div class="card-tools">
			<button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
			    <i class="fas fa-minus"></i>
			</button>
		</div>
	</div>
	<div class="card-body">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-3">
            Solicitudes de prioridad
        </h2>
        <div class="row">
            <div class="table-responsive">
                <table class="table table-hover text-nowrap f-14" id="table2" style="overflow-x:scroll">
                    <thead>
                        <tr>
                            <th></th>
                            <th>solicitante</th>
                            <th>CIF a priorizar </th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($priorizar))
                            @foreach($priorizar as $soli)
                                <tr>
                                    <td>
                                        @if($soli->updated_at === null)
                                        <a href="{{route('viewpriorizar', $soli->id)}}" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i></a>
                                        @else
                                            <button disabled class="btn btn-primary btn-sm" title="ya ha sido aceptada/rechazada"><i class="fa fa-edit"></i></button>
                                        @endif
                                    </td>
                                    <td>
                                        {{$soli->solicitante}}
                                    </td>
                                    <td>
                                        {{$soli->cifPrioritario}} @if(isset($soli->nombreempresa)) :{{$soli->nombreempresa}} @endif
                                    </td>
                                    <td>
                                        {{\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $soli->created_at)}}
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