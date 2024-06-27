<?php

use Illuminate\Support\Facades\Route;


#Esta ruta al ser para el SEO y por su forma debe ir en este archivo al final del todo
Route::get('/{url}', [\App\Http\Controllers\SEOController::class, 'view'])->name('seo-pages-view')->where('url', '.*');