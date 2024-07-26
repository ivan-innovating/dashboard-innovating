@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Organismos</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Listado de CCAAs</h3>
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
        <div class="table-responsive">        
            <table class="table table-hover text-nowrap f-14 w-100" id="table2">
                <thead>
                    <tr>
                        <th></th>
                        <th>Nombre</th>
                        <th>Acronimo</th>                
                        <th>Url</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ccaas as $ccaa)
                    <tr data-item="ccaa" id="ccaa{{$ccaa->id}}">
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="copyContent('{{$ccaa->Nombre}}')" title="Copiar Id departamento"><i class="fa-regular fa-clipboard"></i></button> 
                        </td>
                        <td>
                            {{$ccaa->Nombre}}
                        </td>
                        <td>
                            {{$ccaa->Acronimo}}
                        </td>
                        <td>
                            {{$ccaa->url}}
                        </td>
                        <td>{{$ccaa->Descripcion}}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>       
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
    <script>
        const copyContent = async (identificador) => {
            try {
                await navigator.clipboard.writeText(identificador);
                $.confirm({
                    title: 'Copiado al portapapeles',
                    content: 'Este popup se cerrara pasados 5 segundos',
                    autoClose: 'cerrar|5000',
                    buttons: {
                        cerrar: function () {

                        }
                    }
                });
            } catch (err) {
                console.error('Failed to copy: ', err);
            }
        }
    </script>
@stop   