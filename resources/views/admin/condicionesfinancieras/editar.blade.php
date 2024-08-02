@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Title</h3>
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
        <form action="{{route('admineditcondicionfinanciera')}}" method="POST" class="createcondicion">
            @csrf
            <input type="hidden" name="id" value="{{$condicion->id}}"/>
            <div class="form-group">
                <input type="checkbox" name="todasconvocatorias" id="todasconvocatorias" @if($condicion->todasconvocatorias == 1) checked @endif/>
                <label for="todasconvocatorias">Esta condición es para todas las convocatorias?</label>
            </div>
            <div class="form-group @if($condicion->todasconvocatorias == 1) d-none @endif" id="ids">
                <label for="idsconvocatorias"><span class="text-danger">*</span> Selecciona a que convocatorias afecta la condición</label>
                <select name="idsconvocatorias[]" class="" id="idsconvocatorias" multiple="multiple" style="width:100%;">
                    <option></option>
                    @foreach($convocatorias as $key => $convocatoria)
                        <option value="{{$key}}" @if($condicion->idsconvocatorias !== null && in_array($key, json_decode($condicion->idsconvocatorias))) selected @endif>{{$convocatoria}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="orden"><span class="text-danger">*</span> Orden de ejecución en un analisis financiero</label>
                <input type="number" name="orden" class="form-control" min="1" max="30" value="{{$condicion->orden}}"/>
            </div>
            <div class="form-group">
                <label for="var1"><span class="text-danger">*</span> Variable 1</label>
                <select name="var1" class="select2" required style="width:100%;">
                    <option></option>
                    @foreach($variables as $variable)
                        <option value="{{$variable}}" @if($condicion->var1 == $variable) selected @endif>{{$variable}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="condicion"><span class="text-danger">*</span> Condicion</label>
                <select name="condicion" class="select2" required style="width:100%;">
                    <option></option>
                    <option value=">" @if($condicion->condicion == ">") selected @endif>Variable 1 MAYOR que Variable 2</option>
                    <option value="<" @if($condicion->condicion == "<") selected @endif>Variable 1 MENOR que Variable 2</option>
                </select>
            </div>
            <div class="form-group">
                <label for="var2"><span class="text-danger">*</span> Variable 2</label>
                <select name="var2" class="select2" required style="width:100%;">
                    <option></option>
                    @foreach($variables2 as $variable)
                        <option value="{{$variable}}" @if($condicion->var2 == $variable) selected @endif>{{$variable}}</option>
                    @endforeach
                </select>
                <small class="advice text-muted">* Si la variable 2 seleccionada es "Fijo", el campo "Valor fijo" es obligatorio</small>
            </div>
            <div class="form-group d-none" id="valorfijo">
                <label for="valor"><span class="text-danger">*</span> Introduce el valor fijo para la variable 2</label>
                <input type="number" name="valor" class="form-control" min="0" value="{{$condicion->valor}}"/>
            </div>
            <div class="form-group">
                <label for="coeficiente">Coeficiente</label>
                <input type="number" name="coeficiente" class="form-control" min="0" max="1" step="0.01" value="{{$condicion->coeficiente}}"/>
            </div>
            <div class="form-group">
                <label for="comentario_cumple">Comentario SI se cumple condición</label>
                <textarea name="comentario_cumple" class="form-control" maxlength="250" rows="5">{{$condicion->comentario_cumple}}</textarea>
            </div>
            <div class="form-group">
                <label for="color_cumple">Color del texto SI se cumple condición</label>
                <select name="color_cumple" class="select2" style="width:100%;">
                    @foreach($colors as $color)
                        <option value="{{$color}}" @if($condicion->color_cumple == $color) selected @endif>{{$color}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="comentario_incumple"><span class="text-danger">*</span> Comentario NO se cumple condición</label>
                <textarea name="comentario_incumple" class="form-control" maxlength="250" rows="5" required>{{$condicion->comentario_incumple}}</textarea>
            </div>
            <div class="form-group">
                <label for="color_incumple">Color del texto si NO se cumple condición</label>
                <select name="color_incumple" class="select2" style="width:100%;">
                    @foreach($colors as $color)
                        <option value="{{$color}}" @if($condicion->color_incumple == $color) selected @endif>{{$color}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="link">Enlace explicación comentario</label>
                <input type="text" name="link" class="form-control" maxlength="250" value="{{$condicion->link}}"/>
            </div>
            <button type="submit" class="btn btn-primary">Editar condicion financiera</button>
        </form>
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "Selecciona...",
                allowClear: true,
                theme: "classic",
            });
            $('#idsconvocatorias').select2({
                placeholder: "Selecciona...",
                allowClear: true,
                theme: "classic",
            });
        });
        $('input[name="todasconvocatorias"]').on('click', function(e){
            if($('input[name="todasconvocatorias"]').is(':checked')){
                $("#ids").addClass('d-none');
                $('#idsconvocatorias').select2('destroy');
                $('#idsconvocatorias').select2({
                    placeholder: "Selecciona...",
                    allowClear: true,
                    theme: "classic",
                });
            }else{
                $("#ids").removeClass('d-none');
                $('#idsconvocatorias').select2('destroy');
                $('#idsconvocatorias').select2({
                    placeholder: "Selecciona...",
                    allowClear: true,
                    theme: "classic",
                });
            }
        });
        $("select[name='var2']").on('select2:select', function(e){
            console.log($(this).val());
            if($(this).val() == "Fijo"){
                $("#valorfijo").removeClass('d-none');
                $("input[name='valor']").attr('required', true);
            }else{
                $("#valorfijo").addClass('d-none');
                $("input[name='valor']").attr('required', false);
            }
        });
    </script>
@stop   