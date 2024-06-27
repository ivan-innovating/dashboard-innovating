@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Ayudas</h3>
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
        <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-3">
            Convocatorias: {{$ayudas->total()}}
        </h2>
        <div class="text-left mt-3 mb-3">
            <h5 class="text-muted">Filtrar por Estado:</h5>
            <a class="btn @if(request()->get('Estado') == 'publicadas') btn-success @else btn-outline-success @endif btn-sm"
                    href="{{route('dashboardayudasconvocatorias')}}?Estado=publicadas">Públicadas</a>
            <a class="btn @if(request()->get('Estado') == 'nopublicadas') btn-info @else btn-outline-info @endif btn-sm"
                    href="{{route('dashboardayudasconvocatorias')}}?Estado=nopublicadas">No públicadas</a>
            <a class="btn @if(request()->get('Estado') == 'nuevas') btn-primary @else btn-outline-primary @endif btn-sm"
                    href="{{route('dashboardayudasconvocatorias')}}?Estado=nuevas">Nuevas</a>
            <a class="btn btn-outline-danger btn-sm" href="{{route('dashboardayudasconvocatorias')}}">Quitar filtro</a>
            <button class="btn btn-primary text-white" type="button" data-toggle="modal" data-target="#buscarConvocatoriaModal">Buscar convocatoria</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table2">
                <thead>
                    <tr>
                        <th></th>
                        <th>Editar</th>
                        <th>Extinguida</th>
                        <th># Encajes</th>
                        <th>Acronimo</th>
                        <th>Titulo</th>
                        <th>Organismo</th>
                        <th>CCAA</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>organismo</th>
                        <th>titulo</th>
                        <th>ultimo_editor</th>
                    </tr>
                </thead>
                <tbody>
                    @if($ayudas->count() > 0)
                    @foreach($ayudas as $ayuda)
                    <tr @if($ayuda->totalencajes == 0) class="bg-rojo text-white" @endif>
                        <td class="text-center">
                            <input type="checkbox" name="updateExtinguida" class="updateExtinguida" value="{{$ayuda->id}}"/>
                        </td>
                        <td class="text-center">
                            @if($ayuda->id_ayuda === null)
                            <i class="fa-solid fa-exclamation text-danger"></i>
                            @endif
                            <a href="{{route('admineditarconvocatoria', [$ayuda->id])}}" class="btn btn-primary btn-sm" title="Editar ayuda">
                                <i class="fas fa-edit"></i>
                            </a>
                            <!--<button class="btn btn-danger btn-sm deleteayuda" data-item="{{$ayuda->id}}" title="Borrar ayuda">
                                <i class="fas fa-times"></i>
                            </button>-->
                            @if($ayuda->Publicada == 1)                                
                                <span title="Publicada" class="text-success"><i class="fa-solid fa-eye"></i></span>
                            @else                                
                                <span title="En revisión" class="text-warning"><i class="fa-solid fa-eye-slash"></i></span>                                
                            @endif
                        </td>
                        <td>
                            @if($ayuda->update_extinguida_ayuda === null)
                                N.D.
                            @else
                                @if($ayuda->update_extinguida_ayuda == 1)
                                    "ayuda extinguida" cuando pase a "Cerrada"
                                @else
                                    No hacer nada
                                @endif
                            @endif
                        </td>
                        <td class="text-center">{{$ayuda->totalencajes}}</td>
                        <td>{{$ayuda->Acronimo}}</td>
                        <td>
                            <span data-toggle="tooltip" data-placement="right" data-item="{{$ayuda->Titulo}}" title="{{$ayuda->Titulo}}">
                                {{\Illuminate\Support\Str::limit($ayuda->Titulo, '30', '...')}}
                            </span>
                        </td>
                        <td>
                            @if(isset($ayuda->dpto))
                            <span data-toggle="tooltip" data-placement="right" title="{{$ayuda->dptoNombre}}">
                                {{\Illuminate\Support\Str::limit($ayuda->dptoNombre, '30', '...')}}
                            </span>
                            @else
                                N.D.
                            @endif
                        </td>
                        <td>
                            @if(!empty($ayuda->Ccaas))
                                @if(is_array(json_decode($ayuda->Ccaas)))
                                    @foreach(json_decode($ayuda->Ccaas) as $ccaa)
                                        @if($ccaa != null)
                                            {{$ccaa}}
                                        @endif
                                    @endforeach
                                @endif
                            @endif
                        </td>
                        <td>{{$ayuda->Inicio}}</td>
                        <td>{{$ayuda->Fin}}</td>
                        <td>@if(isset($ayuda->dpto)) {{$ayuda->dptoNombre}} @else N.D. @endif</td>
                        <td>{{$ayuda->Titulo}}</td>
                        <td>{{$ayuda->LastEditor}}: @if($ayuda->updated_at) {{\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $ayuda->updated_at)->format('Y-m-d H:i:s')}} @endif</td>
                    </tr>
                    @endforeach
                    @else
                    <tr>
                        <td colspan="7"> No hay ayudas.
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        
	</div>
	<div class="card-footer">
		Footer
	</div>
</div>
<div class="modal fade" id="buscarConvocatoriaModal" tabindex="-1" role="dialog" aria-labelledby="buscarConvocatoriaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="buscarConvocatoriaModalLabel">Buscar convocatoria</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>            
            <div class="modal-body">
                <label for="cifNombre">Buscar por título o por Acrónimo</label>
                <input type="text" class="form-control mb-3" id="textoConvocatoria" placeholder="Buscar por título o Acrónimo"  aria-describedby="nombreHelp"/>
                <small id="nombreHelp" class="form-text text-muted">*La búsqueda se hará por el texto introducido siempre que aparezca esa parte de texto en el título o acrónimo de una ayuda será develta.</small>
                <button class="btn btn-primary buscarconvocatorias">Buscar</button>                
            </div>
            <div class="modal-footer justify-content-start d-none" id="footer-dnone">
               <h4 class="w-50">Resultados de la búsqueda</h4><br/>
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
	<script>
        $(".buscarconvocatorias").on('click', function(e){
            var text = $("#textoConvocatoria").val();
            $.ajax({
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                url: "{{ route('adminbuscarconvocatorias') }}",
                type:'POST',
                data: {text: text},
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
    </script>
@stop   