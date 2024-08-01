@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Stats generales</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Stats generales</h3>
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
        <ul class="list-group mb-3 w-50">
            <li class="list-group-item bg-primary text-white">Empresas</li>
            <li class="list-group-item border-bottom rounded">Total de Empresas: {{$entidadesEs + $entidadesNoEs}}.</li>
            <li class="list-group-item border-bottom rounded">Total de Empresas ES: {{$entidadesEs}}.</li>
            <li class="list-group-item border-bottom rounded">Total de Empresas No ES: {{$entidadesNoEs}}.</li>
            <li class="list-group-item border-top rounded">Total centros: {{$centros}}.</li>
            <li class="list-group-item border-top rounded">Total en pendientes(tabla de cifs): {{$cifsnozoho}}.</li>
            <li class="list-group-item border-top rounded">Total de Einformas únicos: {{$einformas}}.</li>
            <li class="list-group-item border-top rounded">Total de Axesors únicos: {{$axesors}}.</li>
            <li class="list-group-item border-top rounded">Total datos financieros manuales: {{$manuales}}.</li>
            <li class="list-group-item border-top rounded">Total Empresas Sin calculo TRL España: {{$empresassintrl}}.</li>
            <li class="list-group-item border-top rounded">Total Empresas TRL < 4 España: {{$empresastrlmenor4}}.</li>
            <li class="list-group-item border-top rounded">Total Empresas TRL = 4 España: {{$empresastrl4}}.</li>
            <li class="list-group-item border-top rounded">Total Empresas TRL = 5 España: {{$empresastrl5}}.</li>
            <li class="list-group-item border-top rounded">Total Empresas TRL = 6 España: {{$empresastrl6}}.</li>
            <li class="list-group-item border-top rounded">Total Empresas TRL = 7 España: {{$empresastrl7}}.</li>
            <li class="list-group-item border-top rounded">Total Empresas TRL > 7 España: {{$empresastrlmayor7}}.</li>
            <li class="list-group-item border-top rounded">Total Empresas TRL >= 5 NO España: {{$empresastrl5masnospain}}.</li>
            <li class="list-group-item border-top rounded">Total Empresas TRL < 5 NO España: {{$empresastrl5menosnospain}}.</li>
        </ul>
        <ul class="list-group mb-3 w-50">
            <li class="list-group-item bg-primary text-white">Ayudas</li>
            <li class="list-group-item border-bottom rounded">Total de Ayudas: {{$ayudas}}.</li>
            <li class="list-group-item border-top rounded">Total de Encajes: {{$encajes}}.</li>
        </ul>
        <ul class="list-group mb-3 w-50">
            <li class="list-group-item bg-primary text-white">Concesiones</li>
            <li class="list-group-item border-bottom rounded">Total de Concesiones: {{$concesiones}}.</li>
            <li class="list-group-item border-top rounded">Total de Patentes: {{$patentes}}.</li>
        </ul>
        <ul class="list-group mb-3 w-50">
            <li class="list-group-item bg-primary text-white">Proyectos</li>
            <li class="list-group-item border-bottom rounded">Total de Proyectos: {{$proyectos}}.</li>
            <li class="list-group-item border-top rounded">Total de Proyectos AEI: {{$proyectosaei}}.</li>
            <li class="list-group-item border-top rounded">Total de Proyectos CDTI: {{$proyectoscdti}}.</li>
        </ul>
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
	<script></script>
@stop   