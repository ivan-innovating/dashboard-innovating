@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Envío Emails Usuarios</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
        <ul class="nav nav-pills" id="myTab">
            <li class="nav-item"><a class="active nav-link" href="#emails" data-toggle="tab">Crear envío de emails</a></li>
            <li class="nav-item"><a class="nav-link" href="#cola" data-toggle="tab">Cola correos pendientes</a></li>
        </ul>
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
        <div class="tab-content">
            <!-- /.tab-pane -->
            <div class="active tab-pane" id="emails">               
                <div class="post">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-3">
                        Enviar Emails a Usuarios de Innovating
                    </h2>
                    <div class="row">
                        <div class="col-sm-12 mb-3">
                            <p class="font-weight-bold text-danger">* Los mails generados se mandaran pasada 1 hora</p>
                        </div>  
                        <form action="{{route('admincrearemailusuarios')}}" method="post">
                            @csrf
                            <div class="col-sm-12 mb-3">
                                <input type="checkbox" name="todos" id="todos"/>
                                <label for="todos">Enviar a todos los usuarios?<b>Total usuarios: {{$total}}</b></label>
                            </div>
                            <hr/>
                            <div class="row mb-3 options">
                                <div class="col-sm-12 mb-3">
                                    <label for="empresas[]">Selecciona empresa(s)</label>
                                    <select name="empresas[]" class="form-control select2" id="empresas" style="width:100%;" multiple="multiple">
                                        <option></option>
                                        @foreach($empresas as $key => $empresa)
                                            <option value="{{$key}}">{{$empresa}}</option>
                                        @endforeach
                                    </select> 
                                </div>
                                <div class="col-sm-12 mb-3">
                                    <label for="usuarios[]">Selecciona usuario(s)</label>
                                    <select name="usuarios[]" class="form-control select2" id="usuarios" style="width:100%;" multiple="multiple" disabled>
                                        <option></option>
                                    </select>
                                </div>
                                <div class="col-sm-12 mb-3">
                                    <input type="checkbox" name="sinempresa" id="sinempresa"/>
                                    <label for="sinempresa">Enviar tambien a usuarios sin empresa, con la cuenta sin validar o importados desde Zoho(Beagle)<b>Total: {{$sinverificar}}</b></label>
                                </div>
                            </div>
                            <hr/>
                            <div class="row mb-3">
                                <div class="col-sm-12 mb-3">
                                    <p class="text-info font-weight-bold h5">Sección de contenido y asunto del mail</p>                                                    
                                    <div class="form-group">
                                        <span class="text-danger">*</span> <label for="asunto">Asunto del mail</label>
                                        <input type="text" name="asunto" class="form-control" required maxlength="250"/>
                                    </div>
                                    <div class="form-group">
                                        <span class="text-danger">*</span> <label for="cabecera">Cabecera del mail</label>
                                        <textarea name="cabecera" class="form-control" required cols="5"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <span class="text-danger">*</span> <label for="cuerpo">Cuerpo del mail</label>
                                        <textarea name="cuerpo" class="form-control" required cols="5"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <span class="text-danger">*</span> <label for="pie">Pie del mail</label>
                                        <textarea name="pie" class="form-control" required cols="5"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="url">Url del botón ir a Innovating del mail, formato: https://....'</label>
                                        <input tupe="text" name="url" class="form-control" maxlength="250"/>
                                    </div>
                                </div>     
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Enviar email</button>
                        </form>
                    </div>                                        
                </div>
            </div>
            <!-- /.tab-pane -->
            <div class="tab-pane" id="cola">
                <div class="post">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-3">
                        Cola de correos pendientes
                    </h2>
                    <div class="row">
                        <div class="col-sm-12 mb-3">
                            <p class="font-weight-bold text-danger">* Solo se muestran los correos que no se han enviado</p>
                        </div>  
                        <div class="table-responsive">
                            <table class="table table-hover text-nowrap f-14" style="overflow-x:scroll" id="table2">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Fecha Creacion</th>
                                        <th>Asunto</th>
                                        <th class="text-center">Todos los usuarios</th>                                                            
                                        <th class="text-center">Usuarios sin verificar</th>
                                        <th class="text-center"># Usuarios</th>
                                        <th class="text-center">Empresas</th>
                                        <th class="text-center">Creado por</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($correos->isNotEmpty())
                                        @foreach($correos as $correo)
                                        <tr>
                                            <td>
                                                <a href="{{route('admineditaremail', $correo->id)}}" class="btn btn-primary btn-xs" title="Editar"><i class="fa-solid fa-pen-to-square"></i></a>
                                                <a href="{{route('admindeletemail', $correo->id)}}" class="btn btn-danger btn-xs" title="Borrar"><i class="fa-solid fa-xmark"></i></a>                                                                    
                                            </td>
                                            <td>{{$correo->created_at}}</td>
                                            <td>{{\Illuminate\Support\Str::limit($correo->asunto_mail, 80, '...')}}</td>
                                            <td class="text-center">
                                                @if($correo->todos_usuarios == 1)
                                                <span class="text-success">Si</span>
                                                @else
                                                <span class="text-danger">No</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($correo->usuarios_sinverificar == 1)
                                                <span class="text-success">Si</span>
                                                @else
                                                <span class="text-danger">No</span>
                                                @endif
                                            </td>
                                            <td class="text-center">{{count(json_decode($correo->usuarios))}}</td>
                                            <td class="text-center">
                                                @if($correo->empresas === null)
                                                    N.D.
                                                @else
                                                    @php
                                                        $text = "";
                                                    @endphp
                                                    @foreach(json_decode($correo->empresas) as $key => $nombre)
                                                    @php
                                                        $text .= $nombre.",";
                                                    @endphp
                                                    @endforeach
                                                    {{\Illuminate\Support\Str::limit($text, 80, '...')}}
                                                @endif
                                            </td>
                                            <td class="text-center">{{$correo->user->email}}</td>
                                        </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td>Ahora mismo no hay correos generados como superadmin pendientes de envío</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.tab-pane -->
        </div>        		
	</div>
	<div class="card-footer">
		
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
    @if($correos->isNotEmpty())
    <script src="https://cdn.jsdelivr.net/npm/datatables@1.10.18/media/js/jquery.dataTables.min.js" integrity="sha256-3aHVku6TxTRUkkiibvwTz5k8wc7xuEr1QqTB+Oo5Q7I=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables@1.10.18/media/css/jquery.dataTables.min.css" integrity="sha256-YY1izqyhIj4W3iyJOaGWOpXDSwrHWFL4Nfk+W0LyCHE=" crossorigin="anonymous">
    <script type="text/javascript">
        $('#table2').DataTable({
            "paging": true,
            "pageLength": 100,
            "lengthChange": false,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "columnDefs": [

            ],
        });
    </script>
    @endif
    <script>
        $(document).ready(function() {
            $('#empresas').select2({
                placeholder: "Selecciona...",
                allowClear: true,
                theme: "classic",
            });   
            $('#usuarios').select2({
                placeholder: "Selecciona...",
                allowClear: true,
                theme: "classic",
            });                     
        });
        $('#empresas').on("select2:select", function(){
            var id = $(this).val();
            
            $.ajax({
                url: "{{route('admingetusuariosentidad')}}",
                type: 'GET',
                data: {id:id},
                cache: false,
                success: function(resp){
                    $('#usuarios').empty();
                    var resp = jQuery.parseJSON(resp);
                    $(resp).each(function(k,v) {
                        $('#usuarios').append($('<option>', {
                            value: v.id,
                            text : v.email+" ("+v.role+" - "+v.empresanombre+ ")",
                        }));
                    });
                    $('#usuarios').select2('destroy');
                    $('#usuarios').attr('disabled', false);
                    $('#usuarios').select2({
                        placeholder: "Selecciona...",
                        allowClear: true,
                        theme: "classic",
                    });            
                },
                error: function(){

                }
            });
        });
        $('input[name="todos"]').on("click", function(){
            $('div.options').toggleClass('disabled');
        });
        $('form').on('submit', function(e){
            e.preventDefault();
            if($('input[name="todos"]:checked').length == 0 && $('select[name="usuarios[]"] option:selected').length == 0 && $('input[name="sinempresa"]:checked').length == 0){
                $.alert({
                    title: "Error",
                    content: "Tienes que seleccionar todos los usuarios o los usuarios/empresas a las que enviar el mail"
                });
            }

            e.currentTarget.submit();
        });
    </script>
@stop   