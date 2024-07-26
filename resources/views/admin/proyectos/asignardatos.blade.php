@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Scrappers</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Programar Scrapper</h3>
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
        <p class="text-danger">* En esta sección se van a asignar los proyectos con las convocatorias que tenemos creadas en innovating</p>
        <p class="text-danger font-weight-bold">1. Has de seleccionar un Organo o un departamento, y despues uno de los dos tipos de busqueda por texto convocatoria o texto referencia.<br/>
        2. Retorna un listado de posibles asignaciones, si todo ok pulsar en Asignar.</p>
        <form method="GET" url="{{route('dashboardasignar')}}" class="buscar">
           <input type="hidden" name="tipo" value="proyectos"/>
            <div class="form-row">
                <div class="form-group w-50 mr-1">
                    <span class="text-danger">*</span> <label for='organo'>Selecciona un organo</label>
                    <select class="form-control select2" id="organo" name="organo" @if(request()->query('organo') === null && request()->query('departamento')=== null) required @endif>
                        <option></option>
                        @foreach($organos as $organo)
                            <option value="{{$organo->id}}" @if($organo->id == request()->query('organo')) selected @endif>{{$organo->Nombre}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group w-50">
                    <span class="text-danger">*</span> <label for='departamento'>O Selecciona un departamento</label>
                    <select class="form-control select2" id="departamento" name="departamento" @if(request()->query('organo') === null && request()->query('departamento')=== null) required @endif>
                    <option></option>
                        @foreach($departamentos as $departamento)
                            <option value="{{$departamento->id}}" @if($departamento->id == request()->query('departamento')) selected @endif>{{$departamento->Nombre}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group w-50 mr-1">
                    <label for='texto_convocatoria'>Texto de la convocatoria</label>
                    <input type="text" name="texto_convocatoria" value="{{request()->query('texto_convocatoria')}}" class="form-control"/>
                </div>
                <div class="form-group w-50">
                    <label for='texto_referencia'>O Texto referencia empieza por...</label>
                    <input type="text" name="texto_referencia" value="{{request()->query('texto_referencia')}}" class="form-control"/>
                </div>                                                    
            </div>
            <p class="text-danger font-weight-bold">Selecciona una ayuda y/o convocatoria</p>
            <div class="form-row">                
                <div class="form-group w-50 mr-1">                                                        
                    <label for='ayuda'>Linea de ayuda</label>
                    <select class="form-control select2" id="ayuda" name="ayuda">
                        <option></option>
                        @foreach($ayudas as $ayuda)
                            <option value="{{$ayuda->id}}" @if($ayuda->id == request()->query('ayuda')) selected @endif>{{$ayuda->titulo}}</option>
                        @endforeach
                    </select>                    
                </div>
                <div class="form-group w-50">                                    
                    <label for="convocatoria">O Convocatoria</label>
                    <select class="form-control select2" id="convocatoria" name="convocatoria">
                        <option></option>
                        @foreach($convocatorias as $convocatoria)
                            <option value="{{$convocatoria->id}}" @if($convocatoria->id == request()->query('convocatoria')) selected @endif>{{$convocatoria->Titulo}}</option>
                        @endforeach
                    </select>     
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <button type="submit" class="btn btn-outline-primary">Buscar</button>
                </div>
            </div>
        </form>
        @if($proyectos !== null && $proyectos->count() > 0)
            <form url="{{route('asignardatosproyectos')}}" method="POST" class="asignar d-inline">
                <input type="hidden" name="tipo" value="proyectos"/>
                <input type="hidden" name="organo" value="{{request()->get('organo')}}"/>
                <input type="hidden" name="departamento" value="{{request()->get('departamento')}}"/>
                <input type="hidden" name="texto_convocatoria" value="{{request()->get('texto_convocatoria')}}"/>
                <input type="hidden" name="texto_referencia" value="{{request()->get('texto_referencia')}}"/>
                <input type="hidden" name="ayuda" value="{{request()->query('ayuda')}}"/>
                <input type="hidden" name="convocatoria" value="{{request()->query('convocatoria')}}"/>
                <button type="submit" class="btn btn-outline-danger">Asignar</button>
            </form>        
            <p class="text-warning font-weight-bold">* Se muestran solo los primeros 100 posibles proyectos a asignar, como datos de ejemplo de un total de <b>{{$totalproyectos}}.</b></p>
            <div class="table-responsive">
                <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table2">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Convocatoria</th>
                            <th>Referencia</th>
                            <th>Fecha Creacion</th>
                            <th>Empresa principal</th>
                            <th>Nombre empresa</th>
                            <th>Descripción</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($proyectos->count() > 0)
                            @foreach($proyectos as $proyecto)
                            <tr>
                                <td class="text-center">
                                    <a href="{{route('editarproyecto', [$proyecto->id])}}" class="btn btn-primary btn-sm" title="Editar proyecto">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                                <td>{{$proyecto->id_europeo}}</td>
                                <td>{{$proyecto->Acronimo}}</td>
                                <td>{{$proyecto->created_at->format('Y-m-d')}}</td>
                                <td>{{$proyecto->empresaPrincipal}}</td>
                                <td>
                                    @if($proyecto->nombreEmpresa)
                                        {{ \Illuminate\Support\Str::limit($proyecto->nombreEmpresa, 40, '...')}}
                                    @else
                                        N.D.
                                    @endif
                                </td>
                                <td>
                                    {{ \Illuminate\Support\Str::limit($proyecto->Descripcion, 40, '...') }}
                                </td>
                                <td class="text-center">{{$proyecto->Estado}}</td>
                            </tr>
                            @endforeach
                        @else
                        <tr>
                            <td colspan="6">
                                No hay proyectos.
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        @endif
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
    <!-- jQuery Alerts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>
    <!--Datatables-->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap4.min.css"/>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"> </script>
    <script src="https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap4.min.js"> </script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<script>
        $(document).ready(function() {
            $('.multiple-select').select2({
                placeholder: "Selecciona...",
                allowClear: true
            });
        });
    </script>
	<script>
        $(function () {
            $('#table2').DataTable({
                "paging": true,
                "pageLength": 30,
                "lengthChange": false,
                "searching": true,
                "ordering": true,
                "info": false,
                "autoWidth": false,
                "responsive": false,
                "order": [[3, 'asc']],
            });
            $(".select2").select2({
                placeholder: "Selecciona uno...",
                allowClear: true,
                theme: "classic",
            });
        });
        $('#organo').on('select2:select', function(e, clickedIndex, newValue, oldValue){            
            if(this.value != null){
                $('#departamento').attr('required', false);                       
            }else{               
                $('#departamento').attr('required', true);        
            }
            $('#departamento').select2('destroy');
            $("#departamento").select2({
                placeholder: "Selecciona uno...",
                allowClear: true,
                theme: "classic",
            });               
            
        });
        $('#departamento').on('select2:select', function(e, clickedIndex, newValue, oldValue){
            if(this.value !== null){                
                $('#organo').attr('required', false);                    
            }else{
                $('#organo').attr('required', true);        
            }
            $('#organo').select2('destroy');
            $("#organo").select2({
                placeholder: "Selecciona uno...",
                allowClear: true,
                theme: "classic",
            });    
        });
        $('select[name="ayuda"]').on('select2:select', function(e){
            var id = $(this).val();
            
            $.ajax({
                url: "{{route('getconvocatorias')}}",
                type: 'GET',
                data: {linea:id},
                cache: false,
                success: function(resp){
                    $('#convocatoria').empty();
                    $(resp).each(function(k,v) {
                        $('#convocatoria').append($('<option>', {
                            value: v.idstring,
                            text : v.Titulo,
                            title: (v.Acronimo) ? v.Acronimo+' seleccionado'  : '1 Convocatoria seleccionada'
                        }));
                    });

                    if($('#convocatoria option').length >= 1){
                        $('#convocatoria').prop('disabled', false);                        
                        $('#convocatoria').select2('destroy');
                        $("#convocatoria").select2({
                            placeholder: "Selecciona uno...",
                            allowClear: true,
                            theme: "classic",
                        });    
                    }else{
                        $('#convocatoria').prop('disabled', true);
                        $('#convocatoria').val(null);                    
                        $('#convocatoria').select2('destroy');
                        $("#convocatoria").select2({
                            placeholder: "Selecciona uno...",
                            allowClear: true,
                            theme: "classic",
                        });    
                    }
                },
                error:function(resp){
                    $('#convocatoria').empty();
                    $('#convocatoria').prop('disabled', true);
                    
                    $('#convocatoria').val(null);
                    $('#convocatoria').select2('destroy');
                    $("#convocatoria").select2({
                        placeholder: "Selecciona uno...",
                        allowClear: true,
                        theme: "classic",
                    });    
                    console.log(resp);
                }
            });
        });
        $('form.buscar').on('submit', function(e){
            e.preventDefault();
            if($('input[name="texto_referencia"]').val() == "" && $('input[name="texto_convocatoria"]').val() == ""){
                $.alert({
                    title: 'Error',
                    content: 'Uno de los campos de texto referencia o texto convocatoria no puede estar vacio, para hacer la búsqueda'
                });
                return false;
            }
            if($('input[name="texto_referencia"]').val() != "" && $('input[name="texto_convocatoria"]').val() != ""){
                $.alert({
                    title: 'Error',
                    content: 'Solo puedes buscar por texto de referencia o texto convocatoria no por los dos, para hacer la búsqueda'
                });
                return false;
            }
            if($('#ayuda').val() == "" && $('#convocatoria').val() == ""){
                $.alert({
                    title: 'Error',
                    content: 'Tienes que seleccionar mínimo una ayuda o ayuda y convocotaria para asignar los proyectos'
                });
                return false;
            }
            if($('#ayuda').val() == "" && $('#convocatoria').val() != ""){
                $.alert({
                    title: 'Error',
                    content: 'Para asignar a una convocatoria primero tienes que seleccionar una ayuda'
                });
                return false;
            }

            e.currentTarget.submit();
        });
    </script>
@stop   