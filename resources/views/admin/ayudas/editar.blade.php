@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Ayudas/Convocatorias</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Editar ayuda {{$ayuda->titulo}}</h3>
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
        {{ html()->form('POST', route('admineditayuda'))->class('submitayuda')->open()}}  
            {{ html()->hidden('id', $ayuda->id)}}          
            <div class="form-group">
                {{ html()->label( '<span class="text-danger">*</span> Acrónimo', 'acronimo') }}
                {{ html()->text('acronimo', $ayuda->acronimo)->class('form-control')->required()->maxlength(250) }}
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Título', 'titulo') }}
                {{ html()->text('titulo', $ayuda->titulo)->class('form-control')->required()->maxlength(250) }}
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Descripción', 'descripcion') }}
                {{ html()->textarea('descripcion', $ayuda->descripcion_corta)->class('form-control')->required()->maxlength(250)->rows(5) }}
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Mes apertura 1', 'mes_1') }}
                {{ html()->select('mes_1', $meses, $ayuda->mes_apertura_1)->class('form-control')->required() }}
            </div>
            <div class="form-group">
                {{ html()->label('Mes apertura 2', 'mes_2') }}
                {{ html()->select('mes_2', $meses, $ayuda->mes_apertura_2)->class('form-control')->placeholder('Selecciona uno si es necesario') }}
            </div>
            <div class="form-group">
                {{ html()->label('Mes apertura 3', 'mes_3') }}
                {{ html()->select('mes_3', $meses, $ayuda->mes_apertura_3)->class('form-control')->placeholder('Selecciona uno si es necesario') }}
            </div>
            <div class="form-group">
                {{ html()->label('<span class="text-danger">*</span> Duración convocatorias(nº de meses)', 'duracion') }}
                {{ html()->select('duracion', $meses, $ayuda->duracion_convocatorias)->class('form-control')->required() }}
            </div>
            <div class="form-check pl-1">
                {{ html()->label('Esta ayuda esta Cerrada/Extinguida','extinguida') }}
                {{ html()->checkbox('extinguida', $ayuda->extinguida, $ayuda->extinguida) }}
                <small class="text-muted">* Solo marcar cuando la ayuda esté ya cerrada.</small>
            </div>
            <button type="submit" class="btn btn-primary">Editar ayuda</button>
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
        $('.submitayuda').on('submit', function(e){
            e.preventDefault();
            var mes = $('select[name="mes_1"]').val();
            var mes2 = $('select[name="mes_2"]').val();
            var mes3 = $('select[name="mes_3"]').val();
            var duracion = $('select[name="duracion"]').val();
            var esindefinido = $('input[name="esindefinida"]').is(':checked');

            if(mes == "" && (mes2 != "" || mes3 != "")){
                $.alert({
                    title: 'Error al actualizar ayuda',
                    content: 'No puedes actualizar una ayuda con mes2 o mes3 sin añadir el mes1'
                });
                return false
            }

            if(mes >= mes2 && (!isNaN(mes) && !isNaN(mes2)) && mes != "" && mes2 != "" ||
                mes >= mes3 && (!isNaN(mes) && !isNaN(mes3)) && mes != "" && mes3 != "" ||
                mes2 >= mes3 && (!isNaN(mes2) && !isNaN(mes3) && mes2 != "" && mes3 != "")){
                $.alert({
                    title: 'Error al actualizar ayuda',
                    content: 'Debes poner los meses de apertura en sentido de menor mes al mayor'
                });
                return false
            }

            if(esindefinido === false && mes == "" && duracion == ""){
                $.alert({
                    title: 'Error al actualizar ayuda',
                    content: 'No puedes actualizar una ayuda sin mes de apertura 1, sin seleccionar duración y con el check de es indefinida sin marcar'
                });
                return false
            }

            if(esindefinido === false && (mes == "" || duracion == "")){
                $.alert({
                    title: 'Error al actualizar ayuda',
                    content: 'Una ayuda que no es indefinida debe llevar como mínimo valores en los selectores de mes de apertura 1 y duración'
                });
                return false
            }

            $(this).unbind().submit();

        });
    </script>
@stop   