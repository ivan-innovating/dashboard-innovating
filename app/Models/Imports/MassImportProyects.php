<?php


namespace App\Models\Imports;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithProgressBar;
use Maatwebsite\Excel\Concerns\WithValidation;

/** @package App\Models\Imports */
class MassImportProyects implements ToModel, WithValidation, WithHeadingRow, SkipsOnFailure, WithProgressBar, SkipsEmptyRows, WithChunkReading
{
    protected $rows = 0;
    protected $filename;

    use Importable, SkipsFailures;
    /**
     * @param array $row
     *
     * @return Proyectos|null
     */
    public function model(array $row)
    {

        if($row['ayuda'] == "ID innovating" || $row['cif'] == "Texto (sin guión)"
            || $row['titulo_proyecto'] === null || empty($row['titulo_proyecto']) || $row['organismo'] === null || empty($row['organismo'])){
            return null;
        }

        if(!checkCIF(trim($row['cif'])) && $row['cif'] !== "XXXXXXXXX"){
            return null;
        }

        $ocurrences = array('/ s.a.$/', '/ s.l.$/', '/ S.A.$/', '/ S.L.$/', '/ SA$/', '/ SL$/', '/ SAU$/', '/ S.A.U.$/', '/ s.a.u.$/', '/ sa.$/', '/ sl.$/', '/ sau.$/', '/ S.A.L.$/', '/ S.L.L$/', '/ S L$/', '/ s.a.$/',
        '/ slp$/', '/ slu$/', '/ slne$/', '/ slg$/', '/ sll$/', '/ s.a.$/');

        try{
            $proyectoimportado = \App\Models\Proyectos::where('organismo', $row['organismo'])
            ->where('Titulo', $row['titulo_proyecto'])->where('Fecha', Carbon::parse($row['fecha_concesion'])->format('Y-m-d'))
            ->where('tipoConvocatoria', 'Consorcio')->where('fromFile', $this->filename)->first();           

            if($proyectoimportado){

                if($row['lider'] == "Si"){
                    $proyectoimportado->empresaPrincipal = $row['cif'];
                }

                $participantes = json_decode($proyectoimportado->empresasParticipantes);
                if(!in_array($row['cif'], $participantes)){
                    if($row['cif'] != "XXXXXXXXX"){
                        array_push($participantes, $row['cif']);
                    }
                    $proyectoimportado->empresasParticipantes = json_encode($participantes);
                    $proyectoimportado->NumParticipantes = count(array_unique($participantes));
                    $nombre = preg_replace($ocurrences, '', $row['nombre_empresa'], 1);
                    try{
                        \App\Models\Participantes::updateOrCreate(
                            [
                                'cif_participante' => $row['cif'],
                                'nombre_participante' => $nombre,
                                'id_proyecto' => $proyectoimportado->id,
                                'id_concesion' => null,
                                'from_file' =>  $this->filename
                            ],
                            [
                                'presupuesto_socio' => ($row['presupuesto_socio'] !== null) ? (float)$row['presupuesto_socio'] : null,
                                'ayuda_eq_socio' => ($row['ayuda_pub_eq_socio'] !== null) ? (float)$row['ayuda_pub_eq_socio'] : null,
                            ]
                        );
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        return null;
                    }
                }

                $proyectoimportado->save();
                ++$this->rows;

            }else{

                $presupuestoTotal = null;
                $ayudaEq = null;

                if(isset($row['presupuesto_proyecto'])){
                    $presupuestoTotal = (float)$row['presupuesto_proyecto'];
                }elseif(isset($row['ayuda_publica_eq_proyecto_coop']) && $row['ayuda_publica_eq_proyecto_coop'] > 0){
                    $presupuestoTotal = (float)$row['ayuda_publica_eq_proyecto_coop'];
                }elseif((!isset($row['ayuda_publica_eq_proyecto_coop']) || $row['ayuda_publica_eq_proyecto_coop'] == 0) && isset($row['ayuda_pub_eq_socio'])){
                    $presupuestoTotal = (float)$row['ayuda_pub_eq_socio'];
                }

                if(isset($row['ayuda_publica_eq_proyecto_coop']) && $row['ayuda_publica_eq_proyecto_coop'] > 0){
                    $ayudaEq = (float)$row['ayuda_publica_eq_proyecto_coop'];
                }elseif((!isset($row['ayuda_publica_eq_proyecto_coop']) || $row['ayuda_publica_eq_proyecto_coop'] == 0) && isset($row['ayuda_pub_eq_socio'])){
                    $ayudaEq = (float)$row['ayuda_pub_eq_socio'];
                }

                $uri = cleanUriProyectosBeforeSave(mb_strtolower(trim($row['titulo_proyecto'])));
                $uri = rtrim(str_replace(" ", "-", substr($uri, 0, 100)),"-");

                $checkuri = \App\Models\Proyectos::where('uri', $uri)->count();
                if($checkuri > 0){
                    $uri .= rand(0,9999);
                }

                if($row['expediente'] == "" || $row['expediente'] === null){
                    $unix = substr(time(),-6);
                    $row['expediente'] = "INV".$this->rows.$unix;
                }

                $cif = $row['cif'];
                if(empty($row['cif']) || $row['cif'] === null){
                    $cif = 'XXXXXXXXX';
                }

                $nombre = preg_replace($ocurrences, '', $row['nombre_empresa'], 1);
                
                try{
                    $proyectonuevo = new \App\Models\Proyectos();
                    $proyectonuevo->importado = 1;
                    $proyectonuevo->esEuropeo = 0;
                    $proyectonuevo->Titulo = $row['titulo_proyecto'];
                    $proyectonuevo->Descripcion = $row['descripcion'];
                    $proyectonuevo->organismo = $row['organismo'];
                    $proyectonuevo->idAyudaAcronimo = $row['ayuda'];
                    $proyectonuevo->uri = $uri;
                    $proyectonuevo->empresaPrincipal = $cif;
                    $proyectonuevo->empresasParticipantes = json_encode([]);
                    $proyectonuevo->NumParticipantes = 1;
                    $proyectonuevo->nombreEmpresa =  $nombre;
                    $proyectonuevo->Expediente = $row['expediente'];
                    $proyectonuevo->Fecha = Carbon::parse($row['fecha_concesion'])->format('Y-m-d');
                    $proyectonuevo->PresupuestoTotal = $presupuestoTotal;
                    $proyectonuevo->FinanciacionPublica = (float)$row['financiacion_publica_del_proyecto'];
                    $proyectonuevo->tipoConvocatoria = $row['tipo_tramitacion'];
                    $proyectonuevo->AyudaEq = $ayudaEq;
                    $proyectonuevo->PresupuestoSocio = (float)$row['presupuesto_socio'];
                    $proyectonuevo->AyudaEqSocio = ($row['ayuda_pub_eq_socio'] !== null) ? (float)$row['ayuda_pub_eq_socio'] : null;
                    $proyectonuevo->tipoConvocatoria = $row['tipo_tramitacion'];
                    $proyectonuevo->tipoFinanPublica = $row['financiacion_publica_del_socio'];
                    $proyectonuevo->ambitoConvocatoria = (isset($row['ambito'])) ? $row['ambito'] : 'nacional';
                    $proyectonuevo->Estado = ($row['estado'] == "Financiado") ? "Cerrado" : $row['estado'] ;
                    $proyectonuevo->Tematicas = $row['tematica'];
                    $proyectonuevo->Fondos = $row['fondos'];
                    $proyectonuevo->tituloAyuda = $row['nombre_ayuda'];
                    $proyectonuevo->fromFile =  $this->filename;
                    $proyectonuevo->save();

                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return null;
                }

                if($proyectonuevo->ayudaAcronimo !== null){
                    try{    
                        Artisan::call('calcula:competitividad',
                            [
                                'id' => $proyectonuevo->ayudaAcronimo->id
                            ]
                        );
                    }catch(Exception $e){
                        Log::error($e->getMessage());                        
                    }
                }

                if(!empty($row['cif']) && $row['cif'] !== null){

                    try{
                        \App\Models\Participantes::updateOrCreate(
                            [
                                'cif_participante' => $row['cif'],
                                'nombre_participante' => $nombre,
                                'id_proyecto' => $proyectonuevo->id,
                                'id_concesion' => null,
                                'from_file' =>  $this->filename
                            ],
                            [
                                'presupuesto_socio' => ($row['presupuesto_socio'] !== null) ? (float)$row['presupuesto_socio'] : null,
                                'ayuda_eq_socio' => ($row['ayuda_pub_eq_socio'] !== null) ? (float)$row['ayuda_pub_eq_socio'] : null,
                            ]
                        );
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        return null;
                    }

                }

                if(!empty($row['cif']) && $row['cif'] != "XXXXXXXXX"){

                    try{

                        $textos = \App\Models\TextosElastic::where('cif', $row['cif'])->first();

                        if($textos){
                            if(strripos($textos->Textos_Proyectos, $row['titulo_proyecto']) === false){
                                $textos->Textos_Proyectos = $textos->Textos_Proyectos.",".$row['titulo_proyecto'];
                                $textos->Last_Update = Carbon::now();
                                $textos->save();
                            }
                        }else{
                            $textos = new \App\Models\TextosElastic();
                            $textos->CIF = $row['cif'];
                            $textos->Last_Update = Carbon::now();
                            $textos->Textos_Proyectos = $row['titulo_proyecto'];
                            $textos->save();
                        }              
                        \App\Models\Entidad::where('cif', $row['cif'])->update(
                            [
                                'EntityUpdate' => Carbon::now()
                            ]
                        );      
                    }catch(Exception $e){
                        Log::error($e->getMessage());

                    }

                }
                ++$this->rows;

            }
        }catch(Exception $e){
            Log::error($e->getMessage());
            return null;
        }

        return (isset($proyectonuevo)) ? $proyectonuevo : $proyectoimportado;
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function rules(): array
    {
        return [
            'cif' => 'nullable|string|max:9',
            'organismo' => 'required',
            'titulo_proyecto' => 'nullable|string|min:2',
            'titulo_ayuda' => 'nullable|string|max:254',
            'fecha_concesion' => 'nullable|string|min:2',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'cif.max' => 'Campo CIF mayor de 9 caracteres.',
            'organismo.required' => 'Campo Organismo no puede estar vacio',
            'titulo_proyecto.required' => 'Campo Título proyecto no puede estar vacio',
            'titulo_ayuda.max' => 'El campo Título de la ayuda es mayor a 254 carácteres',
            'fecha_concesion.string' => 'El Campo/Columna fecha concesion a de ser tipo cadena o texto sin formato',
        ];
    }

    public function fromFile($filename)
    {
        $this->filename = $filename;
        return $this->filename;
    }

    public function chunkSize(): int
    {
        return 100;
    }
    
    public function getRowsCount(): int
    {
        return $this->rows;
    }
}
