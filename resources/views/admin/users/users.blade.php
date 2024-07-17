@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Usuarios</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Usuarios</h3>
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
            <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table3">
            <thead>
                    <tr>
                        <th></th>
                        <th>Id</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Fecha registro</th>
                        <th>Fecha validación</th>
                        <th>Fecha último acceso</th>
                    </tr>
                </thead>
                <tbody>
                    @if($usuarios->count() > 0)
                        @foreach($usuarios as $user)
                            <tr>
                                <td>
                                    <a class="btn btn-primary btn-sm" href="{{route('roles', [$user->id])}}"><i class="fas fa-edit"></i></a>
                                </td>
                                <td>{{$user->id}}</td>
                                <td>{{$user->name}}</td>
                                <td>{{$user->email}}</td>
                                <td>{{ \Carbon\Carbon::parse($user->created_at)->format('d/m/Y H:m:s')}}</td>
                                <td>
                                    @if($user->email_verified_at)
                                        {{ \Carbon\Carbon::parse($user->email_verified_at)->format('d/m/Y H:m:s')}}
                                    @else
                                        <span class="text-danger">no validado</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($user->updated_at)->format('d/m/Y H:m:s')}}</td>
                            </tr>
                        @endforeach
                    @else
                    <tr>
                        <td colspan="7">No hay usuarios con empresa</td>
                    </tr>
                    @endif
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