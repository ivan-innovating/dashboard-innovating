<?php

namespace App\Http\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public int $umbral_proyectos;
    public int $umbral_ayudas;
    public bool $allow_register;
    public string $enlace_evento;
    public string $texto_evento;
    public bool $enable_einforma;
    public bool $enable_axesor;
    public string $master_featured;
    #public array $einforma;

    public static function group(): string
    {
        return 'general';
    }
}


