<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('find-my-order/{phone}/{order_id}', 'ApiController@findMyOrder');

Route::get('timezones', 'ApiController@timezones');

Route::prefix('user')->group(function () {

    Route::post('login', 'User\AuthController@login');

    Route::post('logout', 'User\AuthController@logout');

    Route::middleware(['auth:users'])->group(function () {

        Route::get('resume', 'User\AuthController@resume');

        /*
         * Team Routes
         */
        Route::get('team', 'User\TeamController@index');


        /*
         * Customer Routes
         */
        Route::get('customer', 'User\CustomerController@index');


        /*
         * Project Routes
         */
        Route::get('project', 'User\ProjectController@index');

        Route::get('project/{id}', 'User\ProjectController@show');

        Route::post('project', 'User\ProjectController@store');

        Route::put('project/{id}', 'User\ProjectController@update');

        Route::delete('project/{id}', 'User\ProjectController@delete');

        Route::post('project/{id}/clone', 'User\ProjectController@clone');

        Route::post('project/{project_id}/driver/{driver_id}', 'User\ProjectController@addProjectDriver');

        Route::put('project/{project_id}/driver/{driver_id}', 'User\ProjectController@updateProjectDriver');

        Route::delete('project/{project_id}/driver/{driver_id}', 'User\ProjectController@deleteProjectDriver');

        Route::delete('project/{id}/stop/{stop_id}', 'User\ProjectController@deleteProjectStop');

        Route::get('project/{id}/optimize', 'User\ProjectController@optimize');

        Route::put('project/{id}/dispatch', 'User\ProjectController@dispatch');

        Route::put('project/{id}/reverseRoute', 'User\ProjectController@reverseRoute');

        Route::post('project/{id}/reorder', 'User\ProjectController@reorder');

        Route::put('project/{id}/swapRoute', 'User\ProjectController@swapRoute');

        Route::get('project/{id}/sms/{driver}', 'User\ProjectController@sms');

        Route::get('project/{id}/sms-all', 'User\ProjectController@smsAll');

        Route::get('project/{id}/download/summary', 'User\ProjectController@downloadSummary');

        Route::get('project/{id}/download/solution', 'User\ProjectController@downloadSolution');

        Route::get('project/{id}/download/route', 'User\ProjectController@downloadRoute');

        Route::get('projects/report', 'User\ProjectController@report');

        Route::get('projects/report/download', 'User\ProjectController@reportDownload');

        Route::get('projects/report/bags', 'User\ProjectController@bagsReport');

        Route::get('projects/report/bags/download', 'User\ProjectController@bagsReportDownload');

        Route::get('projects/report/drivers', 'User\ProjectController@driversReport');

        Route::get('projects/report/drivers/download', 'User\ProjectController@driversReportDownload');

        Route::get('projects/deliveries', 'User\ProjectController@deliveries');


        /*
         * Driver Routes
         */
        Route::get('driver', 'User\DriverController@index');

        Route::get('driver/team/{team_id}', 'User\DriverController@showTeam');

        Route::post('driver', 'User\DriverController@store');

        Route::put('driver/{id}', 'User\DriverController@update');

        Route::delete('driver/{id}', 'User\DriverController@delete');


        /*
         * Stop Routes
         */
        Route::post('stop', 'User\StopController@store');

        Route::put('stop/{id}', 'User\StopController@update');

        Route::post('stop/import/columnNames', 'User\StopController@columnNames');

        Route::post('stop/import', 'User\StopController@import');        


        /*
         * Only admin users
         */
        Route::middleware(['user.admin'])->group(function () {

            /*
             * Team Routes
             */
            Route::post('team', 'User\TeamController@store');

            Route::put('team/{id}', 'User\TeamController@update');

            Route::delete('team/{id}', 'User\TeamController@delete');

            Route::post('team/{team_id}/manager', 'User\TeamController@attachManager');

            Route::delete('team/{team_id}/manager/{manager_id}', 'User\TeamController@detachManager');


            /*
             * Manager Routes
             */
            Route::get('manager', 'User\ManagerController@index');

            Route::post('manager', 'User\ManagerController@store');

            Route::put('manager/{id}', 'User\ManagerController@update');

            Route::delete('manager/{id}', 'User\ManagerController@delete');

            Route::post('manager/{manager_id}/team', 'User\ManagerController@attachTeam');

            Route::delete('manager/{manager_id}/team/{team_id}', 'User\ManagerController@detachTeam');


            /*
             * Customer Routes
             */
            Route::post('customer', 'User\CustomerController@store');

            Route::put('customer/{id}', 'User\CustomerController@update');

            Route::delete('customer/{id}', 'User\CustomerController@delete');
        });
    });
});


Route::prefix('driver')->group(function () {

    Route::get('{driver_hash}/projects', 'Driver\ApiController@projects');

    Route::get('{driver_hash}/project/{project_hash}', 'Driver\ApiController@project');

    Route::post('project/start', 'Driver\ApiController@start');

    Route::post('project/arrive', 'Driver\ApiController@arrive');

    Route::post('project/skip', 'Driver\ApiController@skip');

    Route::post('project/status/{stop_id}', 'Driver\ApiController@changeStatus');

});


Route::prefix('customer')->group(function () {

    Route::post('login', 'Customers\AuthController@login');

    Route::post('logout', 'Customers\AuthController@logout');

    Route::middleware(['auth:customers'])->group(function () {

        Route::get('project', 'Customers\ProjectController@index');

        Route::get('project/{id}', 'Customers\ProjectController@show');

        Route::get('driver', 'Customers\DriverController@index');

    });

});
