<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class applyRulesOnProjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:apply-rules-on-projects';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aplica las reglas creadas para un organismo para hacer match con las convocatorias con un proyecto scrapeado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $applyrules = \App\Models\ApplyRules::where('updated_at', '>=', Carbon::now()->subHours(24))->where('applied', 0)->get();

        foreach($applyrules as $apply){
            foreach(json_decode($apply->rules) as $ruleid){
                $regla = \App\Models\ReglasScrappers::find($ruleid);
                switch($regla->condicion){
                    case "equals":
                        $condicion = "=";
                    break;
                    case "distinct":
                        $condicion = "!=";
                    break;
                    case "lowerequal":
                        $condicion = "<=";
                    break;
                    case "upperequal":
                        $condicion = ">=";
                    break;
                }
                if(!isset($condicion)){
                    continue;
                }

                $field = 'jsondata->'.$regla->campo_scrapper;
                $values = json_decode($regla->valores, true);

                if($condicion == "="){
                    $proyectosrawdata = \App\Models\ProyectosRawData::whereJsonContains($field, $values[0]);
                    for($i = 1; $i < count($values); $i++) {
                        $proyectosrawdata = $proyectosrawdata->orWhereJsonContains($field, $values[$i]);      
                    }
                    $proyectosrawdata = $proyectosrawdata->get();
                }  
                               
                if($proyectosrawdata->count() > 0){
                    foreach($proyectosrawdata as $rawdata){
                        $newjsondata = json_decode($rawdata->jsondata, true);
                        $newjsondata['id_convocatoria'] = $regla->id_convocatoria;
                        try{
                            $rawdata->jsondata = json_encode($newjsondata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT);
                            $rawdata->save();
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                            return COMMAND::FAILURE;
                        }
                    }
                }
                
            }

            try{
                $apply->applied = 1;
                $apply->save();
            }catch(Exception $e){
                Log::error($e->getMessage());
                return COMMAND::FAILURE;
            }
        }

        return COMMAND::SUCCESS;
    }
}
