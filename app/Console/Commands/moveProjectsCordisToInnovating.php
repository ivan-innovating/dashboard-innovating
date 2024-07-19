<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class moveProjectsCordisToInnovating extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:move-projects-cordis-to-innovating';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pasa los proyectos scrappeados desde los json de CORDIS a innovating, si ya existen se actualizan datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $cordisprojects = \App\Models\ProjectsCordisRawData::get();

        foreach($cordisprojects as $project){

            $proyecto = \App\Models\Proyectos::where('id_raw_data', $project->id)->first();

            if(!$proyecto){
                $proyecto = new \App\Models\Proyectos();
            }

            $uri = cleanUriBeforeSave(str_replace(" ","-",mb_strtolower(quitar_tildes($project->title))));

            try{                
                $proyecto->empresaPrincipal = "XXX".$project->id."XXX";
                $proyecto->organismo = $project->id_organismo;
                $proyecto->Descripcion = $project->objective;
                $proyecto->Titulo = $project->title;
                $proyecto->Acronimo = $project->acronym;
                $proyecto->presupuestoTotal = $project->totalCost;
                $proyecto->tags = $project->keywords;
                $proyecto->Tipo = "publico";
                $proyecto->Estado = "Cerrado";
                $proyecto->importado = 1;                                            
                $proyecto->uri = $uri;
                $proyecto->esEuropeo = 1;
                $proyecto->visibilidad = 1;
                $proyecto->subCall = $project->subCall;
                $proyecto->fin = $project->endDate;
                $proyecto->inicio = $project->ecSignatureDate;
                $proyecto->Fecha = $project->ecSignatureDate;
                $proyecto->otraInformacion = $project->topics;                
                $proyecto->ambitoConvocatoria = "internacional";
                $proyecto->tipoConvocatoria = "consorcio";           
                $proyecto->NumParticipantes = 1;
                $proyecto->id_raw_data = $project->id;
                $proyecto->save();
            }catch(Exception $e){
                Log::Error($e->getMessage());
                return COMMAND::FAILURE;
            }   

        }

        return COMMAND::SUCCESS;
    }
}
