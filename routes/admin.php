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
});