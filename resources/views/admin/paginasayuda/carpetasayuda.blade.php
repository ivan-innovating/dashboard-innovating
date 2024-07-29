@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Páginas de ayuda</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Carpetas de páginas de ayuda</h3>
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
            @if($carpetas->isEmpty())
                <p class="text-muted">No se han creado carpetas de páginas de ayuda</p>
            @else
            <div class="text-right mb-3">
                <a href="{{route('adminecrearcarpeta')}}" class="btn btn-primary btn-sm">Crear carpeta nueva</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table1">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Nombre</th>
                            <th>Visible</th>
                            <th>Orden</th>                            
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($carpetas as $carpeta)
                        <tr>
                            <td>
                                <a href="{{route('admineditarcarpeta', $carpeta->id)}}"  class="btn btn-primary btn-sm editpagina"><i class="fa-solid fa-pen-to-square"></i></a>
                            </td>
                            <td>
                                {{$carpeta->nombre_carpeta}}
                            </td> 
                            <td>
                                @if($carpeta->activa == 1)
                                    <span class="text-success">Visible</span>
                                @else
                                    <span class="text-danger">NO Visible</span>
                                @endif
                            </td>
                            <td>
                                 {{$carpeta->orden}}
                            </td>
                        </tr>
                    @endforeach                                        
                    </tbody>
                </table>
            </div>
            @endif
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
	<script>
         $(function () {
            $('#table1').DataTable({
                "paging": true,
                "pageLength": 100,
                "lengthChange": false,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "columnDefs": [

                ],
                "order":[
                    [0,"desc"]
                ],        
            });
        });
    </script>
@stop   