@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Ayudas/Convocatorias</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Crear Convocatoria</h3>
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
        <form action="{{ route('createayuda') }}" method="post">
            @csrf
            <input type="hidden" name="id" value="">
            <div class="form-group">
                <label for="titulo">Titulo</label>
                <input type="text" class="form-control" id="titulo" name="titulo" placeholder="Titulo" required>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="form-group mb-3">
                        <label for="organo">Organo</label>
                        <br/>
                        <select name="organo" class="form-control selectpicker" title="Selecciona uno..." data-live-search="true"  data-width="100%" required>
                            @foreach($organos as $organo)
                                <option value="{{$organo->id}}">{{$organo->Nombre}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="form-group mb-3">
                        <label for="departamento">Departamento</label>
                        <br/>
                        <select name="departamento" class="form-control selectpicker" title="Selecciona uno..." data-live-search="true"data-width="100%" required>
                            @foreach($departamentos as $departamento)
                                <option value="{{$departamento->id}}">{{$departamento->Nombre}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <small class="text-muted">* Una ayuda solo puede tener Organo o Departamento, las dos opciones no es posible.</small>
            <br/>
            <button type="submit" class="btn btn-primary">Crear</button>
        </form>   
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-autocomplete/1.0.7/jquery.auto-complete.min.js"
    integrity="sha512-TToQDr91fBeG4RE5RjMl/tqNAo35hSRR4cbIFasiV2AAMQ6yKXXYhdSdEpUcRE6bqsTiB+FPLPls4ZAFMoK5WA=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!--Bootstrap Select-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
@stop   