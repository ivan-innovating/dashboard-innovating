<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckUserRole;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::middleware(['auth'])->middleware(CheckUserRole::class)->group(function() {
    #CUSTOM AUTH ROUTES
    Route::post('/actualizargraficosfondo', [\App\Http\Controllers\FondosController::class, 'actualizarGraficos'])->name('actualizargraficosfondo');
    Route::get('/dashboard-paginaayuda', [\App\Http\Controllers\DashboardController::class, 'getAdminHelp'])->name('getadminhelp');
    Route::post('/dashboard-save-paginaayuda', [\App\Http\Controllers\DashboardController::class, 'saveAdminHelp'])->name('saveadminhelp');
    Route::post('/dashboard-save-carpetaayuda', [\App\Http\Controllers\DashboardController::class, 'saveFolderHelp'])->name('savefolderhelp');
    Route::post('/dashboard-get-paginaayuda', [\App\Http\Controllers\DashboardController::class, 'getPaginaHelp'])->name('getajaxpaginaayuda');
    Route::post('/dashboard-get-carpetaayuda', [\App\Http\Controllers\DashboardController::class, 'getFolderHelp'])->name('getajaxcarpetaayuda');
    Route::post('/procesosventa', [\App\Http\Controllers\DashboardController::class, 'GeneraProcesoVenta'])->name('procesosventa');
    Route::post('/mandarbeagle', [\App\Http\Controllers\DashboardController::class, 'mandarEmpresasBeagle'])->name('mandarabeagle');
    Route::post('/crearnoticia', [\App\Http\Controllers\DashboardController::class, 'CrearNoticia'])->name('crearnoticia');
    Route::post('/editnoticia', [\App\Http\Controllers\DashboardController::class, 'EditNoticia'])->name('editnoticia');
    Route::post('/avisarusuariospriorizacion', [\App\Http\Controllers\CashFlowController::class, 'avisarUsuariosAnalisisTesoreria'])->name('avisarusuariospriorizacion');
    Route::post('/createcondicionrecompensas', [\App\Http\Controllers\DashboardController::class, 'createCondicionRecompensa'])->name('createcondicionrecompensas');
    Route::post('/updatecondicionrecompensas', [\App\Http\Controllers\DashboardController::class, 'updateCondicionRecompensa'])->name('updatecondicionrecompensas');
    Route::get('/viewcondicionrecompensa/{id}', [\App\Http\Controllers\DashboardController::class, 'viewCondicionRecompensa'])->name('viewcondicionrecompensa');
    Route::get('/rawdataeu/{id}', [\App\Http\Controllers\DashboardController::class, 'convocatoriasEU'])->name('rawdataeu');
    Route::get('/chatgptdata/{id}', [\App\Http\Controllers\DashboardController::class, 'chatGptDdata'])->name('chatgptdata');
    Route::post('/createchatgptdata', [\App\Http\Controllers\DashboardController::class, 'createChatGptData'])->name('createchatgptdata');
    Route::post('/getchatgptresponses', [\App\Http\Controllers\DashboardController::class, 'getChatGptResponse'])->name('getchatgptresponses');
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/seo-pages', [\App\Http\Controllers\SEOController::class, 'index'])->name('seo-pages');
    Route::get('/seo-company-pages', [\App\Http\Controllers\SEOController::class, 'indexCompanies'])->name('seo-company-pages');
    Route::get('/seo-pages-create', [\App\Http\Controllers\SEOController::class, 'create'])->name('seo-pages-create');
    Route::get('/seo-pages-company-create', [\App\Http\Controllers\SEOController::class, 'createCompanies'])->name('seo-pages-company-create');
    Route::get('/seo-pages-create-block', [\App\Http\Controllers\SEOController::class, 'createBlock'])->name('seo-pages-create-block');
    Route::get('/seo-pages-edit/{id}', [\App\Http\Controllers\SEOController::class, 'edit'])->name('seo-pages-edit');
    Route::get('/seo-company-pages-edit/{id}', [\App\Http\Controllers\SEOController::class, 'editCompany'])->name('seo-company-pages-edit');
    Route::post('/create-seopage', [\App\Http\Controllers\SEOController::class, 'save'])->name('create-seopage');
    Route::post('/create-seopage-block', [\App\Http\Controllers\SEOController::class, 'saveBlock'])->name('create-seopage-block');
    Route::post('/create-seopage-company', [\App\Http\Controllers\SEOController::class, 'saveCompanyPage'])->name('create-seopage-company');
    Route::post('/edit-seopage', [\App\Http\Controllers\SEOController::class, 'update'])->name('edit-seopage');
    Route::post('/edit-company-seopage', [\App\Http\Controllers\SEOController::class, 'updateCompanyPage'])->name('edit-company-seopage');
    Route::post('/get-seopages-ayudas', [\App\Http\Controllers\SEOController::class, 'getSeoAyudas'])->name('get-seopages-ayudas');
    Route::post('/get-seopages-empresas', [\App\Http\Controllers\SEOController::class, 'getSeoEmpresas'])->name('get-seopages-empresas');
    Route::get('/statsgenerales', [\App\Http\Controllers\DashboardController::class, 'statsGenerales'])->name('statsgenerales');    
    Route::get('/dashboard-organos', [\App\Http\Controllers\DashboardController::class, 'organos'])->name('dashboardorganos');
    Route::get('/dashboard-investigadores', [\App\Http\Controllers\UsersController::class, 'investigadores'])->name('dashboardinvestigadores');
    Route::get('/emails-superadmin', [\App\Http\Controllers\EmailsSuperAdmin::class, 'index'])->name('emailssuperadmin');
    Route::get('/datos-beagle', [\App\Http\Controllers\EnviarDatosBeagleController::class, 'index'])->name('enviodatosbeagle');
    Route::post('/get-concessions-beagle', [\App\Http\Controllers\EnviarDatosBeagleController::class, 'getConcessions'])->name('getconcesionsbeagle');
    Route::post('/mandar-concesiones-beagle', [\App\Http\Controllers\EnviarDatosBeagleController::class, 'sendConcessionsBeagle'])->name('mandarconcesionsbeagle');
    Route::get('/get-usuariosentidad', [\App\Http\Controllers\EmailsSuperAdmin::class, 'filterUsers'])->name('getusuariosentidad');
    Route::post('/create-mail', [\App\Http\Controllers\EmailsSuperAdmin::class, 'createMail'])->name('createmail');
    Route::get('/delete-mail/mail/{id}', [\App\Http\Controllers\EmailsSuperAdmin::class, 'deleteMail'])->name('deletemail');
    Route::get('/editsuperadmin/mail/{id}', [\App\Http\Controllers\EmailsSuperAdmin::class, 'viewMail'])->name('viewsuperadminmail');
    Route::post('/edit-mail', [\App\Http\Controllers\EmailsSuperAdmin::class, 'editMail'])->name('editmailsuperadmin');
    Route::post('/send-testmail', [\App\Http\Controllers\EmailsSuperAdmin::class, 'sendTestMail'])->name('sendtestmail');
    Route::get('/dashboard-scrappers', [\App\Http\Controllers\DashboardController::class, 'scrappers'])->name('dashboardscrapper');
    Route::get('/dashboard-ayudas-convocatorias', [\App\Http\Controllers\DashboardController::class, 'ayudas'])->name('dashboardayudas');
    Route::get('/dashboard-ayudas', [\App\Http\Controllers\DashboardController::class, 'ayudasConvocatorias'])->name('dashboardayudasconvocatorias');
    Route::get('/dashboard-ayudas-add', [\App\Http\Controllers\DashboardController::class, 'addConvocatoria'])->name('addconvocatoriaview');
    Route::get('/dashboard-ayudas-duplicar', [\App\Http\Controllers\DashboardController::class, 'duplicarConvocatoria'])->name('duplicateconvocatoriaview');
    Route::get('/dashboard-condicionesfinancieras', [\App\Http\Controllers\CondicionesFinancierasController::class, 'condicionesFinancieras'])->name('dashboardcondicionesfinancieras');
    Route::get('/configuration', [\App\Http\Controllers\DashboardController::class, 'config'])->name('configuration');
    Route::get('/dashboard-convocatorias', [\App\Http\Controllers\DashboardController::class, 'convocatorias'])->name('dashboardconvocatorias');
    Route::get('/dashboard-concesiones', [\App\Http\Controllers\DashboardController::class, 'concesiones'])->name('dashboardconcesiones');
    Route::get('/dashboard-proyectos', [\App\Http\Controllers\DashboardController::class, 'proyectos'])->name('dashboardproyectos');
    Route::get('/dashboard-proyectos-usuario', [\App\Http\Controllers\DashboardController::class, 'proyectosUsuario'])->name('dashboardproyectosuser');
    Route::get('/dashboard-targetizadas/{opcion}', [\App\Http\Controllers\DashboardController::class, 'empresasTargetizadas'])->name('empresastargetizadas');
    Route::get('/dashboard-patentes', [\App\Http\Controllers\DashboardController::class, 'patentes'])->name('dashboardpatentes');
    Route::get('/dashboard-asignar', [\App\Http\Controllers\DashboardController::class, 'asignador'])->name('dashboardasignar');
    Route::get('/dashboard-proyectosimportados', [\App\Http\Controllers\DashboardController::class, 'proyectosImportados'])->name('dashboardproyectosimportados');    
    Route::get('/dashboard-concesionesimportadas', [\App\Http\Controllers\DashboardConcesionesController::class, 'concesionesImportadas'])->name('dashboardconcesionesimportadas');
    Route::get('/dashboard-programarscrapper', [\App\Http\Controllers\DashboardConcesionesController::class, 'programarScrapperConcesiones'])->name('dashboardprogramconcessionsscrapper');
    Route::post('/asignardatosproyectos', [\App\Http\Controllers\DashboardController::class, 'asignarDatosProyectos'])->name('asignardatosproyectos');
    Route::get('/dashboard-dashboardproyectosdesestimados', [\App\Http\Controllers\DashboardController::class, 'proyectosDesestimados'])->name('dashboardproyectosdesestimados');
    Route::get('/dashboard-proyectosimportadosmatchs', [\App\Http\Controllers\DashboardController::class, 'proyectosImportadosMatchs'])->name('dashboardproyectosimportadosmatchs');
    Route::get('/dashboard-lastnews', [\App\Http\Controllers\DashboardController::class, 'lastnews'])->name('dashboardlastnews');
    Route::get('/editlastnew/id/{id}', [\App\Http\Controllers\DashboardController::class, 'viewLastnew'])->name('editlastnew');
    Route::get('/editconvocatoria/id/{id}', [\App\Http\Controllers\DashboardController::class, 'editconvocatoria'])->name('editconvocatoria');
    Route::get('/editfondoeuropeo/id/{id}', [\App\Http\Controllers\DashboardController::class, 'editFondo'])->name('editfondo');
    Route::get('/editeinforma/id/{id}', [\App\Http\Controllers\DashboardController::class, 'viewEinforma'])->name('editeinforma');
    Route::get('/editproyecto/id/{id}', [\App\Http\Controllers\DashboardController::class, 'editarProyecto'])->name('editarproyecto');
    Route::get('/convocatoria/id/{id}', [\App\Http\Controllers\DashboardController::class, 'viewConvocatoria'])->name('viewconvocatoria');
    Route::get('/viewproyectoimportado/id/{id}', [\App\Http\Controllers\DashboardProyectosController::class, 'viewPeoyectoImportado'])->name('viewproyectoimportado');
    Route::get('/dashboard-empresas', [\App\Http\Controllers\DashboardController::class, 'empresas'])->name('dashboardempresas');
    Route::get('/viewproyectocreado/{id}', [\App\Http\Controllers\DashboardProyectosController::class, 'viewProyectoCreado'])->name('viewproyectocreado');        
    Route::get('/viewbusquedacreada/{id}', [\App\Http\Controllers\DashboardProyectosController::class, 'viewBusquedaCreada'])->name('viewbusquedacreada');
    Route::get('/empresa/id/{id}/cif/{cif}', [\App\Http\Controllers\DashboardController::class, 'viewEmpresa'])->name('viewempresa');
    Route::get('/viewvalidacion/id/{id}', [\App\Http\Controllers\DashboardController::class, 'viewValidacion'])->name('viewvalidacion');
    Route::get('/viewpriorizar/id/{id}', [\App\Http\Controllers\DashboardController::class, 'viewPriorizar'])->name('viewpriorizar');
    Route::get('/viewcondicion/id/{id}', [\App\Http\Controllers\CondicionesFinancierasController::class, 'viewCondicionFinanciera'])->name('viewcondicion');
    Route::post('/rechazavalidacion', [\App\Http\Controllers\DashboardController::class, 'rechazaValidacion'])->name('rechazavalidacion');
    Route::post('/aceptavalidacion', [\App\Http\Controllers\DashboardController::class, 'aceptaValidacion'])->name('aceptavalidacion');
    Route::post('/rechazapriorizar', [\App\Http\Controllers\DashboardController::class, 'rechazaPriorizar'])->name('rechazapriorizar');
    Route::post('/aceptapriorizar', [\App\Http\Controllers\DashboardController::class, 'aceptaPriorizar'])->name('aceptapriorizar');
    Route::post('/createeinformamanual', [\App\Http\Controllers\DashboardController::class, 'createEinformaManual'])->name('createeinformamanual');
    Route::post('/importarproyectos', [\App\Http\Controllers\DashboardProyectosController::class, 'importarProyectos'])->name('importarProyectos');
    Route::post('/importarconcesiones', [\App\Http\Controllers\DashboardConcesionesController::class, 'importarConcesiones'])->name('importarconcesiones');
    Route::post('/subirarchivoproyectos', [\App\Http\Controllers\DashboardProyectosController::class, 'subirArchivoProyectos'])->name('subirarchivoproyectos');
    Route::post('/subirarchivoproyectosactualizados', [\App\Http\Controllers\DashboardProyectosController::class, 'subirArchivoProyectosActualizados'])->name('subirarchivoproyectosactualizados');
    Route::post('/deleteproyectosimportados', [\App\Http\Controllers\DashboardProyectosController::class, 'deleteProyectos'])->name('deleteproyectosimportados');
    Route::post('/deleteconcesionesimportadas', [\App\Http\Controllers\DashboardConcesionesController::class, 'deleteConcesiones'])->name('deleteconcesionesimportadas');
     
    Route::post('/delete-programscrapper', [\App\Http\Controllers\DashboardConcesionesController::class, 'deleteProgramScrapper'])->name('deleteprogramscrapper');
    Route::post('/deletearchivos', [\App\Http\Controllers\DashboardProyectosController::class, 'deleteArchivos'])->name('deletearchivos');
    Route::post('/deletecondicion', [\App\Http\Controllers\CondicionesFinancierasController::class, 'deleteCondicion'])->name('deletecondicion');
    Route::post('/crearempresasproyectos', [\App\Http\Controllers\DashboardProyectosController::class, 'crearEmpresasProyectos'])->name('crearEmpresasProyectos');
    Route::post('/matchproyectosconcesiones', [\App\Http\Controllers\DashboardProyectosController::class, 'matchProyectosConcesiones'])->name('matchProyectosConcesiones');
    Route::post('/matchproyectosempresas', [\App\Http\Controllers\DashboardProyectosController::class, 'matchProyectosEmpresas'])->name('matchproyectosempresas');    
    Route::post('/exportarexcel', [\App\Http\Controllers\DashboardProyectosController::class, 'exportarExcel'])->name('exportarexcel');
    Route::post('/importarexcel', [\App\Http\Controllers\DashboardProyectosController::class, 'importarExcel'])->name('importarexcel');
    Route::post('/recalculonivelcooperacion', [\App\Http\Controllers\DashboardProyectosController::class, 'recalculanivelcooperacion'])->name('recalculanivelcooperacion');
    Route::post('/imasdelastic', [\App\Http\Controllers\DashboardProyectosController::class, 'recalculoimasdmandarelastic'])->name('recalculoimasdmandarelastic');
    Route::post('/getconcesionescif', [\App\Http\Controllers\DashboardProyectosController::class, 'getConcesionesCif'])->name('getconcesionescif');
    Route::post('/setconcesionparticipante', [\App\Http\Controllers\DashboardProyectosController::class, 'setConcesionParticipante'])->name('setconcesionparticipante');
    Route::post('/removeconcesionparticipante', [\App\Http\Controllers\DashboardProyectosController::class, 'removeConcesionParticipante'])->name('removeconcesionparticipante');
    Route::post('/updateproyectoimportado', [\App\Http\Controllers\DashboardProyectosController::class, 'updateProyectoImportado'])->name('updateproyectoimportado');
    Route::post('/asociarempresaproyecto', [\App\Http\Controllers\DashboardProyectosController::class, 'asociarEmpresaProyecto'])->name('asociarempresaproyecto');
    Route::post('/updateumbrales', [\App\Http\Controllers\DashboardAjaxController::class, 'updateUmbrales'])->name('updateumbrales');
    Route::post('/buscarcif', [\App\Http\Controllers\BuscarController::class, 'buscar'])->name('buscarcif');
    Route::post('/buscarconcesiones', [\App\Http\Controllers\BuscarController::class, 'buscarconcesiones'])->name('buscarconcesiones');
    Route::post('/buscarpatentes', [\App\Http\Controllers\BuscarController::class, 'buscarpatentes'])->name('buscarpatentes');
    Route::post('/buscarprioridades', [\App\Http\Controllers\BuscarController::class, 'buscarprioridades'])->name('buscarprioridades');
    Route::post('/buscarempresas', [\App\Http\Controllers\BuscarController::class, 'buscarEmpresas'])->name('buscarempresas');
    Route::post('/buscarconvocatorias', [\App\Http\Controllers\BuscarController::class, 'buscarConvocatorias'])->name('buscarconvocatorias');
    Route::post('/buscarorganismos', [\App\Http\Controllers\BuscarController::class, 'buscarOrganismos'])->name('buscarorganismos');
    Route::post('/buscarempresasusuario', [\App\Http\Controllers\BuscarController::class, 'buscarEmpresasUser'])->name('buscarempresasuser');
    Route::post('/buscarinvestigador', [\App\Http\Controllers\BuscarController::class, 'buscarInvestigador'])->name('buscarinvestigador');
    Route::post('/getdptoorgano', [\App\Http\Controllers\DashboardAjaxController::class, 'getDptoOrgano'])->name('getorganodpto');
    Route::post('/savedptoorgano', [\App\Http\Controllers\DashboardAjaxController::class, 'saveDptoOrgano'])->name('saveorganodpto');
    Route::post('/createdptoorgano', [\App\Http\Controllers\DashboardAjaxController::class, 'createDptoOrgano'])->name('createorganodpto');
    Route::post('/saveayuda', [\App\Http\Controllers\DashboardAjaxController::class, 'saveAyuda'])->name('saveayuda');
    Route::post('/createayuda', [\App\Http\Controllers\DashboardAyudaController::class, 'createAyuda'])->name('createayuda');
    Route::post('/editarayuda', [\App\Http\Controllers\DashboardAjaxController::class, 'editarAyuda'])->name('editarayuda');
    Route::post('/editarempresa', [\App\Http\Controllers\DashboardAjaxController::class, 'editarEmpresa'])->name('editarempresa');
    Route::post('/crearempresa', [\App\Http\Controllers\DashboardAjaxController::class, 'crearEmpresa'])->name('crearempresa');
    Route::post('/avisarusuarios', [\App\Http\Controllers\DashboardAjaxController::class, 'avisarUsuarios'])->name('avisarusuarios');
    Route::post('/actualizargraficos', [\App\Http\Controllers\OrganismoController::class, 'actualizarGraficosOrganismo'])->name('actualizargraficos');
    Route::post('/creareinforma', [\App\Http\Controllers\DashboardAjaxController::class, 'crearEinforma'])->name('creareinforma');
    Route::post('/createcondicion', [\App\Http\Controllers\CondicionesFinancierasController::class, 'crearCondicionFinanciera'])->name('createcondicion');
    Route::post('/updatecondicion', [\App\Http\Controllers\CondicionesFinancierasController::class, 'editCondicionFinanciera'])->name('updatecondicion');
    Route::post('/deleteeinforma', [\App\Http\Controllers\DashboardAjaxController::class, 'borrarEinforma'])->name('deleteeinforma');
    Route::post('/deleteayuda', [\App\Http\Controllers\DashboardAjaxController::class, 'borrarAyuda'])->name('deleteayuda');
    Route::post('/maasiveconvocatoriasupdate', [\App\Http\Controllers\DashboardAyudaController::class, 'maasiveConvocatoriaUpdate'])->name('maasiveconvocatoriasupdate');
    Route::post('/duplicarayuda', [\App\Http\Controllers\DashboardAyudaController::class, 'duplicarAyuda'])->name('duplicarayuda');
    Route::post('/updategraficosayuda', [\App\Http\Controllers\DashboardAyudaController::class, 'updateGraficosAyuda'])->name('updategraficosayuda');
    Route::post('/asociar', [\App\Http\Controllers\BuscarController::class, 'asociar'])->name('asociar');
    Route::post('/insertar', [\App\Http\Controllers\BuscarController::class, 'insertar'])->name('insertar');
    Route::post('/updatepublicadaayuda', [\App\Http\Controllers\DashboardAyudaController::class, 'updatePublicadaAyuda'])->name('updatepublicadaayuda');
    Route::post('/quitarencaje', [\App\Http\Controllers\DashboardAyudaController::class, 'quitarEncaje'])->name('quitarencaje');
    Route::post('/addencaje', [\App\Http\Controllers\DashboardAyudaController::class, 'addEncaje'])->name('addencaje');
    Route::post('/getencaje', [\App\Http\Controllers\DashboardAyudaController::class, 'getEncaje'])->name('getencaje');
    Route::post('/editarencaje', [\App\Http\Controllers\DashboardAyudaController::class, 'editarEncaje'])->name('editarencaje');
    Route::post('/dashboard-editarproyecto', [\App\Http\Controllers\DashboardAjaxController::class, 'DashboardEditProyecto'])->name('dashboardeditproyecto');
    Route::post('/getempresasproyecto', [\App\Http\Controllers\DashboardAyudaController::class, 'getEmpresasProyecto'])->name('getempresasproyecto');
    Route::post('/editarconvocatoria', [\App\Http\Controllers\ConvocatoriaController::class, 'editarConvocatoria'])->name('editarconvocatoria');
    Route::post('/editarfondo', [\App\Http\Controllers\DashboardController::class, 'editarFondo'])->name('editarfondo');
    Route::post('/rechazaconvocatoria', [\App\Http\Controllers\DashboardAjaxController::class, 'rechazaConvocatoria'])->name('rechazaconvocatoria');
    Route::get('/removeempresa', [\App\Http\Controllers\EmpresaController::class, 'removeEmpresa'])->name('removeempresa');
    Route::post('/calculaimasd', [\App\Http\Controllers\EmpresaController::class, 'calculaImasD'])->name('calculaimasd');
    Route::post('/calculanivelcoop', [\App\Http\Controllers\EmpresaController::class, 'calculaNivelCooperacion'])->name('calculanivelcoop');
    Route::post('/aceptarvalidacion', [\App\Http\Controllers\DashboardController::class, 'aceptarvalidacion'])->name('aceptarvalidacion');
    Route::post('/adminaddencajeproyecto', [\App\Http\Controllers\DashboardAjaxController::class, 'addEncaje'])->name('adminaddencajeproyecto');
    Route::post('/admineditarencajeproyecto', [\App\Http\Controllers\DashboardAjaxController::class, 'editarEncaje'])->name('admineditarencajeproyecto');
    Route::post('/adminquitarencajeproyecto', [\App\Http\Controllers\DashboardAjaxController::class, 'quitarEncaje'])->name('adminquitarencajeproyecto');
    Route::post('/updateayudapreguntas', [\App\Http\Controllers\DashboardAyudaController::class, 'updatePreguntas'])->name('updateayudapreguntas');
    Route::post('/borrarayudapregunta', [\App\Http\Controllers\DashboardAyudaController::class, 'deletePregunta'])->name('borrarayudapregunta');
    Route::post('/updateayudaanalisis', [\App\Http\Controllers\DashboardAyudaController::class, 'updateAyudaAnalisis'])->name('updateayudaanalisis');
    Route::post('/validateuser', [\App\Http\Controllers\UsersController::class, 'validateUser'])->name('validateuser');

    
    Route::get('/editinvestigador/id/{id}', [\App\Http\Controllers\UsersController::class, 'viewInvestigador'])->name('editinvestigador');
    Route::post('/saveinvestigador', [\App\Http\Controllers\UsersController::class, 'saveInvestigador'])->name('saveinvestigador');
    Route::post('/updateinvestigadores', [\App\Http\Controllers\UsersController::class, 'updateInvestigadores'])->name('updateinvestigadores');
    Route::post('/updateinvestigadorempresa', [\App\Http\Controllers\UsersController::class, 'asociarInvestigadorEmpresa'])->name('updateinvestigadorempresa');
    Route::post('/descartarinvestigadorentidad', [\App\Http\Controllers\UsersController::class, 'descartarInvestigadorEmpresa'])->name('descartarinvestigadorentidad');
    Route::post('/updatelistadoinvestigadorempresa', [\App\Http\Controllers\UsersController::class, 'updateInvestigadorEntidades'])->name('updatelistadoinvestigadorempresa');
    Route::post('/updateorcid', [\App\Http\Controllers\UsersController::class, 'updateOrcid'])->name('updateorcid');
    Route::post('/editarcnae', [\App\Http\Controllers\DashboardCnaeController::class, 'editarCnae'])->name('editarcnae');
    Route::get('/editpatente/id/{id}', [\App\Http\Controllers\DashboardController::class, 'viewPatente'])->name('editpatente');
    Route::get('/editconcesion/id/{id}', [\App\Http\Controllers\DashboardController::class, 'viewConcesion'])->name('editconcesion');
    Route::post('/editarpatente', [\App\Http\Controllers\DashboardController::class, 'editarPatente'])->name('editarpatente');
    Route::post('/editareinforma', [\App\Http\Controllers\DashboardController::class, 'editarEinforma'])->name('editareinforma');
    Route::post('/solicitareinforma', [\App\Http\Controllers\DashboardAjaxController::class, 'solicitarEinforma'])->name('solicitareinforma');
    Route::post('/solicitaraxesor', [\App\Http\Controllers\DashboardAjaxController::class, 'solicitarAxesor'])->name('solicitaraxesor');
    Route::post('/obtenereinformaactual', [\App\Http\Controllers\DashboardAjaxController::class, 'obtenerEinformaActual'])->name('obtenereinformaactual');
    Route::post('/mostrarultimasnoticias', [\App\Http\Controllers\DashboardAjaxController::class, 'mostrarUltimasNoticias'])->name('mostrarultimasnoticias');
    Route::post('/obteneraxesoractual', [\App\Http\Controllers\DashboardAjaxController::class, 'obtenerAxesorActual'])->name('obteneraxesoractual');
    Route::post('/editarconcesion', [\App\Http\Controllers\DashboardController::class, 'editarConcesion'])->name('editarconcesion');
    Route::get('/editscrapper/id/{id}', [\App\Http\Controllers\DashboardController::class, 'viewScrapper'])->name('editscrapper');
    Route::post('/editarscrapper', [\App\Http\Controllers\DashboardController::class, 'editarScrapper'])->name('editarscrapper');
    Route::post('/solucionaralarma', [\App\Http\Controllers\DashboardAjaxController::class, 'solucionaralarma'])->name('solucionaralarma');
    Route::post('/flushcache', [\App\Http\Controllers\DashboardController::class, 'borrarCacheporId'])->name('flushcache');
    Route::post('/saveayudaconvocatoria', [\App\Http\Controllers\DashboardController::class, 'saveAyudaConvocatoria'])->name('saveayudaconvocatoria');    
    Route::get('/editayudaconvocatoria/{id}', [\App\Http\Controllers\DashboardController::class, 'viewAyudaConvocatoria'])->name('editayudaconvocatoria');
    Route::post('/updateayudaconvocatoria', [\App\Http\Controllers\DashboardController::class, 'updateAyudaConvocatoria'])->name('updateayudaconvocatoria');
    Route::post('/createayudafromconvocatoria', [\App\Http\Controllers\DashboardController::class, 'crearAyudaDesdeConvocatoria'])->name('createayudafromconvocatoria');
    Route::get('/dashboard-ayudas-fondos', [\App\Http\Controllers\DashboardController::class, 'ayudasFondos'])->name('dashboardayudasfondos');
    Route::get('/viewfondo/id/{id}', [\App\Http\Controllers\DashboardController::class, 'editFondo'])->name('viewfondo');
    Route::get('/viewsubfondo/{id}', [\App\Http\Controllers\FondosController::class, 'viewSubfondo'])->name('viewsubfondo');
    Route::get('/viewtypeofaction/{id}', [\App\Http\Controllers\FondosController::class, 'viewTypeOfAction'])->name('viewaction');
    Route::get('/viewbudgetyearmap/{id}', [\App\Http\Controllers\FondosController::class, 'viewBudgetYearMap'])->name('viewbudget');
    Route::post('/savefondo', [\App\Http\Controllers\FondosController::class, 'saveFondo'])->name('savefondo');
    Route::post('/savesubfondo', [\App\Http\Controllers\FondosController::class, 'saveSubFondo'])->name('savesubfondo');
    Route::post('/saveaction', [\App\Http\Controllers\FondosController::class, 'saveAction'])->name('saveaction');
    Route::post('/savebudget', [\App\Http\Controllers\FondosController::class, 'saveBudget'])->name('savebudget');
    Route::post('/editsubfondo', [\App\Http\Controllers\FondosController::class, 'editSubFondo'])->name('editsubfondo');
    Route::post('/edittypeofaction', [\App\Http\Controllers\FondosController::class, 'editAction'])->name('editaction');
    Route::post('/editbudgetyearmap', [\App\Http\Controllers\FondosController::class, 'editBudget'])->name('editbudget');
    Route::post('checksubfondos', [\App\Http\Controllers\FondosController::class, 'checkIfSubfondos'])->name('checksubfondos');   
    //Nuevas rutas sistema gestion de usuarios
    Route::get('usuarios', [\App\Http\Controllers\UsersController::class, 'users'])->name('usuarios');
    Route::get('usuarios/roles/{id}', [\App\Http\Controllers\UsersController::class, 'roles'])->name('roles');
    Route::post('usuarios/roles/update/{id}', [\App\Http\Controllers\UsersController::class, 'userUpdate'])->name('usuarioupdate');
    //Route::resource('cache', [\App\Http\Controllers\CacheController::class])->middleware('checkRole:SuperAdmin');
    ###Rutas nuevas superadmin cashflow(02/01/2024)
    Route::get('/editcashflow/id/{id}/{id2}', [\App\Http\Controllers\CashFlowController::class,'editCashflow'])->name('editcashflow');
    Route::post('/savecashflow', [\App\Http\Controllers\CashFlowController::class,'saveCashflow'])->name('savecashflow');
    Route::post('/deletecashflow', [\App\Http\Controllers\CashFlowController::class,'deleteCashflow'])->name('deletecashflow');
    Route::post('/savetipoproyectocashflow', [\App\Http\Controllers\CashFlowController::class,'saveTipoProyectoCashflow'])->name('savetipoproyectocashflow');
    Route::post('/savefinanciacioncashflow', [\App\Http\Controllers\CashFlowController::class,'saveFinanciacionCashflow'])->name('savefinanciacioncashflow');
    Route::post('/updatetipoproyectocashflow', [\App\Http\Controllers\CashFlowController::class,'updateTipoProyectoCashflow'])->name('updatetipoproyectocashflow');
    Route::post('/updatefinanciacioncashflow', [\App\Http\Controllers\CashFlowController::class,'updateFinanciacionCashflow'])->name('updatefinanciacioncashflow');
    Route::post('/getdatatipoproyectocashflow', [\App\Http\Controllers\CashFlowController::class,'getAjaxTipoProyectoCashflow'])->name('getdatatipoproyectocashflow');
    Route::post('/getdatafinanciacioncashflow', [\App\Http\Controllers\CashFlowController::class,'getAjaxFinanciacionCashflow'])->name('getajaxfinanciacioncashflow');
    Route::post('/deletetipoproyectocashflow', [\App\Http\Controllers\CashFlowController::class,'deleteTipoProyectoCashflow'])->name('deletetipoproyectocashflow');
    Route::post('/deletefinanciacioncashflow', [\App\Http\Controllers\CashFlowController::class,'deleteFinanciacionCashflow'])->name('deletefinanciacioncashflow');
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
});   

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/seo.php';


Auth::routes();


