<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Models\Driver;
use App\Models\Project;
use App\Models\ProjectDriver;
use App\Models\Stop;
use Illuminate\Http\Request;

class ApiController extends Controller
{


    private $rota = null;


    public function findMyOrder($phone, $order_id)
    {
        $stop = Stop::where('phone', $phone)
            ->where('order_id', $order_id)
            ->firstOrFail();

        $project_driver = ProjectDriver::with('driver')
            ->where('driver_id', $stop->driver_id)
            ->where('project_id', $stop->project_id)
            ->firstOrFail();

        foreach ($project_driver->routes as $route) {

            if ($route['end_order_id'] == $order_id) {

                if ($route['arrived_at']) {
                    $timestamp = $route['arrived_at'];
                }

                elseif ($route['skipped_at']) {
                    $timestamp = $route['skipped_at'];
                }

                else {
                    $timestamp = $route['forecast'];
                }

                break;

            }

        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'            => $stop->id,
                'name'          => $stop->name,
                'order_id'      => $stop->order_id,
                'address'       => $stop->address,
                'image'         => $route['image'],
                'status'        => $route['status'],
                'timestamp'     => $timestamp,
                'utc_offset'    => $project_driver->utc_offset
            ]
        ]);
    }

    public function timezones(Request $request)
    {
        $request->validate([
            'date' => 'required|date|date_format:Y-m-d'
        ]);

        [$year, $month, $day] = explode('-', $request->date);

        $datetime = (new \DateTime())->setDate($year, $month, $day);

        $timezones = [];

        $timezones[] = [
            'id'        => 'America/Los_Angeles',
            'name'      => 'Pacific Standard Time (PST)',
            'offset'    => $datetime->setTimezone(new \DateTimeZone('America/Los_Angeles'))->getOffset()
        ];

        return response()->json([
            'success'   => true,
            'data'      => $timezones
        ]);
    }


    // public function findMyOrder($phone, $order_id, $tz = null)
    // {
    //     $stop = Stop::where('phone', $phone)
    //         ->where('order_id', $order_id)
    //         ->first();

    //     if ( !$stop )
    //         throw new CustomException('Not Found', 404);

    //     $projectDriver = ProjectDriver::where( 'driver_id', $stop->driver_id )
    //         ->where( 'project_id', $stop->project_id )
    //         ->first();

    //     if ( !$projectDriver )
    //         throw new CustomException('Not Found', 404);


    //     $duration = 0;
    //     $started  = false;

    //     $this->rota = $projectDriver->routes;

    //     $this->calc( $projectDriver, $stop );

    //     //dd($this->rota);
    //     //die('-');

    //     foreach ( $this->rota as $route )
    //     {
    //         if ($route['end_id'] == $stop->id)
    //             break;
    //     }

    //     //if( $started )
    //     //    $delivery_forecast_at = $this->started( $projectDriver, $stop, $tz, $route, $duration );
    //     //else
    //     //    $delivery_forecast_at = $this->default( $projectDriver, $stop, $tz, $route, $duration );

    //     return response()->json([
    //         'success'   => true,
    //         'data'      => [
    //             'name'                   => $route['end_name'],
    //             'address'                => $route['end_address'],
    //             'image'                  => $route['image'],
    //             'status'                 => $route['status'],
    //             'started_at'             => $route['started_at'],
    //             'arrived_at'             => $route['arrived_at'],
    //             'skipped_at'             => $route['skipped_at'],
    //             'delivery_forecast_at'   => $route['delivery_forecast_at'],
    //             'delivery_forecast_at_h' => $route['delivery_forecast_at_h']
    //         ]
    //     ]);
    // }

    private function default( $projectDriver, $stop, $tz, $route, $duration )
    {
        if ( $projectDriver->routes[0]['started_at'] )
        {
            $delivery_forecast_at = gmdate('Y-m-d\TH:i:s\Z', strtotime($projectDriver->routes[0]['started_at']) + $duration);
        }
        else
        {
            $project = Project::find($stop->project_id);
            $driver  = Driver::find($stop->driver_id);

            if( $tz )
                $dts = gmdate('H:i:s', strtotime( $driver->start_time . ' GMT-' . $tz) );
            else
                $dts = gmdate('H:i:s', strtotime( $driver->start_time ) - strtotime('07:00:00'));

            $date                 = "{$project->date}T{$dts}Z";
            $delivery_forecast_at = gmdate('Y-m-d\TH:i:s\Z', strtotime($date) + $duration);
        }

        return $delivery_forecast_at;

    } // private function default( $projectDriver )



    private function started( $projectDriver, $stop, $tz, $route, $duration )
    {
        $lastDt   = null;
        $duration = 0;

        foreach ( $projectDriver->routes as $rt )
        {
            $duration += $rt['duration'];
            //$duration += $rt['duration'] + $rt['downtime'];
            //echo '<pre>'; print_r( $duration ); echo '</pre>';

            if ($rt['end_id'] == $stop->id)
                break;

            if( $rt['status'] == 2 || $rt['status'] == 3 )
            {
                $lastDt   = ( $rt['status'] == 2 ) ? $rt['arrived_at'] : $rt['skipped_at'];
                $duration = 0;
            }
        }

        //dd($lastDt, date('H:i:s', strtotime( $lastDt ) + $duration ) );

        $project = Project::find( $stop->project_id );
        $dts = date( 'H:i:s', strtotime( $lastDt ) );

        $date                 = "{$project->date}T{$dts}Z";
        $delivery_forecast_at = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( $date ) + $duration );

        return $delivery_forecast_at;

    } // private function started( $projectDriver, $stop, $tz, $route, $duration )





    private function calc( $projectDriver, $stop )
    {
        $project    = ProjectDriver::where([ 'project_id' => $stop->project_id ])->first();
        $last       = null;
        $lf         = false;
        $duration   = 0;
        $start_time = null;

        if( count( $this->rota ) > 0 && $this->rota[0]['started_at'] != '' )
            $start_time = $this->rota[0]['started_at'];
        else
            $start_time = $project->start_time;

        foreach ( $this->rota as $key => &$route )
        {
            if( $route['status'] == 2 || $route['status'] == 3 )
            {
                $dts                             = ( $route['status'] == 2 ) ? $route['arrived_at'] : $route['skipped_at'];
                $dts                             = date( 'H:i:s', strtotime( $dts ) );
                $date                            = "{$project->date}T{$dts}Z";
                $route['delivery_forecast_at']   = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( $date ) );
                $route['delivery_forecast_at_h'] = gmdate( 'H:i', strtotime( $date ) );

                $last = $route;
            }
            else // if( $route['status'] == 2 || $route['status'] == 3 )
            {
                if( $last )
                {
                    if( !$lf )
                    {
                        $lf       = true;
                        $duration = 0;
                    }

                    $duration                       += $route['duration'];
                    $dts                             = ( $last['status'] == 2 ) ? $last['arrived_at'] : $last['skipped_at'];
                    $dts                             = date( 'H:i:s', strtotime( $dts ) );
                    $date                            = "{$project->date}T{$dts}Z";
                    $route['delivery_forecast_at']   = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( $date ) + $duration );
                    $route['delivery_forecast_at_h'] = gmdate( 'H:i' , strtotime( $date ) + $duration );
                }
                else
                {
                    $duration                       += $route['duration'];
                    $start_time                      = date( 'H:i:s', strtotime( $start_time ) );
                    $date                            = "{$project->date}T{$start_time}Z";
                    $route['delivery_forecast_at']   = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( $date ) + $duration );
                    $route['delivery_forecast_at_h'] = gmdate( 'H:i', strtotime( $date ) + $duration );
                }

            } // else - if( $route['status'] == 2 || $route['status'] == 3 )

            $duration += $route['downtime'];

        } // foreach ( $this->rota as $key => $value )

    } // private function calc( $projectDriver, $stop )



}
