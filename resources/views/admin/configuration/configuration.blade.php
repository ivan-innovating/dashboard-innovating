@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Configuración</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Configuración</h3>
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
        <div class="card">
            <div class="card-header p-2">
                <ul class="nav nav-pills" id="myTab">
                    <li class="nav-item"><a class="nav-link @if(!request()->get('option') && !request()->query('page')) active @endif" href="#config" data-toggle="tab">Configuración</a></li>
                    <li class="nav-item"><a class="nav-link @if(request()->get('option')) active @endif" href="#cnaes" data-toggle="tab">Cnaes</a></li>
                    <li class="nav-item"><a class="nav-link @if(request()->query('page')) active @endif" href="#recompensas" data-toggle="tab">Stats Cnaes</a></li>
                    <li class="nav-item"><a class="nav-link" href="#condicionesrecompensas" data-toggle="tab">Condiciones Recompensas</a></li>
                    <li class="nav-item"><a class="nav-link" href="#scrappers" data-toggle="tab">Scrappers</a></li>
                    <li class="nav-item"><a class="nav-link" href="#alarmas" data-toggle="tab">Alarmas</a></li>
                </ul>
            </div><!-- /.card-header -->
            <div class="card-body">
                <div class="tab-content">
                    @if(session()->has('success'))
                        <div class="alert alert-success text-left">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger text-left">
                            {{$errors->first()}}
                        </div>
                    @endif
                    <div class="@if(!request()->get('option') && !request()->query('page')) active @endif tab-pane" id="config">
                        <div class="post">
                            <p class="text-muted">En esta sección se muestran los resultados de los scrappers ejecutados por tareas cron
                                y las opciones globales editables para el funcionamiento de innovating.works</p>
                            <div class="row mb-3">
                                <div class="col-sm-12">
                                    <form method="post" action="{{route('updateumbrales')}}">
                                        @csrf
                                        <div class="form-group">
                                            <label for="umbralayudas">Umbral(>=) Recomendar Encaje tecnologico en ayudas</label>
                                            <input type="number" value="{{$umbral_ayudas}}" min="0" step="0" class="form-control" id="umbralayudas" name="umbralayudas" aria-describedby="umbralayudasHelp" placeholder="Umbral ayudas (min: 0)">
                                            <small id="umbralayudasHelp" class="form-text text-muted">El valor de score a partir del cual a una empresa se le recomienda un encaje tecnológico con una ayuda.</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="umbralproyectos">Umbral encaje tecnológico proyectos</label>
                                            <input type="number" value="{{$umbral_proyectos}}" min="0" step="0" class="form-control" id="umbralproyectos" name="umbralproyectos" aria-describedby="umbralproyectosHelp" placeholder="Umbral proyectos (min: 0)">
                                            <small id="umbralproyectosHelp" class="form-text text-muted">El valor de score a partir del cual una empresa tiene encaje tecnológico con un proyecto.</small>
                                        </div>
                                        <div class="form-check mb-3">
                                            @if($allow_register === true)
                                            <input class="form-check-input" type="checkbox" value="0" checked id="allow" name="allow">
                                            <label class="form-check-label" for="allow">
                                                ¿Deshabilitar el registro de usuarios sin invitación?
                                            </label>
                                            @else
                                            <input class="form-check-input" type="checkbox" value="1" id="allow" name="allow">
                                            <label class="form-check-label" for="allow">
                                                ¿Habilitar el registro de usuarios sin invitación?
                                            </label>
                                            @endif
                                        </div>
                                        <div class="form-group">
                                            <label for="textoevento">Texto para el Enlace de próximo evento</label>
                                            <input type="text" value="{{$texto_evento}}" min="0" step="0" class="form-control" id="textoevento" name="textoevento" aria-describedby="textoeventoHelp">
                                            <small id="textoeventoHelp" class="form-text text-muted">El texto para el enlace al evento, solo se motrará si el campo enlace no esta vacío.</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="enlaceevento">Enlace para próximo evento</label>
                                            <input type="text" value="{{$enlace_evento}}" min="0" step="0" class="form-control" id="enlaceevento" name="enlaceevento" aria-describedby="enlaceeventoHelp" placeholder="Formato https://...">
                                            <small id="enlaceeventoHelp" class="form-text text-muted">El enlace al evento que se mostrar en la pagina de inicio y en los resultados de búsqueda, dejar vacío para no mostrar.</small>
                                        </div>
                                        <div class="form-check mb-3">
                                            @if($enable_axesor === true)
                                            <input class="form-check-input" type="checkbox" checked id="enable_axesor" name="enable_axesor">
                                            <label class="form-check-label" for="enable_axesor">
                                                ¿Deshabilitar las consultas a la API de Axesor como superadmin?
                                            </label>
                                            @else
                                            <input class="form-check-input" type="checkbox" id="enable_axesor" name="enable_axesor">
                                            <label class="form-check-label" for="enable_axesor">
                                                ¿Habilitar las consultas a la API de Axesor como superadmin?
                                            </label>
                                            @endif
                                        </div>
                                        <div class="form-check mb-3">
                                            @if($enable_einforma === true)
                                            <input class="form-check-input" type="checkbox" checked id="enable_einforma" name="enable_einforma">
                                            <label class="form-check-label" for="enable_einforma">
                                                ¿Deshabilitar las consultas a la API de eInforma como superadmin?
                                            </label>
                                            @else
                                            <input class="form-check-input" type="checkbox" id="enable_einforma" name="enable_einforma">
                                            <label class="form-check-label" for="enable_einforma">
                                                ¿Habilitar las consultas a la API de eInforma como superadmin?
                                            </label>
                                            @endif
                                        </div>
                                        <div class="form-check mb-3">
                                            @if($master_featured == "1")
                                            <input class="form-check-input" type="checkbox" checked id="master_featured" name="master_featured">
                                            <label class="form-check-label" for="master_featured">
                                                Mostrando en los <b>resultados de búsqueda de empresas</b>, las empresas <span class="text-yellow">destacadas</span>
                                            </label>
                                            @else
                                            <input class="form-check-input" type="checkbox" id="master_featured" name="master_featured">
                                            <label class="form-check-label" for="master_featured">
                                                <b>NO</b> se esta mostrando en los <b>resultados de búsqueda de empresas</b>, las empresas <span class="text-yellow font-weight-bold">Destacadas</span>
                                            </label>
                                            @endif
                                        </div>
                                        <button type="submit" class="btn btn-primary">Actualizar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.tab-pane -->
                    <div class="@if(request()->get('option')) active @endif tab-pane" id="cnaes">
                        <div class="table-responsive">
                            <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table2">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Trl</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cnaes as $cnae)
                                        <tr>
                                            <td><a href="{{route('admineditarcnae', [$cnae->id])}}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pen-to-square"></i></a></td>
                                            <td>{{$cnae->Nombre}}</td>
                                            <td>{{$cnae->Tipo}}</td>
                                            <td>{{$cnae->TrlMedio}}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- /.tab-pane -->
                    <div class="tab-pane" id="scrappers">
                        <div class="post">
                            <div class="table-responsive">
                                <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table3">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Organo/departamento Id</th>
                                            <th>Organo/departamento Nombre</th>
                                            <th>Datos última ejecución</th>
                                            <th>última actualización</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($scrappersdata as $data)
                                        <tr>
                                            <td><a href="{{route('admineditarscrapper', $data->id)}}" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i></a></td>
                                            <td>{{$data->name}}</td>
                                            <td>{{$data->orgdpto->Nombre}}</td>

                                            @if(isset($data->datos['Total']))
                                                <td>{{ "Total: ". $data->datos['Total']." - Páginas: ". $data->datos['current']}}</td>
                                                <td>{{ \Carbon\Carbon::parse($data->updated_at)->format('Y-m-d h:i:s') }}</td>
                                            @else
                                                <td>N.D.</td>
                                                <td>{{ \Carbon\Carbon::parse($data->updated_at)->format('Y-m-d h:i:s') }}</td>
                                            @endif
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                    <!-- /.tab-pane -->
                    <div class="tab-pane" id="alarmas">
                        <div class="post">
                            <div class="table-responsive">
                                <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table4">
                                    <thead>
                                        <tr>
                                            <th width="10%">Marcar como solucionado</th>
                                            <th>Nombre</th>
                                            <th>Tipo</th>
                                            <th>Error</th>
                                            <th>Fecha</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($alarmas as $alarma)
                                        <tr>
                                            <td><button class="btn btn-warning btn-sm solucionar"  @if($alarma->solucionado == 1) disabled @else data-item="{{$alarma->id}}" @endif><i class="fa-solid fa-check"></i></button></td>
                                            <td>{{$alarma->nombre}}</td>
                                            <td>{{$alarma->tipo}}</td>
                                            <td>{{$alarma->error}}</td>
                                            <td>{{\Carbon\Carbon::parse($alarma->created_at)->format('Y-m-d h:i:s')}}</td>
                                            <td class="text-center">
                                                <span class="d-none">{{$alarma->solucionado}}</span>
                                                @if($alarma->solucionado == 1)
                                                    <span class="text-success"><i class="fa-solid fa-circle-check"></i></span>
                                                @else
                                                    <span class="text-danger"><i class="fa-solid fa-circle-xmark"></i></span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- /.tab-pane -->
                    <div class="tab-pane @if(request()->query('page')) active @endif" id="recompensas">
                        <div class="post">
                            <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-3">
                                Stats Cnaes: {{$recompensas->total()}}
                            </h2>
                            <div class="row">
                                <div class="table-responsive">
                                    {{ $recompensas->onEachSide(2)->links() }}
                                    <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table5">
                                        <thead>
                                            <tr>
                                                <th>Fecha actualizacion</th>
                                                <th>CNAE</th>
                                                <th>Categoria</th>
                                                <th>Trl(Max|Medio|Min)</th>
                                                <th>Gastos en I+D+i(Max|Medio|Min)</th>
                                                <th>Esfuerzo en I+D+i(Max|Medio|Min)</th>
                                                <th># Empresas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @if($recompensas->count() > 0)
                                            @foreach($recompensas as $recompensa)

                                            <tr>
                                                <td>{{ $recompensa->updated_at }}</td>
                                                <td>{{ \Illuminate\Support\Str::limit($recompensa->cnae->Nombre, 50, '...')}}</td>
                                                <td>{{ $recompensa->categoria }}</td>
                                                <td>@if($recompensa->num_empresas > 0) {{ $recompensa->trl_max }} | {{ $recompensa->trl_medio }} | {{ $recompensa->trl_min }} @else No hay empresas @endif</td>
                                                <td>@if($recompensa->num_empresas > 0) {{ $recompensa->gasto_max }} | {{ $recompensa->gasto_medio }} | {{ $recompensa->gasto_min }} @else No hay empresas @endif</td>
                                                <td>@if($recompensa->num_empresas > 0) {{ $recompensa->esfuerzo_max }} | {{ $recompensa->esfuerzo_medio }} | {{ $recompensa->esfuerzo_min }} @else No hay empresas @endif</td>
                                                <td>{{ $recompensa->num_empresas }}</td>
                                            </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="6">
                                                    No hay reompensas creadas.
                                                </td>
                                            </tr>
                                        @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.tab-pane -->
                    <div class="tab-pane" id="condicionesrecompensas">
                        <div class="post">
                            <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-3">
                                Condiciones Recompensas: {{$condicionesrecompensas->count()}}
                            </h2>
                            <div class="row">
                                <div class="col-sm-12 text-right mb-3">
                                    <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#crearCondicion">
                                        Crear Condicion
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table6">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Tipo premio</th>
                                                <th>Dato Global</th>
                                                <th>Condicion</th>
                                                <th>Dato Empresa</th>
                                                <th>Valor</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @if($condicionesrecompensas->count() > 0)
                                            @foreach($condicionesrecompensas as $condicion)
                                            <tr>
                                                <td>
                                                    <a href="{{route('admineditarcondicion', $condicion->id)}}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pen-to-square"></i></a>
                                                </td>
                                                <td>{{ $condicion->tipo_premio }}</td>
                                                <td>{{ $condicion->dato }} </td>
                                                <td>{{ $condicion->condicion }} </td>
                                                <td>{{ $condicion->dato2 }} </td>
                                                <td>{{$condicion->operacion}} {{ $condicion->valor }} @if($condicion->es_porcentaje == 1) % @endif</td>
                                                <td>
                                                    @if($condicion->estado == 1)
                                                        <i class="fa-solid fa-check text-success"></i>
                                                    @else
                                                        <i class="fa-solid fa-xmark text-danger"></i>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="6">
                                                    No hay condiciones de recompensas creadas.
                                                </td>
                                            </tr>
                                        @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.tab-content -->
            </div><!-- /.card-body -->
        </div>
	</div>
	<div class="card-footer">
	</div>
<div class="modal fade" id="crearCondicion" tabindex="-1" role="dialog" aria-labelledby="crearCondicionTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="crearCondicionTitle">Crear condición recompensa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{route('admincreatecondicion')}}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tipo"><span class="text-danger">*</span> Tipo de premio</label>
                        <select name="tipo" class="form-control select2" required style="width: 100%;">
                            <option></option>
                            <option value="Mención">Mención</option>
                            <option value="Premio">Premio</option> 
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="dato"><span class="text-danger">*</span> Tipo de dato</label>
                        <select name="dato" class="form-control select2" required style="width: 100%;">
                            <option></option>
                            <option value="trl_medio">Trl Medio</option>
                            <option value="trl_max">Trl máximo</option> 
                            <option value="trl_min">Trl mínimo</option>
                            <option value="gasto_medio">Gastos Medio I+D+i</option> 
                            <option value="gasto_max">Gastos máximo I+D+i</option>
                            <option value="gasto_min">Gastos mínimo I+D+i</option> 
                            <option value="esfuerzo_medio">Esfuerzo Medio I+D+i</option> 
                            <option value="esfuerzo_max">Esfuerzo máximo I+D+i</option>
                            <option value="esfuerzo_min">Esfuerzo mínimo I+D+i</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="condicion"><span class="text-danger">*</span> Condición</label>
                        <select name="condicion" class="form-control select2" required style="width: 100%;">
                            <option></option>
                            <option value=">">Mayor</option> 
                            <option value=">=">Mayor igual</option>
                            <option value="=">Igual</option>
                            <option value="<=">Menor igual</option>
                            <option value="<">Menor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="dato2"><span class="text-danger">*</span> Dato de la empresa</label>
                        <select name="dato2" class="form-control select2" required style="width: 100%;">
                            <option></option>
                            <option value="valorTrl">Trl de la empresa</option> 
                            <option value="cantidadImasD">Gasto en I+D</option>
                            <option value="esfuerzoID">Esfuerzo en I+D</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="valor"><span class="text-danger">*</span> Valor</label>
                        <input type="number" name="valor" class="form-control" required/>
                    </div>
                    <div class="form-group">
                        <label for="esporcentaje">El campo valor es un porcentaje?</label>
                        <input type="checkbox" name="esporcentaje"id="esporcentaje"/>
                    </div>
                    <div class="form-group">
                        <label for="operacion"><span class="text-danger">*</span> Operacón para el campo valor</label>
                        <select name="operacion" class="form-control select2" required style="width: 100%;">
                            <option></option>
                            <option value="+">Suma</option> 
                            <option value="-">Resta igual</option>
                            <option value="*">Multiplicación</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Crear condición</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </form>
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
    <script src="https://cdn.jsdelivr.net/npm/datatables@1.10.18/media/js/jquery.dataTables.min.js" integrity="sha256-3aHVku6TxTRUkkiibvwTz5k8wc7xuEr1QqTB+Oo5Q7I=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables@1.10.18/media/css/jquery.dataTables.min.css" integrity="sha256-YY1izqyhIj4W3iyJOaGWOpXDSwrHWFL4Nfk+W0LyCHE=" crossorigin="anonymous">
    <script type="text/javascript">
        $('#table2').DataTable({
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
        });
        $('#table3').DataTable({
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
        });
        $('#table4').DataTable({
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
        });
        $('#table5').DataTable({
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
        });    
        $('#table6').DataTable({
            "paging": true,
            "pageLength": 100,
            "lengthChange": false,
            "searching": false,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "columnDefs": [

            ],
        });
    </script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "Selecciona...",
                allowClear: true,
                theme: "classic",
            });
            
        });
    </script>
@stop   