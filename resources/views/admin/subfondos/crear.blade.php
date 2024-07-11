@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Fondos</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Crear Subfondo</h3>
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
        {{ html()->form('POST', route('adminsavesubfondo'))->open()}} 
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Nombre', 'nombre') }}
                {{ html()->text('nombre', null)->class('form-control')->required()->maxlength(100) }}
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Acronimo', 'acronimo') }}
                {{ html()->text('acronimo', null)->class('form-control')->required()->maxlength(100) }}
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Nivel', 'nivel') }}
                {{ html()->select('nivel', ['1' => '1', '2' => '2'], null)->class('form-control')->placeholder('Selecciona uno...')->required() }}
            </div>
            <div class="form-group d-none" id="nivel_superior">
                {{ html()->label('<span class="text-danger">*</span> Padre del nivel 2', 'id_nivel_superior') }}
                {{ html()->select('id_nivel_superior',  $subfondos->pluck('nombre','external_id')->toArray(), null)->class('form-control')->placeholder('Selecciona uno...') }}
            </div>        
        <button type="submit" class="btn btn-primary">Crear Subfondo</button>                
        {{ html()->form()->close()}}
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
	<script>
        $("select[name='nivel']").on("change", 
            function(e, clickedIndex, newValue, oldValue) {
            if(this.value == 2){
                $("#nivel_superior").removeClass('d-none');
                $('#id_nivel_superior').attr('required', true);
            }else{
                $("#nivel_superior").addClass('d-none');
                $('#id_nivel_superior').attr('required', false);
            }
        });
    </script>
@stop   