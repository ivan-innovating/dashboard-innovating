@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Condiciones Financieras</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
        <ul class="nav nav-pills" id="myTab">
            <li class="nav-item"><a class="nav-link active" href="#condiciones" data-toggle="tab">Condiciones Financieras</a></li>
            <li class="nav-item"><a class="nav-link" href="#analisis" data-toggle="tab">Analisis Financieros</a></li>
        </ul>
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
            <!-- /.tab-pane -->
            <div class="active tab-pane" id="condiciones">
                <div class="text-right mb-3">
                    <!-- Button trigger modal -->
                    <a href="{{route('admincrearcondicionfinanciera')}}" class="btn btn-warning btn-sm">
                        Crear condicion financiera
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table2">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Orden</th>
                                <th>Variable</th>
                                <th>Condicion</th>
                                <th>Coeficiente</th>
                                <th>Variable 2 | Fijo</th>
                                <th>Comentario NO</th>
                                <th>Comentario SI</th>
                                <th>Todas las convocatorias</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($condiciones as $condicion)
                            <tr>
                                <td>
                                    <a class="btn btn-primary btn-xs" href="{{route('admineditarcondicionfinanciera', $condicion->id)}}"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <form action="{{route('adminborrarcondicionfinanciera')}}" class="d-inline deletecondicion" method="POST">
                                        @csrf
                                        <input type="hidden" name="id" value="{{$condicion->id}}"/>
                                        <button class="btn btn-danger btn-xs" type="submit"><i class="fa-solid fa-xmark"></i></button>
                                    </form>
                                </td>
                                <td>{{$condicion->orden}}</td>
                                <td>{{$condicion->var1}}</td>
                                <td>{{$condicion->condicion}}</td>
                                <td>{{$condicion->coeficiente}} <span class="text-primary text-sm">*</span></td>
                                <td>{{$condicion->var2}} @if($condicion->var2 == "Fijo") | {{ $condicion->valor}} € @endif</td>
                                <td>
                                    <span class="text-{{$condicion->color_incumple}}">{{ \Illuminate\Support\Str::limit($condicion->comentario_incumple, 50, '...')}}</span>
                                </td>
                                <td>
                                    <span class="text-{{$condicion->color_cumple}}">{{ \Illuminate\Support\Str::limit($condicion->comentario_cumple, 50, '...')}}</span>                                                        
                                </td>
                                <td>
                                    @if($condicion->todasconvocatorias == 1)
                                        <span class="text-dark">Si</span>
                                    @else
                                        <span class="text-info">No</span>
                                    @endif
                                    <span class="d-none">{{$condicion->todasconvocatorias}}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <small class="text-primary">* se multiplica por la variable siempre que sea distinto de 1.</small>
                </div>
            </div>
            <!-- /.tab-pane -->
            <div class="tab-pane" id="analisis">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table3">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Usuario</th>
                                <th>Empresa Creadora</th>
                                <th>Empresa Analisis</th>
                                <th>Ayuda Analisis</th>
                                <th>Presupuesto</th>
                                <th>Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($analisisfinancieros as $analisis)
                                <tr>
                                    <td><a class="btn btn-primary btn-xs" href="{{config('app.innovatingurl')}}/viewanalisiscashflow/{{$analisis->id}}"><i class="fa-solid fa-eye"></i></a></td>
                                    <td>{{$analisis->creator->email}}</td>
                                    <td>{{$analisis->company_creator->Nombre}}</td>
                                    <td>{{$analisis->company->Nombre}}</td>
                                    <td>{{$analisis->ayuda->Titulo}}</td>
                                    <td>{{$analisis->valor}}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($analisis->resultado, 50, '...')}}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
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
    <!-- jQuery Alerts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables@1.10.18/media/js/jquery.dataTables.min.js" integrity="sha256-3aHVku6TxTRUkkiibvwTz5k8wc7xuEr1QqTB+Oo5Q7I=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables@1.10.18/media/css/jquery.dataTables.min.css" integrity="sha256-YY1izqyhIj4W3iyJOaGWOpXDSwrHWFL4Nfk+W0LyCHE=" crossorigin="anonymous">
    <script type="text/javascript">
        $(function () {
            $('#table2').DataTable({
                "paging": true,
                "pageLength": 100,
                "lengthChange": false,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": true,
                "responsive": true,
            });
            $('#table3').DataTable({
                "paging": true,
                "pageLength": 100,
                "lengthChange": false,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": true,
                "responsive": true,
            });
        });
        $('.deletecondicion').on('submit', function(e){
            e.preventDefault();
            $.confirm({
                title: 'Borrar condición',
                content: "Vas a borrar una condición <b>¿estas seguro?</b>",
                buttons:{
                    ok: function(){
                        e.currentTarget.submit();
                    },
                    cancel: function(){},
                }
            });
            return false;
        });
    </script>
@stop   