@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Scrappers</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Editar Regla {{$regla->id}}</h3>
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
        {{ html()->form('POST', route('admineeditregla'))->class('')->open()}}               
            {{ html()->hidden('id', $regla->id) }}
            {{ html()->hidden('organismo', $regla->id_organismo) }}
            <div class="form-group">
                <label>Selecciona columna</label><br/>
                <select name="columna" id="columna" class="form-control" width="100%" style="width:100%" required>
                    <option></option>
                    @foreach($columnas as $columna)
                        @if($columna == $regla->campo_scrapper)
                        <option value="{{$columna}}" selected>{{$columna}}</option>
                        @else
                        <option value="{{$columna}}">{{$columna}}</option>
                        @endif
                    @endforeach       
                </select>
            </div>
            <div class="form-group">
                <label>Selecciona condicion</label><br/>
                <select name="condicion" class="form-control select" width="100%" style="width:100%" required>                        
                    <option value="equals" @if($regla->condicion == "equals") selected @endif>{{__('igual que')}}</option>
                    <option value="distinct" @if($regla->condicion == "distinct") selected @endif>{{__('distinto de')}}</option>
                    <option value="lowerequal" @if($regla->condicion == "lowerequal") selected @endif>{{__('menor igual que')}}</option>
                    <option value="upperequal" @if($regla->condicion == "upperequal") selected @endif>{{__('mayor igual que')}}</option>
                </select>
            </div>
            <div class="form-group">
                <label>Selecciona valor o valores</label><br/>
                <select name="valores[]" class="form-control valores" width="100%" multiple="multiple" required>  
                    @foreach(json_decode($regla->valores, true) as $valor)
                        <option value="{{$valor}}" selected>{{$valor}}</option>
                    @endforeach                            
                </select>
            </div>                      
            <div class="form-group">
                <label>Selecciona convocatoria</label><br/>
                <select name="convocatoria" class="form-control select" width="100%" style="width:100%" required>                         
                    @foreach($convocatorias as $convocatoria)
                        @if($convocatoria->id == $regla->id_convocatoria)
                        <option value="{{$convocatoria->id}}" selected>{{$convocatoria->Acronimo}}</option>
                        @else
                        <option value="{{$convocatoria->id}}">{{$convocatoria->Acronimo}}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Prioridad</label><br/>
                <select name="prioridad" class="form-control select" width="100%" style="width:100%" required>                        
                    <option value="1" @if($regla->prioridad == "1") selected @endif>1</option>
                    <option value="2" @if($regla->prioridad == "2") selected @endif>2</option>
                    <option value="3" @if($regla->prioridad == "3") selected @endif>3</option>
                    <option value="4" @if($regla->prioridad == "4") selected @endif>4</option>
                    <option value="5" @if($regla->prioridad == "5") selected @endif>5</option>
                    <option value="6" @if($regla->prioridad == "6") selected @endif>6</option>
                    <option value="7" @if($regla->prioridad == "7") selected @endif>7</option>
                    <option value="8" @if($regla->prioridad == "8") selected @endif>8</option>
                    <option value="9" @if($regla->prioridad == "9") selected @endif>9</option>
                </select>
                <small class="text-info">* Si hay dos reglas con la misma prioridad prevalecerá la que tenga una fecha de creación más reciente</small>
            </div>
            <div class="form-group">
                <label for="activo">Esta activada esta regla?</label>
                <input type="checkbox" name="activo" id="activo" @if($regla->activo == 1) checked @endif><br/>
                <small class="text-info">* Para poder borra una regla en el listado de reglas, este campo debe estar desmarcado/desactivado</small>
            </div>
            <button type="submit" class="btn btn-primary">Actualizar Regla</button>
        {{html()->form()->close()}}

	</div>
	<div class="card-footer">
		
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
    <!-- jQuery Alerts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<script>
        $(document).ready(function() {
            $('#columna').select2({
                placeholder: "Selecciona uno...",
                allowClear: true,
                theme: "classic"
            });
            $('.select').select2({
                placeholder: "Selecciona uno...",
                allowClear: true,
                theme: "classic", 
            
            });
            $('.multiple-select').select2({
                placeholder: "Selecciona uno(s)...",
                allowClear: true,
                theme: "classic",
             
            });
            $('.valores').select2({
                placeholder: "Selecciona uno(s)...",
                allowClear: true,
                theme: "classic",
            
            });
        });
        $('#columna').on('change', function(e){
            var columna = $(this).val();
            var organismo = "{{$regla->id_organismo}}";
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
                                text: value,
                            }));
                        });
                        $('.valores').val('').change();
                        $('.valores').select2({
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
     
    </script>
@stop   