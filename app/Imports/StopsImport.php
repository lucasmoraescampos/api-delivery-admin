<?php

namespace App\Imports;

use App\Models\Stop;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StopsImport implements ToModel, WithHeadingRow, WithValidation
{
    private $project_id;

    private $column_names;

    /**
     * StopsImport constructor.
     *
     * @param mixed $project_id
     */
    public function __construct($project_id = null, $column_names = null)
    {
        $this->project_id = $project_id;

        $this->column_names = $column_names;
    }

    /**
    * @param array $row
    * @return Stop|null
    */
    public function model(array $row)
    {
        $name = "{$row[$this->column_names['first_name']]}";

        if (isset($this->column_names['last_name'])) {

            $name .= " {$row[$this->column_names['last_name']]}";

        }

        $address = "{$row[$this->column_names['street']]}";

        if (isset($this->column_names['city'])) {

            if (!Str::contains(Str::lower($address), Str::lower($row[$this->column_names['city']]))) {

                $address .= ", {$row[$this->column_names['city']]}";

            }

        }

        if (isset($this->column_names['zip_code'])) {

            if (!Str::contains(Str::lower($address), Str::lower($row[$this->column_names['zip_code']]))) {

                $address .= ", {$row[$this->column_names['zip_code']]}";

            }

        }
        
        $stop = new Stop([
            'project_id'        =>  $this->project_id,
            'order_id'          =>  $row[$this->column_names['order_id']],
            'name'              =>  $name,
            'phone'             =>  $row[$this->column_names['phone']],
            'address'           =>  $address
        ]);

        $response = \GoogleMaps::load('geocoding')
            ->setParam(['address' => $address])
            ->get();

        $response = json_decode($response);

        if (count($response->results) > 0) {

            $result = $response->results[0];

            $stop->lat = $result->geometry->location->lat;

            $stop->lng = $result->geometry->location->lng;

        }

        return $stop;
    }

    public function rules(): array
    {
        return [
            $this->column_names['order_id']      => 'required',
            $this->column_names['first_name']    => 'required',
            $this->column_names['street']        => 'required',
            $this->column_names['phone']         => 'required'
        ];
    }
}
