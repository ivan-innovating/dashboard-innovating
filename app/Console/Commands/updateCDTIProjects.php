<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class updateCDTIProjects extends Command
{
    const CDTI = 1768;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-cdti-projects';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Los proyectos que han pasado por aplicar reglas y han obtenido un id de convocatoria, se buscan en la tabla proyectos y se hace match por titulo y empresaPrincipal(NIF)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $proyectosrawdata = \App\Models\ProyectosRawData::where('jsondata', 'LIKE', '%id_convocatoria%')->get();

        foreach($proyectosrawdata as $rawdata){

            $titulo = json_decode($rawdata->jsondata, true)['TituloProyecto'];
            $nif = str_replace("-","",json_decode($rawdata->jsondata, true)['CodigoEntidad']);

            if(str_contains($titulo,"(") && str_contains($titulo,")")){
                $titulo = trim(substr($titulo,0,strripos($titulo,"(")));
            }

            $proyecto = \App\Models\Proyectos::where('empresaPrincipal', $nif)->where('Titulo', 'LIKE', '%'.$titulo.'%')->where('organismo', self::CDTI)->first();

            if($proyecto){
                try{
                    $proyecto->id_raw_data = $rawdata->id;
                    $proyecto->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return COMMAND::FAILURE;
                }
            }
        }

        return command::SUCCESS;
    }
}
