<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsHasDrivers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects_has_drivers', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained('projects')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('drivers')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedInteger('total_distance')->nullable();
            $table->unsignedInteger('total_time')->nullable();
            $table->json('polyline_points')->nullable();
            $table->json('routes')->nullable();
            $table->json('stops_order')->nullable();
            $table->json('position_history')->nullable();
            $table->string('start_address', 255)->nullable();
            $table->string('start_lat', 40)->nullable();
            $table->string('start_lng', 40)->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('utc_offset')->nullable();
            $table->unsignedTinyInteger('later')->nullable();
            $table->unsignedTinyInteger('status');
            $table->primary(['project_id', 'driver_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects_has_drivers');
    }
}
