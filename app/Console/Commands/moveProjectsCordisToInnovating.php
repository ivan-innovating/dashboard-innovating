<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

##########
/*
    Logica para actualizar datos CORDIS, importante realizarlo siempre en este orden, 
    1. subir los json a su carpeta en el S3
    1.2 Si es necesario borrar todos los proyectos y participantes de los proyectos antes de importa
    2. pasar el nombre de la carpeta S3 como parámetro al comando import:cordis_json 
    3. ejecutar primero el comando app:move-projects-cordis-to-innovating, crear proyectos en innovating
    4. despues app:move-organizations-cordis-to-innovating crear los participantes y las emrpesas si es necesario en innovating(en caso de no existir)
*/
##########
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

            $uri = substr(str_replace(" ","-",trim(cleanUriProyectosBeforeSave(seo_quitar_tildes(mb_strtolower(preg_replace("/[^A-Za-z0-9À-Ùà-ú@.!? ]/",'',str_replace(array("\r", "\n"), '', $project->title))))))),0,254);

            try{                
                $proyecto->empresaPrincipal = "XXX".$project->id."XXX";
                $proyecto->organismo = $project->id_organismo;
                $proyecto->Descripcion = $project->objective;
                $proyecto->Titulo = $project->title;
                $proyecto->Acronimo = $project->acronym;
                $proyecto->presupuestoTotal = ($project->totalCost == 0) ? $project->ecMaxContribution : $project->totalCost;
                $proyecto->ecMaxContribution = $project->ecMaxContribution;
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
