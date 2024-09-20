<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp;
use Illuminate\Support\Facades\Log;

class GetCdtiProyectosScrapper extends Command
{

    const BASEURL = "https://sede.cdti.gob.es/AreaPrivada/Servicios/DatosAbiertos/api/datos/proyectosidi/JSON/";

    public $client;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrapper:cdti_proyectos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obtiene los datos de proyectos del buscador de cdti, filtrados por mes actual y mes anterior, url buscador: https://www.cdti.es/datos-abiertos-creditos-subvenciones-y-lineas';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->client = new GuzzleHttp\Client();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $fechas = [            
            /*[//6 meses menos respecto al mes actual
                Carbon::now()->subMonths(6)->format('m'),
                Carbon::now()->subMonths(6)->format('Y')
            ],
            [//5 meses menos respecto al mes actual
                Carbon::now()->subMonths(5)->format('m'),
                Carbon::now()->subMonths(5)->format('Y')
            ],
            [//4 meses menos respecto al mes actual
                Carbon::now()->subMonths(4)->format('m'),
                Carbon::now()->subMonths(4)->format('Y')
            ],
            [//3 meses menos respecto al mes actual
                Carbon::now()->subMonths(3)->format('m'),
                Carbon::now()->subMonths(3)->format('Y')
            ],*/
            [//2 meses menos respecto al mes actual
                Carbon::now()->subMonths(2)->format('m'),
                Carbon::now()->subMonths(2)->format('Y')
            ],
            [//1 mes menos respecto al mes actual 
                Carbon::now()->subMonths(1)->format('m'),
                Carbon::now()->subMonths(1)->format('Y')
            ] ,            
            [//mes y año actual
                Carbon::now()->format('m'),
                Carbon::now()->format('Y')
            ],
        ];

        foreach($fechas as $fecha){

            try{            
                $baseurl = self::BASEURL.$fecha[1]."/ALL/".$fecha[0];    
                $response = $this->client->get($baseurl, [
                    'headers' => [
                        'Accept'       => 'application/json',
                    ],
                ]);

                $body = $response->getBody();
                $statusCode = $response->getStatusCode();
                $data = json_decode($body->getContents());

            }catch(ClientException $e){
                Log::error("client exception: ".$e->getMessage());
                return false;
            }catch (ServerException $e){
                Log::error("server exception: ".$e->getMessage());
                return false;
            }

            if(!empty($data)){

                $fechaInit = Carbon::createFromFormat('Y-m-d H:i:s', "2024-06-26 00:00:00");

                foreach($data as $proyecto){

                    /*if(Carbon::parse($proyecto->FechaAprobacion) <= $fechaInit){
                        continue;
                    }*/

                    $proyectorawdata = \App\Models\ProyectosRawData::where('proyecto_string', $proyecto->CodigoEntidad."-".$proyecto->Presupuesto."-".$proyecto->FechaAprobacion."-".$proyecto->IdTipologia)->first();

                    if(!$proyectorawdata){
                        $proyectorawdata = new \App\Models\ProyectosRawData();
                    }

                    try{
                        $proyectorawdata->id_organismo = 1768;
                        $proyectorawdata->proyecto_string = $proyecto->CodigoEntidad."-".$proyecto->Presupuesto."-".$proyecto->FechaAprobacion."-".$proyecto->IdTipologia;
                        $proyectorawdata->type = "scrapper";
                        $proyectorawdata->jsondata = json_encode($proyecto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT);
                        $proyectorawdata->save();
                    }catch(Exception $e){
                        Log::error("server exception: ".$e->getMessage());
                        return false;
                    }
                }
            }
        }


        return COMMAND::SUCCESS;
    }
}
