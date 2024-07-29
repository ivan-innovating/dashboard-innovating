@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Patentes</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Editar patente {{$patente->Nombre}}</h3>
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
        <form method="post" action="{{route('admineditpatente')}}" class="editapatente">
            @csrf
            <input type="hidden" name="id" value="{{$patente->id}}"/>
            <div class="form-group">
                <label for="titulo"><span class="text-danger">*</span> Titulo</label>
                <input type="text" readonly class="form-control" id="titulo" name="titulo" value="{{$patente->Titulo}}" required>
            </div>
            <div class="form-group">
                <label for="fecha"><span class="text-danger">*</span> Fecha</label>
                <input type="text" readonly class="form-control" id="fecha" name="fecha" value="{{$patente->Fecha_publicacion}}" required>
            </div>
            <div class="form-group">
                <label for="numero"><span class="text-danger">*</span> # Solicitud</label>
                <input type="text" readonly class="form-control" id="numero" name="numero" value="{{$patente->Numero_solicitud}}" required>
            </div>
            <div class="form-group">
                <label for="solicitantes"><span class="text-danger">*</span> Solicitantes</label>
                <input type="text" readonly class="form-control" id="solicitantes" name="solicitantes" value="{{$patente->Solicitantes}}" required>
            </div>                                                    
            <div class="form-group">
                <label for="cif"><span class="text-danger">*</span> CIF</label>
                <input type="text" maxlength="9" class="form-control" id="cif" name="cif" value="{{$patente->CIF}}" required readonly>                                                   
                <button type="button" class="btn btn-primary btn-sm mr-3" data-toggle="modal" data-target="#ModalSearch"><i class="fa-solid fa-magnifying-glass"></i> Buscar empresa por CIF</button>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="iguales" id="iguales">
                <label class="form-check-label" for="iguales">
                ¿Asignar todas la patentes con este solicitante <b>{{$patente->Solicitantes}}</b> al CIF seleccionado?
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
	</div>
	<div class="card-footer">
		
	</div>
</div>
<!-- Modal -->
<div class="modal fade" id="ModalSearch" tabindex="-1" role="dialog" aria-labelledby="ModalSearchLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalSearchLabel">Buscar Empresa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <label for="cifNombre">CIF</label>
                <input type="text" class="form-control mb-3" id="cifnombre" placeholder="Buscar por CIF"  aria-describedby="cifHelp"/>
                <button class="btn btn-primary buscarempresas">Buscar</button>
                <small id="cifHelp" class="form-text text-muted">*búsqueda por cif correcto, formato ej: B12345678.</small><br/>
            </div>
            <div class="modal-footer justify-content-start d-none" id="footer-dnone">
               <h4 class="w-100">Resultados de la búsqueda</h4><br/>
               <div class="resultados-busqueda"></div>               
            </div>
            <button class="removereadonly btn btn-warning d-none"><i class="fa-solid fa-triangle-exclamation"></i> Habilitar Escritura Campo CIF</button> 
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
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- jQuery Alerts -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>
<script>
    function isValidCif(cif) {

        if (!cif || cif.length !== 9) {
            return false;
        }

        var letters = ['J', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
        var digits = cif.substr(1, cif.length - 2);
        var letter = cif.substr(0, 1);
        var control = cif.substr(cif.length - 1);
        var sum = 0;
        var i;
        var digit;

        if (!letter.match(/[A-Z]/)) {
            return false;
        }

        for (i = 0; i < digits.length; ++i) {
            digit = parseInt(digits[i]);

            if (isNaN(digit)) {
                return false;
            }
        }

        return true;
    }
    $('form').on('submit', function(e){

        if (!isValidCif($("#cif").val())) {
            $.alert({
                title: 'CIF incorrecto',
                content: 'Para poder actualizar la patente debes añadir un cif con formato correcto'
            });
            return false;
        }

        $('form button[type="submit"]').attr('disabled', true);
    });
    $(".buscarempresas").on('click', function(e){
        var text = $("#cifnombre").val();

        if(text.length < 8 || isValidCif(text) === false){
            $.alert(
                {
                    title: 'Cif erroneo',
                    content: 'Introduce in CIF válido'
                }
            );
            return false;
        }

        $.ajax({
            headers: {
                'X-CSRF-Token': '{{ csrf_token() }}',
            },
            url: "{{ route('buscarempresas') }}",
            type:'POST',
            data: {text: text, tipo: 'asociar'},
            success: function(resp){
                $("#footer-dnone").removeClass('d-none');
                $(".resultados-busqueda").empty();
                $(".resultados-busqueda").append(resp);
            },
            error: function(resp){
                $("#footer-dnone").addClass('d-none');
                $(".resultados-busqueda").empty();
                $.alert(
                    {
                        title: 'Empresa no encontrada',
                        content: 'Puedes habilitar la escritura del campo CIF con el boton amarillo y añadir el CIF a mano'
                    }
                );
                $(".removereadonly").removeClass('d-none');
                return false;
            }
        });

        return false;
    });

    $(document).on('click', '.asociarempresa', function(e){
        e.preventDefault();            
        var value = $(this).attr('data-item');
        $('input[name="cif"]').val(value);
        $('#ModalSearch').modal('toggle');
    });

    $(document).on('click', '.removereadonly', function(e){
        e.preventDefault();
        $('input[name="cif"]').removeAttr('readonly');
        $('#ModalSearch').modal('toggle');
    });

    $(document).ready(function() {
        $(".js-example-tags").select2({
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