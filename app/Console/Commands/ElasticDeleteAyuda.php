<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ElasticDeleteAyuda extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elastic:deleteayuda {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Borrar ayuda del entorno de elastic mediente su ID(id de encaje)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $id = $this->argument('id');

        if(!isset($id) || $id == ""){

            $date = Carbon::now()->subDays(14);
            $elasticApi = new \App\Libs\ElasticApi();
            $ayudas = \App\Models\Ayudas::where('convocatorias_ayudas.Estado', 'Cerrada')->where('convocatorias_ayudas.Fin', '<', $date->format('Y-m-d'))->where('Encajes_zoho.Tipo', '!=', 'Proyecto')
            ->leftJoin('Encajes_zoho', 'Encajes_zoho.Ayuda_id', '=', 'convocatorias_ayudas.id')->select('Encajes_zoho.id as encajeid')->get();

            foreach($ayudas as $ayuda){

                $resultApi = $elasticApi->deleteEncaje($ayuda->encajeid);

                if($resultApi !== NULL && $resultApi === true){
                    #$this->info('La ayuda con id:'.$ayuda->encajeid.' ha sido borrada del buscador');
                    #$this->info($resultApi);
                    continue;
                }elseif($resultApi !== NULL && $resultApi == "borrada"){
                    #$this->info('La ayuda con id:'.$ayuda->encajeid.' no existe o ya fue borrada de elastic, error: 002');
                    continue;
                }else{
                    #$this->info('La ayuda con id:'.$ayuda->encajeid.' no ha sido borrada del buscador, error: 003');
                    #$this->error("Error");
                    continue;
                }

                #$this->info('Encaje con id borrado: '.$ayuda->encajeid);
                exit;

            }

        }else{

            if(!$id){
                #$this->info('No se ha encontrado esa ayuda, error: 001');
                return NULL;
            }

            $ayuda = DB::table('Encajes_zoho')->where('id', $id)->first();

            if(!$ayuda){
                #$this->info('No se ha encontrado esa ayuda, error: 002');
                return NULL;
            }

            $elasticApi = new \App\Libs\ElasticApi();

            $resultApi = $elasticApi->deleteEncaje($id);

            if($resultApi !== NULL){
                #$this->info('La ayuda con id:'.$id.' ha sido borrada del buscador');
                #$this->info($resultApi);
            }else{
                #$this->info('La ayuda con id:'.$id.' no ha sido borrada del buscador, error: 003');
                #$this->error("Error");
                return NULL;
            }

            return true;

        }
    }
}
