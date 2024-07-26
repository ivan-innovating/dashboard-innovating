@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Proyectos</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Proyectos Europeos</h3>
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
        <div class="table-responsive">
            {{$proyectos->links()}}
            <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table2">
                <thead>
                    <tr>
                        <th></th>
                        <th>Nombre</th>
                        <th>Estado</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                @if($proyectos->count() > 0)
                    @foreach($proyectos as $proyecto)
                    <tr>
                        <td><a href="{{route('admineditarproyecto', $proyecto->id)}}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pen-to-square"></i></a></td>
                        <td>{{$proyecto->Acronimo}} {{\Illuminate\Support\Str::limit($proyecto->Titulo, '50', '...')}}</td>
                        <td>{{$proyecto->ambitoConvocatoria}}</td>
                        <td>{{$proyecto->tipoConvocatoria}}</td>
                        <td>{{$proyecto->Estado}}</td>
                    </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
        </div>
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
    <script src="
        https://cdn.jsdelivr.net/npm/datatables@1.10.18/media/js/jquery.dataTables.min.js
        "></script>
        <link href="
        https://cdn.jsdelivr.net/npm/datatables@1.10.18/media/css/jquery.dataTables.min.css
        " rel="stylesheet">
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