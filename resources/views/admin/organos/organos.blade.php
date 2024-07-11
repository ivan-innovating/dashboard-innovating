@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Organismos</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Listado de Fondos</h3>
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
        <div class="text-right mb-3">                                      
            <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#organismoModal">Buscar Organo</button>                                                
            <a href="{{route('admincrearorgano')}}" class="btn btn-primary btn-sm">Crear organo</a>
        </div>
        <div class="table-responsive">        
            <table class="table table-hover text-nowrap f-14 w-100" id="table2">
                <thead>
                    <tr>
                        <th></th>
                        <th>Trl</th>
                        <th>Scrapper</th>
                        <th># Ayudas</th>
                        <th># Concesiones</th>
                        <th>Nombre</th>
                        <th>Acronimo</th>
                        <th>Url</th>
                        <th>extradata2</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($organos as $organo)
                    <tr data-item="organo" id="organo{{$organo->id}}">
                        <td>
                            <a href="{{route('admineeditarorgano', $organo->id)}}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></a>
                            <button class="btn btn-sm btn-warning" onclick="copyContent('{{$organo->id}}')" title="Copiar Id departamento"><i class="fa-regular fa-clipboard"></i></button>
                        </td>
                        <td>{{$organo->Tlr}}</td>
                        <td>
                            @if($organo->scrapper == 1)
                                <i class="fa-solid fa-spider"></i>
                                <span class="d-none">1</span>
                            @else
                                <span class="d-none">0</span>
                            @endif</td>
                        <td>{{$organo->totalayudas}}</td>
                        <td>{{$organo->totalconcesiones}}</td>
                        <td>
                            {{$organo->Nombre}}
                        </td>
                        <td>
                            {{$organo->Acronimo}}
                        </td>
                        <td>
                            {{$organo->url}}
                        </td>
                        <td>{{$organo->Descripcion}}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>       
	</div>
	<div class="card-footer">
		
	</div>
</div>
<div class="modal fade" id="organismoModal" tabindex="-1" role="dialog" aria-labelledby="organismoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="crerAyudaConvocatoriaLabel">Buscar organismo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <label for="cifNombre">Buscar por nombre o por Acrónimo del organismo</label>
                <input type="text" class="form-control mb-3" id="textoOrganismos" placeholder="Buscar por título o Acrónimo"  aria-describedby="nombreHelp"/>
                <small id="nombreHelp" class="form-text text-muted">*La búsqueda se hará por el texto introducido siempre que aparezca esa parte de texto en el nombre o acrónimo de un organismo ayuda será develto.</small>
                <button class="btn btn-primary buscarorganismos">Buscar</button>                
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
	<script>   
        $(".buscarorganismos").on('click', function(e){
            var text = $("#textoOrganismos").val();

            if(text == "" || text.length < 3){
                $.alert(
                    {
                        title: 'Texto mínimo',
                        content: 'Mínimo 3 carácteres para poder buscar un organismo'
                    }
                );
                return false;
            }
            $.ajax({
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                url: "{{ route('buscarorganismos') }}",
                type:'POST',
                data: {text: text, url: 1},
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
                            title: 'Texto no encontrado',
                            content: resp.responseText+' prueba modificando la búsqueda para ver si encuentras la convocatoria'
                        }
                    );
                    return false;
                }
            });

            return false;
        });
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