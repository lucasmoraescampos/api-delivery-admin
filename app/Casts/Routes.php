<?php

namespace App\Casts;

use App\Models\ProjectDriver;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class Routes implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, $key, $value, $attributes)
    {
        $routes = json_decode($value, true);

        if ($model->status == ProjectDriver::STATUS_WAITING) {
            $timestamp = strtotime($model->start_time) - $model->utc_offset;
        }

        else {
            $timestamp = strtotime($routes[0]['started_at']);
        }

        foreach ($routes as &$route) {

            if ($route['arrived_at'] || $route['skipped_at'] || $route['started_at']) {

                if ($route['arrived_at'] || $route['skipped_at']) {

                    $timestamp = strtotime($route['arrived_at'] ?? $route['skipped_at']);

                }

                else {

                    $timestamp = strtotime($route['started_at']) + $route['duration'] + $route['downtime'];

                }

            }

            else {

                $timestamp += $route['duration'] + $route['downtime'];
                
            }

            $route['forecast'] = date('Y-m-d\TH:i:s\Z', $timestamp);

        }

        return $routes;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  array  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, $key, $value, $attributes)
    {
        return json_encode($value);
    }
}
