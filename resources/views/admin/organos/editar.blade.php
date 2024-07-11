@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Organismos</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Editar Organo {{$organismo->Nombre}}</h3>
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
        <form action="{{route('adminsaveorgano')}}" class="saveorganodpto" id="saveorganodpto" method="post">
            <input type="hidden" name="id" value="{{$organismo->id}}">
            <input type="hidden" name="idempresa" value="{{$organismo->empresa->id}}">      
            <div class="form-group mb-3" id="ministerio">
                <label for="ministerio">Ministerio</label>
                <br/>
                <select name="ministerio" class="multiple-select" title="Selecciona uno..." data-live-search="true"  data-width="100%">
                    @foreach($ministerios as $ministerio)
                        <option value="{{$ministerio->id}}" @if($ministerio->id == $organismo->id_ministerio) selected @endif>{{$ministerio->Nombre}}</option>
                    @endforeach
                </select>
            </div>            
            @if($organismo->empresa !== null)
            <div class="form-group">
                <label for="empresaasociada"><span class="text-danger">*</span> Empresa Asociada</label>
                <input type="text" class="form-control" value="{{$organismo->empresa->Nombre}}" disabled>
            </div>
            @endif
            <button type="button" class="btn btn-primary btn-sm mr-3" data-toggle="modal" data-target="#ModalSearch"><i class="fa-solid fa-magnifying-glass"></i> Buscar empresa para asociar por CIF </button>
            <div class="form-group">
                <label for="acronimo"><span class="text-danger">*</span>  Acronimo</label>
                <input type="text" class="form-control" name="acronimo" value="{{$organismo->Acronimo}}" required>
            </div>
            <div class="form-group" id="url">
                <label for="url"><span class="text-danger">*</span> Url</label>
                <input type="text" class="form-control" name="url" value="{{$organismo->url}}"  required>
                <small class="text-muted">* Solo letras, números y sin espacios en blanco, ej: instituto-madrileno-de-investigacion</small>
            </div>
            <div class="form-group" id="web">
                <label for="Web">Web</label>
                <input type="text" class="form-control" name="web" value="{{$organismo->Web}}">
            </div>
            <div class="form-group" id="tlr">
                <label for="descripcion">TRL</label>
                <input type="number" min="1" max="10" class="form-control" name="tlr" value="{{$organismo->Tlr}}" >
            </div>
            <div class="form-group">
                <label for="descripcion">Descripcion</label>
                <textarea cols="20" rows="4" class="form-control" name="descripcion" value="{{$organismo->Descripcion}}"></textarea>
            </div>
            <div class="form-check mb-3" id="import">
                <input class="form-check-input" type="checkbox" id="importante" name="importante" @if($organismo->scrapper == 1) checked @endif>
                <label class="form-check-label" for="importante">
                    ¿es un Organo/Departamento scrapeable de la base de datos del BDNS?
                </label>
            </div>
            <div class="form-check mb-3" id="fondop">
                <input class="form-check-input" type="checkbox" id="fondoperdido" name="fondoperdido" @if($organismo->esFondoPerdido == 1) checked @endif>
                <label class="form-check-label" for="fondoperdido">
                    ¿es un Organo/Departamento a fondo perdido?
                </label>
            </div>
            <div class="form-check mb-3" id="fondop">
                <input class="form-check-input" type="checkbox" id="visibilidad" name="visibilidad" @if($organismo->visibilidad == 1) checked @endif>
                <label class="form-check-label" for="visibilidad">
                    ¿es visible en los resultados de búsqueda?
                </label>
            </div>
            <div class="form-check mb-3" id="importados">
                <input class="form-check-input" type="checkbox" id="proyectosimportados" name="proyectosimportados" @if($organismo->proyectosImportados == 1) checked @endif>
                <label class="form-check-label" for="proyectosimportados">
                    ¿este organismo tiene proyectos importados por Excel?
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Actualizar</button>                                        
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
                <br/>
                <a class="btn btn-primary btn-sm crearempresaboton d-none" href="{{route('adminnewempresa')}}" target="_blank">Crear Empresa</a>
            </div>
            <div class="modal-footer justify-content-start d-none" id="footer-dnone">
               <h4 class="w-100">Resultados de la búsqueda</h4><br/>
               <div class="resultados-busqueda"></div>
            </div>
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
        $(document).ready(function() {
            $('.multiple-select').select2({
            });
        });
        $(".buscarempresas").on('click', function(e){
            var text = $("#cifnombre").val();

            validateCif(text)

            if(text.length < 8 || validateCif(text) === false){
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
                data: {text: text},
                success: function(resp){
                    $("#footer-dnone").removeClass('d-none');
                    $(".resultados-busqueda").empty();
                    $(".resultados-busqueda").append(resp);
                    $(".crearempresaboton").addClass('d-none');
                },
                error: function(resp){
                    $("#footer-dnone").addClass('d-none');
                    $(".resultados-busqueda").empty();
                    $.alert(
                        {
                            title: 'Empresa no encontrada',
                            content: resp.responseText+' utiliza el botón de crear empresa para crear una empresa nueva'
                        }
                    );
                    $(".crearempresaboton").removeClass('d-none');
                    $(".crearempresaboton").attr('href', $(".crearempresaboton").attr('href')+"?cif="+text);
                    return false;
                }
            });

            return false;
        });
        function validateCif(cif){
            console.log(/([a-z]|[A-Z]|[0-9])[0-9]{7}([a-z]|[A-Z]|[0-9])/g.test(cif));
        }
     
    </script>
@stop   