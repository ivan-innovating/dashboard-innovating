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

    ### RUTAS PARA GESTION DE EMPRESAS SUPERADMIN
    Route::get('/admin/empresas', [\App\Http\Controllers\DashboardEmpresasController::class, 'empresas'])->name('adminempresas');
    Route::get('/admin/empresas/buscar', [\App\Http\Controllers\DashboardEmpresasController::class, 'buscarEmpresas'])->name('adminempresasbuscar');
    Route::get('/admin/centros', [\App\Http\Controllers\DashboardEmpresasController::class, 'centros'])->name('admincentros');
    Route::get('/admin/validar-empresas', [\App\Http\Controllers\DashboardEmpresasController::class, 'validarEmpresas'])->name('adminvalidarempresas');
    Route::get('/admin/priorizar-empresas', [\App\Http\Controllers\DashboardEmpresasController::class, 'priorizarEmpresas'])->name('adminpriorizarempresas');
    Route::get('/admin/crearempresa', [\App\Http\Controllers\DashboardEmpresasController::class, 'crearEmpresas'])->name('admincrearempresas');
    Route::get('/admin/editarempresa/{cif}/{id}', [\App\Http\Controllers\DashboardEmpresasController::class, 'editarEmpresas'])->name('admineditarempresa');
    Route::post('/admin/crearempresa', [\App\Http\Controllers\DashboardEmpresasController::class, 'newEmpresa'])->name('adminnewempresa');
    Route::post('/admin/editempresa', [\App\Http\Controllers\DashboardEmpresasController::class, 'editEmpresa'])->name('admineditempresa');
    Route::get('/admin/priorizar-empresa/id/{id}', [\App\Http\Controllers\DashboardEmpresasController::class, 'viewPriorizar'])->name('viewpriorizar');
    Route::post('/admin/aceptapriorizar', [\App\Http\Controllers\DashboardEmpresasController::class, 'aceptaPriorizar'])->name('adminaceptapriorizar');
    Route::post('/admin/rechazapriorizar', [\App\Http\Controllers\DashboardEmpresasController::class, 'rechazaPriorizar'])->name('adminrechazapriorizar');
    Route::get('/admin/ver-validacion/id/{id}', [\App\Http\Controllers\DashboardEmpresasController::class, 'viewValidacion'])->name('adminviewvalidacion');
    Route::post('/aceptarvalidacion', [\App\Http\Controllers\DashboardEmpresasController::class, 'aceptarvalidacion'])->name('adminaceptarvalidacion');

    ### RUTAS PARA GESTION DE AYUDAS Y CONVOCATORIAS SUPERADMIN
    Route::get('/admin/ayudas', [\App\Http\Controllers\DashboardAyudasController::class, 'ayudas'])->name('adminayudas');
    Route::get('/admin/crearayuda', [\App\Http\Controllers\DashboardAyudasController::class, 'crearAyuda'])->name('admincrearayuda');
    Route::post('/admin/saveayuda', [\App\Http\Controllers\DashboardAyudasController::class, 'saveAyuda'])->name('adminsaveayuda');
    Route::get('/admin/editarayuda/id/{id}', [\App\Http\Controllers\DashboardAyudasController::class, 'editarAyuda'])->name('admineditarayuda');
    Route::post('/admin/editayuda', [\App\Http\Controllers\DashboardAyudasController::class, 'editAyuda'])->name('admineditayuda');
    Route::get('/admin/convocatorias', [\App\Http\Controllers\DashboardConvocatoriasController::class, 'convocatorias'])->name('adminconvocatorias');
    Route::get('/admin/editarconvocatoria/id/{id}', [\App\Http\Controllers\DashboardConvocatoriasController::class, 'editarConvocatoria'])->name('admineditarconvocatoria');
    Route::post('/admin/buscarconvocatorias', [\App\Http\Controllers\DashboardConvocatoriasController::class, 'buscarConvocatorias'])->name('adminbuscarconvocatorias');
    Route::post('/admin/editconvocatoria', [\App\Http\Controllers\DashboardConvocatoriasController::class, 'editConvocatoria'])->name('admineditconvocatoria');
    Route::get('/admin/crearconvocatoria', [\App\Http\Controllers\DashboardConvocatoriasController::class, 'crearConvocatoria'])->name('admincrearconvocatoria'); 
    Route::post('/admin/saveconvocatoria', [\App\Http\Controllers\DashboardConvocatoriasController::class, 'saveConvocatoria'])->name('adminsaveconvocatoria');
    Route::get('/admin/duplicarconvocatoria', [\App\Http\Controllers\DashboardConvocatoriasController::class, 'duplicarConvocatoria'])->name('adminduplicarconvocatoria'); 
    Route::post('/admin/cloneconvocatoria', [\App\Http\Controllers\DashboardConvocatoriasController::class, 'cloneConvocatoria'])->name('admincloneconvocatoria');
    Route::post('/admin/editencaje', [\App\Http\Controllers\DashboardConvocatoriasController::class, 'editEncaje'])->name('admineditencaje');
    Route::get('/admin/crearencaje/id/{id}', [\App\Http\Controllers\DashboardConvocatoriasController::class, 'crearEncaje'])->name('admincrearencaje');
    Route::post('/admin/saveencaje', [\App\Http\Controllers\DashboardConvocatoriasController::class, 'saveEncaje'])->name('adminsaveencaje');
    Route::post('/admin/deleteencaje', [\App\Http\Controllers\DashboardConvocatoriasController::class, 'deleteEncaje'])->name('admindeleteencaje');
    ### RUTAS PARA GESTION DE FONDOS, SUBFONDOS 
    Route::get('/admin/fondos', [\App\Http\Controllers\DashboardFondosController::class, 'fondos'])->name('adminfondos');
    Route::get('/admin/crearfondo', [\App\Http\Controllers\DashboardFondosController::class, 'crearFondo'])->name('admincrearfondo');
    Route::post('/admin/savefondo', [\App\Http\Controllers\DashboardFondosController::class, 'saveFondo'])->name('adminsavefondo');
    Route::get('/admin/editarfondo/id/{id}', [\App\Http\Controllers\DashboardFondosController::class, 'editarFondo'])->name('admineditarfondo');
    Route::post('/admin/editfondo', [\App\Http\Controllers\DashboardFondosController::class, 'editFondo'])->name('admineditfondo');
    Route::post('/actualizargraficosfondo', [\App\Http\Controllers\DashboardFondosController::class, 'actualizarGraficos'])->name('actualizargraficosfondo');
    Route::get('/admin/subfondos', [\App\Http\Controllers\DashboardFondosController::class, 'subfondos'])->name('adminsubfondos');
    Route::get('/admin/crearsubfondo', [\App\Http\Controllers\DashboardFondosController::class, 'crearSubfondo'])->name('admincrearsubfondo');
    Route::post('/admin/savesubfondo', [\App\Http\Controllers\DashboardFondosController::class, 'saveSubfondo'])->name('adminsavesubfondo');
    Route::get('/admin/editarsubfondo/id/{id}', [\App\Http\Controllers\DashboardFondosController::class, 'editarSubfondo'])->name('admineditarsubfondo');
    Route::post('/admin/editsubfondo', [\App\Http\Controllers\DashboardFondosController::class, 'editSubfondo'])->name('admineditsubfondo');
    Route::get('/admin/typeofactions', [\App\Http\Controllers\DashboardFondosController::class, 'typeofactions'])->name('admintypeofactions');
    Route::get('/admin/creartypeofaction', [\App\Http\Controllers\DashboardFondosController::class, 'crearTypeofaction'])->name('admincreartypeofaction');
    Route::post('/admin/savetypeofaction', [\App\Http\Controllers\DashboardFondosController::class, 'saveTypeofaction'])->name('adminsavetypeofaction');
    Route::get('/admin/editartypeofaction/id/{id}', [\App\Http\Controllers\DashboardFondosController::class, 'editarTypeofaction'])->name('admineditartypeofaction');
    Route::post('/admin/edittypeofaction', [\App\Http\Controllers\DashboardFondosController::class, 'editTypeofaction'])->name('adminedittypeofaction');
    Route::get('/admin/budgetyearmap', [\App\Http\Controllers\DashboardFondosController::class, 'budgetyearmap'])->name('adminbudgetyearmap');
    Route::get('/admin/crearbudgetyearmap', [\App\Http\Controllers\DashboardFondosController::class, 'crearBudgetyearmap'])->name('admincrearbudgetyearmap');
    Route::post('/admin/savebudgetyearmap', [\App\Http\Controllers\DashboardFondosController::class, 'saveBudgetyearmap'])->name('adminsavebudgetyearmap');
    Route::get('/admin/editarbudgetyearmap/id/{id}', [\App\Http\Controllers\DashboardFondosController::class, 'editarBudgetyearmap'])->name('admineditarbudgetyearmap');
    Route::post('/admin/editbudgetyearmap', [\App\Http\Controllers\DashboardFondosController::class, 'editBudgetyearmap'])->name('admineditbudgetyearmap');

    ### RUTAS PARA GESTION DE ORGANOS, DEPARTAMENTOS 
    Route::get('/admin/organos', [\App\Http\Controllers\DashboardOrganosController::class, 'organos'])->name('adminorganos');
    Route::get('/admin/crearorgano', [\App\Http\Controllers\DashboardOrganosController::class, 'crearOrgano'])->name('admincrearorgano');
    Route::post('/admin/saveorgano', [\App\Http\Controllers\DashboardOrganosController::class, 'saveOrgano'])->name('adminsaveorgano');
    Route::get('/admin/editarorgano/id/{id}', [\App\Http\Controllers\DashboardOrganosController::class, 'editarOrgano'])->name('admineeditarorgano');
    Route::post('/admin/editorgano', [\App\Http\Controllers\DashboardOrganosController::class, 'editOrgano'])->name('admineeditorgano');

    Route::get('/admin/departamentos', [\App\Http\Controllers\DashboardOrganosController::class, 'departamentos'])->name('admindepartamentos');
    Route::get('/admin/ministerios', [\App\Http\Controllers\DashboardOrganosController::class, 'ministerios'])->name('adminministerios');
    Route::get('/admin/ccaas', [\App\Http\Controllers\DashboardOrganosController::class, 'ccaas'])->name('adminccaas');

    ### RUTAS PARA DATOS SCRAPPERS
    Route::get('/admin/scrappers', [\App\Http\Controllers\DashboardScrapperController::class, 'scrappers'])->name('adminscrappers');
    Route::get('/admin/reglasscrappers/{id}', [\App\Http\Controllers\DashboardScrapperController::class, 'reglasScrappers'])->name('adminscrapperreglas');
    Route::get('/admin/datosagrupados', [\App\Http\Controllers\DashboardScrapperController::class, 'datosAgrupados'])->name('adminsdatosagrupados');
    Route::post('/admin/scrappers/getajaxvalues', [\App\Http\Controllers\DashboardScrapperController::class, 'ajaxGetValues'])->name('admingetajaxvalues');
    Route::post('/admin/saveregla', [\App\Http\Controllers\DashboardScrapperController::class, 'saveRegla'])->name('adminsaveregla');
    Route::get('/admin/editarregla/id/{id}', [\App\Http\Controllers\DashboardScrapperController::class, 'editarRegla'])->name('admineeditarregla');
    Route::post('/admin/deleteregla', [\App\Http\Controllers\DashboardScrapperController::class, 'deleteRegla'])->name('admindeleteregla');
    Route::post('/admin/editregla', [\App\Http\Controllers\DashboardScrapperController::class, 'editRegla'])->name('admineeditregla');
    Route::post('/admin/aplicarreglas', [\App\Http\Controllers\DashboardScrapperController::class, 'aplicarReglas'])->name('adminaplicarreglas');
    Route::get('/admin/programarscrapper', [\App\Http\Controllers\DashboardScrapperController::class, 'programarScrapper'])->name('adminprogramarscrapper');
    Route::post('/admin/crearprogramscrapper', [\App\Http\Controllers\DashboardScrapperController::class, 'createProgramScrapper'])->name('admincreateprogramscrapper');   
    Route::post('/admin/deleteprogramscrapper', [\App\Http\Controllers\DashboardScrapperController::class, 'deleteProgramScrapper'])->name('admindeleteprogramscrapper');   
    

    ### RUTAS PARA GESTION DE USUARIOS
    Route::get('/admin/usuarios', [\App\Http\Controllers\DashboardUsuariosController::class, 'users'])->name('adminsusuarios');
    Route::get('/admin/usuariossinvalidar', [\App\Http\Controllers\DashboardUsuariosController::class, 'usersSinValidar'])->name('adminsusuariossinvalidar');
    Route::get('/admin/usuariossinempresa', [\App\Http\Controllers\DashboardUsuariosController::class, 'usersSinEmpresa'])->name('adminsusuariossinempresa');
    Route::get('/admin/usuariosconempresa', [\App\Http\Controllers\DashboardUsuariosController::class, 'usersConEmpresa'])->name('adminsusuariosconempresa');
    Route::get('/admin/investigadores', [\App\Http\Controllers\DashboardUsuariosController::class, 'investigadores'])->name('admininvestigadores');
});