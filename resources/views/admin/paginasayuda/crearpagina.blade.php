@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Páginas de ayuda</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Crear página de ayuda</h3>
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
        <form method="post" action="{{route('admineditpagina')}}" class="crearpagina">
            @csrf                   
            <input type="hidden" name="type" value="nueva"/>
            <input type="hidden" name="id" value="null"/>
            <div class="form-group">
                <label for="titulo"><span class="text-danger">*</span> Título</label>
                <input type="text" maxlength="9" class="form-control" id="titulo" name="titulo" value="" required>                                                   
                <small class="text-muted">* Longitud máxima permitida 190 caracteres</small>
            </div>
            <div class="form-group">
                <label for="descripcion"><span class="text-danger">*</span> Descripción</label>
                <input type="text" class="form-control" id="descripcion" name="descripcion" value="" required>
                <small class="text-muted">* Longitud máxima permitida 190 caracteres</small>
            </div>       

            <div class="form-group">
                <label for="link"><span class="text-danger">*</span> Link</label>
                <input type="url" class="form-control" id="link" name="link" value="" required>
                <small class="text-muted">* Longitud máxima permitida 190 caracteres, formato url http(s)://...</small>                                
            </div>
            <div class="form-group">
                <label for="carpetas"><span class="text-danger">*</span> Carpeta(s)</label>
                <select class="form-control select2" id="carpetas" name="carpetas[]" required multiple title="Selecciona una...">
                    @foreach($carpetasarray as $key => $carpeta)
                        <option value="{{$key}}">{{$carpeta}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="posicion"><span class="text-danger">*</span> Posición</label>
                <input type="number" class="form-control" id="posicion" name="posicion" value="" min="1" max="100" required>
                <small class="text-muted">* orden de vista de ayudas, el orden es de 1 a 100</small>
            </div>
            <div class="form-group">                                             
                <input type="checkbox" name="activa">
                <label for="activa">Activar</label>
                <small class="text-muted">* Marcado = activada, Desmarcado = desactivada</small>
            </div>                  
            <button type="submit" class="btn btn-primary btn-sm">Crear página</button>
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
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- jQuery Alerts -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>
<script>
    $(document).ready(function() {
        $(".select2").select2({
            tags: true,
            maximumSelectionLength: 40
        });
    });
    $('form').on('submit', function(){
        var $preloader = $('.preloader');
        $preloader.css('height', '100%');
        setTimeout(function () {
            $preloader.children().show();
        });
    });
</script>
@stop   