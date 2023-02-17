<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStops extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->onUpdate('cascade')->onDelete('set null');
            $table->string('order_id', 20)->nullable();
            $table->string('name', 200);
            $table->string('phone', 40);
            $table->string('address', 255);
            $table->unsignedTinyInteger('status');
            $table->string('lat', 40)->nullable();
            $table->string('lng', 40)->nullable();
            $table->unsignedTinyInteger('in_window');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stops');
    }
}
