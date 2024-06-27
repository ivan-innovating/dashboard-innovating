@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Editar Prioridad ID: {{$priorizar->id}}</h3>
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
        <form method="post" class="updatepriorizar">
            @csrf
            <input type="hidden" name="id" value="{{$priorizar->id}}"/>
            <div class="form-group">
                <label for="solicitante"><span class="text-danger">*</span> Solicitante</label>
                <input type="text" class="form-control" id="solicitante" name="solicitante" value="{{$priorizar->solicitante}}" disabled>
            </div>
            @if($priorizar->esOrgano == 0)
            <div class="form-group">
                <label for="priorizar"><span class="text-danger">*</span> Cif a Priorizar</label>
                <input type="text" class="form-control" id="priorizar" name="priorizar" value="{{$priorizar->cifPrioritario}}" disabled>
            </div>
            @else
            <div class="form-group">
                <label for="priorizar"><span class="text-danger">*</span> Nombre del Organo/Departamento a Priorizar</label>
                <input type="text" class="form-control" id="priorizar" name="priorizar" value="{{$priorizar->NombreOrgano}}" disabled>
            </div>
            @endif
            <div class="form-group">
                <label for="fecha"><span class="text-danger">*</span> Fecha solicitud</label>
                <input type="text" class="form-control" id="fecha" name="fecha" value="{{$priorizar->created_at}}" disabled>
            </div>
            <br/>
            <button class="btn btn-success aceptar">Aceptar</button>
            <button class="btn btn-danger rechazar">Rechazar</button>
        </form>
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
<!--DatePicker-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js" integrity="sha512-qTXRIMyZIFb8iQcfjXWCO8+M5Tbc38Qi5WzdPOYZHIlZpzBHG3L3by84BBBOiRGiEb7KKtAOAs5qYdUiZiQNNQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/js/tempusdominus-bootstrap-4.min.js" integrity="sha512-k6/Bkb8Fxf/c1Tkyl39yJwcOZ1P4cRrJu77p83zJjN2Z55prbFHxPs9vN7q3l3+tSMGPDdoH51AEU8Vgo1cgAA==" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/css/tempusdominus-bootstrap-4.min.css" integrity="sha512-3JRrEUwaCkFUBLK1N8HehwQgu8e23jTH4np5NHOmQOobuC4ROQxFwFgBLTnhcnQRMs84muMh0PnnwXlPq5MGjg==" crossorigin="anonymous" />
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
    $(function(){
        $('#adminloader').fadeOut("slow");
        $('#datetimepicker1').datetimepicker({
            format: 'L'
        });
    });
    $('.aceptar').on('click', function(e){
        e.preventDefault();
        $("#adminloader").fadeIn();
        var data = $('.updatepriorizar').serialize();
        $.confirm({
            title: 'Aceptar solicitud de priorizar',
            content: 'Vas a aceptar la solicitud, ¿estas de acuerdo?',
            buttons: {
                ok: function(){
                    $.ajax({
                        headers: {
                            'X-CSRF-Token': '{{ csrf_token() }}',
                        },
                        url: "{{route('adminaceptapriorizar')}}",
                        type: "post",
                        data: data,
                        success: function(resp){
                            $('#adminloader').fadeOut("slow");
                            if(resp.error !== undefined){
                                $.alert({
                                    title: 'Aceptar solicitud',
                                    content: resp.error
                                });
                                $('.einforma').attr('disabled', false);
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