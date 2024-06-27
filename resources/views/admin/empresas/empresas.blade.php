@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Empresas</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Empresas</h3>
		<div class="card-tools">
			<button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
			    <i class="fas fa-minus"></i>
			</button>
		</div>
	</div>
	<div class="card-body">
		<h2 class="font-semibold text-xl text-gray-800 leading-tight mb-3">
			Total Empresas: {{$totalempresas}}
		</h2>
		<div class="row">
			<div class="col-sm-12 mb-4">
				<h5 class="text-muted">Filtrar empresas:</h5>
				<a class="btn @if(request()->get('tipo') == 'perfilcompleto') btn-info @else btn-outline-info @endif btn-sm"
						href="{{route('dashboardempresas')}}?tipo=perfilcompleto&page=1">Con Perfil tecnológico completo</a>
				<a class="btn @if(request()->get('tipo') == 'efectowow') btn-info @else btn-outline-info @endif btn-sm"
						href="{{route('dashboardempresas')}}?tipo=efectowow&page=1">Con Efecto WOW completo</a>
				<a class="btn @if(request()->get('tipo') == 'envioelastic') btn-info @else btn-outline-info @endif btn-sm"
						href="{{route('dashboardempresas')}}?tipo=envioelastic&page=1">Envio 1 o más veces a elastic</a>
				<a class="btn btn-outline-danger btn-sm" href="{{route('dashboardempresas')}}?page=1">Quitar filtros</a>
			</div>
			<div class="table-responsive">
				<table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table2">
					<thead>
						<tr>
							<th></th>
							<th>Nombre</th>
							<th>CIF</th>
							<th>Web</th>
							<th>Total Intereses</th>
							<th>es Consultoria?</th>
						</tr>
					</thead>
					<tbody>
					@if($empresas)
						@foreach($empresas as $key => $empresa)
						<tr id="{{$empresa->id}}">
							<td>
								<a href="{{route('admineditarempresa', [$empresa->CIF, $empresa->id])}}" class="btn btn-primary btn-sm" title="Editar Centro"><i class="fas fa-edit"></i></a>								
							</td>
							<td>
								{{$empresa->Nombre}}
							</td>
							<td>
								{{$empresa->CIF}}
							</td>
							<td>
								{{$empresa->Web}}
							</td>
							<td>
								@if(isset($empresa->Intereses) && $empresa->Intereses !== "null")
									{{count(json_decode($empresa->Intereses, true))}}
								@else
									0
								@endif
							</td>
							<td class="text-center">
								@if(isset($empresa->esConsultoria) && $empresa->esConsultoria == 1)
								<i class="fa-solid fa-check text-success"></i>
								@else
								<i class="fa-solid fa-xmark text-danger"></i>
								@endif
							</td>
						</tr>
						@endforeach
					@else
						<tr>
							<td colspan="8"> No hay concesiones.
							</td>
						</tr>
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