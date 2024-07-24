@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Scrappers</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Reglas scrapper Proyectos         
            @if($organismo->Acronimo !== null && $organismo->Acronimo !== "")
                {{ \Illuminate\Support\Str::limit($organismo->Acronimo, 25, '...') }}
            @else
                {{ \Illuminate\Support\Str::limit($organismo->Nombre, 25, '...') }}
            @endif         
        </h3>
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
        <div class="text-right mb-3">
            <button type="button" class="btm btn-warning btn-sm" data-toggle="modal" data-target="#CrearReglaModal">Crear Nueva Regla</button>
        </div>
        @if($reglas->count() > 0 && $enableButton === true)
        <div class="text-left mb-3">            
            @if($applyRulePending === false)
            <small class="text-danger">Tienes cambios en reglas sin aplicar</small><br/>                        
            @else
            <small class="text-danger">Ya hay una aplicación pendiente en este organimos de las reglas creadas</small><br/>            
            <small class="text-danger">Si has añadido/quitado o modificado alguna regla tienes que darle al boton "Aplicar cambios reglas"</small><br/>            
            @endif
            <button type="button" class="btm btn-danger btn-sm mt-2" data-toggle="modal" data-target="#AplicarReglasModal">Aplicar cambios reglas</button><br/>            
            <small class="text-danger">* Aplicar los cambios en las reglas a los proyectos, se ejecuta de manera asincrona</small>
        </div>
        @endif
        @if($reglas->isEmpty())
            <p class="text-muted">No se han encontrado reglas para el scrapper de este organismo</p>
        @else
            <div class="table-responsive mt-3">
                <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table2">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Prioridad</th>
                            <th>Campo Scrapper</th>
                            <th>Condicion</th>
                            <th>Valores</th>
                            <th>Convocatoria</th>
                            <th>Activo</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($reglas as $regla)
                        <tr>
                            <td>
                                <a href="{{route('admineeditarregla', $regla->id)}}" class="btn btn-primary btn-sm">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                @if($regla->activo == 0)
                                {{ html()->form('POST', route('admindeleteregla'))->class('d-inline deleteregla')->open()}}       
                                {{ html()->hidden('id', $regla->id)}}
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                                {{ html()->form()->close()}}
                                @endif
                            </td>
                            <td>
                                {{$regla->prioridad}}
                            </td>
                            <td>
                                {{$regla->campo_scrapper}}
                            </td>
                            <td>
                                {{$regla->condicion}}
                            </td>
                            <td>
                                @foreach(json_decode($regla->valores) as $valor)
                                    @if(!$loop->last)
                                        {{$valor}} OR 
                                    @else 
                                        {{$valor}}
                                    @endif
                                @endforeach
                            </td>
                            <td>
                                {{$regla->convocatoria->Acronimo}}
                            </td>
                            <td>
                                @if($regla->activo == 1)
                                    <span><i class="fa-solid fa-check text-success"></i></span>
                                @else
                                    <span><i class="fa-solid fa-xmark text-danger"></i></span>
                                @endif
                                <span class="d-none">{{$regla->activo}}</span>
                            </td>
                        </tr>
                    @endforeach        
                    </tbody>
                </table>
            </div>
        @endif
	</div>
	<div class="card-footer">
		
	</div>
<!-- Modal -->
<div class="modal fade" id="CrearReglaModal" tabindex="-1" role="dialog" aria-labelledby="CrearReglaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="CrearReglaModalLabel">Crear Regla</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            {{ html()->form('POST', route('adminsaveregla'))->class('')->open()}}               
                {{ html()->hidden('organismo', request()->route('id')) }}
                <div class="modal-body">
                    <div class="form-group">
                        <label>Selecciona columna</label><br/>
                        <select name="columna" id="columna" class="form-control" width="100%" style="width:100%" required>
                            <option></option>
                            @foreach($columnas as $columna)
                                <option value="{{$columna}}">{{$columna}}</option>
                            @endforeach       
                        </select>
                    </div>
                    <div class="d-none" id="selectores">
                        <div class="form-group">
                            <label>Selecciona condicion</label><br/>
                            <select name="condicion" class="form-control select" width="100%" style="width:100%" required>                        
                                <option value="equals">{{__('igual que')}}</option>
                                <option value="distinct">{{__('distinto de')}}</option>
                                <option value="lowerequal">{{__('menor igual que')}}</option>
                                <option value="upperequal">{{__('mayor igual que')}}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Selecciona valor o valores</label><br/>
                            <select name="valores[]" class="form-control valores" width="100%" multiple="multiple" required>                              
                            </select>
                        </div>                      
                        <div class="form-group">
                            <label>Selecciona convocatoria</label><br/>
                            <select name="convocatoria" class="form-control select" width="100%" style="width:100%" required>                        
                                @foreach($convocatorias as $convocatoria)
                                    <option value="{{$convocatoria->id}}">{{$convocatoria->Acronimo}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Prioridad</label><br/>
                            <select name="prioridad" class="form-control select" width="100%" style="width:100%" required>                        
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                                <option value="7">7</option>
                                <option value="8">8</option>
                                <option value="9">9</option>
                            </select>
                            <small class="text-warning">* Si hay dos reglas con la misma prioridad prevalecerá la que tenga una fecha de creación más reciente</small>
                        </div>
                    </div>                                    
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Guardar Regla</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>                    
                </div>
            {{html()->form()->close()}}
        </div>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="AplicarReglasModal" tabindex="-1" role="dialog" aria-labelledby="AplicarReglasModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="AplicarReglasModalLabel">Aplicar Reglas</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            {{ html()->form('POST', route('adminaplicarreglas'))->class('')->open()}}               
                {{ html()->hidden('organismo', request()->route('id')) }}
                <div class="modal-body">
                    <p class="text-muted">Esta acción aplicará las reglas de este organismo a todos los proyectos obtenidos vía scrapper, provocando cambios en las vistas de organismos en innovating.works, <b>¿estás seguro?</b>.</p>
                    <p class="text-muted">Igualmente las reglas se aplicarán de manera asíncrona durante el proceso de actualización de datos nocturno.</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Aplicar Reglas</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>                    
                </div>
            {{html()->form()->close()}}
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
    <!--Datatables-->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap4.min.css"/>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"> </script>
    <script src="https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap4.min.js"> </script>
    <!-- jQuery Alerts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<script>
        $(document).ready(function() {
            $('#columna').select2({
                dropdownParent: $('#CrearReglaModal'),
                placeholder: "Selecciona uno...",
                allowClear: true,
                theme: "classic"
            });
            $('.select').select2({
                dropdownParent: $('#CrearReglaModal'),
                placeholder: "Selecciona uno...",
                allowClear: true,
                theme: "classic", 
            
            });
            $('.multiple-select').select2({
                dropdownParent: $('#CrearReglaModal'),
                placeholder: "Selecciona uno(s)...",
                allowClear: true,
                theme: "classic",
             
            });
        });
        $('#columna').on('change', function(e){
            var columna = $(this).val();
            var organismo = "{{request()->route('id')}}";
            if(columna !== undefined && columna != ""){
                $.ajax({
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    url: "{{ route('admingetajaxvalues') }}",
                    type:'POST',
                    data: {organismo: organismo, columna: columna},
                    success: function(resp){
                        console.log(resp);
                        var options = jQuery.parseJSON(resp);
                        console.log(options);
                        $("#selectores").removeClass('d-none');
                        $('select[name="valores[]"]').find('option').remove();
                        $.each(options, function(key, value) {
                            $('select[name="valores[]"]').append($('<option>', {
                                value: value,
                                text: value
                            }));
                        });
                        $('.valores').select2({
                            dropdownParent: $('#CrearReglaModal'),
                            placeholder: "Selecciona uno(s)...",
                            allowClear: true,
                            theme: "classic",
                        
                        });
                    },
                    error: function(resp){
                        $("#selectores").addClass('d-none');
                        $.alert(
                            {
                                title: 'Error en carga de valores',
                                content: resp.responseText
                            }
                        );
                        return false;
                    }
                });
            }
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
        $('.deleteregla').on('submit', function(e){
            e.preventDefault();
            var form = $(this);

            $.confirm({
                title : 'Borrar regla',
                content: 'vas a borrar esta regla puede provocar error en datos en innovating.works, <b>¿estás seguro?</b>',
                type: 'red',

                buttons: {
                    borrar:{
                        text: 'Borrar',
                        btnClass: 'btn-red',
                        action: function(){
                            $(form).unbind('submit').submit();
                        }
                    },
                    cancelar: function(){}
                }
            });
        })
    </script>
@stop   