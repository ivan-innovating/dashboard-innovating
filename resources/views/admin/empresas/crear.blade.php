@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard Empresas</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Crear empresa</h3>
		<div class="card-tools">
			<button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
			    <i class="fas fa-minus"></i>
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
        {{ html()->form('POST', route('adminnewempresa'))->class('createempresa')->open()}}            
            {{ html()->hidden('cif', request()->query('cif'))}}
                <div class="form-group mb-3">
                    <label for="nombre"><span class="text-danger">*</span> Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre"  aria-describedby="nombreHelp" required/>
                    <small id="nombreHelp" class="form-text text-muted">Evita poner el tipo de empresa S.A., SL, etc...</small>
                </div>
                <div class="form-group mb-3">
                    <label for="web"><span class="text-danger">*</span> Web</label>
                    <input type="text" class="form-control" id="web" name="web" aria-describedby="webHelp" required/>
                    <small id="webHelp" class="form-text text-muted">Formato https://... o http://...</small>
                </div>
                <div class="form-group mb-3">
                    <label for="ccaa"><span class="text-danger">*</span> CCAA</label>
                    <br/>
                    <select name="ccaa" class="selectpicker" title="Selecciona uno..." data-live-search="true" data-width="100%">
                        @foreach($ccaas as $ccaa)
                            <option value="{{$ccaa->Nombre}}">{{$ccaa->Nombre}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-3">
                <label for="pais"><span class="text-danger">*</span> País</label>
                    <br/>
                    <select name="pais" class="selectpicker" title="Selecciona uno..." data-live-search="true" data-width="100%">
                        @foreach($paises as $pais)
                            <option value="{{strtolower($pais->iso2)}}">{{$pais->Nombre_es}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-3">
                <label for="cnae"><span class="text-danger">*</span> CNAE</label>
                    <br/>
                    <select name="cnae" class="selectpicker" title="Selecciona uno..." data-live-search="true" data-width="100%">
                        @foreach($cnaes as $cnae)
                            <option value="{{$cnae->Nombre}}">{{$cnae->Nombre}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Crear</button>
        {{ html()->form()->close() }}
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
    <!--Bootstrap Select-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-autocomplete/1.0.7/jquery.auto-complete.min.js"
 integrity="sha512-TToQDr91fBeG4RE5RjMl/tqNAo35hSRR4cbIFasiV2AAMQ6yKXXYhdSdEpUcRE6bqsTiB+FPLPls4ZAFMoK5WA=="
 crossorigin="anonymous" referrerpolicy="no-referrer"></script>
 	<script type="text/javascript">
     
	</script>
@stop   