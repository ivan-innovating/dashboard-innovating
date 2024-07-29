@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Patentes</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Listado de patentes con CIF</h3>
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
		<div class="tab-content">
            <div class="tab-pane active show" id="patentes2">
                <div class="post">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-3">
                        Patentes con match: {{$patentesconmatch->count()}}
                    </h2>
                    <div class="row">
                        <div class="table-responsive">
                            <table class="table table-hover text-nowrap f-14" id="table2" style="overflow-x:scroll">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>CIF</th>
                                        <th>Nombre</th>
                                        <th>Fecha</th>
                                        <th># Solicitud</th>
                                        <th>Solicitantes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($patentesconmatch->count() > 0)
                                        @foreach($patentesconmatch as $patente)
                                        <tr>
                                            <td>
                                                <a href="{{route('admineditarpatente', [$patente->id])}}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></a>
                                            </td>
                                            <td>
                                                {{$patente->CIF}}
                                            </td>
                                            <td>{{\Illuminate\Support\Str::limit($patente->Titulo, 150, '...')}}</td>
                                            <td>
                                                {{$patente->Fecha_publicacion}}
                                            </td>
                                            <td>{{$patente->Numero_solicitud}}</td>
                                            <td>
                                                {{$patente->Solicitantes}}
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
            <!-- /.tab-pane -->
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
    <script src="
        https://cdn.jsdelivr.net/npm/datatables@1.10.18/media/js/jquery.dataTables.min.js
        "></script>
        <link href="
        https://cdn.jsdelivr.net/npm/datatables@1.10.18/media/css/jquery.dataTables.min.css
        " rel="stylesheet">
    <script>
        $(function () {
            $('#table2').DataTable({
                "dom": 'Pfrtip',
                "paging": true,
                "pageLength": 100,
                "lengthChange": false,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": false,
                "order": [
                    [
                        2, "desc"
                    ]
                ],
            });
        });

        $('input[name="patentes"]').on('click', function(e){
            console.log($(this).val());
        });

    </script>
@stop   