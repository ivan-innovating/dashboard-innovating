<?php


namespace App\Models\Imports;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithValidation;

/** @package App\Models\Imports */
class ExcelImportConcessions implements ToModel, WithValidation, WithHeadingRow, WithLimit, SkipsOnFailure
{
    protected $rows = 0;
    protected $rows2 = 0;

    use Importable, SkipsFailures;
    /**
     * @param array $row
     *
     * @return Proyectos|null
     */
    public function model(array $row)
    {

        $file = request()->file('excel');

        if(!$file){
            return null;
        }

        if($row['cif'] == "id_organismo"){
            return null;
        }

        if(empty($row['id_organismo']) || empty($row['cif']) || empty($row['fecha_concesion']) || empty($row['nombre_empresa']) 
            || empty($row['presupuesto']) || empty($row['ayuda_equivalente'])){
            return null;
        }

        if(!checkCIF(trim($row['cif']))){
            return null;
        }

        $organo = \App\Models\Organos::find($row['id_organismo']);
        if(!$organo){
            $dpto = \App\Models\Departamentos::find($row['id_organismo']);
            if(!$dpto){
                return null;
            }
            $administration = \App\Models\Ccaa::find($dpto->id_ccaa);
        }else{
            $administration = \App\Models\Ministerios::find($organo->id_ministerio);
        }

        $date = intval($row['fecha_concesion']);
        $fecha = \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date));

        try{
            $concesionimportada = \App\Models\Concessions::where('id_organo', $row['id_organismo'])->where('custom_field_cif', $row['cif'])->where('fecha', $fecha->format('Y-m-d'))
            ->where('amount', $row['presupuesto'])->first();

            if(!$concesionimportada){
                $concesionimportada = \App\Models\Concessions::where('id_departamento', $row['id_organismo'])->where('custom_field_cif', $row['cif'])->where('fecha', $fecha->format('Y-m-d'))
                ->where('amount', $row['presupuesto'])->first();
            }

            if(!$concesionimportada){       
                                
                try{
                    $concesionnueva = new \App\Models\Concessions();
                    $concesionnueva->id_concesion = 0;
                    $concesionnueva->administration = $administration->Nombre;
                    $concesionnueva->department = (isset($dpto) && $dpto !== null) ? $dpto->Nombre : null;
                    $concesionnueva->organ = (isset($organo) && $organo !== null) ? $organo->Nombre : null;
                    $concesionnueva->announcement = "";
                    $concesionnueva->url_bbrr =  $row['url'];
                    $concesionnueva->budget_application = "";
                    $concesionnueva->grant_date = $fecha->format('d/m/Y');
                    $concesionnueva->beneficiary = $row['cif']." ".$row['nombre_empresa'];
                    $concesionnueva->amount = floatval($row['presupuesto']);
                    $concesionnueva->equivalent_aid = floatval($row['ayuda_equivalente']);
                    $concesionnueva->custom_field_cif = $row['cif'];
                    $concesionnueva->fecha = $fecha->format('Y-m-d');
                    $concesionnueva->id_organo = (isset($organo) && $organo !== null) ? $organo->id : null;
                    $concesionnueva->id_departamento = (isset($dpto) && $dpto !== null) ? $dpto->id : null;
                    $concesionnueva->importada = 1;
                    $concesionnueva->type = "uploadexcel";
                    $concesionnueva->file_name = $file->getClientOriginalName();
                    $concesionnueva->save();

                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return null;
                }

                ++$this->rows;

            }else{
                ++$this->rows2;
            }
        }catch(Exception $e){
            Log::error($e->getMessage());
            return null;
        }

        return (isset($concesionnueva)) ? $concesionnueva : $concesionimportada;
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function limit(): int
    {
        return 200;
    }

    public function getRowsCount(): int
    {
        return $this->rows;
    }

    public function getRows2Count(): int
    {
        return $this->rows2;
    }


    public function rules(): array
    {
        return [
            'id_organismo' => 'required|integer',
            'cif' => 'required|string|max:9',            
            'fecha_concesion' => 'required|integer',
            'nombre_empresa' => 'required|max:254',
            'presupuesto' => 'required|min:0',
            'ayuda_equivalente' => 'required|min:0',
            'url' => 'nullable|string|max:254'
        ];
    }

    public function customValidationMessages()
    {
        return [
            'cif.required' => 'Campo CIF no puede estar vacio ni ser mayor mayor de 9 caracteres.',
            'cif.max' => 'Campo CIF no puede estar vacio ni ser mayor mayor de 9 caracteres.',
            'id_organismo.required' => 'Campo Id Organismo no puede estar vacio ni ser diferente a un número entero.',
            'id_organismo.integer' => 'Campo Id Organismo no puede estar vacio ni ser diferente a un número entero.',
            'fecha_concesion.required' => 'El campo fecha concesion no puede estar vacio ni ser diferente al formato yyyy-mm-dd.',
            'fecha_concesion.integer' => 'El campo fecha concesion no puede estar vacio ni ser diferente al formato yyyy-mm-dd.',
            'nombre_empresa.required' => 'El campo Nombre de la empresa no puede estar vacio ni ser mayor a 254 carácteres',
            'nombre_empresa.max' => 'El campo Nombre de la empresa no puede estar vacio ni ser mayor a 254 carácteres',
            'presupuesto.required' => 'El campo presupuesto no puede estar vacio ni ser inferior a 0',
            'presupuesto.min' => 'El campo presupuesto no puede estar vacio ni ser inferior a 0',
            'ayuda_equivalente.required' => 'El campo ayuda equivalente no puede estar vacio ni ser inferior a 0',
            'ayuda_equivalente.min' => 'El campo ayuda equivalente no puede estar vacio ni ser inferior a 0',
            'url.string' => 'El campo url debe ser del tipo stipo cadena o texto sin formato',
        ];
    }
}

