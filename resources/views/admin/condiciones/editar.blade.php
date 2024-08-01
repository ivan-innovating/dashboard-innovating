@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Stats generales</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Editar Condición recompensa</h3>
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
        <form action="{{route('admineditcondicion')}}" method="POST">
            @csrf
            <input type="hidden" name="id" value="{{$condicion->id}}"/>
            <div class="form-group">
                <label for="tipo"><span class="text-danger">*</span> Tipo de premio</label>
                <select name="tipo" class="form-control select2" required>
                    <option></option>
                    <option value="Mención" @if($condicion->tipo_premio == "Mención") selected @endif>Mención</option>
                    <option value="Premio" @if($condicion->tipo_premio == "Premio") selected @endif>Premio</option> 
                </select>
            </div>
            <div class="form-group">
                <label for="dato"><span class="text-danger">*</span> Tipo de dato</label>
                <select name="dato" class="form-control select2" required>
                    <option></option>
                    <option value="trl_medio" @if($condicion->dato == "trl_medio") selected @endif>Trl Medio</option>
                    <option value="trl_max" @if($condicion->dato == "trl_max") selected @endif>Trl máximo</option> 
                    <option value="trl_min" @if($condicion->dato == "trl_min") selected @endif>Trl mínimo</option>
                    <option value="gasto_medio" @if($condicion->dato == "gasto_medio") selected @endif>Gastos Medio I+D+i</option> 
                    <option value="gasto_max" @if($condicion->dato == "gasto_max") selected @endif>Gastos máximo I+D+i</option>
                    <option value="gasto_min" @if($condicion->dato == "gasto_min") selected @endif>Gastos mínimo I+D+i</option> 
                    <option value="esfuerzo_medio" @if($condicion->dato == "esfuerzo_medio") selected @endif>Esfuerzo Medio I+D+i</option> 
                    <option value="esfuerzo_max" @if($condicion->dato == "esfuerzo_max") selected @endif>Esfuerzo máximo I+D+i</option>
                    <option value="esfuerzo_min" @if($condicion->dato == "esfuerzo_min") selected @endif>Esfuerzo mínimo I+D+i</option>
                </select>
            </div>
            <div class="form-group">
                <label for="condicion"><span class="text-danger">*</span> Condición</label>
                <select name="condicion" class="form-control select2" required>
                    <option></option>
                    <option value=">" @if($condicion->condicion == ">") selected @endif>Mayor</option> 
                    <option value=">=" @if($condicion->condicion == ">=") selected @endif>Mayor igual</option>
                    <option value="=" @if($condicion->condicion == "=") selected @endif>Igual</option>
                    <option value="<=" @if($condicion->condicion == "<=") selected @endif>Menor igual</option>
                    <option value="<" @if($condicion->condicion == "<") selected @endif>Menor</option>
                </select>
            </div>
            <div class="form-group">
                <label for="dato2"><span class="text-danger">*</span> Dato de la empresa</label>
                <select name="dato2" class="form-control select2" required>
                    <option></option>
                    <option value="valorTrl" @if($condicion->dato2 == "valorTrl") selected @endif>Trl de la empresa</option> 
                    <option value="cantidadImasD" @if($condicion->dato2 == "cantidadImasD") selected @endif>Gasto en I+D</option>
                    <option value="esfuerzoID" @if($condicion->dato2 == "esfuerzoID") selected @endif>Esfuerzo en I+D</option>
                </select>
            </div>
            <div class="form-group">
                <label for="valor"><span class="text-danger">*</span> Valor</label>
                <input type="number" name="valor" class="form-control" value="{{$condicion->valor}}" required/>
            </div>
            <div class="form-group">
                <label for="esporcentaje">El campo valor es un porcentaje?</label>
                <input type="checkbox" name="esporcentaje" id="esporcentaje" @if($condicion->esporcentaje == 1) checked @endif />
            </div>
            <div class="form-group">
                <label for="operacion"><span class="text-danger">*</span> Operacón para el campo valor</label>
                <select name="operacion" class="form-control select2" required>
                    <option></option>
                    <option value="+" @if($condicion->operacion == "+") selected @endif>Suma</option> 
                    <option value="-" @if($condicion->operacion == "-") selected @endif>Resta igual</option>
                    <option value="*" @if($condicion->operacion == "*") selected @endif>Multiplicación</option>
                </select>
            </div>    
            <button type="submit" class="btn btn-primary">Editar condición recompensa</button>         
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
        });
    </script>
@stop   