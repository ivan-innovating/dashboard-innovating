@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard Empresas</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Buscar empresas</h3>
		<div class="card-tools">
			<button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
			    <i class="fas fa-minus"></i>
			</button>
		</div>
	</div>
	<div class="card-body">
		<h2 class="font-semibold text-xl text-gray-800 leading-tight mb-3">
			<!--<button class="btn btn-primary btn-sm" style="float:right" data-toggle="modal" data-target="#ModalCreate">Crear Empresa</button>-->
			<button class="btn btn-primary btn-sm mr-3" data-toggle="modal" data-target="#ModalSearch"><i class="fa-solid fa-magnifying-glass"></i> Buscar por CIF entre las {{$totalempresas}} empresas</button>
		</h2>
	</div>
</div>
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
                <a href="{{route('admincrearempresas')}}" class="btn btn-primary btn-sm crearempresaboton d-none">Crear Empresa</a>
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
	<!-- jQuery Alerts -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>    
    <!--Bootstrap Select-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-autocomplete/1.0.7/jquery.auto-complete.min.js"
 integrity="sha512-TToQDr91fBeG4RE5RjMl/tqNAo35hSRR4cbIFasiV2AAMQ6yKXXYhdSdEpUcRE6bqsTiB+FPLPls4ZAFMoK5WA=="
 crossorigin="anonymous" referrerpolicy="no-referrer"></script>
 	<script type="text/javascript">
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