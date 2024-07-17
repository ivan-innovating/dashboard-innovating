@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Usuarios</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Investigadores</h3>
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
        <div class="text-left">
            <ul>
                <li>total investigadores: {{$investigadores->total()}}</li>
                <li>total investigadores asociados: {{$investigadoresasociados}}</li>
                <li>total investigadores sin asociar: {{$investigadoresinsasociar}}</li>
                <li>total investigadores descartados: {{$invesnoentidadesdescartados}}</li>
                <li>total investigadores asociados auto: {{$invesentidadesauto}}</li>
                <li>total investigadores asociados manual: {{$invesentidadesmanual}}</li>
            </ul>
        </div>
        <div class="table-responsive">
            <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table3">
                <thead>
                    <tr>
                        <th></th>
                        <th>Nombre</th>
                        <th>Empresa Innovating</th>
                        <th>Id de ORCID</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($investigadores as $investigador)
                        <tr>
                            <td><a href="{{route('editinvestigador', [$investigador->id])}}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pen-to-square"></i></a></td>
                            <td>{{$investigador->investigador}}</td>
                            <td>{{$investigador->universidad_name}}</td>
                            <td>{{$investigador->orcid_id}}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
	</div>
	<div class="card-footer">
		Footer
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
	<script>
        $('#table3').DataTable({
            "paging": true,
            "pageLength": 30,
            "lengthChange": false,
            "searching": true,
            "ordering": true,
            "info": false,
            "autoWidth": false,
            "responsive": false,
            "order": [[1, 'desc']],
        });
    </script>    
@stop   