@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Editar Validación ID: {{$validacion->id}}</h3>
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
        <form class="updatevalidacion" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="id" value="{{$validacion->id}}"/>
            <div class="form-group">
                <label for="nombre"><span class="text-danger">*</span> Usuario</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="{{$validacion->solicitante->email}}" disabled>
            </div>
            <div class="form-group">
                <label for="tipo"><span class="text-danger">*</span> CIF</label>
                <input type="text" class="form-control" id="cif" name="cif" value="{{$validacion->cif}}" disabled>
            </div>
            <div class="form-group">
                <label for="tipo"><span class="text-danger">*</span> Documento</label>
                <a href="{{asset('uploadfiles'.'/'.$validacion->doc)}}" target="_blank" class="btn btn-outline-primary btn-xs">Ver documento</a>
            </div>
            @if($validacion->esEntidad == 1)
                <small class="advice">* Aceptar significa que se creará la compañía en la Base de Datos, luego haremos la peticion a e-informa y luego daremos acceso al usuario con perfil admin y se te abre un modal para enviar iun e-mail de confirmación de creación de la compañía.</small>
                <br/>
                <button class="btn btn-success aceptar">Aceptar</button>
                <button class="btn btn-danger rechazar">Rechazar</button>
            @else
                <a href="{{route('admincrearempresas')}}?cif={{$validacion->cif}}" class="btn btn-primary">Crear empresa</a>
            @endif
        </form>
    </div>
</div>
<div class="modal fade" id="rechazavalidacion" tabindex="-1" role="dialog" aria-labelledby="rechazavalidacionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rechazavalidacionModalLabel">Rechazar solicitud de validación</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{route('rechazavalidacion')}}" method="post">
                @csrf
                <input type="hidden" name="id" value="{{Request::route('id')}}">
                <div class="modal-body">
                    <label><span class="text-danger">*</span> Indica el motivo del rechazo</label><br/>
                    <textarea class="form-control" cols="5" required name="mensaje"></textarea>
                    <small class="advice">* Este campo le será enviado por email al usuario que realizo la solicitud</small>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Rechazar</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </form>
        </div>
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
	<script>

    $('.aceptar').on('click', function(e){
        e.preventDefault();
        $('#adminloader').show();
        var data = $('.updatevalidacion').serialize();
        $.confirm({
            title: 'Aceptar solicitud de validación',
            content: 'Vas a aceptar la solicitud, ¿estas de acuerdo?',
            buttons: {
                ok: function(){
                    $.ajax({
                        headers: {
                            'X-CSRF-Token': '{{ csrf_token() }}',
                        },
                        url: "{{route('aceptavalidacion')}}",
                        type: "post",
                        data: data,
                        success: function(resp){
                            $('#adminloader').fadeOut("slow");
                            if(resp.error !== undefined){
                                $.alert({
                                    title: 'Aceptar solicitud',
                                    content: resp.error
                                });
                            }else{
                                $.alert({
                                    title: 'Aceptar solicitud',
                                    content: resp.success
                                });
                            }
                        },
                        error: function(xhr, status, error){
                            console.log(xhr);
                            console.log(status);
                            console.log(error);
                            return false;
                        }
                    })
                },
                cancel: function(){}
            }
        })
    });
    $('.rechazar').on('click', function(e){
        e.preventDefault();
        $('#rechazavalidacion').modal('toggle');
    });
        e.preventDefault();
        var id = "{{request()->route('id')}}";
        $.confirm({
            title: 'Rechazar solicitud de priorizar',
            content: 'Vas a rechazar la solicitud, ¿estas de acuerdo?',
            buttons: {
                ok: function(){
                    $.ajax({
                        headers: {
                            'X-CSRF-Token': '{{ csrf_token() }}',
                        },
                        url: "{{route('adminrechazapriorizar')}}",
                        type: "post",
                        data: {id: id},
                        success: function(resp){
                            if(resp.error !== undefined){
                                $.alert({
                                    title: 'Rechazar solicitud',
                                    content: resp.error
                                });
                            }else{
                                $.alert({
                                    title: 'Rechazar solicitud',
                                    content: resp.success
                                });
                            }
                        },
                        error: function(xhr, status, error){
                            console.log(xhr);
                            console.log(status);
                            console.log(error);
                            return false;
                        }
                    })
                },
                cancel: function(){}
            }
        })
    });
</script>
@stop   