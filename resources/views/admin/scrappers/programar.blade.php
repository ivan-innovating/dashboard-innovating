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
        <h4>Datos a tener en cuenta antes de crear scrappers de organismos</h4>
        <ul>
            <li>Solo se pueden solicitar scrappers de organismos que esten como scrapeables</li>
            <li>La fecha desde debe ser siempre menor a la fecha hasta</li>
            <li>Entre la fecha desde y la fecha hasta no puede haber más de 15 días de diferencia</li>
            <li>Mientras no se haya ejecutado el scrapper se podra borrar en la tabla de abajo, una vez ejecutado ya no se podrá borrar</li>
            <li>Si quieres scrapear un organismo desde cero este no es el sitio desde donde hacerlo</li>
            <li>Los scrappers programados se ejecutaran cada noche y se actualizaran en el motor de elastic</li>
            <li>Si el scrapper programado ha de obtener muchos datos(digamos 20000 concesiones por ejemplo), mejor no programar nada y decirselo al programador para que lo ejecute el.</li>
        </ul>
        {{ html()->form('POST', route('admincreateprogramscrapper'))->class('create-task')->open()}}
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Selecciona Organo', 'organo') }}<br/>                
                <select name="organo" class="select2 w-100" id="organo" required>
                    <option></option>
                    @foreach($organos as $key => $dpto)
                    <option value="{{$key}}">{{$dpto}}</option>
                    @endforeach
                </select> 
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> O Selecciona un departamento', 'dpto') }}<br/>                
                <select name="dpto" class="select2 w-100" id="dpto" required>
                    <option></option>
                    @foreach($departamentos as $key => $dpto)
                    <option value="{{$key}}">{{$dpto}}</option>
                    @endforeach
                </select> 
            </div>
            <div class="form-group flex justify-between">
                <div class="w-50">
                    {{ html()->label('<span class="text-danger">*</span> Fecha desde', 'desde') }}
                    <div class="input-group date" id="desde" data-target-input="nearest">
                        <input type="text" name="desde" class="form-control-xs datetimepicker-input" data-target="#desde" required aria-describedby="fechahelp" placeholder="Fecha desde" onkeydown="return false"/>
                        <div class="input-group-append" data-target="#desde" data-toggle="datetimepicker">
                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                        </div>
                        <div class="input-group-append">
                            <div class="input-group-text"><button type="button" class="btn btn-link btn-xs text-muted clearbutton" data-item="desde"><i class="fa fa-times"></i></button></div>
                        </div>
                    </div>         
                </div>
                <div class="w-50">
                    {{ html()->label('<span class="text-danger">*</span> Fecha hasta', 'hasta') }}<br/>
                    <div class="input-group date" id="hasta" data-target-input="nearest">                                                            
                        <input type="text" name="hasta" class="form-control-xs datetimepicker-input" data-target="#hasta" required aria-describedby="fechahelp" placeholder="Fecha hasta" onkeydown="return false"/>
                        <div class="input-group-append" data-target="#hasta" data-toggle="datetimepicker">
                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                        </div>
                        <div class="input-group-append">
                            <div class="input-group-text"><button type="button" class="btn btn-link btn-xs text-muted clearbutton" data-item="hasta"><i class="fa fa-times"></i></button></div>
                        </div>
                    </div>                
                </div>
            </div>                                            
            <div class="form-row">
                <button type="submit" class="btn btn-primary">Crear</button>                
            </div>
        {{ html()->form()->close()}}
        <h6 class="pb-0 mb-0 mt-3 text-muted">Solo se muestran los últimos 100 scrappers programados por fecha de más reciente a más antiguo</h6>
        <div class="table-responsive">
            <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table3">
                <thead>
                    <tr>
                        <th></th>
                        <th>Organo a scrappear</th>
                        <th>Fecha desde</th>
                        <th>Fecha hasta</th>
                        <th>Creado por</th>
                        <th>Terminado</th>         
                        <th>Borrar tarea programada</th>                                                                                                           
                    </tr>
                </thead>
                <tbody>
                @if($scrapperprogramados->count() > 0)
                    @foreach($scrapperprogramados as $programado)
                    <tr>
                        <td>
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </td>
                        <td>
                            @if($programado->organo !== null)                                                                    
                                @if($programado->organo->Acronimo !== null)                                                                    
                                    {{$programado->organo->Acronimo}}
                                @else
                                    {{$programado->organo->Nombre}}
                                @endif
                            @elseif($programado->departamento !== null)                                                                    
                                @if($programado->departamento->Acronimo !== null)                                                                    
                                    {{$programado->departamento->Acronimo}}
                                @else
                                    {{$programado->departamento->Nombre}}
                                @endif
                            @endif       
                            <span class="d-none">{{$programado->id_organismo}}</span>
                        </td>                                                            
                        <td>
                            {{$programado->desde}}                                                                
                        </td>
                        <td>
                            {{$programado->hasta}}                                                                
                        </td>
                        <td>                                                            
                            {{$programado->user->email}}                                                                
                        </td>
                        <td> 
                            @if($programado->ejecutado == 0)                                                                    
                                <span class="text-warning">Pendiente</span>
                            @else 
                                <span class="text-succes">Terminado</span>
                            @endif       
                        </td>
                        <td>
                            @if($programado->ejecutado == 0)                  
                                {{ html()->form('POST', route('admindeleteprogramscrapper'))->open()}}     
                                    {{ html()->hidden('id', $programado->id)}}                                             
                                    <button type="submit" class="btn-xs btn-warning"><i class="fa-solid fa-xmark"></i></button>
                                {{ html()->form()->close()}}
                            @else 
                                Ejecutado el {{$programado->updated_at}}
                            @endif                                                                
                        </td>                      
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="9">
                            No hay scrappers programdos.
                        </td>
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
        <!--DatePicker-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js" integrity="sha512-qTXRIMyZIFb8iQcfjXWCO8+M5Tbc38Qi5WzdPOYZHIlZpzBHG3L3by84BBBOiRGiEb7KKtAOAs5qYdUiZiQNNQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/js/tempusdominus-bootstrap-4.min.js" integrity="sha512-k6/Bkb8Fxf/c1Tkyl39yJwcOZ1P4cRrJu77p83zJjN2Z55prbFHxPs9vN7q3l3+tSMGPDdoH51AEU8Vgo1cgAA==" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/css/tempusdominus-bootstrap-4.min.css" integrity="sha512-3JRrEUwaCkFUBLK1N8HehwQgu8e23jTH4np5NHOmQOobuC4ROQxFwFgBLTnhcnQRMs84muMh0PnnwXlPq5MGjg==" crossorigin="anonymous" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<script>
        $(function () {
            $('#table3').DataTable({
                "paging": true,
                "pageLength": 30,
                "lengthChange": false,
                "searching": true,
                "ordering": true,
                "info": false,
                "autoWidth": false,
                "responsive": false,
                "order": [[6, 'asc']],
            });
            $(".select2").select2({
                placeholder: "Selecciona uno...",
                allowClear: true,
                theme: "classic",
            });
        });
        $('#organo').on('select2:select', function(e, clickedIndex, newValue, oldValue){            
            if(this.value != null){
                $('#dpto').attr('required', false);                       
            }else{               
                $('#dpto').attr('required', true);        
            }
            $('#dpto').select2('destroy');
            $("#dpto").select2({
                placeholder: "Selecciona uno...",
                allowClear: true,
                theme: "classic",
            });    
            console.log("pepe");
        });
        $('#dpto').on('select2:select', function(e, clickedIndex, newValue, oldValue){
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
        $("#desde").datetimepicker({
            format: 'DD/MM/YYYY',
            viewMode: 'years',
        });
        $("#hasta").datetimepicker({
            format: 'DD/MM/YYYY',
            viewMode: 'years',
            useCurrent: false,
        });
        $('.clearbutton').on('click', function(e){
            e.preventDefault();
            var val = $(this).attr('data-item');
            $('input[name="'+val+'"]').val('');
        });
        $("#desde").on("change.datetimepicker", function (e) {
            if($('#hasta').val() == ""){
                var d1 = new Date(e.date);
                d1.setDate(d1.getDate() + 1);
                var d2 = new Date(e.date);
                d2.setDate(d2.getDate() + 15);                
                $('#hasta').datetimepicker('minDate', d1);
                $('#hasta').datetimepicker('maxDate', d2);                
            }
        });
        $("#hasta").on("change.datetimepicker", function (e) {
            if($('#desde').val() == ""){
                var d1 = new Date(e.date);
                d1.setDate(d1.getDate() - 15);
                var d2 = new Date(e.date);
                d2.setDate(d2.getDate() -1);                
                $('#desde').datetimepicker('maxDate', d2);
                $('#desde').datetimepicker('minDate', d1);
            }
        });
    </script>
@stop   