@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Páginas de ayuda</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Crear carpeta de ayuda</h3>
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
        <form method="post" action="{{route('admineditcarpeta')}}" class="editarcarpeta">
            @csrf                
            <input type="hidden" name="type" value="nueva"/>
            <input type="hidden" name="id" value="null"/>
            <div class="form-group">
                <label for="nombre"><span class="text-danger">*</span> Nombre</label>
                <input type="text" name="nombre" value="" class="form-control" required  maxlength="100"/>
                <small class="text-muted">* Longitud máxima permitida 100 caracteres</small>
            </div>
            <div class="form-group">
                <label for="orden"><span class="text-danger">*</span> Posición</label>
                <input type="number" name="orden" value="" class="form-control" required min="1" max="100"/>
                <small class="text-muted">* orden de vista de ayudas, el orden es de 1 a 100</small>
            </div>
            <div class="form-group">                        
                <input type="checkbox" name="activa"/>
                <label for="activa">Activar</label>
                <small class="text-muted">* Marcado = activada, Desmarcado = desactivada</small>
            </div>                                    
            <button type="submit" class="btn btn-primary btn-sm">Crear carpeta</button>
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
<!-- jQuery Alerts -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>
<script>
  
    $('form').on('submit', function(){
        var $preloader = $('.preloader');
        $preloader.css('height', '100%');
        setTimeout(function () {
            $preloader.children().show();
        });
    });
</script>
@stop   