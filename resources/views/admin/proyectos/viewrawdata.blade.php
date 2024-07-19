@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
	<h1>Dashboard Proyectos</h1>
@stop

@section('content')
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Datos importados {{$rawdata->acronym}}</h3>
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
        <ul>         
            <li>Datos internos:</li>
            <li>id: {{$rawdta->id}}</li>
            <li>created_at: {{$rawdta->created_at}}</li>
            <li>updated_at: {{$rawdta->updated_at}}</li>
            <li>id_organismo: {{$rawdta->id_organismo}}</li>
            <hr/>
            <li>Datos scrapper:</li>
            <li>ProjectID: {{$rawdta->project_id}}</li>            
            <li>acronym: @if($rawdta->acronym === null) "NULL" @else {{$rawdta->acronym}} @endif</li>
            <li>contentUpdateDate: @if($rawdta->contentUpdateDate === null) "NULL" @else {{$rawdta->contentUpdateDate}} @endif</li>
            <li>ecMaxContribution: @if($rawdta->ecMaxContribution === null) "NULL" @else {{$rawdta->ecMaxContribution}} @endif</li>
            <li>ecSignatureDate: @if($rawdta->ecSignatureDate === null) "NULL" @else {{$rawdta->ecSignatureDate}} @endif</li>
            <li>endDate: @if($rawdta->endDate === null) "NULL" @else {{$rawdta->endDate}} @endif</li>
            <li>frameworkProgramme: @if($rawdta->frameworkProgramme === null) "NULL" @else {{$rawdta->frameworkProgramme}} @endif</li>
            <li>fundingScheme:  @if($rawdta->fundingScheme === null) "NULL" @else {{$rawdta->fundingScheme}} @endif</li>
            <li>grantDoi:  @if($rawdta->grantDoi === null) "NULL" @else {{$rawdta->grantDoi}} @endif</li>
            <li>legalBasis:  @if($rawdta->legalBasis === null) "NULL" @else {{$rawdta->legalBasis}} @endif</li>
            <li>nature:  @if($rawdta->nature === null) "NULL" @else {{$rawdta->nature}} @endif</li>
            <li>objective:  @if($rawdta->objective === null) "NULL" @else {{$rawdta->objective}} @endif</li>
            <li>rcn:  @if($rawdta->rcn === null) "NULL" @else {{$rawdta->rcn}} @endif</li>
            <li>status:  @if($rawdta->status === null) "NULL" @else {{$rawdta->status}} @endif</li>
            <li>subCall:  @if($rawdta->subCall === null) "NULL" @else {{$rawdta->subCall}} @endif</li>
            <li>title:  @if($rawdta->title === null) "NULL" @else {{$rawdta->title}} @endif</li>
            <li>topics:  @if($rawdta->topics === null) "NULL" @else {{$rawdta->topics}} @endif</li>
            <li>totalCost:  @if($rawdta->totalCost === null) "NULL" @else {{$rawdta->totalCost}} @endif</li>
            <li>keywords:  @if($rawdta->keywords === null) "NULL" @else {{$rawdta->keywords}} @endif</li>
            <li>physUrl:  @if($rawdta->physUrl === null) "NULL" @else {{$rawdta->physUrl}} @endif</li>
        </ul>
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
	<script></script>
@stop   