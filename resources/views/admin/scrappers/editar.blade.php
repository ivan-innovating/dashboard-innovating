@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Scrappers</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Editar Scrapper {{$scrapper->name}}</h3>
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
		<form method="post" action="{{route('admineditscrapper')}}">
            @csrf
            <input type="hidden" name="id" value="{{$scrapper->id}}"/>
            <div class="form-group">
                <label for="nombre"><span class="text-danger">*</span> Nombre</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="{{$scrapper->name}}" disabled readonly>
            </div>
            <div class="form-group">
                <label for="orgdpto"><span class="text-danger">*</span> Organo/Departamento</label>
                <input type="text" class="form-control" id="orgdpto" name="orgdpto" value="{{$scrapper->orgdpto->Nombre}}" disabled readonly>
            </div>
            <div class="form-group">
                <label for="total"><span class="text-danger">*</span> Total concesiones scrapeadas última ejecución</label>
                <input type="number" min="1" class="form-control" id="total" name="total" value="{{$scrapper->datos['Total']}}" disabled readonly>
            </div>
            <div class="form-group">
                <label for="pages"><span class="text-danger">*</span> Número de páginas scrappeadas</label>
                <input type="number" min="1" class="form-control" id="pages" name="pages" value="{{$scrapper->datos['pages']}}" disabled readonly>
            </div>
            <div class="form-group">
                <label for="ultima"><span class="text-danger">*</span>Última página scrappeada</label>
                <input type="number" min="0" class="form-control" id="ultima" name="ultima" value="{{$scrapper->datos['current']}}" aria-describedby="fechainiciohelp">
                <small id="setnullhelp" class="form-text text-muted">* Cambiar este valor si se ha interrumpido la ejecución del scrapper</small>
            </div>
            <label for="inicio">Fecha última ejecución</label><br/>
            <div class="input-group date" id="inicio" data-target-input="nearest">
                <input type="text" name="inicio" class="form-control datetimepicker-input" data-target="#inicio" aria-describedby="fechainiciohelp"/>
                <div class="input-group-append" data-target="#inicio" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                </div>
            </div>
            <small id="fechainiciohelp" class="form-text text-muted">Esta es la fecha de la que se tiene en cuenta para scrappear las ayudas de este organismo, si se pone una fecha se buscaran desde esa fecha hasta un mes antes.</small>
            <br/>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="setnull" name="setnull" aria-describedby="setnullhelp">
                <label class="form-check-label" for="setnull">¿Setear la fecha a null?</label>
                <small id="setnullhelp" class="form-text text-muted">Si se marca este checkbox al guardar, en la próxima actualización de datos de este organo departamento se tomara como fecha de inicio: 01/01/2021</small>
            </div>
            <br/>
            <button type="submit" class="btn btn-primary">Guardar</button>
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
	<!--DatePicker-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js" integrity="sha512-qTXRIMyZIFb8iQcfjXWCO8+M5Tbc38Qi5WzdPOYZHIlZpzBHG3L3by84BBBOiRGiEb7KKtAOAs5qYdUiZiQNNQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/js/tempusdominus-bootstrap-4.min.js" integrity="sha512-k6/Bkb8Fxf/c1Tkyl39yJwcOZ1P4cRrJu77p83zJjN2Z55prbFHxPs9vN7q3l3+tSMGPDdoH51AEU8Vgo1cgAA==" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/css/tempusdominus-bootstrap-4.min.css" integrity="sha512-3JRrEUwaCkFUBLK1N8HehwQgu8e23jTH4np5NHOmQOobuC4ROQxFwFgBLTnhcnQRMs84muMh0PnnwXlPq5MGjg==" crossorigin="anonymous" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
    <script src="https://cdn.metroui.org.ua/v4.3.2/js/metro.min.js" async></script>
    <!-- jQuery Alerts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>
    <script>
        $(function(){
            $("#inicio").datetimepicker({
                format: 'DD/MM/YYYY hh:mm:ss',
                viewMode: 'days',
                defaultDate: "{{$scrapper->updated_at}}"
            });
            $("#setnull").on('click', function(){
                if($('.datetimepicker-input').prop('disabled')){
                    $(".datetimepicker-input").attr('disabled', false);
                }else{
                    $(".datetimepicker-input").attr('disabled', true);
                }
            });
        });

    </script>
@stop                                                   